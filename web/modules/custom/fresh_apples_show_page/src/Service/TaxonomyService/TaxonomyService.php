<?php

namespace Drupal\fresh_apples_show_page\Service\TaxonomyService;

use Drupal\Core\Entity\EntityTypeManagerInterface;

class TaxonomyService implements TaxonomyServiceInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * PlanetPaymentMethodsService constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   *   The node translation service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public function getTaxonomyTermsArray(string $taxonomy_vocabulary_name): ?array {
    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($taxonomy_vocabulary_name);

    foreach ($terms as $key=>$term) {
      $term_data[$key] = $term->name;
    }

    return $term_data;
  }

  /**
   * {@inheritDoc}
   */
  public function getNthLevelTaxonomyTermsArray(string $taxonomy_vocabulary_name, int $level = NULL): ?array {
    $terms =\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($taxonomy_vocabulary_name);

    foreach ($terms as $key=>$term) {
      if ($level === NULL) {
        $term_data[$key] = $term->name;
      }
      else {
        if ($term->depth === $level) {
          $term_data[$key] = $term->name;
        }
      }

    }

    return $term_data;
  }

  /**
   * Retrieves the taxonomy term ID by its name and vocabulary.
   *
   * @param string $term_name The name of the taxonomy term.
   * @param string $vocabulary The machine name of the vocabulary.
   *
   * @return int|null The taxonomy term ID, or NULL if not found.
   */
  function getTermIdByTermName($term_name, $vocabulary):?int {
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties([
        'name' => $term_name,
        'vid' => $vocabulary,
      ]);

    // If there are any terms, return the first one's ID.
    if ($terms) {
      $term = reset($terms);
      return $term->id();
    }

    return NULL;
  }

  /**
   * Get the term name by its ID.
   *
   * @param int $term_id
   *   The term ID.
   *
   * @return ?string
   *   The term name, null otherwise.
   */
  function getTermNameById($term_id):?string {
    $term = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->load($term_id);

    return $term->getName();
  }

}
