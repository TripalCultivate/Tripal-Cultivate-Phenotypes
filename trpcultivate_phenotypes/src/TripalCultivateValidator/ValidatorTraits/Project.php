<?php

namespace Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits;

use Drupal\tripal_chado\Controller\ChadoProjectAutocompleteController;

/**
 * Provides getter/setters regarding project used by the importer.
 */
trait Project {
  /**
   * The key in the context array used to reference and retrieve the project.
   *
   * @var string
   */
  private string $context_key = 'project';

  /**
   * Sets a single project for use by a validator.
   *
   * NOTE: This project must exist in the chado.project table already and both
   * the project_id and project name will be saved for use by the validator.
   *
   * @param string|int $project
   *   A string value is a project name (project.name), whereas an integer value
   *   is a project id number (project.project_id).
   *
   * @throws \Exception
   *   - If project name is an empty string value.
   *   - If project id is 0.
   */
  public function setProject(string|int $project) {

    // Determine if parameter is a project name (string) or a project id (int).
    if (is_numeric($project)) {
      // Project id number.
      if ($project <= 0) {
        // Since this is a user-provided value, the error is going to be logged
        // and then checked by a validator so that the error can be passed to
        // the user in a friendly way.
        $this->logger->error('The Project Trait requires project id number to be a number greater than 0.');
      }

      // Look up the project id to retrieve the project name.
      $project_rec = ChadoProjectAutocompleteController::getProjectName($project);

      $set_project = [
        'project_id' => $project,
        'name' => $project_rec,
      ];
    }
    else {
      // Project name.
      if (trim($project) === '') {
        // Since this is a user-provided value, the error is going to be logged
        // and then checked by a validator so that the error can be passed to
        // the user in a friendly way.
        $this->logger->error('The Project Trait requires project name to be a non-empty string value.');
      }

      // Look up the project name to retrieve the project id number.
      $project_rec = ChadoProjectAutocompleteController::getProjectId($project);

      $set_project = [
        'project_id' => $project_rec,
        'name' => $project,
      ];
    }

    // Check if $set_project is set before setting the context array, and log an
    // error if the project can't be found in the database.
    if ($set_project['project_id'] <= 0 || empty($set_project['name'])) {
      // Since this is a user-provided value, the error is going to be logged
      // and then checked by a validator so that the error can be passed to the
      // user in a friendly way.
      $this->logger->error('The Project Trait requires a project that exists in the database.');
    }
    else {
      $this->context[$this->context_key] = $set_project;
    }
  }

  /**
   * Returns a single project which has been verified to exist by the setter.
   *
   * @return array
   *   An array that includes the folowing keys:
   *   - 'project id': the project id number
   *   - 'name': project name
   *
   * @throws \Exception
   *   - If project has NOT been set by setProject().
   */
  public function getProject() {

    if (array_key_exists($this->context_key, $this->context)) {
      return $this->context[$this->context_key];
    }
    else {
      throw new \Exception('Cannot retrieve project from the context array as one has not been set by setProject() method.');
    }
  }

}
