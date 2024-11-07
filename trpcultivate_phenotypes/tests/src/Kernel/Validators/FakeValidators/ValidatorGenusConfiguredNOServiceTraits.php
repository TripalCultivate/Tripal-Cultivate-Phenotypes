<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Kernel\Validators\FakeValidators;

use Drupal\tripal_chado\Database\ChadoConnection;
use Drupal\trpcultivate_phenotypes\Service\TripalCultivatePhenotypesGenusOntologyService;
use Drupal\trpcultivate_phenotypes\Service\TripalCultivatePhenotypesTraitsService;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\TripalCultivatePhenotypesValidatorBase;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits\GenusConfigured;

/**
 * Fake Validator that does not implement any of its own methods.
 *
 * Used to test the GenusConfigured trait with NO PhenoTraits service.
 *
 * @TripalCultivatePhenotypesValidator(
 * id = "validator_configured_genus_no_service_trait",
 * validator_name = @Translation("Validator Using GenusConfigured Trait"),
 * input_types = {"header-row", "data-row"}
 * )
 */
class ValidatorGenusConfiguredNOServiceTraits extends TripalCultivatePhenotypesValidatorBase {

  use GenusConfigured;

  /**
   * An instance of the Genus Ontology service.
   *
   * @var Drupal\trpcultivate_phenotypes\Service\TripalCultivatePhenotypesGenusOntologyService
   */
  // phpcs:ignore
  protected TripalCultivatePhenotypesGenusOntologyService $service_PhenoGenusOntology;

  /**
   * An instance of the Traits Service.
   *
   * @var Drupal\trpcultivate_phenotypes\Service\TripalCultivatePhenotypesTraitsService
   */
  // phpcs:ignore
  protected TripalCultivatePhenotypesTraitsService $service_PhenoTraits;

  /**
   * A Database query interface for querying Chado using Tripal DBX.
   *
   * @var Drupal\tripal_chado\Database\ChadoConnection
   */
  // phpcs:ignore
  protected ChadoConnection $chado_connection;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ChadoConnection $chado_connection, TripalCultivatePhenotypesGenusOntologyService $service_PhenoGenusOntology, TripalCultivatePhenotypesTraitsService $service_PhenoTraits) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->chado_connection = $chado_connection;
    $this->service_PhenoGenusOntology = $service_PhenoGenusOntology;
    // $this->service_PhenoTraits = $service_PhenoTraits;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tripal_chado.databse'),
      $container->get('trpcultivate_phenotypes.genus_ontology'),
      $container->get('trpcultivate_phenotypes.traits')
    );
  }

}
