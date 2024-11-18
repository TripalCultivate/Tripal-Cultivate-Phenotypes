<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Kernel\Validators;

use Drupal\Tests\tripal_chado\Kernel\ChadoTestKernelBase;
use Drupal\Tests\trpcultivate_phenotypes\Kernel\Validators\FakeValidators\ValidatorColumnIndices;

/**
 * Tests the ColumnIndices validator trait.
 *
 * @group trpcultivate_phenotypes
 * @group validator_traits
 */
class ValidatorTraitColumnIndicesTest extends ChadoTestKernelBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'file',
    'user',
    'tripal',
    'tripal_chado',
    'trpcultivate_phenotypes',
  ];

  /**
   * The validator instance to use for testing.
   *
   * @var \Drupal\Tests\trpcultivate_phenotypes\Kernel\Validators\FakeValidators\ValidatorColumnIndices
   */
  protected ValidatorColumnIndices $instance;

  /**
   * An array of indices which are invalid.
   *
   * @var array
   */
  protected array $invalid_indices;

  /**
   * An array of indices which are valid.
   *
   * @var array
   */
  protected array $valid_indices;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set test environment.
    \Drupal::state()->set('is_a_test_environment', TRUE);

    // Install module configuration.
    $this->installConfig(['trpcultivate_phenotypes']);

    // Create a new object for the purpose of testing.
    $my_array = ['key' => 'value'];
    $my_object = (object) $my_array;

    // Setup our invalid indices array.
    $invalid_indices = [
      [1, 2, [3, 4, 5]],
      [1, $my_object, 3],
      [0.5, -7.3, 6.6],
    ];
    $this->invalid_indices = $invalid_indices;

    // Setup our valid indices array.
    $valid_indices = [
      [1, 2, 3],
      ['Trait', 'Method', 'Unit'],
    ];
    $this->valid_indices = $valid_indices;

    // Create a fake plugin instance for testing.
    $configuration = [];
    $validator_id = 'validator_requiring_column_indices';
    $plugin_definition = [
      'id' => $validator_id,
      'validator_name' => 'Validator Using ColumnIndices Trait',
      'input_types' => ['header-row', 'data-row'],
    ];
    $instance = new ValidatorColumnIndices(
      $configuration,
      $validator_id,
      $plugin_definition
    );
    $this->assertIsObject(
      $instance,
      "Unable to create $validator_id validator instance to test the ColumnIndices trait."
    );

    $this->instance = $instance;
  }

  /**
   * Tests ColumnIndices::setIndices() and ColumnIndices::getIndices()
   */
  public function testColumnIndicesSetterGetter() {

    // Try to get indices before any have been set.
    // Exception message should trigger.
    $expected_message = 'Cannot retrieve an array of indices as one has not been set by the setIndices() method.';

    $exception_caught = FALSE;
    $exception_message = 'NONE';
    try {
      $this->instance->getIndices();
    }
    catch (\Exception $e) {
      $exception_caught = TRUE;
      $exception_message = $e->getMessage();
    }

    $this->assertTrue($exception_caught, 'Calling getIndices() when no indices have been set should have thrown an exception but did not.');
    $this->assertStringContainsString(
      $expected_message,
      $exception_message,
      'The exception thrown does not have the message we expected when trying to get indices but none have been set yet.'
    );

    // Try to set an empty array of indices.
    // Exception message should trigger.
    $empty_indices = [];
    $expected_message = 'The ColumnIndices Trait requires a non-empty array of indices.';

    $exception_caught = FALSE;
    $exception_message = 'NONE';
    try {
      $this->instance->setIndices($empty_indices);
    }
    catch (\Exception $e) {
      $exception_caught = TRUE;
      $exception_message = $e->getMessage();
    }

    $this->assertTrue($exception_caught, 'Calling setIndices() with an empty array should have thrown an exception but did not.');
    $this->assertStringContainsString(
      $expected_message,
      $exception_message,
      'The exception thrown does not have the message we expected when trying to set indices with an empty array.'
    );

    // Try to set a multi-dimensional array (only 1-dimensional allowed).
    // Exception message should trigger.
    foreach ($this->invalid_indices as $indices) {
      $expected_message = 'The ColumnIndices Trait requires a one-dimensional array with values that are of type integer or string only.';

      $exception_caught = FALSE;
      $exception_message = 'NONE';
      try {
        $this->instance->setIndices($indices);
      }
      catch (\Exception $e) {
        $exception_caught = TRUE;
        $exception_message = $e->getMessage();
      }

      $this->assertTrue($exception_caught, 'Calling setIndices() with an array of values that are not of type integer or string should have thrown an exception but did not.');
      $this->assertStringContainsString(
        $expected_message,
        $exception_message,
        'The exception thrown does not have the message we expected when trying to set indices with an array that has a value which is not a string or integer.'
      );
    }

    // Set valid indices and then check that they've been set.
    foreach ($this->valid_indices as $indices) {
      $exception_caught = FALSE;
      $exception_message = 'NONE';
      try {
        $this->instance->setIndices($indices);
      }
      catch (\Exception $e) {
        $exception_caught = TRUE;
        $exception_message = $e->getMessage();
      }
      $this->assertFalse(
        $exception_caught,
        "Calling setIndices() with a valid set of indices should not have thrown an exception but it threw '$exception_message'"
      );

      // Check that we can get the indices we just set.
      $grabbed_indices = $this->instance->getIndices();
      $this->assertEquals(
        $indices,
        $grabbed_indices,
        'Could not grab the set of valid indices using getIndices() despite having called setIndices() on it.'
      );
    }
  }

}
