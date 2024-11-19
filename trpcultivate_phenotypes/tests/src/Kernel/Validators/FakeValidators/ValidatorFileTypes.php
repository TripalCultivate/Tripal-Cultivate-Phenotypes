<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Kernel\Validators\FakeValidators;

use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\TripalCultivatePhenotypesValidatorBase;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits\FileTypes;

/**
 * Fake Validator that does not implement any of its own methods.
 *
 * Used to test the FileTypes trait.
 *
 * @TripalCultivatePhenotypesValidator(
 *   id = "validator_requiring_filetypes",
 *   validator_name = @Translation("Validator Using File Types Trait"),
 *   input_types = {"file"}
 * )
 */
class ValidatorFileTypes extends TripalCultivatePhenotypesValidatorBase {

  use FileTypes;

}
