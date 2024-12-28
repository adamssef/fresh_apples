<?php

namespace Drupal\fresh_apples_content_import\Plugin\migrate\process\FileLoaders;

use Drupal\fresh_apples_content_import\Plugin\migrate\process\AbstractProcessPluginBaseExtended;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileSystemInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * @MigrateProcessPlugin(
 *   id = "participation_loader"
 * )
 */
class ParticipationLoader extends AbstractProcessPluginBaseExtended {

  /**
   * {@inheritdoc}
   */
  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->logger->notice('Starting participation transformation');

    // If value is already an array (coming from @value notation), extract it properly
    if (is_array($value) && isset($value['@first_name'])) {
      $participations = [$value];
    } else {
      // Otherwise try to decode JSON
      $participations = json_decode($value, TRUE);
      if (!$participations) {
        $participations = [];
      }
    }

    \Drupal::logger('fresh_apples_content_import')->notice('Participations to process: ' . print_r($participations, TRUE));

    $paragraph_ids = [];

    foreach ($participations as $participation) {
      // Convert @ prefixed keys to regular keys
      $cleaned_participation = [];
      foreach ($participation as $key => $val) {
        $clean_key = ltrim($key, '@');
        $cleaned_participation[$clean_key] = $val;
      }

      // Try to find existing persona
      $persona_id = $this->findOrCreatePersona($cleaned_participation);

      if ($persona_id) {
        // Create participation paragraph
        $paragraph = Paragraph::create([
          'type' => 'participation',
          'field_character_name' => $cleaned_participation['character'] ?? '',
          'field_persona' => [
            'target_id' => $persona_id,
          ],
          'field_role' => $this->getRoleId($cleaned_participation['role']),
        ]);

        $paragraph->save();
        $paragraph_ids[] = [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ];
      }
    }

    return $paragraph_ids;
  }  /**
   * Find or create a persona based on provided data.
   */
  protected function findOrCreatePersona($participation) {
    // Extract person details
    $first_name = $participation['first_name'] ?? '';
    $last_name = $participation['last_name'] ?? '';
    $middle_name = $participation['middle_name'] ?? '';
    $birth_date = $participation['birth_date'] ?? '';
    $birth_place = $participation['birth_place'] ?? '';
    $profile_image_url = $participation['profile_image'] ?? '';
    $tmdb_id = $participation['tmdb_id'] ?? '';

    if (empty($first_name) || empty($last_name)) {
      return NULL;
    }

    // Try to find existing persona
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'persona')
      ->condition('field_first_name', $first_name)
      ->condition('field_persona_last_name', $last_name)
      ->accessCheck(FALSE);

    // Add birth date to query if available
    if (!empty($birth_date)) {
      try {
        $date = new DrupalDateTime($birth_date);
        $formatted_date = $date->format('Y-m-d');
        $query->condition('field_persona_birth_date', $formatted_date);
      }
      catch (\Exception $e) {
        $this->logger->warning('Invalid birth date format: @date', ['@date' => $birth_date]);
      }
    }

    $persona_ids = $query->execute();

    // Return existing persona if found
    if (!empty($persona_ids)) {
      return reset($persona_ids);
    }

    // Create media entity for profile image if URL is provided
    $media_id = NULL;
    if (!empty($profile_image_url)) {
      $media_id = $this->createMediaFromUrl($profile_image_url, $first_name . ' ' . $last_name);
    }

    // Create new persona
    try {
      $persona = Node::create([
        'type' => 'persona',
        'title' => $first_name . ' ' . $last_name,
        'field_first_name' => $first_name,
        'field_persona_last_name' => $last_name,
        'field_persona_middle_name' => $middle_name,
        'field_persona_birth_place' => $birth_place,
      ]);

      // Set birth date if available
      if (!empty($birth_date)) {
        try {
          $date = new DrupalDateTime($birth_date);
          $persona->set('field_persona_birth_date', $date->format('Y-m-d'));
        }
        catch (\Exception $e) {
          $this->logger->warning('Could not set birth date for persona: @date', ['@date' => $birth_date]);
        }
      }

      // Set profile image if media was created
      if ($media_id) {
        $persona->set('field_persona_image', ['target_id' => $media_id]);
      }

      $persona->save();
      return $persona->id();
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to create persona: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Create media entity from URL.
   */
  protected function createMediaFromUrl($url, $title) {
    try {
      // Download the file
      $client = \Drupal::httpClient();
      $response = $client->get($url);
      $data = $response->getBody()->getContents();

      // Prepare the directory
      $directory = 'public://shows/posters';
      $filesystem = \Drupal::service('file_system');
      $filesystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

      // Save the file
      $filename = basename($url);
      $uri = $directory . '/' . $filename;

      // Use saveData from the file_system service
      $uri = $filesystem->saveData($data, $uri, FileSystemInterface::EXISTS_REPLACE);

      if ($uri === FALSE) {
        throw new \Exception('Failed to save file');
      }

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
        'name' => $title,
        'field_media_image' => [
          'target_id' => $file->id(),
          'alt' => $title,
        ],
        'status' => 1,
      ]);
      $media->save();

      return $media->id();
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to create media: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }
  /**
   * Get role term ID.
   */
  protected function getRoleId($role_name) {
    if (empty($role_name)) {
      return NULL;
    }

    $term_id = $this->taxonomyService->getTermIdByTermName($role_name, 'persona_roles');
    if (!$term_id) {
      // Create new role term if it doesn't exist
      $term = Term::create([
        'vid' => 'persona_roles',
        'name' => $role_name,
      ]);
      $term->save();
      return $term->id();
    }

    return $term_id;
  }

}
