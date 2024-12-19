<?php

namespace Drupal\fresh_apples_show_page\Service\MediaService;

interface MediaServiceInterface {
  /**
   * Get the image URL for a media entity.
   *
   * It takes file path from sites/default/files.
   *
   * @param int $media_id
   *   The media entity ID.
   * @param string $field_name
   *   The field name of the image.
   *
   * @return ?string
   *   The URL of the image, NULL otherwise.
   */
  public function getImageUrl($media_id, $field_name): ?string;

  /**
   * Get the styled image URL for a media entity.
   *
   * @param int $media_id
   *   The media entity ID.
   * @param string $style_name
   *   The image style name.
   *
   * @return string
   *   The URL of the styled image.
   */
  public function getStyledImageUrl($media_id, $style_name);

  }
