<?php

namespace Drupal\fresh_apples_show_page\Service\TaxonomyService;

interface TaxonomyServiceInterface {

  /**
   * Get taxonomy terms array.
   *
   * @param string $taxonomy_vocabulary_name
   *   The taxonomy vocabulary name.
   *
   * @return array|null
   *   The taxonomy terms array.
   */
  public function getTaxonomyTermsArray(string $taxonomy_vocabulary_name): ?array;


  /**
   * Get top level taxonomy terms array.
   *
   * @param string $taxonomy_vocabulary_name
   *   The taxonomy vocabulary name.
   *
   * @return array|null
   *   The taxonomy terms array.
   */
  public function getNthLevelTaxonomyTermsArray(string $taxonomy_vocabulary_name, int $level): ?array;

  /**
   * Retrieves the taxonomy term ID by its name and vocabulary.
   *
   * @param string $term_name The name of the taxonomy term.
   * @param string $vocabulary The machine name of the vocabulary.
   *
   * @return int|null The taxonomy term ID, or NULL if not found.
   */
  public function getTermIdByTermName(string $term_name, string $vocabulary): ?int;

  /**
   * Get the term name by its ID.
   *
   * @param int $term_id
   *   The term ID.
   *
   * @return ?string
   *   The term name, null otherwise
   */
  function getTermNameById($term_id): ?string;
}
