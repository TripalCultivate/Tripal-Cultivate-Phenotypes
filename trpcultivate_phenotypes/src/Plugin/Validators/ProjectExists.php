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
   *   An array of values from the submitted form where each key maps to a form
   *   element and the value is what the user entered.
   *   Each form element value can be accessed using the field element key
   *   ie. field name/key project - $form_values['project'].
   *
   *   This array is the result of calling $form_state->getValues().
   *
   * @return array
   *   An associative array with the following keys.
   *   - 'case': a developer focused string describing the case checked.
   *   - 'valid': TRUE if the provided project exists, FALSE otherwise.
   *   - 'failedItems': an array of items that failed with the following keys.
   *     This is an empty array if the metadata input was valid.
   *     - 'project_provided': The name of the project provided.
   *
   * @throws \Exception
   *   - If the 'project' key does not exist in $form_values.
   */
  public function validateMetadata(array $form_values) {
    // This validator assumes that a field with name/key project was implemented
    // in the Importer form.
    $expected_field_key = 'project';

    // Failed to locate the project field element.
    if (!array_key_exists($expected_field_key, $form_values)) {
      throw new \Exception('Failed to locate project field element. ProjectExists validator expects a form field element name project.');
    }

    // Validator response values for a valid project value.
    $case = 'Project exists';
    $valid = TRUE;
    $failed_items = [];

    $project = trim($form_values[$expected_field_key]);

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
