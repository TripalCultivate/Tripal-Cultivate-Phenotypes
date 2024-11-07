<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Kernel\Validators\FakeValidators;

use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\TripalCultivatePhenotypesValidatorBase;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits\Project;

/**
 * Fake Validator that does not implement any of its own methods.
 *
 * Used to test the Project trait.
 *
 * @TripalCultivatePhenotypesValidator(
 *   id = "validator_requiring_project",
 *   validator_name = @Translation("Validator Using Project Trait"),
 *   input_types = {"metadata"}
 * )
 */
class ValidatorProject extends TripalCultivatePhenotypesValidatorBase {

  use Project;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

}
