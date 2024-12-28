<?php

namespace Drupal\fresh_apples_show_page\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\fresh_apples_show_page\Service\MediaService\MediaServiceInterface;
use Drupal\fresh_apples_show_page\Service\TaxonomyService\TaxonomyServiceInterface;
use Drupal\node\NodeInterface;

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
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    TaxonomyServiceInterface $taxonomy_service,
    MediaServiceInterface $media_service,

  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->taxonomyService = $taxonomy_service;
    $this->mediaService = $media_service;
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

    if (!empty($genres)) {
      foreach ($genres as $genre) {
        $genre_names[] = $genre->getName();
      }
    }

    $length_in_minutes = $node->get('field_length')->value;
    $media_id = $node->get('field_show_cover_image')->target_id;
    $cover_image_url = $this->mediaService->getStyledImageUrl($media_id, 'wide');
    $show_type = $node->get('field_show_type')->referencedEntities()[0]->getName();
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

    $participation_paragraphs_entities = $this->entityTypeManager->getStorage('paragraph')->loadMultiple($participation_paragraphs_ids);
    $participation_paragraphs_data = [];

    foreach ($participation_paragraphs_entities as $paragraph) {
      $persona = $paragraph->get('field_persona')->referencedEntities()[0];
      $persona_full_name = $persona->getTitle();
      $persona_image_id = $persona->get('field_persona_image')->target_id;
      $persona_image_url = $this->mediaService->getStyledImageUrl($persona_image_id, 'thumbnail');
      $participation_paragraphs_data[] = [
        'character_name' => $paragraph->get('field_character_name')->value,
        'role' => $paragraph->get('field_role')->referencedEntities()[0]->getName(),
        'persona_full_name' => $persona_full_name,
        'persona_image_url' => $persona_image_url,
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
    ];
  }

}
