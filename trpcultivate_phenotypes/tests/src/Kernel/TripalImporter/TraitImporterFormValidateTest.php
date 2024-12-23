<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Kernel\TripalImporter;

use Drupal\Core\Form\FormState;
use Drupal\Tests\tripal_chado\Kernel\ChadoTestKernelBase;
use Drupal\Tests\trpcultivate_phenotypes\Traits\PhenotypeImporterTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\tripal\Services\TripalLogger;
use Drupal\tripal_chado\Database\ChadoConnection;

/**
 * Tests the formValidate() functionality of the Traits Importer.
 *
 * @group traitsImporter
 */
class TraitImporterFormValidateTest extends ChadoTestKernelBase {

  use UserCreationTrait;
  use PhenotypeImporterTestTrait;

  /**
   * Theme used in the test environment.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'file',
    'tripal',
    'tripal_chado',
    'trpcultivate_phenotypes',
  ];

  /**
   * Our instance of the Traits Importer for testing.
   *
   * @var Drupal\trpcultivate_phenotypes\Plugin\TripalImporter\TripalCultivatePhenotypesTraitsImporter
   */
  protected TripalCultivatePhenotypesTraitsImporter $importer;

  /**
   * A Database query interface for querying Chado using Tripal DBX.
   *
   * @var \Drupal\tripal_chado\Database\ChadoConnection
   */
  protected ChadoConnection $chado_connection;

  /**
   * Saves details regarding the config.
   *
   * @var array
   */
  protected array $cvdbon;

  /**
   * The terms required by this module mapped to the cvterm_ids they are set to.
   *
   * @var array
   */
  protected array $terms;

