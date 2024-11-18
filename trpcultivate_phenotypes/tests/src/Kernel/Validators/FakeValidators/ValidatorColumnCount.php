<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Kernel\Validators\FakeValidators;

use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\TripalCultivatePhenotypesValidatorBase;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits\ColumnCount;

/**
 * Fake Validator that does not implement any of its own methods.
 *
 * Used to test the ColumnCount trait.
 *
 * @TripalCultivatePhenotypesValidator(
 *   id = "validator_requiring_column_count",
 *   validator_name = @Translation("Validator Using Column Count Trait"),
 *   input_types = {"header-row"}
 * )
 */
class ValidatorColumnCount extends TripalCultivatePhenotypesValidatorBase {

  use ColumnCount;

}
