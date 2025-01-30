<?php

namespace Drupal\fresh_apples_show_page\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\fresh_apples_show_page\Service\MediaService\MediaServiceInterface;
use Drupal\fresh_apples_show_page\Service\TaxonomyService\TaxonomyServiceInterface;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class FreshApplesShowPageService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The taxonomy service.
   *
   * @var \Drupal\fresh_apples_show_page\Service\TaxonomyService\TaxonomyServiceInterface
   */
  protected $taxonomyService;

  /**
   * The media service.
   *
   * @var \Drupal\fresh_apples_show_page\Service\MediaService\MediaServiceInterface
   */
  protected $mediaService;

  /**
   * Constructs a new FreshApplesShowPageService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */

  protected $httpClient;
  private const TMDB_API_KEY = '11bc8a20af20e33048b136e2b3b4acdc';
  private const TMDB_READ_API_KEY = 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIxMWJjOGEyMGFmMjBlMzMwNDhiMTM2ZTJiM2I0YWNkYyIsIm5iZiI6MTczNjg5MjI0OS45NjgsInN1YiI6IjY3ODZkZjU5Y2FhNTNlOTA5ODdhZWQyNyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.bM84BWvXmqw5VJ0gI1F5NqXKmJpjpfP4WpmiWCCyUqQ';

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    TaxonomyServiceInterface $taxonomy_service,
    MediaServiceInterface $media_service,

  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->taxonomyService = $taxonomy_service;
    $this->mediaService = $media_service;
    $this->httpClient = new Client();
  }

  public function getMovieId($title) {
    $base_url = 'https://api.themoviedb.org/3/search/movie';
    try {
      $response = $this->httpClient->request('GET', $base_url, [
        'headers' => [
          'Authorization' => 'Bearer ' . self::TMDB_READ_API_KEY,
          'Content-Type' => 'application/json',
        ],
        'query' => [
          'query' => $title,
          'include_adult' => 'false',
          'language' => 'en-US',
        ],
        'timeout' => 10,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      return $data['results'][0]['id'] ?? NULL;
    }
    catch (RequestException $e) {
      return NULL;
    }
  }

  public function getProvidersForShow($title) {
    $movie_id = $this->getMovieId($title);
    if (!$movie_id) {
      return [];
    }

    $base_url = "https://api.themoviedb.org/3/movie/{$movie_id}/watch/providers";
    try {
      $response = $this->httpClient->request('GET', $base_url, [
        'headers' => [
          'Authorization' => 'Bearer ' . self::TMDB_READ_API_KEY,
          'Content-Type' => 'application/json',
        ],
        'timeout' => 10,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE);
      $providers = $data['results']['PL'] ?? [];

      return [
        'rent' => array_column($providers['rent'] ?? [], 'provider_name'),
        'flatrate' => array_column($providers['flatrate'] ?? [], 'provider_name'),
        'buy' => array_column($providers['buy'] ?? [], 'provider_name'),
      ];
    }
    catch (RequestException $e) {
      return [];
    }
  }

  /**
   * Prepares data for the show page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to prepare data for.
   */
  public function prepareShowData(NodeInterface $node) {
    if ($node->getType() !== 'show') {
      return [];
    }

    $show_title = $node->getTitle();
    $description = $node->get('body')->value;
    $release_year = date('Y', strtotime($node->get('field_release_year')->value));
    $genres = $node->get('field_genre')->referencedEntities();
    $genre_names = [];
    $providers = $this->getProvidersForShow($show_title);

    if (!empty($genres)) {
      foreach ($genres as $genre) {
        $genre_names[] = $genre->getName();
      }
    }

    $length_in_minutes = $node->get('field_length')->value;
    $media_id = $node->get('field_show_cover_image')->target_id;
    $cover_image_url = $this->mediaService->getStyledImageUrl($media_id, 'wide');
    $show_type = $node->get('field_show_type')
      ->referencedEntities()[0]->getName();
    $languages = $node->get('field_available_languages')->referencedEntities();

    $available_languages = [];

    foreach ($languages as $language) {
      $available_languages[] = $language->getName();
    }

    $participation_paragraphs = $node->get('field_participation')->getValue();
    $participation_paragraphs_ids = [];

    foreach ($participation_paragraphs as $paragraph) {
      $participation_paragraphs_ids[] = $paragraph['target_id'];
    }

    $participation_paragraphs_entities = $this->entityTypeManager->getStorage('paragraph')
      ->loadMultiple($participation_paragraphs_ids);
    $participation_paragraphs_data = [];

    foreach ($participation_paragraphs_entities as $paragraph) {
      $persona = $paragraph->get('field_persona')->referencedEntities()[0];
      $persona_full_name = $persona->getTitle();
      $persona_image_id = $persona->get('field_persona_image')->target_id;
      $persona_link = '/node/' . $persona->id();
      $persona_image_url = $this->mediaService->getStyledImageUrl($persona_image_id, 'medium');

      $participation_paragraphs_data[] = [
        'character_name' => $paragraph->get('field_character_name')->value,
        'role' => $paragraph->get('field_role')
          ->referencedEntities()[0]->getName(),
        'persona_full_name' => $persona_full_name,
        'persona_image_url' => $persona_image_url,
        'persona_link' => $persona_link,
      ];
    }

    $user = \Drupal::currentUser();
    $is_user_authenticated = $user->isAuthenticated();

    if (!$is_user_authenticated) {
      $already_reviewed = FALSE;
    }
    else {
      $user_id = $user->id();
      $query = $this->entityTypeManager->getStorage('node')
        ->getQuery()
        ->condition('type', 'review')
        ->condition('uid', $user_id)
        ->condition('field_show', $node->id())
        ->range(0, 1)
        ->accessCheck(FALSE);

      $query_result = $query->execute();
      $already_reviewed = !empty($query_result);

      if ($query_result) {
        $review_node = $this
          ->entityTypeManager
          ->getStorage('node')
          ?->load(reset($query_result));

        if ($review_node) {
          $rating = $this->getPrevRating($review_node);
          $already_reviewed = TRUE;
        }
        else {
          $rating = NULL;
          $already_reviewed = FALSE;
        }
      }
    }

    $all_this_movie_reviews_from_critics = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->condition('type', 'review')
      ->condition('field_show', $node->id())
      ->condition('field_is_from_ciritc', 1)
      ->accessCheck(FALSE)
      ->execute();

    $all_this_movie_reviews_from_regular_users = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->condition('type', 'review')
      ->condition('field_show', $node->id())
      ->condition('field_is_from_ciritc', 0)
      ->accessCheck(FALSE)
      ->execute();

    $review_data_critics = [];
    $review_data_regulars = [];

    foreach ($all_this_movie_reviews_from_critics as $review_id) {
      $review_node = $this->entityTypeManager->getStorage('node')->load($review_id);
      $uid = $review_node->get('uid')->referencedEntities()[0]->id();
      $account = User::load($uid);
      $author = $account->getAccountName();
      $review_data_critics[] = [
        'title' => $review_node->getTitle(),
        'rating' => $review_node->get('field_review_rating')->value,
        'review' => $review_node->get('field_review_content')->value,
        'author' => $author,
        'date' => date('Y-m-d', $review_node->get('changed')->value),
        'is_from_critics' => $review_node->get('field_is_from_ciritc')->value,
      ];
    }

    foreach ($all_this_movie_reviews_from_regular_users as $review_id) {
      $review_node = $this->entityTypeManager->getStorage('node')->load($review_id);
      $uid = $review_node->get('uid')->referencedEntities()[0]->id();
      $account = User::load($uid);
      $author = $account->getAccountName();
      $review_data_regulars[] = [
        'rating' => $review_node->get('field_review_rating')->value,
        'review' => $review_node->get('field_review_content')->value,
        'author' => $author,
        'date' => date('Y-m-d', $review_node->get('changed')->value),
        'is_from_critics' => $review_node->get('field_is_from_ciritc')->value,
      ];
    }

    return [
      'show_title' => $show_title,
      'description' => $description,
      'release_year' => $release_year,
      'genres' => $genre_names,
      'length_in_minutes' => $length_in_minutes,
      'cover_image_url' => $cover_image_url,
      'show_type' => $show_type,
      'available_languages' => $available_languages,
      'participation_paragraphs' => $participation_paragraphs_data,
      'already_reviewed' => $already_reviewed,
      'prev_rating' => $rating ?? NULL,
      'reviews_critics' => $review_data_critics,
      'reviews_regulars' => $review_data_regulars,
      'providers' => $providers,
    ];
  }

  public function getTheReviewByUidAndShowId($uid, $show_id) {
    $query = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->condition('type', 'review')
      ->condition('uid', $uid)
      ->condition('field_show', $show_id)
      ->range(0, 1)
      ->accessCheck(FALSE);

    $query_result = $query->execute();
    $already_reviewed = !empty($query_result);

    if (!$already_reviewed) {
      return NULL;
    }
    else {
      $review_node = $this->entityTypeManager->getStorage('node')
        ->load(reset($query_result));
      return $review_node;
    }
  }

  public function getPrevRating(EntityInterface|null $node): ?int {
    if ($node === NULL) {
      return NULL;
    }
    $user = \Drupal::currentUser();
    $is_user_authenticated = $user->isAuthenticated();

    if (!$is_user_authenticated) {
      return NULL;
    }
    else {
      $this_page_node = \Drupal::routeMatch()->getParameter('node');
      $user_id = $user->id();
      $query = $this->entityTypeManager->getStorage('node')
        ->getQuery()
        ->condition('type', 'review')
        ->condition('uid', $user_id)
        ->condition('field_show', $this_page_node->id())
        ->range(0, 1)
        ->accessCheck(FALSE);

      $query_result = $query->execute();
      $already_reviewed = !empty($query_result);

      if (!$already_reviewed) {
        return NULL;
      }
      else {
        $review_node = $this->entityTypeManager->getStorage('node')
          ->load(reset($query_result));
        $rating = $review_node->get('field_review_rating')->value;

        return $rating;
      }
    }
  }

  public function getAverageRatingFromCritics() {
    $this_movie_node = \Drupal::routeMatch()->getParameter('node');

    $query = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->condition('field_show', $this_movie_node->id())
      ->condition('type', 'review')
      ->condition('field_is_from_ciritc', 1)
      ->accessCheck(FALSE);

    $query_result = $query->execute();
    $all_ratings = [];

    foreach ($query_result as $review_id) {
      $review_node = $this->entityTypeManager->getStorage('node')->load($review_id);
      $rating = $review_node->get('field_review_rating')->value;
      $all_ratings[] = $rating;
    }

    if (count($all_ratings) === 0) {
      return 'brak ocen dla tego filmu';
    }

    $average_rating = array_sum($all_ratings) / count($all_ratings);

    return $average_rating;
  }

  public function getAverageRatingFromRegularUsers() {
    $this_movie_node = \Drupal::routeMatch()->getParameter('node');
    $query = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->condition('field_show', $this_movie_node->id())
      ->condition('type', 'review')
      ->condition('field_is_from_ciritc', 0)
      ->accessCheck(FALSE);

    $query_result = $query->execute();
    $all_ratings = [];

    foreach ($query_result as $review_id) {
      $review_node = $this->entityTypeManager->getStorage('node')->load($review_id);
      $rating = $review_node->get('field_review_rating')->value;
      $all_ratings[] = $rating;
    }

    if (count($all_ratings) === 0) {
      return 'brak ocen dla tego filmu';
    }

    $average_rating = array_sum($all_ratings) / count($all_ratings);

    return $average_rating;
  }

}
