<?php

namespace Drupal\fresh_apples_content_import\Plugin\migrate\process\FileLoaders;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

/**
 * @MigrateProcessPlugin(
 *   id = "url_to_media"
 * )
 */
class UrlToMedia extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      return NULL;
    }

    try {
      // Download the file
      $client = \Drupal::httpClient();
      $response = $client->get($value);
      $data = $response->getBody()->getContents();

      // Prepare the filename and directory
      $filename = basename($value);
      $directory = 'public://shows/posters';

      /** @var \Drupal\Core\File\FileSystemInterface $fileSystem */
      $fileSystem = \Drupal::service('file_system');
      $fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

      // Save the file
      $uri = $directory . '/' . $filename;
      $fileSystem->saveData($data, $uri, FileSystemInterface::EXISTS_REPLACE);

      // Create file entity
      $file = File::create([
        'uri' => $uri,
        'uid' => 1,
        'status' => 1,
        'filename' => $filename,
      ]);
      $file->save();

      // Create media entity
      $media = Media::create([
        'bundle' => 'image',
        'uid' => 1,
        'field_media_image' => [
          'target_id' => $file->id(),
          'alt' => $row->getSourceProperty('title'),
        ],
        'name' => $row->getSourceProperty('title'),
        'status' => 1,
      ]);
      $media->save();

      return $media->id();
    }
    catch (\Exception $e) {
      \Drupal::logger('fresh_apples_content_import')->error('Failed to import media from @url: @message', [
        '@url' => $value,
        '@message' => $e->getMessage(),
      ]);
      return NULL;
    }
  }
}
