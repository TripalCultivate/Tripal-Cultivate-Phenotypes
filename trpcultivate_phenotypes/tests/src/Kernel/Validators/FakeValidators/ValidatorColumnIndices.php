<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Kernel\Validators\FakeValidators;

use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\TripalCultivatePhenotypesValidatorBase;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits\ColumnIndices;

/**
 * Fake Validator that does not implement any of its own methods.
 *
 * Used to test the ColumnIndices trait.
 *
 * @TripalCultivatePhenotypesValidator(
 * id = "validator_requiring_column_indices",
 * validator_name = @Translation("Validator Using ColumnIndices Trait"),
 * input_types = {"header-row", "data-row"}
 * )
 */
class ValidatorColumnIndices extends TripalCultivatePhenotypesValidatorBase {

  use ColumnIndices;

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
