<?php

namespace Drupal\fresh_apples_homepage\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\fresh_apples_show_page\Service\MediaService\MediaServiceInterface;

class NewReleasedMoviesService {

  protected $entityTypeManager;
  protected $aliasManager;
  protected $mediaService;

  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    AliasManagerInterface $alias_manager,
    MediaServiceInterface $media_service
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasManager = $alias_manager;
    $this->mediaService = $media_service;
  }
  public function getNewReleasedMovies() {
    $connection = \Drupal::database();

    // Get the 10 most recently added movies.
    $query = $connection->select('node_field_data', 'nfd');
    $query->join('node__field_show', 'ns', 'nfd.nid = ns.field_show_target_id');
    $query->join('node__field_review_rating', 'nrr', 'ns.entity_id = nrr.entity_id');
    $query->join('node_field_data', 'nf', 'nf.nid = ns.entity_id');
    $query->join('users_field_data', 'ufd', 'ufd.uid = nf.uid');
    $query->leftJoin('user__roles', 'ur', 'ur.entity_id = ufd.uid');
    $query->fields('nfd', ['nid', 'title', 'created']);
    $query->addExpression("AVG(CASE WHEN ur.roles_target_id = 'Reviewer' THEN nrr.field_review_rating_value ELSE NULL END)", 'critic_rating');
    $query->addExpression("AVG(CASE WHEN ur.roles_target_id != 'Reviewer' THEN nrr.field_review_rating_value ELSE NULL END)", 'user_rating');
    $query->condition('nfd.type', 'show');
    $query->groupBy('nfd.nid');
    $query->orderBy('nfd.created', 'DESC');
    $query->range(0, 10);

    $result = $query->execute()->fetchAll();

    $new_released_movies = [];
    foreach ($result as $row) {
      $movie_node = $this->entityTypeManager->getStorage('node')->load($row->nid);

      $cover_image_url = NULL;
      if ($movie_node && $movie_node->hasField('field_show_cover_image')) {
        $media_id = $movie_node->get('field_show_cover_image')->target_id;
        $cover_image_url = $this->mediaService->getStyledImageUrl($media_id, 'medium');
      }

      $new_released_movies[] = [
        'nid' => $row->nid,
        'title' => $row->title,
        'user_rating' => $row->user_rating !== NULL ? round($row->user_rating, 1) : 'N/A',
        'critic_rating' => $row->critic_rating !== NULL ? round($row->critic_rating, 1) : 'Brak',
        'link' => $this->aliasManager->getAliasByPath('/node/' . $row->nid),
        'image' => $cover_image_url,
      ];
    }

    return $new_released_movies;
  }

}
