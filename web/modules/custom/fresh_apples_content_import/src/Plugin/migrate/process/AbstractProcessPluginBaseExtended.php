<?php

namespace Drupal\fresh_apples_content_import\Plugin\migrate\process;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\fresh_apples_show_page\Service\TaxonomyService\TaxonomyServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract class for custom tag lookup for migration.
 */
abstract class AbstractProcessPluginBaseExtended extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Active database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Taxonomy service.
   *
   * @var \Drupal\fresh_apples_show_page\Service\TaxonomyService\TaxonomyServiceInterface
   */
  protected $taxonomyService;

  /**
   * Constructs a FileLocationMatcher object.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param array $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   * The database connection to be used.
   * @param \Psr\Log\LoggerInterface $logger
   * The logger.
   * @param \Drupal\fresh_apples_show_page\Service\TaxonomyService\TaxonomyServiceInterface $taxonomy_service
   *  Taxonomy service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $database,
    LoggerInterface $logger,
    TaxonomyServiceInterface $taxonomy_service,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->logger = $logger;
    $this->taxonomyService = $taxonomy_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('logger.factory')->get('action'),
      $container->get('fresh_apples_show_page.taxonomy_service'),
    );
  }

}
