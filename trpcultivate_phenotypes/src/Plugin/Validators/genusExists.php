<?php

/**
 * @file
 * Contains validator plugin definition.
 */

namespace Drupal\trpcultivate_phenotypes\Plugin\Validators;

use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\TripalCultivatePhenotypesValidatorBase;
use Drupal\trpcultivate_phenotypes\Service\TripalCultivatePhenotypesGenusOntologyService;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validate that genus exists and is configured.
 *
 * @TripalCultivatePhenotypesValidator(
 *   id = "trpcultivate_phenotypes_validator_genus_exists",
 *   validator_name = @Translation("Genus Exists and Configured Validator"),
 *   input_types = {"metadata"}
 * )
 */
class genusExists extends TripalCultivatePhenotypesValidatorBase implements ContainerFactoryPluginInterface {
  /**
   * Genus Ontology Service;
   */
  protected $service_PhenoGenusOntology;

  /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
    TripalCultivatePhenotypesGenusOntologyService $service_genus_ontology) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    
    // Genus ontology service.
    $this->service_PhenoGenusOntology = $service_genus_ontology;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition){
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('trpcultivate_phenotypes.genus_ontology')
    );
  }

  /**
   * Validate that genus provided exists and configured.
   * 
   * @param array $form_values
   *   The values entered to any form field elements implemented by the importer.
   *   Each form element value can be accessed using the field element key
   *   ie. field name/key genus - $form_values['genus'].
   * 
   *   This array is the result of calling $form_state->getValues().
   *
   * @return array
   *   An associative array with the following keys.
   *     - case: a developer focused string describing the case checked.
   *     - valid: either TRUE or FALSE depending on if the genus value is valid or not.
   *     - failedItems: the failed genus value provided. This will be empty if the value was valid.
   */
  public function validateMetadata($form_values) {
    // This genus validator assumes that a field with name/key genus was
    // implemented in the Importer form.
    $expected_field_key = 'genus';
  
    // Parameter passed to the method is not an array.
    if (!is_array($form_values)) {
      throw new \Exception(t('Unexpected @type type was passed as parameter to genusExists validator.', ['@type' => gettype($form_values)]));
    }
    
    // Failed to locate the genus field element.
    if (is_array($form_values) && !array_key_exists($expected_field_key, $form_values)) {
      throw new \Exception(t('Failed to locate genus field element. genusExists validator expects a form field element name genus.'));
    }
  
    // Validator response values for a valid genus value.
    $case = 'Genus exists and is configured with phenotypes';
    $valid = TRUE;
    $failed_items = '';
    
    // Genus.
    $genus = trim($form_values[ $expected_field_key ]);

    // This method has now curated all genus available in the organism table,
    // both configured and non-configured genus.
    $genus_config = $this->service_PhenoGenusOntology->getGenusOntologyConfigValues($genus);

    if (!$genus_config) {
      // The genus provided does not exist.
      $case = 'Genus does not exist';
      $valid = FALSE;
      $failed_items = $genus;
    }
    else {
      // The genus provided does exist, test that it was
      // fully configured, that is, a cv was set for trait configuration.

      if (empty($genus_config['trait']) || $genus_config['trait'] <= 0) {
        // Not configured genus.
        $case = 'Genus exists but is not configured';
        $valid = FALSE;
        $failed_items = $genus;
      }
    }
    
    return [
      'case' => $case,
      'valid' => $valid,
      'failedItems' => $failed_items
    ];
  }
}