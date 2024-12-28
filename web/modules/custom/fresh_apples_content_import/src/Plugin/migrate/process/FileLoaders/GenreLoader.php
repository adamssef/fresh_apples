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
 *   id = "genre_loader"
 * )
 */
class GenreLoader extends AbstractProcessPluginBaseExtended {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Log the message when the transformation begins.
    $this->logger->notice('Starting transformation for @value', ['@value' => $value]);

    $value_with_no_spaces = str_replace(' ', '', $value);
    $value_exploded = explode(',', $value_with_no_spaces);
    $tids = [];

    foreach ($value_exploded as $genre) {
      if ($this->taxonomyService->getTermIdByTermName($genre, 'genre')) {
        $tids[] = $this->taxonomyService->getTermIdByTermName($genre, 'genre');
      }
      else {
        $new_term = Term::create([
          'vid' => 'genre',
          'name' => $genre,
        ]);

        $new_term->save();
        $tids[] = $new_term->id();
      }
    }

    return $tids;
  }

}
