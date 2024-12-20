<?php

declare(strict_types=1);

namespace Drupal\fresh_apples_header_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a header_menu block.
 *
 * @Block(
 *   id = "fresh_apples_header_menu_header_menu",
 *   admin_label = @Translation("header_menu"),
 *   category = @Translation("Fresh Apples"),
 * )
 */
final class HeaderMenuBlock extends BlockBase {



  /**
   * {@inheritdoc}
   */
  public function build():array {
    $build['content'] = [
      '#theme' => 'block__header_menu',
    ];
    return $build;
  }

}
