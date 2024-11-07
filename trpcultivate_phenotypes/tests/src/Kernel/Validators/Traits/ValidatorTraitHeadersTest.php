<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Kernel\Validators;

use Drupal\Tests\tripal_chado\Kernel\ChadoTestKernelBase;
use Drupal\Tests\trpcultivate_phenotypes\Kernel\Validators\FakeValidators\ValidatorHeaders;
use Drupal\Tests\trpcultivate_phenotypes\Traits\PhenotypeImporterTestTrait;

/**
 * Tests the headers validator trait.
 *
 * @group trpcultivate_phenotypes
 * @group validator_traits
 */
class ValidatorTraitHeadersTest extends ChadoTestKernelBase {

  use PhenotypeImporterTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'user',
    'tripal',
    'tripal_chado',
    'trpcultivate_phenotypes',
  ];

  /**
   * The validator instance to use for testing.
   *
   * @var \Drupal\Tests\trpcultivate_phenotypes\Kernel\Validators\FakeValidators\ValidatorHeaders
   */
  protected ValidatorHeaders $instance;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set test environment.
    \Drupal::state()->set('is_a_test_environment', TRUE);

    // Install module configuration.
    $this->installConfig(['trpcultivate_phenotypes']);

    // Create a fake plugin instance for testing.
    $configuration = [];
    $validator_id = 'validator_requiring_headers';
    $plugin_definition = [
      'id' => $validator_id,
      'validator_name' => 'Validator Using Headers Trait',
      'input_types' => ['header-row', 'data-row'],
    ];

    $instance = new ValidatorHeaders(
      $configuration,
      $validator_id,
      $plugin_definition
    );

    $this->assertIsObject(
      $instance,
      "Unable to create $validator_id validator instance to test the Header Metadata trait."
    );

    $this->instance = $instance;
  }

  /**
   * Data Provider: provides scenarios with headers rows.
   *
   * @return array
   *   Each scenario/element is an array with the following values.
   *   - A string, human-readable short description of the test scenario.
   *   - An array, the headers array input.
   *   - An array, the types array input to the getHeaders() getter method.
   *   - Boolean value, TRUE or FALSE to indicate if the scenario is expecting
   *     an exception thrown by the setter method.
   *   - An array of expected exception messages by setter and getter, keyed by:
   *     - 'setter': exception message thrown by setHeaders().
   *     - 'getter-all': exception message thrown by getHeaders().
   *     - 'getter-required': exception message thrown by getRequiredHeaders().
   *     - 'getter-optional': exception message thrown by getOptionalHeaders().
   *   - An array of expected headers array generated by getters, keyed by:
   *     - 'all': headers array returned by the getHeaders() (all headers).
   *     - 'required': headers array returned by the getRequiredHeaders().
   *     - 'optional: headers array returned by the getOptionalHeaders().
   */
  public function provideHeadersForHeadersSetter() {
    return [
      // #0: Test the headers input array is an empty array value.
      [
        'headers array is empty',
        [],
        ['required', 'optional'],
        TRUE,
        [
          'setter' => 'The Headers Trait requires an array of headers and must not be empty.',
          'getter-all' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
          'getter-required' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
          'getter-optional' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
        ],
        [
          'all' => FALSE,
          'required' => FALSE,
          'optional' => FALSE,
        ],
      ],

      // #1: In headers input array, an element is missing the name key.
      [
        'missing name key',
        [
          [
            'not-name' => 'Header',
            'type' => 'required',
          ],
        ],
        ['required', 'optional'],
        TRUE,
        [
          'setter' => 'Headers Trait requires the header key: name when defining headers.',
          'getter-all' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
          'getter-required' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
          'getter-optional' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
        ],
        [
          'all' => FALSE,
          'required' => FALSE,
          'optional' => FALSE,
        ],
      ],

      // #2: In the headers input array, an element is missing the type key.
      [
        'missing type key',
        [
          [
            'name' => 'Header',
            'not-type' => 'required',
          ],
        ],
        ['required', 'optional'],
        TRUE,
        [
          'setter' => 'Headers Trait requires the header key: type when defining headers.',
          'getter-all' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
          'getter-required' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
          'getter-optional' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
        ],
        [
          'all' => FALSE,
          'required' => FALSE,
          'optional' => FALSE,
        ],
      ],

      // #3. In the headers input array, an element has an empty name key.
      [
        'empty name value',
        [
          [
            'name' => '',
            'type' => 'required',
          ],
        ],
        ['required', 'optional'],
        TRUE,
        [
          'setter' => 'Headers Trait requires the header key: name to be have a value.',
          'getter-all' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
          'getter-required' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
          'getter-optional' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
        ],
        [
          'all' => FALSE,
          'required' => FALSE,
          'optional' => FALSE,
        ],
      ],

      // #4. In the headers input array, an element has an empty type key.
      [
        'empty type value',
        [
          [
            'name' => 'Header',
            'type' => '',
          ],
        ],
        ['required', 'optional'],
        TRUE,
        [
          'setter' => 'Headers Trait requires the header key: type to be have a value.',
          'getter-all' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
          'getter-required' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
          'getter-optional' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
        ],
        [
          'all' => FALSE,
          'required' => FALSE,
          'optional' => FALSE,
        ],
      ],

      // #5: In the headers input array, an element has an invalid type key.
      [
        'type is invalid',
        [
          [
            'name' => 'Header',
            'type' => 'spurious type',
          ],
        ],
        ['required', 'optional'],
        TRUE,
        [
          'setter' => 'Headers Trait requires the header key: type value to be one of',
          'getter-all' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
          'getter-required' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
          'getter-optional' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
        ],
        [
          'all' => FALSE,
          'required' => FALSE,
          'optional' => FALSE,
        ],
      ],

      // #6: All the headers in the headers input array are of type required.
      [
        'all types required',
        [
          [
            'name' => 'Header 1',
            'type' => 'required',
          ],
          [
            'name' => 'Header 2',
            'type' => 'required',
          ],
        ],
        ['required', 'optional'],
        FALSE,
        [
          'setter' => '',
          'getter-all' => '',
          'getter-required' => '',
          'getter-optional' => '',
        ],
        [
          'all' => [
            0 => 'Header 1',
            1 => 'Header 2',
          ],
          'required' => [
            0 => 'Header 1',
            1 => 'Header 2',
          ],
          'optional' => [],
        ],
      ],

      // #7: All headers in the headers input array are of type optional.
      [
        'all types optional',
        [
          [
            'name' => 'Header 1',
            'type' => 'optional',
          ],
          [
            'name' => 'Header 2',
            'type' => 'optional',
          ],
        ],
        ['required', 'optional'],
        FALSE,
        [
          'setter' => '',
          'getter-all' => '',
          'getter-required' => '',
          'getter-optional' => '',
        ],
        [
          'all' => [
            0 => 'Header 1',
            1 => 'Header 2',
          ],
          'required' => [],
          'optional' => [
            0 => 'Header 1',
            1 => 'Header 2',
          ],
        ],
      ],

      // #8: Input headers are of mixed header types (optional and required).
      [
        'mix types',
        [
          [
            'name' => 'Header 1',
            'type' => 'required',
          ],
          [
            'name' => 'Header 2',
            'type' => 'required',
          ],
          [
            'name' => 'Header 3',
            'type' => 'optional',
          ],
          [
            'name' => 'Header 4',
            'type' => 'optional',
          ],
          [
            'name' => 'Header 5',
            'type' => 'required',
          ],
        ],
        ['required', 'optional'],
        FALSE,
        [
          'setter' => '',
          'getter-all' => '',
          'getter-required' => '',
          'getter-optional' => '',
        ],
        [
          'all' => [
            0 => 'Header 1',
            1 => 'Header 2',
            4 => 'Header 5',
            2 => 'Header 3',
            3 => 'Header 4',
          ],
          'required' => [
            0 => 'Header 1',
            1 => 'Header 2',
            4 => 'Header 5',
          ],
          'optional' => [
            2 => 'Header 3',
            3 => 'Header 4',
          ],
        ],
      ],

      // #9: Test getter method with invalid header type.
      [
        'invalid header types',
        [
          [
            'name' => 'Header 1',
            'type' => 'required',
          ],
          [
            'name' => 'Header 2',
            'type' => 'optional',
          ],
        ],
        // Invalid types.
        ['not my type', 'required', 'rare type'],
        FALSE,
        [
          'setter' => '',
          'getter-all' => 'Cannot retrieve invalid header types: not my type, rare type',
          'getter-required' => '',
          'getter-optional' => '',
        ],
        [
          'all' => FALSE,
          'required' => [
            0 => 'Header 1',
          ],
          'optional' => [
            1 => 'Header 2',
          ],
        ],
      ],
    ];
  }

  /**
   * Test getters when attempting to get headers BEFORE a call to setHeaders().
   */
  public function testGettersWithUnsetHeaders() {

    foreach (['required', 'optional'] as $type) {
      $getter = 'get' . ucfirst($type) . 'Headers';

      try {
        $this->instance->$getter();
      }
      catch (\Exception $e) {
        $exception_caught = TRUE;
        $exception_message = $e->getMessage();
      }

      $this->assertTrue($exception_caught, 'Header type ' . $type . ' getter method should throw an exception for unset header.');
      $this->assertStringContainsString(
        'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
        $exception_message,
        'Expected exception message does not match the message when trying to get headers of type ' . $type . ' on unset headers.'
      );
    }

    // Getter for ALL headers.
    try {
      // No specific type, the getter will get default types.
      $this->instance->getHeaders();
    }
    catch (\Exception $e) {
      $exception_caught = TRUE;
      $exception_message = $e->getMessage();
    }

    $this->assertTrue($exception_caught, 'Header getter method should throw an exception for unset header.');
    $this->assertStringContainsString(
      'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
      $exception_message,
      'Expected exception message does not match the message when trying to get headers.'
    );
  }

  /**
   * Test setter and getters.
   *
   * @param string $scenario
   *   Human-readable text description of the test scenario.
   * @param array $headers_input
   *   Headers array input value.
   * @param array $types_input
   *   Header types array input value.
   * @param bool $has_exception
   *   Indicates if the scenario will throw an exception (TRUE) or not (FALSE).
   * @param array $exception_message
   *   An array of exception messages that setter and getters will throw.
   * @param array $expected
   *   An array of headers array each getter will produce.
   *
   * @dataProvider provideHeadersForHeadersSetter
   */
  public function testHeaderSetterAndGetters($scenario, $headers_input, $types_input, $has_exception, $exception_message, $expected) {

    // Test setter method.
    $exception_caught = FALSE;
    $exception_get_message = '';

    try {
      $this->instance->setHeaders($headers_input);
    }
    catch (\Exception $e) {
      $exception_caught = TRUE;
      $exception_get_message = $e->getMessage();
    }

    $this->assertEquals($exception_caught, $has_exception, 'Exception was expected by setter method for scenario:' . $scenario);
    $this->assertStringContainsString(
      $exception_message['setter'],
      $exception_get_message,
      'The expected exception message thrown by the setter method does not match message thrown for test scenario: ' . $scenario
    );

    // Test required and optional getter methods.
    foreach (['required', 'optional'] as $type) {
      $exception_get_message = '';
      $headers = FALSE;

      $getter = 'get' . ucfirst($type) . 'Headers';

      try {
        $headers = $this->instance->$getter();
      }
      catch (\Exception $e) {
        $exception_get_message = $e->getMessage();
      }

      $this->assertStringContainsString(
        $exception_message['getter-' . $type],
        $exception_get_message,
        'The expected exception message thrown by the getter for ' . $type . ' headers does not match message thrown for test scenario: ' . $scenario
      );

      // Check that it returned the correct headers array.
      $this->assertEquals($headers, $expected[$type], 'Header returned does not match the expected headers for scenario:' . $scenario);
    }

    // Test header getter method (get all headers).
    $exception_get_message = '';
    $headers = FALSE;

    try {
      $headers = $this->instance->getHeaders($types_input);
    }
    catch (\Exception $e) {
      $exception_get_message = $e->getMessage();
    }

    $this->assertStringContainsString(
      $exception_message['getter-all'],
      $exception_get_message,
      'The expected exception message thrown by the headers getter does not match message thrown for test scenario: ' . $scenario
    );

    // Check that it returned the correct headers array.
    $this->assertEquals($headers, $expected['all'], 'Header returned does not match the expected headers for scenario: ' . $scenario);
  }

}
