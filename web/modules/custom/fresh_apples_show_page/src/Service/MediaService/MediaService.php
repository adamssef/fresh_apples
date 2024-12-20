<?php
namespace Drupal\fresh_apples_show_page\Service\MediaService;

use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;

class MediaService implements MediaServiceInterface {

  /**
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * PlanetCoreMediaService constructor.
   *
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file URL generator.
   */
  public function __construct(FileUrlGeneratorInterface $fileUrlGenerator) {
    $this->fileUrlGenerator = $fileUrlGenerator;
  }

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
   * @return string
   *   The URL of the image.
   */
  public function getImageUrl($media_id, $field_name): ?string {
    $media = Media::load($media_id);

    if ($media->hasField($field_name) === FALSE) {
      return NULL;
    }

    $file = $media->get($field_name)->entity;
    $file_uri = $file->getFileUri();

    return $this->fileUrlGenerator->generateAbsoluteString($file_uri);
  }

  /**
   * {@inheritDoc}
   */
  public function getStyledImageUrl($media_id, $style_name = 'thumbnail') {
    $media = Media::load($media_id);

    if ($media && $media->bundle() == 'image') {
      $image_field = $media->get('field_media_image');
      $file = $image_field->entity;

      if ($file) {
        $file_uri = $file->getFileUri();
        $style = ImageStyle::load($style_name);

        if ($style) {
          $derivative_uri = $style->buildUri($file_uri);

          if (!file_exists($derivative_uri) && $file->getMimeType() !== 'image/svg+xml') {
            $style->createDerivative($file_uri, $derivative_uri);
          }

          if ($file->getMimeType() === 'image/svg+xml') {
            // In case of SVG we just want to return original file location.
            return $this->fileUrlGenerator->generateAbsoluteString($file_uri);
          }

          return $style->buildUrl($file_uri);
        }
      }
    }

    return NULL;
  }
}