  /**
   * A default listing of annotations associated with our importer.
   *
   * @var array
   */
  protected $definitions = [
    'test-trait-importer' => [
      'id' => 'trpcultivate-phenotypes-traits-importer',
      'label' => 'Tripal Cultivate: Phenotypic Trait Importer',
      'description' => 'Loads Traits for phenotypic data into the system. This is useful for large phenotypic datasets to ease the upload process.',
      'file_types' => ["tsv"],
      'use_analysis' => FALSE,
      'require_analysis' => FALSE,
      'upload_title' => 'Phenotypic Trait Data File*',
      'upload_description' => 'This should not be visible!',
      'button_text' => 'Import',
      'file_upload' => TRUE,
      'file_load' => FALSE,
      'file_remote' => FALSE,
      'file_required' => FALSE,
      'cardinality' => 1,
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Ensure we see all logging in tests.
    \Drupal::state()->set('is_a_test_environment', TRUE);

    // Open connection to Chado.
    $this->chado_connection = $this->getTestSchema(ChadoTestKernelBase::PREPARE_TEST_CHADO);

    // Ensure we can access file_managed related functionality from Drupal.
    // ... users need access to system.action config?
    $this->installConfig(['system', 'trpcultivate_phenotypes']);
    // ... managed files are associated with a user.
    $this->installEntitySchema('user');
    // ... Finally the file module + tables itself.
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('tripal_chado', ['tripal_custom_tables']);
    // Ensure we have our tripal import tables.
    $this->installSchema('tripal', ['tripal_import', 'tripal_jobs']);
    // Create and log-in a user.
    $this->setUpCurrentUser();

    // We need to mock the logger to test the progress reporting.
    $container = \Drupal::getContainer();
    $mock_logger = $this->getMockBuilder(TripalLogger::class)
      ->onlyMethods(['notice', 'error'])
      ->getMock();
    $mock_logger->method('error')
      ->willReturnCallback(function ($message, $context, $options) {
        // @todo Revisit print out of log messages, but perhaps setting an option
        // for log messages to not print to the UI?
        // print str_replace(array_keys($context), $context, $message);
        return NULL;
      });
    $container->set('tripal.logger', $mock_logger);
  }

  /**
   * Data Provider: provides files with expected validation result.
   *
   * @return array
   *   Each scenario is an array with the following:
   *   - The genus name that gets selected in the dropdown of the form
   *   - The filename of the test file used for this scenario (test files are
   *     located in: tests/src/Fixtures/TraitImporterFiles/)
   *   - An array indicating the expected validation results:
   *     - Each key is the unique name of a feedback line provided to the UI
   *       through processValidationMessages(). Currently, there is a feedback
   *       line for each unique validator instance that was instantiated by the
   *       configureValidators() method in the Traits Importer class.
   *       - 'status': [REQUIRED] One of 'pass', 'todo', or 'fail'
   *       - 'title': [REQUIRED if 'status' = 'fail'] A string that matches the
   *         title set in processValidationMessages() method in the Traits
   *         Importer class for this validator instance.
   *       - 'details': [REQUIRED if 'status' = 'fail'] A string that is ideally
   *         unique to the scenario that is expected to be in the render array.
   *   - an integer indicating the number of form validation messages we expect
   *     to see when the form is submitted.
   *     NOTE: These validation messages are produced by the form via Drupal and
   *     are not related to this module's use of validator plugins.
   */
  public function provideFilesForValidation() {

    // Set our default variables for genus.
    $valid_genus = 'Tripalus';
    $invalid_genus = 'INVALID';
    // Set our number of expected validation messages to 0, since only the
    // 'genus_exists' validator should cause this number to change.
    $num_form_validation_messages = 0;

    $scenarios = [];

    // #0: File is valid but genus is not
    $scenarios[] = [
      $invalid_genus,
      'simple_example.txt',
      [
        'genus_exists' => [
          'title' => 'The genus is valid',
          'status' => 'fail',
          'details' => 'The selected genus does not exist in this site. Please contact your administrator to have this added.',
        ],
        'valid_data_file' => ['status' => 'todo'],
        'valid_delimited_file' => ['status' => 'todo'],
        'valid_header' => ['status' => 'todo'],
        'empty_cell' => ['status' => 'todo'],
        'valid_data_type' => ['status' => 'todo'],
        'duplicate_traits' => ['status' => 'todo'],
      ],
      // Selecting an invalid genus should be impossible, so 1 form validation
      // error is expected.
      1,
    ];

    // #1: File is empty.
    $scenarios[] = [
      $valid_genus,
      'empty_file.tsv',
      [
        'genus_exists' => ['status' => 'pass'],
        'valid_data_file' => [
          'title' => 'File is valid and not empty',
          'status' => 'fail',
          'details' => 'The file provided has no contents in it to import. Please ensure your file has the expected header row and at least one row of data.',
        ],
        'valid_delimited_file' => ['status' => 'todo'],
        'valid_header' => ['status' => 'todo'],
        'empty_cell' => ['status' => 'todo'],
        'valid_data_type' => ['status' => 'todo'],
        'duplicate_traits' => ['status' => 'todo'],
      ],
      $num_form_validation_messages,
    ];

    // #2: Header is improperly delimited, with proper data rows.
    $scenarios[] = [
      $valid_genus,
      'improperly_delimited_header_with_data.tsv',
      [
        'genus_exists' => ['status' => 'pass'],
        'valid_data_file' => ['status' => 'pass'],
        'valid_delimited_file' => [
          'title' => 'Lines are properly delimited',
          'status' => 'fail',
          'details' => 'This importer requires a strict number of 6 columns for each line. The following lines do not contain the expected number of columns.',
        ],
        'valid_header' => ['status' => 'todo'],
        'empty_cell' => ['status' => 'todo'],
        'valid_data_type' => ['status' => 'todo'],
        'duplicate_traits' => ['status' => 'todo'],
      ],
      $num_form_validation_messages,
    ];

    // #3: 2nd row of file is improperly delimited.
    $scenarios[] = [
      $valid_genus,
      'correct_header_improperly_delimited_data_row.tsv',
      [
        'genus_exists' => ['status' => 'pass'],
        'valid_data_file' => ['status' => 'pass'],
        'valid_delimited_file' => [
          'title' => 'Lines are properly delimited',
          'status' => 'fail',
          'details' => 'This importer requires a strict number of 6 columns for each line. The following lines do not contain the expected number of columns.',
        ],
        // Since the header row has the correct number of columns, validation
        // for valid_header is expected to pass.
        'valid_header' => ['status' => 'pass'],
        'empty_cell' => ['status' => 'todo'],
        'valid_data_type' => ['status' => 'todo'],
        'duplicate_traits' => ['status' => 'todo'],
      ],
      $num_form_validation_messages,
    ];

    // #4: Contains correct header but no data.
    // Never reaches the validators for data-row since file content is empty.
    $scenarios[] = [
      $valid_genus,
      'correct_header_no_data.tsv',
      [
        'genus_exists' => ['status' => 'pass'],
        'valid_data_file' => ['status' => 'pass'],
        'valid_delimited_file' => ['status' => 'pass'],
        'valid_header' => ['status' => 'pass'],
        'empty_cell' => ['status' => 'todo'],
        'valid_data_type' => ['status' => 'todo'],
        'duplicate_traits' => ['status' => 'todo'],
      ],
      $num_form_validation_messages,
    ];

    // #5: Contains incorrect header and one line of correct data.
    $scenarios[] = [
      $valid_genus,
      'incorrect_header_with_data.tsv',
      [
        'genus_exists' => ['status' => 'pass'],
        'valid_data_file' => ['status' => 'pass'],
        'valid_delimited_file' => ['status' => 'pass'],
        'valid_header' => [
          'title' => 'File has all of the column headers expected',
          'status' => 'fail',
          'details' => 'One or more of the column headers in the input file does not match what was expected. Please check if your column header is in the correct order and matches the template exactly.',
        ],
        'empty_cell' => ['status' => 'todo'],
        'valid_data_type' => ['status' => 'todo'],
        'duplicate_traits' => ['status' => 'todo'],
      ],
      $num_form_validation_messages,
    ];

    // #6: Contains correct header and one line of correct data.
    // 3rd line has an empty 'Short Method Name'.
    $scenarios[] = [
      $valid_genus,
      'correct_header_emptycell_method.tsv',
      [
        'genus_exists' => ['status' => 'pass'],
        'valid_data_file' => ['status' => 'pass'],
        'valid_delimited_file' => ['status' => 'pass'],
        'valid_header' => ['status' => 'pass'],
        'empty_cell' => [
          'title' => 'Required cells contain a value',
          'status' => 'fail',
          'details' => 'The following line number and column header combinations were empty, but a value is required.',
        ],
        'valid_data_type' => ['status' => 'pass'],
        'duplicate_traits' => ['status' => 'pass'],
      ],
      $num_form_validation_messages,
    ];

    // #7: Contains correct header and one line of correct data.
    // 3rd line has an empty line (not a validation error).
    // 4th line has an empty 'Short Method Name'.
    $scenarios[] = [
      $valid_genus,
      'correct_header_emptycell_after_empty_line.tsv',
      [
        'genus_exists' => ['status' => 'pass'],
        'valid_data_file' => ['status' => 'pass'],
        'valid_delimited_file' => ['status' => 'pass'],
        'valid_header' => ['status' => 'pass'],
        'empty_cell' => [
          'title' => 'Required cells contain a value',
          'status' => 'fail',
          'details' => 'The following line number and column header combinations were empty, but a value is required.',
        ],
        'valid_data_type' => ['status' => 'pass'],
        'duplicate_traits' => ['status' => 'pass'],
      ],
      $num_form_validation_messages,
    ];

    // #8: Contains correct header and two lines of data.
    // First line has an invalid value for 'Type' column.
    $scenarios[] = [
      $valid_genus,
      'correct_header_invalid_datatype.tsv',
      [
        'genus_exists' => ['status' => 'pass'],
        'valid_data_file' => ['status' => 'pass'],
        'valid_delimited_file' => ['status' => 'pass'],
        'valid_header' => ['status' => 'pass'],
        'empty_cell' => ['status' => 'pass'],
        'valid_data_type' => [
          'title' => 'Values in required cells are valid',
          'status' => 'fail',
          'details' => 'The following line number and column combinations did not contain one of the following allowed values: "Quantitative", "Qualitative". Note that values should be case sensitive. <strong>Empty cells indicate the value given was one of the allowed values.</strong>',
        ],
        'duplicate_traits' => ['status' => 'pass'],
      ],
      $num_form_validation_messages,
    ];

    // #9: Contains correct header and a duplicate trait-method-unit combo.
    $scenarios[] = [
      $valid_genus,
      'correct_header_duplicate_traitMethodUnit.tsv',
      [
        'genus_exists' => ['status' => 'pass'],
        'valid_data_file' => ['status' => 'pass'],
        'valid_delimited_file' => ['status' => 'pass'],
        'valid_header' => ['status' => 'pass'],
        'empty_cell' => ['status' => 'pass'],
        'valid_data_type' => ['status' => 'pass'],
        'duplicate_traits' => [
          'title' => 'All trait-method-unit combinations are unique',
          'status' => 'fail',
          'details' => 'These trait-method-unit combinations occurred multiple times within your input file. The line number indicates the duplicated occurrence(s).',
        ],
      ],
      $num_form_validation_messages,
    ];

    return $scenarios;
  }

  /**
   * Tests the validation aspect of the trait importer form.
   *
   * @param string $submitted_genus
   *   The name of the genus that is submitted with the form.
   * @param string $filename
   *   The name of the file being tested. (Test files are located in
   *   tests/src/Fixtures/TraitImporterFiles/)
   * @param array $expected_validator_results
   *   An array that is keyed by the unique name of each validator instance
   *   (these names are declared in the configureValidators() method in the
   *   Traits Importer class). Each validator instance in the array is further
   *   keyed by the following. Some are required but others are optional,
   *   dependent upon the expected validation results.
   *   - 'status': [REQUIRED] One of 'pass', 'todo', or 'fail'.
   *   - 'title': [REQUIRED if 'status' = 'fail'] A string that matches the
   *     title set in processValidationMessages() method in the Trait Importer
   *     class for this validator instance.
   *   - 'details': [REQUIRED if 'status' = 'fail'] A string that is ideally
   *     unique to the scenario that is expected to be in the render array.
   * @param int $expected_num_form_validation_errors
   *   The number of form validation messages we expect to see when the form is
   *   submitted. NOTE: These validation messages are produced by the form via
   *   Drupal and are not related to this module's use of validator plugins.
   *
   * @dataProvider provideFilesForValidation
   */
  public function testTraitFormValidation(string $submitted_genus, string $filename, array $expected_validator_results, int $expected_num_form_validation_errors) {

    $formBuilder = \Drupal::formBuilder();
    $form_id = 'Drupal\tripal\Form\TripalImporterForm';
    $plugin_id = 'trpcultivate-phenotypes-traits-importer';

    // Configure the module.
    $genus = 'Tripalus';
    $organism_id = $this->chado_connection->insert('1:organism')
      ->fields([
        'genus' => $genus,
        'species' => 'databasica',
      ])
      ->execute();
    $this->assertIsNumeric($organism_id,
      "We were not able to create an organism for testing.");
    $this->cvdbon = $this->setOntologyConfig($genus);
    $this->terms = $this->setTermConfig();

    // Create a file to upload.
    $file = $this->createTestFile([
      'filename' => $filename,
      'content' => ['file' => 'TraitImporterFiles/' . $filename],
    ]);

    // Setup the form_state.
    $form_state = new FormState();
    $form_state->addBuildInfo('args', [$plugin_id]);

    // Submit our genus.
    $form_state->setValue('genus', $submitted_genus);

    // Submit our file.
    $form_state->setValue('file_upload', $file->id());

    // Now try validation!
    $formBuilder->submitForm($form_id, $form_state);
    // And retrieve the form that would be shown after the above submit.
    $form = $formBuilder->retrieveForm($form_id, $form_state);

    // Check that we did validation.
    $this->assertTrue($form_state->isValidationComplete(),
      "We expect the form state to have been updated to indicate that validation is complete.");

    // Looking for form validation errors.
    $form_validation_messages = $form_state->getErrors();
    $helpful_output = [];
    foreach ($form_validation_messages as $element => $markup) {
      $helpful_output[] = $element . " => " . (string) $markup;
    }

    // Compare number of form validation errors received to the number expected.
    $this->assertCount(
      $expected_num_form_validation_errors,
      $form_validation_messages,
      "The number of form state errors we expected (" . $expected_num_form_validation_errors . ") does not match what we received: " . implode(" AND ", $helpful_output)
    );
    // Confirm that there is a validation window open.
    $this->assertArrayHasKey('validation_result', $form,
      "We expected a validation failure reported via our plugin setup but it's not showing up in the form.");
    $validation_element_data = $form['validation_result']['#data']['validation_result'];

    // Now check our expectations are met.
    foreach ($expected_validator_results as $validation_plugin => $expected) {
      // Check status.
      $this->assertEquals(
        $expected['status'],
        $validation_element_data[$validation_plugin]['status'],
        "We expected the form validation element to indicate the $validation_plugin plugin had the specified status."
      );
      // We don't want the value of 'details' in $expectations (from the data
      // provider) to be empty since assertStringContainsString() will evaluate
      // to true in that scenario. It can be tempting to set it to empty and
      // then come back to it when you figure out what the expected string
      // should be- just don't do it!
      if (array_key_exists('details', $expected)) {
        $this->assertNotEmpty(
          $expected['details'],
          "An empty string was provided with a 'details' key within the data provider - trust me, don't do that!"
        );

        // Now check details.
        $this->assertIsArray(
          $validation_element_data[$validation_plugin]['details'],
          "We expected the details for $validation_plugin to be an array, but it is not."
        );

        // Check for the key #type which is common in all render arrays.
        $this->assertArrayHasKey('#type', $validation_element_data[$validation_plugin]['details'], "We expected the details for $validation_plugin to be a render array by having the #type key, but it does not.");

        // Walk recursively through the render array, and report whether our
        // 'details' item is present in the array or not.
        $item_to_find = $expected['details'];
        $found = FALSE;
        array_walk_recursive(
          $validation_element_data[$validation_plugin]['details'],
          function ($item, $key) use (&$found, $item_to_find) {
            if ($item == $item_to_find) {
              $found = TRUE;
            }
          }
        );

        $this->assertTrue($found, "We expected to find \"$item_to_find\" in the
        resulting render array for $validation_plugin failures, but did not.");
      }
    }

    // Assert that the default value of genus field is the genus
    // entered/selected, indicating that on form validate error, the form was
    // not submitted and reloaded with the genus value as default.
    $this->assertEquals(
      $form_state->getValue('genus'),
      $submitted_genus,
      'The import form should set the default value of genus to the genus entered if the form was not submitted due to validation error.'
    );

    // If the form was not submitted due to validation error, check to ensure
    // that no Tripal Job was created in the process.
    $tripal_jobs = $this->chado_connection->query(
      'SELECT job_id FROM {tripal_jobs} ORDER BY job_id DESC LIMIT 1'
    )
      ->fetchField();

    $this->assertFalse(
      $tripal_jobs,
      'A failed import due to validation error that did not submit should not create a job request.'
    );
  }

}
