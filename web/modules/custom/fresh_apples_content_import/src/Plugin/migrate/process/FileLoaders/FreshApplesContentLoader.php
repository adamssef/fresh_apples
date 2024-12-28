<?php
namespace Drupal\fresh_apples_content_import\Plugin\migrate\process\FileLoaders;

use Drupal\fresh_apples_content_import\Plugin\migrate\process\AbstractProcessPluginBaseExtended;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Migrates movie data into existing nodes.
 *
 * @MigrateProcessPlugin(
 *   id = "crew_loader"
 * )
 */
class FreshApplesContentLoader extends AbstractProcessPluginBaseExtended {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Log the message when the transformation begins.
//    $this->logger->notice('Starting transformation for @value', ['@value' => $value]);
  }

}
