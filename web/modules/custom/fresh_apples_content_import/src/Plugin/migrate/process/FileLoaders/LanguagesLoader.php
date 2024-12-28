<?php
namespace Drupal\fresh_apples_content_import\Plugin\migrate\process\FileLoaders;

use Drupal\fresh_apples_content_import\Plugin\migrate\process\AbstractProcessPluginBaseExtended;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\taxonomy\Entity\Term;

/**
 * Migrates movie data into existing nodes.
 *
 * @MigrateProcessPlugin(
 *   id = "languages_loader"
 * )
 */
class LanguagesLoader extends AbstractProcessPluginBaseExtended {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->logger->notice('Starting transformation for @value', ['@value' => $value]);

    // Clean and normalize the value
    $value_clean = trim($value); // Remove leading/trailing spaces

    // Replace multiple spaces with a single space
    $value_clean = preg_replace('/\s+/', ' ', $value_clean);

    // Split by commas to get an array of languages
    $value_exploded = explode(',', $value_clean);

    // Log the exploded value for debugging
    $this->logger->notice('Languages: @value', ['@value' => print_r($value_exploded, TRUE)]);

    $tids = [];

    // Loop through each language and process it
    foreach ($value_exploded as $language) {
      // Clean up any extra spaces around each language
      $language = trim($language);

      if (empty($language)) {
        // Skip empty values that might appear after splitting
        continue;
      }

      \Drupal::logger('fresh_apples_content_import')->notice('Language: ' . $language);

      // Check if the term exists, if not, create it
      if ($this->taxonomyService->getTermIdByTermName($language, 'spoken_languages')) {
        $this->logger->notice('Language exists: @value', ['@value' => $language]);
        $language = trim($language);
        $tids[] = $this->taxonomyService->getTermIdByTermName($language, 'spoken_languages');
      }
      else {
        $new_term = Term::create([
          'vid' => 'spoken_languages',
          'name' => $language,
        ]);

        $new_term->save();
        $tids[] = $new_term->id();
      }
    }

    // Log the term IDs for debugging
    $this->logger->notice('Languages TIDs: @value', ['@value' => print_r($tids, TRUE)]);

    return $tids;
  }
}
