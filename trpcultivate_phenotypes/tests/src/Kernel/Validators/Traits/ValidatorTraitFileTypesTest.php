<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Kernel\Validators;

use Drupal\Tests\tripal_chado\Kernel\ChadoTestKernelBase;
use Drupal\Tests\trpcultivate_phenotypes\Kernel\Validators\FakeValidators\ValidatorFileTypes;
use Drupal\Tests\trpcultivate_phenotypes\Traits\PhenotypeImporterTestTrait;
use Drupal\tripal\Services\TripalLogger;

/**
 * Tests the FileTypes validator trait.
 *
 * @group trpcultivate_phenotypes
 * @group validator_traits
 */
class ValidatorTraitFileTypesTest extends ChadoTestKernelBase {

  use PhenotypeImporterTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'tripal',
    'tripal_chado',
    'trpcultivate_phenotypes',
  ];

  /**
   * The validator instance to use for testing.
   *
   * @var \Drupal\Tests\trpcultivate_phenotypes\Kernel\Validators\FakeValidators\ValidatorFileTypes
   */
  protected ValidatorFileTypes $instance;

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
    $validator_id = 'validator_requiring_filetypes';
    $plugin_definition = [
      'id' => $validator_id,
      'validator_name' => 'Validator Using File Types Trait',
      'input_types' => ['file'],
    ];

    $instance = new ValidatorFileTypes(
      $configuration,
      $validator_id,
      $plugin_definition
    );

    // We need to mock the logger to test the progress reporting.
    $mock_logger = $this->getMockBuilder(TripalLogger::class)
      ->onlyMethods(['notice', 'error'])
      ->getMock();
    $mock_logger->method('notice')
      ->willReturnCallback(function ($message, $context, $options) {
        print str_replace(array_keys($context), $context, $message);
        return NULL;
      });
    $mock_logger->method('error')
      ->willReturnCallback(function ($message, $context, $options) {
        print str_replace(array_keys($context), $context, $message);
        return NULL;
      });
    // Finally, use setLogger() for this validator instance.
    $instance->setLogger($mock_logger);

    $this->assertIsObject(
      $instance,
      "Unable to create $validator_id validator instance to test the File Types trait."
    );

    $this->instance = $instance;
  }

  /**
   * Data Provider: provides various scenarios of file extensions.
   */
  public function provideExtensionsForSetter() {

    $scenarios = [];

    // For each senario we expect the following:
    // - scenario label to provide helpful feedback if a test fails.
    // - an array of arrays pertaining to file extensions:
    //   - an array of file extensions to pass to setSupportedMimeTypes().
    //   - an array of file extensions we expect to have returned by
    //     getSupportedFileExtensions().
    // - an array of the expected mime types returned by getSupportedMimeTypes()
    // - an array indicating whether to expect an exception with the keys
    //   being the method and the value being TRUE if we expect an exception
    //   when calling it for this senario.
    // - an array of expected exception messages with the key being the method
    //   and value being the message we expect (NULL if no exception expected)
    // NOTE: getters have only one exception message and they are different
    // depending on the getter.
    $get_types_exception_message = 'Cannot retrieve supported file mime-types as they have not been set by setSupportedMimeTypes() method.';
    $get_ext_exception_message = 'Cannot retrieve supported file extensions as they have not been set by setSupportedMimeTypes() method.';

    // #0: Test with an empty extensions array
    $scenarios[] = [
    // Scenario label.
      'empty string',
      [
        'input_file_extensions' => [],
        'expected_file_extensions' => [],
      ],
      // Expected mime-types.
      [],
      // Expected exception thrown.
      [
        'setSupportedMimeTypes' => TRUE,
        'getSupportedMimeTypes' => TRUE,
        'getSupportedFileExtensions' => TRUE,
      ],
      // Expected exception message.
      [
        'setSupportedMimeTypes' => 'The setSupportedMimeTypes() setter requires an array of file extensions that are supported by the importer and must not be empty.',
        'getSupportedMimeTypes' => $get_types_exception_message,
        'getSupportedFileExtensions' => $get_ext_exception_message,
      ],
    ];

    // #1: Just tsv
    $scenarios[] = [
    // Scenario label.
      'tsv',
      [
        'input_file_extensions' => ['tsv'],
        'expected_file_extensions' => ['tsv'],
      ],
      // Expected mime-types.
      ['text/tab-separated-values'],
      [
        'setSupportedMimeTypes' => FALSE,
        'getSupportedMimeTypes' => FALSE,
        'getSupportedFileExtensions' => FALSE,
      ],
      [
        'setSupportedMimeTypes' => '',
        'getSupportedMimeTypes' => '',
        'getSupportedFileExtensions' => '',
      ],
    ];

    // #2: Just csv
    $scenarios[] = [
    // Scenario label.
      'csv',
      [
        'input_file_extensions' => ['csv'],
        'expected_file_extensions' => ['csv'],
      ],
      // Expected mime-types.
      ['text/csv'],
      [
        'setSupportedMimeTypes' => FALSE,
        'getSupportedMimeTypes' => FALSE,
        'getSupportedFileExtensions' => FALSE,
      ],
      [
        'setSupportedMimeTypes' => '',
        'getSupportedMimeTypes' => '',
        'getSupportedFileExtensions' => '',
      ],
    ];

    // #3: Just txt
    $scenarios[] = [
    // Scenario label.
      'txt',
      [
        'input_file_extensions' => ['txt'],
        'expected_file_extensions' => ['txt'],
      ],
      // Expected mime-types.
      ['text/plain'],
      [
        'setSupportedMimeTypes' => FALSE,
        'getSupportedMimeTypes' => FALSE,
        'getSupportedFileExtensions' => FALSE,
      ],
      [
        'setSupportedMimeTypes' => '',
        'getSupportedMimeTypes' => '',
        'getSupportedFileExtensions' => '',
      ],
    ];

    // #4: tsv, txt
    $scenarios[] = [
    // Scenario label.
      'tsv, txt',
      [
        'input_file_extensions' => ['tsv', 'txt'],
        'expected_file_extensions' => ['tsv', 'txt'],
      ],
      // Expected mime-types.
      ['text/tab-separated-values', 'text/plain'],
      [
        'setSupportedMimeTypes' => FALSE,
        'getSupportedMimeTypes' => FALSE,
        'getSupportedFileExtensions' => FALSE,
      ],
      [
        'setSupportedMimeTypes' => '',
        'getSupportedMimeTypes' => '',
        'getSupportedFileExtensions' => '',
      ],
    ];

    // #5: csv, txt
    $scenarios[] = [
    // Scenario label.
      'csv, txt',
      [
        'input_file_extensions' => ['csv', 'txt'],
        'expected_file_extensions' => ['csv', 'txt'],
      ],
      // Expected mime-types.
      ['text/csv', 'text/plain'],
      [
        'setSupportedMimeTypes' => FALSE,
        'getSupportedMimeTypes' => FALSE,
        'getSupportedFileExtensions' => FALSE,
      ],
      [
        'setSupportedMimeTypes' => '',
        'getSupportedMimeTypes' => '',
        'getSupportedFileExtensions' => '',
      ],
    ];

    // Invalid types.
    // #6: jpg, gif, svg.
    $scenarios[] = [
    // Scenario label.
      'jpg, gif, svg',
      [
        'input_file_extensions' => ['jpg', 'gif', 'svg'],
        'expected_file_extensions' => [],
      ],
      // Expected mime-types.
      [],
      [
        'setSupportedMimeTypes' => TRUE,
        'getSupportedMimeTypes' => TRUE,
        'getSupportedFileExtensions' => TRUE,
      ],
      [
        'setSupportedMimeTypes' => 'The setSupportedMimeTypes() setter does not recognize the following extensions: jpg, gif, svg',
        'getSupportedMimeTypes' => $get_types_exception_message,
        'getSupportedFileExtensions' => $get_ext_exception_message,
      ],
    ];

    // #7: png, pdf
    $scenarios[] = [
    // Scenario label.
      'png, pdf',
      [
        'input_file_extensions' => ['png', 'pdf'],
        'expected_file_extensions' => [],
      ],
      // Expected mime-types.
      [],
      [
        'setSupportedMimeTypes' => TRUE,
        'getSupportedMimeTypes' => TRUE,
        'getSupportedFileExtensions' => TRUE,
      ],
      [
        'setSupportedMimeTypes' => 'The setSupportedMimeTypes() setter does not recognize the following extensions: png, pdf',
        'getSupportedMimeTypes' => $get_types_exception_message,
        'getSupportedFileExtensions' => $get_ext_exception_message,
      ],
    ];

    // #8: gzip
    $scenarios[] = [
    // Scenario label.
      'gzip',
      [
        'input_file_extensions' => ['gzip'],
        'expected_file_extensions' => [],
      ],
      // Expected mime-types.
      [],
      [
        'setSupportedMimeTypes' => TRUE,
        'getSupportedMimeTypes' => TRUE,
        'getSupportedFileExtensions' => TRUE,
      ],
      [
        'setSupportedMimeTypes' => 'The setSupportedMimeTypes() setter does not recognize the following extensions: gzip',
        'getSupportedMimeTypes' => $get_types_exception_message,
        'getSupportedFileExtensions' => $get_ext_exception_message,
      ],
    ];

    // Mixed types
    // #9: tsv, jpg.
    $scenarios[] = [
    // Scenario label.
      'tsv, jpg',
      [
        'input_file_extensions' => ['tsv', 'jpg'],
        'expected_file_extensions' => [],
      ],
      // Expected mime-types.
      [],
      [
        'setSupportedMimeTypes' => TRUE,
        'getSupportedMimeTypes' => TRUE,
        'getSupportedFileExtensions' => TRUE,
      ],
      [
        'setSupportedMimeTypes' => 'The setSupportedMimeTypes() setter does not recognize the following extensions: jpg',
        'getSupportedMimeTypes' => $get_types_exception_message,
        'getSupportedFileExtensions' => $get_ext_exception_message,
      ],
    ];

    return $scenarios;
  }

  /**
   * Data Provider: provides scenarios of mime-types from a single input file.
   */
  public function provideMimeTypeForSetter() {

    $scenarios = [];

    // For each senario we expect the following:
    // - scenario label to provide helpful feedback if a test fails.
    // - a string that is the mime-type to pass to setFileMimeType()
    // - an array indicating the expectations when testing this scenario,
    //   containing the following keys:
    //   - 'returned_values': the expected return value from getFileMimeType()
    //     for this scenario
    //   - 'exception_thrown': whether to expect an exception with the keys
    //     being the method and the value being TRUE if we expect an exception
    //     when calling it for this senario.
    //   - 'exception_message': an array of expected exception messages with
    //     the keys being the method and the value being the message we expect
    //     (empty string if no exception expected)
    //   - 'logged_message': an array of expected logged messages with
    //     the keys being the method and the value being the message we expect
    //     (empty string if no logged message expected)
    // NOTE: getters have only one exception message, so assign it to a variable
    // to avoid repetition.
    $get_type_exception_message = 'Cannot retrieve the input file mime-type as it has not been set by setFileMimeType() method.';

    // #0: Test with an empty mime-type
    $scenarios[] = [
    // Scenario label.
      'empty string',
    // mime-type.
      '',
      [
        'returned_values' => [
          'mime-type' => '',
        ],
        // Expected exception thrown.
        'exception_thrown' => [
          'setFileMimeType' => TRUE,
          'getFileMimeType' => TRUE,
        ],
        // Expected exception message.
        'exception_message' => [
          'setFileMimeType' => "The setFileMimeType() setter requires a string of the input file's mime-type and must not be empty.",
          'getFileMimeType' => $get_type_exception_message,
        ],
        'logged_message' => [
          'setFileMimeType' => '',
          'getFileMimeType' => '',
        ],
      ],
    ];

    // #1: Test with the mime-type for tsv files
    $scenarios[] = [
      'tsv mime-type',
      'text/tab-separated-values',
      [
        'returned_values' => [
          'mime-type' => 'text/tab-separated-values',
        ],
        'exception_thrown' => [
          'setFileMimeType' => FALSE,
          'getFileMimeType' => FALSE,
        ],
        'exception_message' => [
          'setFileMimeType' => '',
          'getFileMimeType' => '',
        ],
        'logged_message' => [
          'setFileMimeType' => '',
          'getFileMimeType' => '',
        ],
      ],
    ];

    // #2: Test with the mime-type for csv files
    $scenarios[] = [
      'csv mime-type',
      'text/csv',
      [
        'returned_values' => [
          'mime-type' => 'text/csv',
        ],
        'exception_thrown' => [
          'setFileMimeType' => FALSE,
          'getFileMimeType' => FALSE,
        ],
        'exception_message' => [
          'setFileMimeType' => '',
          'getFileMimeType' => '',
        ],
        'logged_message' => [
          'setFileMimeType' => '',
          'getFileMimeType' => '',
        ],
      ],
    ];

    // #3: Test with the mime-type for txt files
    $scenarios[] = [
      'txt mime-type',
      'text/plain',
      [
        'returned_values' => [
          'mime-type' => 'text/plain',
        ],
        'exception_thrown' => [
          'setFileMimeType' => FALSE,
          'getFileMimeType' => FALSE,
        ],
        'exception_message' => [
          'setFileMimeType' => '',
          'getFileMimeType' => '',
        ],
        'logged_message' => [
          'setFileMimeType' => '',
          'getFileMimeType' => '',
        ],
      ],
    ];

    // #4: Test with a random string
    $scenarios[] = [
      'random string',
      'hello world',
      [
        'returned_values' => [
          'mime-type' => '',
        ],
        'exception_thrown' => [
          'setFileMimeType' => FALSE,
          'getFileMimeType' => TRUE,
        ],
        'exception_message' => [
          'setFileMimeType' => '',
          'getFileMimeType' => 'Cannot retrieve the input file mime-type as it has not been set by setFileMimeType() method.',
        ],
        'logged_message' => [
          'setFileMimeType' => "The setFileMimeType() setter requires a supported mime-type but 'hello world' is unsupported.",
          'getFileMimeType' => '',
        ],
      ],
    ];

    return $scenarios;
  }

  /**
   * Tests setter/getters are focused on what the importer supports.
   *
   * Specifically,
   *  - FileTypes::setSupportedMimeTypes()
   *  - FileTypes::getSupportedMimeTypes()
   *  - FileTypes::getSupportedFileExtensions()
   *
   * @param string $scenario
   *   Human-readable text description to give feedback if a scenario fails.
   * @param array $file_extensions
   *   An array with 2 keys:
   *   - 'input_file_extensions' is a list of file extensions as the input for
   *     setSupportedMimeTypes()
   *   - 'expected_file_extensions' is the list of file extensions that is
   *     expected as the output from getSupportedFileExtensions()
   * @param array $expected_mime_types
   *   A list of the expected mime-types from getSupportedMimeTypes()
   * @param array $expected_exception_thrown
   *   An array of expected exception outcomes (TRUE if expected, FALSE if
   *   not expected) where the keys are the names of the methods.
   * @param array $expected_exception_message
   *   An array of expected exception messages with the key being the method
   *   name and value being the expected message (empty string if not expected).
   *
   * @dataProvider provideExtensionsForSetter
   */
  public function testSupportedMimeTypes($scenario, $file_extensions, $expected_mime_types, $expected_exception_thrown, $expected_exception_message) {

    // These exception messages are expected when we intially call the getter
    // methods for every scenario.
    $get_types_exception_message = 'Cannot retrieve supported file mime-types as they have not been set by setSupportedMimeTypes() method.';
    $get_ext_exception_message = 'Cannot retrieve supported file extensions as they have not been set by setSupportedMimeTypes() method.';

    // scenario: Check getSupportedMimeTypes() throws exception when not set.
    // --------------------------------------------------------------------------.
    $exception_caught = FALSE;
    $exception_message = '';
    try {
      $this->instance->getSupportedMimeTypes();
    }
    catch (\Exception $e) {
      $exception_caught = TRUE;
      $exception_message = $e->getMessage();
    }

    $this->assertTrue($exception_caught, 'FileTypes::getSupportedMimeTypes() method should throw an exception when trying to get supported mime types before setting them.');
    $this->assertEquals(
      $get_types_exception_message,
      $exception_message,
      'Exception message does not match the expected one when trying to get supported mime types before setting them.'
    );

    // scenario: Check that the getSupportedFileExtensions() throws exception
    // when not set.
    // --------------------------------------------------------------------------.
    $exception_caught = FALSE;
    $exception_message = '';
    try {
      $this->instance->getSupportedFileExtensions();
    }
    catch (\Exception $e) {
      $exception_caught = TRUE;
      $exception_message = $e->getMessage();
    }

    $this->assertTrue($exception_caught, 'FileTypes::getSupportedFileExtensions() method should throw an exception when trying to get supported file extensions before setting them.');
    $this->assertEquals(
      $get_ext_exception_message,
      $exception_message,
      'Exception message does not match the expected one when trying to get supported file extensions before setting them.'
    );

    // scenario: Test setSupportedMimeTypes() with current scenario.
    // Test various file extensions (see data provider) and check that their
    // expected supported mime types are returned by the getter method.
    // --------------------------------------------------------------------------.
    $exception_caught = FALSE;
    $exception_message = '';
    try {
      $this->instance->setSupportedMimeTypes($file_extensions['input_file_extensions']);
    }
    catch (\Exception $e) {
      $exception_caught = TRUE;
      $exception_message = $e->getMessage();
    }
    $this->assertEquals(
      $expected_exception_thrown['setSupportedMimeTypes'],
      $exception_caught,
      "Unexpected exception activity occured for scenario: '" . $scenario . "'");
    $this->assertEquals(
      $expected_exception_message['setSupportedMimeTypes'],
      $exception_message,
      "The expected and actual exception messages do not match when using FileTypes::setSupportedMimeTypes() for scenario: '" . $scenario . "'"
    );

    // scenario: Check getSupportedMimeTypes() returns expected mime types after
    // setting.
    // --------------------------------------------------------------------------.
    $actual_types = [];
    $exception_caught = FALSE;
    $exception_message = '';
    try {
      $actual_types = $this->instance->getSupportedMimeTypes();
    }
    catch (\Exception $e) {
      $exception_caught = TRUE;
      $exception_message = $e->getMessage();
    }
    $this->assertEquals(
      $expected_exception_thrown['getSupportedMimeTypes'],
      $exception_caught,
      "Unexpected exception activity occured when trying to get supported mime-types for scenario: '" . $scenario . "'"
    );
    $this->assertEquals(
      $expected_exception_message['getSupportedMimeTypes'],
      $exception_message,
      "The expected and actual exception messages do not match when calling FileTypes::getSupportedMimeTypes() for scenario: '" . $scenario . "'"
    );
    // Finally, check that our retrieved mime-types match our expected.
    $this->assertEquals(
      $expected_mime_types,
      $actual_types,
      "The expected mime-types using FileTypes::getSupportedMimeTypes() did not match the actual ones for scenario: '" . $scenario . "'"
    );

    // scenario: Check getSupportedFileExtensions() returns valid extensions
    // after setting.
    // --------------------------------------------------------------------------.
    $actual_extensions = [];
    $exception_caught = FALSE;
    $exception_message = '';
    try {
      $actual_extensions = $this->instance->getSupportedFileExtensions();
    }
    catch (\Exception $e) {
      $exception_caught = TRUE;
      $exception_message = $e->getMessage();
    }
    $this->assertEquals(
      $expected_exception_thrown['getSupportedFileExtensions'],
      $exception_caught,
      "Unexpected exception activity occured when trying to get file extensions for scenario: '" . $scenario . "'"
    );
    $this->assertEquals(
      $expected_exception_message['getSupportedFileExtensions'],
      $exception_message,
      "The expected and actual exception messages do not match when calling FileTypes::getSupportedFileExtensions() for scenario: '" . $scenario . "'"
    );
    // Finally, check that our retrieved file extensions match our expected.
    $this->assertEquals(
      $file_extensions['expected_file_extensions'],
      $actual_extensions,
      "The expected file extensions using FileTypes::getSupportedFileExtensions() did not match the actual ones for scenario: '" . $scenario . "'"
    );
  }

  /**
   * Tests setter/getters focused on the file in the current run.
   *
   * Specifically,
   *  - FileTypes::setFileMimeType()
   *  - FileTypes::getFileMimeType()
   *
   * @param string $scenario
   *   Human-readable text description to give feedback if a scenario fails.
   * @param string $mime_type
   *   A string that is the mime-type to pass to setFileMimeType() and that we
   *   also expect to have returned by getFileMimeType()
   * @param array $expectations
   *   An array with the following 4 keys:
   *   - 'returned_values': the expected return value from getFileMimeType()
   *      for this scenario
   *   - 'exception_thrown': whether to expect an exception with the keys
   *      being the method and the value being TRUE if we expect an exception
   *      when calling it for this senario.
   *   - 'exception_message': an array of expected exception messages with
   *      the keys being the method and the value being the message we expect
   *      (empty string if no exception expected)
   *   - 'logged_message': an array of expected logged messages with
   *      the keys being the method and the value being the message we expect
   *      (empty string if no logged message expected)
   *
   * @dataProvider provideMimeTypeForSetter
   */
  public function testFileMimeType($scenario, $mime_type, $expectations) {

    // This exception message is expected when we intially call the getter
    // method for every scenario.
    $get_type_exception_message = 'Cannot retrieve the input file mime-type as it has not been set by setFileMimeType() method.';

    // scenario: Check that getFileMimeType() throws exception when not set.
    // --------------------------------------------------------------------------.
    $exception_caught = FALSE;
    $exception_message = '';
    try {
      $this->instance->getFileMimeType();
    }
    catch (\Exception $e) {
      $exception_caught = TRUE;
      $exception_message = $e->getMessage();
    }

    $this->assertTrue($exception_caught, 'FileTypes::getFileMimeType() method should throw an exception when trying to get a mime type before setting it.');
    $this->assertEquals(
      $get_type_exception_message,
      $exception_message,
      'Exception message does not match the expected one when trying to get mime type before setting it using FileTypes::setFileMimeType().'
    );

    // scenario: Test setFileMimeType() with current scenario.
    // Test various mime-types (see data provider) and check that the same value
    // gets returned
    // --------------------------------------------------------------------------.
    $exception_caught = FALSE;
    $exception_message = '';
    $printed_output = '';
    try {
      ob_start();
      $this->instance->setFileMimeType($mime_type);
      $printed_output = ob_get_contents();
    }
    catch (\Exception $e) {
      $exception_caught = TRUE;
      $exception_message = $e->getMessage();
    }
    ob_end_clean();
    $this->assertEquals(
      $expectations['exception_thrown']['setFileMimeType'],
      $exception_caught,
      "Unexpected exception activity occured for scenario: '" . $scenario . "'"
    );
    $this->assertEquals(
      $expectations['exception_message']['setFileMimeType'],
      $exception_message,
      "The expected and actual exception messages do not match when using FileTypes::setFileMimeType() for scenario: '" . $scenario . "'"
    );
    $this->assertEquals(
      $expectations['logged_message']['setFileMimeType'],
      $printed_output,
      "The expected and actual logged messages do not match when using FileTypes::setFileMimeType() for scenario: '" . $scenario . "'"
    );

    // scenario: Check getFileMimeType() returns expected mime-type after
    // setting.
    // --------------------------------------------------------------------------.
    $actual_type = '';
    $exception_caught = FALSE;
    $exception_message = '';
    try {
      $actual_type = $this->instance->getFileMimeType();
    }
    catch (\Exception $e) {
      $exception_caught = TRUE;
      $exception_message = $e->getMessage();
    }
    $this->assertEquals(
      $expectations['exception_thrown']['getFileMimeType'],
      $exception_caught,
      "Unexpected exception activity occured when trying to get file mime-type for scenario: '" . $scenario . "'. Exception message: '" . $exception_message . "'."
    );
    $this->assertEquals(
      $expectations['exception_message']['getFileMimeType'],
      $exception_message,
      "The expected and actual exception messages do not match when calling FileTypes::getFileMimeType() for scenario: '" . $scenario . "'"
    );
    // Finally, check that our retrieved mime-type matches our expected.
    $this->assertEquals(
      $expectations['returned_values']['mime-type'],
      $actual_type,
      "The expected mime-type using FileTypes::getFileMimeType() did not match the actual ones for scenario: '" . $scenario . "'"
    );
  }

}
