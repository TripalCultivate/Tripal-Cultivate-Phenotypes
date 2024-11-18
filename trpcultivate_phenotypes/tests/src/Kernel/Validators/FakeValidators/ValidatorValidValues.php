<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Kernel\Validators\FakeValidators;

use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\TripalCultivatePhenotypesValidatorBase;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits\ValidValues;

/**
 * Fake Validator that does not implement any of its own methods.
 *
 * Used to test the ValidValues trait.
 *
 * @TripalCultivatePhenotypesValidator(
 * id = "validator_requiring_valid_values",
 * validator_name = @Translation("Validator Using ValidValues Trait"),
 * input_types = {"header-row", "data-row"}
 * )
 */
class ValidatorValidValues extends TripalCultivatePhenotypesValidatorBase {

  use ValidValues;

}
