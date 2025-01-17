<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Kernel\Validators;

use Drupal\Tests\tripal_chado\Kernel\ChadoTestKernelBase;
use Drupal\Tests\trpcultivate_phenotypes\Traits\PhenotypeImporterTestTrait;

/**
 * Tests Tripal Cultivate Phenotypes Data File Delimited Validator Plugins.
 *
 * @group trpcultivate_phenotypes
 * @group validators
 */
class ValidatorValidDelimitedFileTest extends ChadoTestKernelBase {

  use PhenotypeImporterTestTrait;

  /**
   * An instance of the data file delimiter validator.
   *
   * @var object
   */
  protected $validator_instance;

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set test environment.
    \Drupal::state()->set('is_a_test_environment', TRUE);

    // Create a plugin instance for this validator.
    $validator_id = 'valid_delimited_file';
    $this->validator_instance = \Drupal::service('plugin.manager.trpcultivate_validator')
      ->createInstance($validator_id);

    // Set the supported mime types for this test.
    $this->validator_instance->setSupportedMimeTypes([
      'tsv',
      'txt',
    ]);

    // Set the input file MIME type.
    $this->validator_instance->setFileMimeType('text/tab-separated-values');
  }

  /**
   * Data provider: provides test data file raw row.
   *
   * @return array
   *   Each scenario/element is an array with the following values:
   *   - A human-readable short description of the test scenario.
   *   - A single line (string) representing a single row in an input file.
   *   - Configuration values given to setExpectedColumns():
   *     - 'number_of_columns': number of column headers to expect after
   *       splitting the line.
   *     - 'strict': indicates if number of columns must be exact.
   *   - An array of expected validation results.
   *     - 'case': validation test case message.
   *     - 'valid': true if validation passed, false if failed.
   */
  public function provideRawRowToDelimitedFileValidator() {
    return [

      // # 0: Raw row line is an empty string.
      [
        'empty raw row',
        '',
        [
          'number_of_columns' => 1,
          'strict' => FALSE,
        ],
        [
          'case' => 'Raw row is empty',
          'valid' => FALSE,
          'failedItems' => ['raw_row' => 'is an empty string value'],

        ],
      ],

      // #1: None of the supported delimiters for the file type was used.
      [
        'no delimiter',
        'Data Value One - Data Value 2 - Data Value 3',
        [
          'number_of_columns' => 2,
          'strict' => FALSE,
        ],
        [
          'case' => 'None of the delimiters supported by the file type was used',
          'valid' => FALSE,
          'failedItems' => ['raw_row' => 'Data Value One - Data Value 2 - Data Value 3'],
        ],
      ],

      // #2: Not the expected number of columns (more and strict comparison).
      [
        'column number mismatch - more',
        "Data Value One\tData Value Two\tData Value Three\tData Value Four\tData Value Five",
        [
          'number_of_columns' => 4,
          'strict' => TRUE,
        ],
        [
          'case' => 'Raw row exceeds number of strict columns',
          'valid' => FALSE,
          'failedItems' => [
            'raw_row' => "Data Value One\tData Value Two\tData Value Three\tData Value Four\tData Value Five",
            'expected_columns' => 4,
            'strict' => TRUE,
          ],
        ],
      ],

      // #3: Not the expected number of columns (less and strict comparison).
      [
        'column number mismatch - less',
        "Data Value One\tData Value Two\tData Value Three",
        [
          'number_of_columns' => 4,
          'strict' => TRUE,
        ],
        [
          'case' => 'Raw row has insufficient number of columns',
          'valid' => FALSE,
          'failedItems' => [
            'raw_row' => "Data Value One\tData Value Two\tData Value Three",
            'expected_columns' => 4,
            'strict' => TRUE,
          ],
        ],
      ],

      // #4: Not the expected number of columns (not strict comparison).
      [
        'column number failed minimum',
        "Data Value One\tData Value Two\tData Value Three",
        [
          'number_of_columns' => 4,
          'strict' => FALSE,
        ],
        [
          'case' => 'Raw row has insufficient number of columns',
          'valid' => FALSE,
          'failedItems' => [
            'raw_row' => "Data Value One\tData Value Two\tData Value Three",
            'expected_columns' => 4,
            'strict' => FALSE,
          ],
        ],
      ],

      // #5: Line has 2 different delimiters (tab + comma) where one is used to
      // delimit values and the other exists within the values.
      [
        'two delimiters used',
        "Data Value One\tData Value Two\tData Value Three\t\"Data\tValue, Four\"",
        [
          'number_of_columns' => 4,
          'strict' => FALSE,
        ],
        [
          'case' => 'Raw row is delimited',
          'valid' => TRUE,
          'failedItems' => [],
        ],
      ],

      // #6: Valid raw row and expecting exactly 4 columns.
      [
        'valid raw row with exact columns',
        "Data Value One\tData Value Two\tData Value Three\tData Value Four",
        [
          'number_of_columns' => 4,
          'strict' => TRUE,
        ],
        [
          'case' => 'Raw row is delimited',
          'valid' => TRUE,
          'failedItems' => [],
        ],
      ],

      // #7: Valid raw row and expecting at least 3 columns.
      [
        'valid raw row with minimum columns',
        "Data Value One\tData Value Two\tData Value Three\tData Value Four",
        [
          'number_of_columns' => 3,
          'strict' => FALSE,
        ],
        [
          'case' => 'Raw row is delimited',
          'valid' => TRUE,
          'failedItems' => [],
        ],
      ],

      // #8: Raw row has one column with strict flag set to FALSE (mininum).
      [
        'one column with strict set to false',
        "Data Value One",
        [
          'number_of_columns' => 1,
          'strict' => FALSE,
        ],
        [
          'case' => 'Raw row has expected number of columns',
          'valid' => TRUE,
          'failedItems' => [],
        ],
      ],

      // #9: Raw row has one column with strict flag set to TRUE (exact match).
      [
        'one column with strict flag set to true',
        "Data Value One",
        [
          'number_of_columns' => 1,
          'strict' => TRUE,
        ],
        [
          'case' => 'Raw row has expected number of columns',
          'valid' => TRUE,
          'failedItems' => [],
        ],
      ],
    ];
  }

  /**
   * Test data file row is properly delimited.
   *
   * @param string $scenario
   *   A human-readable short description of the test scenario.
   * @param string $raw_row_input
   *   A string representing a single row in an input file.
   * @param array $validator_config
   *   Configuration values given to setExpectedColumns():
   *   - 'number_of_columns': number of column headers to expect after
   *     splitting the line.
   *   - 'strict': indicates if number of columns must be exact.
   * @param array $expected
   *   An array of expected validation results:
   *   - 'case': validation test case message.
   *   - 'valid': true if validation passed, false if failed.
   *
   * @dataProvider provideRawRowToDelimitedFileValidator
   */
  public function testDataFileRowIsDelimited(string $scenario, string $raw_row_input, $validator_config, $expected) {
    // Set validator configuration.
    $this->validator_instance->setExpectedColumns($validator_config['number_of_columns'], $validator_config['strict']);

    $validation_status = $this->validator_instance->validateRawRow($raw_row_input);
    foreach ($validation_status as $key => $value) {
      $this->assertEquals($value, $expected[$key],
        'The validation status key ' . $key . ' does not match the same status key in scenario: ' . $scenario);
    }
  }

}
