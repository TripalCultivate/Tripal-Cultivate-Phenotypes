<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Kernel\Validators;

use Drupal\Tests\tripal_chado\Kernel\ChadoTestKernelBase;
use Drupal\Tests\trpcultivate_phenotypes\Traits\PhenotypeImporterTestTrait;
use Drupal\Tests\trpcultivate_phenotypes\Kernel\Validators\FakeValidators\ValidatorHeaders;

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
   */
  protected static $modules = [
    'user',
    'tripal',
    'tripal_chado',
    'trpcultivate_phenotypes'
  ];

  /**
   * The validator instance to use for testing.
   *
   * @var ValidatorHeaders
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
   * Data Provider: provides test headers.
   * 
   * @return array
   *   Each scenario/element is an array with the following values.
   *
   *   - A string, human-readable short description of the test scenario.
   *   - An array, the headers array input.
   *   - An array, the types array input to the getHeaders() getter method.
   *   - Boolean value, TRUE or FALSE to indicate if the scenario is expecting an exception thrown by the setter method.
   *   - An array of exception messages thrown by setter method and getters method, keyed by:
   *     - setter: exception message thrown by the setter method.
   *     - getter-all: exception message thrown by the getHeaders() getter method.
   *     - getter-required: exception message thrown by getRequiredHeaders() getter method.
   *     - getter-optional: exception message thrown by getOptionalHeaders() getter method.
   *   - An array of expected headers array result generated by getters method, keyed by:
   *     - all: headers array returned by the getHeaders() getter method (all headers).
   *     - required: headers array returned by the getRequiredHeaders() getter method (required type headers).
   *     - optional: headers array returned by the getOptionalHeaders() getter method (optional type headers).
   */
  public function provideHeadersForHeadersSetter() {
    return [
      [
        'headers array is empty',
        [],
        ['required', 'optional'], // Default to fetch all types.
        TRUE,
        [
          'setter' => 'The Headers Trait requires an array of headers and must not be empty.',
          'getter-all' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
          'getter-required' => 'Cannot retrieve required headers from the context array as one has not been set by setHeaders() method.',
          'getter-optional' => 'Cannot retrieve optional headers from the context array as one has not been set by setHeaders() method.'
        ],
        [
          'all' => FALSE,
          'required' => FALSE,
          'optional' => FALSE
        ]
      ],

      //
      [
        'missing name key',
        [
          [
            'not-name' => 'Header',
            'type' => 'required'
          ]
        ],
        ['required', 'optional'], // Default to fetch all types.
        TRUE,
        [
          'setter' => 'Headers Trait requires the header key: name when defining headers.',
          'getter-all' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
          'getter-required' => 'Cannot retrieve required headers from the context array as one has not been set by setHeaders() method.',
          'getter-optional' => 'Cannot retrieve optional headers from the context array as one has not been set by setHeaders() method.'
        ],
        [
          'all' => FALSE,
          'required' => FALSE,
          'optional' => FALSE
        ]
      ],

      //
      [
        'missing type key',
        [
          [
            'name' => 'Header',
            'not-type' => 'required'
          ]
        ],
        ['required', 'optional'], // Default to fetch all types.
        TRUE,
        [
          'setter' => 'Headers Trait requires the header key: type when defining headers.',
          'getter-all' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
          'getter-required' => 'Cannot retrieve required headers from the context array as one has not been set by setHeaders() method.',
          'getter-optional' => 'Cannot retrieve optional headers from the context array as one has not been set by setHeaders() method.'
        ],
        [
          'all' => FALSE,
          'required' => FALSE,
          'optional' => FALSE
        ]
      ],

      //
      [
        'empty name value',
        [
          [
            'name' => '',
            'type' => 'required'
          ]
        ],
        ['required', 'optional'], // Default to fetch all types.
        TRUE,
        [
          'setter' => 'Headers Trait requires the header key: name to be have a value.',
          'getter-all' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
          'getter-required' => 'Cannot retrieve required headers from the context array as one has not been set by setHeaders() method.',
          'getter-optional' => 'Cannot retrieve optional headers from the context array as one has not been set by setHeaders() method.'
        ],
        [
          'all' => FALSE,
          'required' => FALSE,
          'optional' => FALSE
        ]
      ],

      //
      [
        'empty type value',
        [
          [
            'name' => 'Header',
            'type' => ''
          ]
        ],
        ['required', 'optional'], // Default to fetch all types.
        TRUE,
        [
          'setter' => 'Headers Trait requires the header key: type to be have a value.',
          'getter-all' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
          'getter-required' => 'Cannot retrieve required headers from the context array as one has not been set by setHeaders() method.',
          'getter-optional' => 'Cannot retrieve optional headers from the context array as one has not been set by setHeaders() method.'
        ],
        [
          'all' => FALSE,
          'required' => FALSE,
          'optional' => FALSE
        ]
      ],

      //
      [
        'type is invalid',
        [
          [
            'name' => 'Header',
            'type' => 'spurious type'
          ]
        ],
        ['required', 'optional'], // Default to fetch all types.
        TRUE,
        [
          'setter' => 'Headers Trait requires the header key: type value to be one of',
          'getter-all' => 'Cannot retrieve headers from the context array as one has not been set by setHeaders() method.',
          'getter-required' => 'Cannot retrieve required headers from the context array as one has not been set by setHeaders() method.',
          'getter-optional' => 'Cannot retrieve optional headers from the context array as one has not been set by setHeaders() method.'
        ],
        [
          'all' => FALSE,
          'required' => FALSE,
          'optional' => FALSE
        ]
      ],
      
      //
      [
        'all types required',
        [
          [
            'name' => 'Header 1',
            'type' => 'required'
          ],
          [
            'name' => 'Header 2',
            'type' => 'required'
          ]
        ],
        ['required', 'optional'], // Default to fetch all types.
        FALSE,
        [
          'setter' => '',
          'getter-all' => '',
          'getter-required' => '',
          'getter-optional' => ''
        ],
        [
          'all' => [
            0 => 'Header 1',
            1 => 'Header 2',
          ],
          'required' => [
            0 => 'Header 1',
            1 => 'Header 2'
          ],
          'optional' => []
        ]
      ],

      //
      [
        'all types optional',
        [
          [
            'name' => 'Header 1',
            'type' => 'optional'
          ],
          [
            'name' => 'Header 2',
            'type' => 'optional'
          ]
        ],
        ['required', 'optional'], // Default to fetch all types.
        FALSE,
        [
          'setter' => '',
          'getter-all' => '',
          'getter-required' => '',
          'getter-optional' => ''
        ],
        [
          'all' => [
            0 => 'Header 1',
            1 => 'Header 2',
          ],
          'required' => [],
          'optional' => [
            0 => 'Header 1',
            1 => 'Header 2'
          ]
        ]
      ],

      //
      [
        'mix types',
        [
          [
            'name' => 'Header 1',
            'type' => 'required'
          ],
          [
            'name' => 'Header 2',
            'type' => 'required'
          ],
          [
            'name' => 'Header 3',
            'type' => 'optional'
          ],
          [
            'name' => 'Header 4',
            'type' => 'optional'
          ],
          [
            'name' => 'Header 5',
            'type' => 'required'
          ]
        ],
        ['required', 'optional'], // Default to fetch all types.
        FALSE,
        [
          'setter' => '',
          'getter-all' => '',
          'getter-required' => '',
          'getter-optional' => ''
        ],
        [
          'all' => [
            0 => 'Header 1',
            1 => 'Header 2',
            2 => 'Header 3',
            3 => 'Header 4',
            4 => 'header 5'
          ],
          'required' => [
            0 => 'Header 1',
            1 => 'Header 2',
            4 => 'Header 5'
          ],
          'optional' => [
            2 => 'Header 3',
            3 => 'Header 4'
          ]
        ]
      ],

      //
      [
        'invalid header types',
        [
          [
            'name' => 'Header 1',
            'type' => 'required'
          ],
          [
            'name' => 'Header 2',
            'type' => 'optional'
          ]
        ],
        ['not my type', 'required', 'rare type'], // Invalid types.
        FALSE,
        [
          'setter' => '',
          'getter-all' => 'Cannot retrieve invalid header types: not my type, rare type',
          'getter-required' => '',
          'getter-optional' => ''
        ],
        [
          'all' => [
            0 => 'Header 1',
            1 => 'Header 2'
          ],
          'required' => [
            0 => 'Header 1'
          ],
          'optional' => [
            1 => 'Header 2'
          ]
        ]
      ]
    ];
  }

  /**
   * Test getter will trigger an error when attempting to get a type(s) of headers
   * prior to a call to headers setter method.
   */
  public function testGettersWithUnsetHeaders() {

    // Exception message when calling a getter prior to call setter method.
    $expected_message = 'Cannot retrieve%s headers from the context array as one has not been set by setHeaders() method.';

    foreach(['required', 'optional'] as $type) {
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
        sprintf($expected_message, ' ' . $type),
        $exception_message,
        'Expected exception message does not match the message when trying to get headers of type ' . $type . ' on unset headers.'
      );
    }

    // Header getter.
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
      sprintf($expected_message, ''),
      $exception_message,
      'Expected exception message does not match the message when trying to get headers.'
    );
  }

  /**
   * Test setter and getter.
   * 
   * @param string $scenario
   *   Human-readable text description of the test scenario.
   * @param array $headers_input
   *   Headers array input value.
   * @param array $types_input
   *   Header types array input value.
   * @param boolean $has_exception
   *   Indicates if the test scenario will thrown an exception (TRUE) or not (FALSE).
   * @param array $exception_message
   *   An array of exception messages that setter and getters will throw.
   * @param array $expected
   *   An array of headers array each getter will produce. 
   * 
   * @return void
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


    // Test required and optional getter methods (get required and optional type headers).
    foreach(['required', 'optional'] as $type) {
      $exception_get_message = '';

      $getter = 'get' . ucfirst($type) . 'Headers';
      
      try {
        $this->instance->$getter();
      }
      catch (\Exception $e) {
        $exception_get_message = $e->getMessage();
      }

      $this->assertStringContainsString(
        $exception_message[ 'getter-' . $type ],
        $exception_get_message,
        'The expected exception message thrown by the getter for ' . $type . ' headers does not match message thrown for test scenario: ' . $scenario
      );      
    }
    

    // Test header getter method (get all headers).
    $exception_get_message = '';

    try {
      $this->instance->getHeaders($types_input);
    }
    catch (\Exception $e) {
      $exception_get_message = $e->getMessage();
    }

    $this->assertStringContainsString(
      $exception_message['getter-all'],
      $exception_get_message,
      'The expected exception message thrown by the headers getter does not match message thrown for test scenario: ' . $scenario
    );      
  }
}
