<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Kernel\Validators\FakeValidators;

use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\TripalCultivatePhenotypesValidatorBase;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits\Headers;

/**
 * Fake Validator that does not implement any of its own methods.
 *
 * Used to test the Headers trait.
 *
 * @TripalCultivatePhenotypesValidator(
 *   id = "validator_requiring_headers",
 *   validator_name = @Translation("Validator Using Headers Trait"),
 *   input_types = {"header-row", "data-row"}
 * )
 */
class ValidatorHeaders extends TripalCultivatePhenotypesValidatorBase {

  use Headers;

}
