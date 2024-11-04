<?php

namespace Drupal\trpcultivate_phenotypes\Plugin\Validators;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\tripal_chado\Controller\ChadoProjectAutocompleteController;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\TripalCultivatePhenotypesValidatorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validate that project exists.
 *
 * @TripalCultivatePhenotypesValidator(
 *   id = "project_exists",
 *   validator_name = @Translation("Project Exists Validator"),
 *   input_types = {"metadata"}
 * )
 */
class ProjectExists extends TripalCultivatePhenotypesValidatorBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
  }

  /**
   * Validate that project provided exists.
   *
   * @param array $form_values
   *   The values entered to any form field elements implemented by the importer.
   *   Each form element value can be accessed using the field element key
   *   ie. field name/key project - $form_values['project'].
   *
   *   This array is the result of calling $form_state->getValues().
   *
   * @return array
   *   An associative array with the following keys.
   *     - case: a developer focused string describing the case checked.
   *     - valid: either TRUE or FALSE depending on if the project value existed or not.
   *     - failedItems: an array of "items" that failed to be used in the message to the user. This is an empty array if the metadata input was valid.
   */
  public function validateMetadata(array $form_values) {
    // This project exists validator assumes that a field with name/key project was
    // implemented in the Importer form.
    $expected_field_key = 'project';

    // Failed to locate the project field element.
    if (!array_key_exists($expected_field_key, $form_values)) {
      throw new \Exception('Failed to locate project field element. ProjectExists validator expects a form field element name project.');
    }

    // Validator response values for a valid project value (exists).
    $case = 'Project exists';
    $valid = TRUE;
    $failed_items = [];

    // Project.
    $project = trim($form_values[ $expected_field_key ]);

    // Determine what was provided to the project field: project id or name.
    if (is_numeric($project)) {
      // Value is integer. Project id was provided.
      // Test project by looking up the id to retrieve the project name.
      $project_rec = ChadoProjectAutocompleteController::getProjectName((int) $project);
    }
    else {
      // Value is string. Project name was provided.
      // Test project by looking up the name to retrieve the project id.
      $project_rec = ChadoProjectAutocompleteController::getProjectId($project);
    }

    if ($project_rec <= 0 || empty($project_rec)) {
      // The project provided, whether the name or project id, does not exist.
      $case = 'Project does not exist';
      $valid = FALSE;
      $failed_items = ['project_provided' => $project];
    }

    return [
      'case' => $case,
      'valid' => $valid,
      'failedItems' => $failed_items
    ];
  }
}
