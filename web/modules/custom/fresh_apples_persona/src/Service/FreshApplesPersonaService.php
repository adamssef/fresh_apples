<?php

namespace Drupal\fresh_apples_persona\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\fresh_apples_show_page\Service\MediaService\MediaServiceInterface;
use Drupal\fresh_apples_show_page\Service\TaxonomyService\TaxonomyServiceInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;

class FreshApplesPersonaService {

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
  public function preparePersonaData(NodeInterface $node) {
    if ($node->getType() !== 'persona') {
      return [];
    }

    $first_name = $node->get('field_first_name')->value;
    $middle_name = $node->get('field_persona_middle_name')->value;
    $last_name = $node->get('field_persona_last_name')->value;
    $field_birth_date = $node->get('field_persona_birth_date')->value;
    $media_id = $node->get('field_persona_image')->getValue()[0]['target_id'];
    $image_url = $this->mediaService->getStyledImageUrl($media_id, 'medium');
    $place_of_birth = $node->get('field_persona_birth_place')->value;


    $node_storage = $this->entityTypeManager->getStorage('node');

    // Query for show nodes.
    $query = $node_storage->getQuery();
    $query->condition('type', 'show'); // Replace 'show' with your content type machine name.
    $query->condition('status', 1); // Only published nodes.

    // Join the paragraph entities in the participations field.
    $query->addTag('entity_reference_revisions');
    $query->addMetaData('entity_reference_revisions', [
      'field_name' => 'field_participation', // Replace with your field machine name.
    ]);

    // Add the condition to filter paragraphs by the field_persona reference.
    $query->condition('field_participation.entity.field_persona.target_id', $node->id());
    $query->accessCheck(FALSE);

    // Execute the query.
    $show_ids = $query->execute();

    $shows = $node_storage->loadMultiple($show_ids);

    $paticipations_data = [];

    foreach ($shows as $show) {
      $participations = $show->get('field_participation')->referencedEntities();


      foreach ($participations as $participation) {

        $person_from_participation = $participation->get('field_persona')->entity;

        if ($person_from_participation->id() !== $node->id()) {
          continue;
        }

        $role_id = $participation->get('field_role')->getValue()[0]['target_id'];
        $role_name = Term::load($role_id)->getName();
        $show_image_url = $this->mediaService->getStyledImageUrl($show->get('field_show_cover_image')->getValue()[0]['target_id'], 'medium');
        $show_url_alias = \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $show->id()) ?? \Drupal::service('path_alias.manager')->getAliasByPath('/node/' . $show->id());

        $participation_data = [
          'role' => $role_name,
          'character' => $participation->get('field_character_name')->value,
          'show' => $show->getTitle(),
          'show_url' => $show_image_url,
          'show_link' => $show->toUrl()->toString(),
          'show_url_alias' => $show_url_alias,
        ];
        $participations_data[] = $participation_data;
      }
    }

    $sorted_participations_data = [];

    foreach ($participations_data as $participation_data) {
      $role = $participation_data['role'];
      $sorted_participations_data[$role][] = $participation_data;
    }

    if (isset($sorted_participations_data['Actor']) && count($sorted_participations_data['Actor']) < 5) {
      $sorted_participations_data['Actor'] = array_merge($sorted_participations_data['Actor'], $sorted_participations_data['Actor'], $sorted_participations_data['Actor'], $sorted_participations_data['Actor'], $sorted_participations_data['Actor']);
    }

    if (isset($sorted_participations_data['Director']) && count($sorted_participations_data['Director']) < 5) {
      $sorted_participations_data['Director'] = array_merge($sorted_participations_data['Director'], $sorted_participations_data['Director'], $sorted_participations_data['Director'], $sorted_participations_data['Director'], $sorted_participations_data['Director']);
    }

    if (isset($sorted_participations_data['Screenwriter']) && count($sorted_participations_data['Screenwriter']) < 5) {
      $sorted_participations_data['Writer'] = array_merge($sorted_participations_data['Screenwriter'], $sorted_participations_data['Screenwriter'], $sorted_participations_data['Screenwriter'], $sorted_participations_data['Screenwriter'], $sorted_participations_data['Screenwriter']);
    }

    return [
      'first_name' => $first_name,
      'middle_name' => $middle_name,
      'last_name' => $last_name,
      'birth_date' => $field_birth_date,
      'place_of_birth' => $place_of_birth,
      'image_url' => $image_url,
      'participations' => $sorted_participations_data,
    ];
  }
}
