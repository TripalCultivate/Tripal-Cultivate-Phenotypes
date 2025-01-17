<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Kernel\TripalImporter;

use Drupal\KernelTests\AssertContentTrait;
use Drupal\Tests\tripal_chado\Kernel\ChadoTestKernelBase;
use Drupal\Tests\trpcultivate_phenotypes\Traits\PhenotypeImporterTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\tripal\Services\TripalLogger;
use Drupal\tripal_chado\Database\ChadoConnection;
use Drupal\trpcultivate_phenotypes\Plugin\TripalImporter\TripalCultivatePhenotypesTraitsImporter;

/**
 * Tests processValidationMessages() and related methods in the Traits Importer.
 *
 * @group traitsImporter
 */
class TraitImporterProcessValidationTest extends ChadoTestKernelBase {

  use AssertContentTrait;
  use PhenotypeImporterTestTrait;
  use UserCreationTrait;

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
   * Drupal render service.
   *
   * @var Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * A Database query interface for querying Chado using Tripal DBX.
   *
   * @var \Drupal\tripal_chado\Database\ChadoConnection
   */
  protected ChadoConnection $chado_connection;

  /**
   * Our instance of the Traits Importer for testing.
   *
   * @var Drupal\trpcultivate_phenotypes\Plugin\TripalImporter\TripalCultivatePhenotypesTraitsImporter
   */
  protected TripalCultivatePhenotypesTraitsImporter $importer;

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

    // Mock the logger to test if logged messages occur where we expect.
    $container = \Drupal::getContainer();
    $mock_logger = $this->getMockBuilder(TripalLogger::class)
      ->onlyMethods(['notice', 'error', 'info'])
      ->getMock();
    $mock_logger->method('error')
      ->willReturnCallback(function ($message, $context, $options) {
        // @todo Revisit print out of log messages, but perhaps setting an option
        // for log messages to not print to the UI?
        // print str_replace(array_keys($context), $context, $message);
        return NULL;
      });
    // Mock the 'info' log type as well.
    $container->set('tripal.logger', $mock_logger);

    // Get our renderer.
    $this->renderer = $this->container->get('renderer');

    // Create an instance of the Traits Importer.
    $this->importer = new TripalCultivatePhenotypesTraitsImporter(
      [],
      'trpcultivate-phenotypes-traits-importer',
      $this->definitions,
      $this->chado_connection,
      $this->container->get('trpcultivate_phenotypes.genus_ontology'),
      $this->container->get('trpcultivate_phenotypes.traits'),
      $this->container->get('plugin.manager.trpcultivate_validator'),
      $this->container->get('entity_type.manager'),
      $this->container->get('trpcultivate_phenotypes.template_generator'),
      $this->renderer,
      $this->container->get('messenger'),
    );
  }

  /**
   * Data Provider for testProcessGenusExistsFailures().
   *
   * @return array
   *   Each scenario is an array with the following:
   *   - The validation result array that gets passed to the process method. It
   *     contains the following keys:
   *     - 'case': a developer-focused string describing the case checked.
   *     - 'valid': FALSE to indicate that validation failed.
   *     - 'failedItems': an array of items that failed with the following keys.
   *       - 'genus_provided': The name of the genus provided.
   *   - An array of expectations in the rendered output which has the following
   *     keys:
   *     - 'expected_message': The message expected in the return value of the
   *       process method for this scenario.
   */
  public function provideGenusExistsFailedCases() {
    $scenarios = [];

    // #0: The genus does not exist
    $scenarios[] = [
      [
        'case' => 'Genus does not exist',
        'valid' => FALSE,
        'failedItems' => [
          'genus_provided' => 'Tripalus',
        ],
      ],
      [
        'expected_message' => 'The selected genus does not exist in this site.',
      ],
    ];

    // #1: The genus exists but is not configured.
    $scenarios[] = [
      [
        'case' => 'Genus exists but is not configured',
        'valid' => FALSE,
        'failedItems' => [
          'genus_provided' => 'Tripalus',
        ],
      ],
      [
        'expected_message' => 'The selected genus has not yet been configured for use with phenotypic data.',
      ],
    ];

    return $scenarios;
  }

  /**
   * Tests the message processor method for the GenusExists validator.
   *
   * @param array $validation_result
   *   The validation result array that gets passed to the process method. It
   *     contains the following keys:
   *     - 'case': a developer-focused string describing the case checked.
   *     - 'valid': FALSE to indicate that validation failed.
   *     - 'failedItems': an array of items that failed with the following keys.
   *       - 'genus_provided': The name of the genus provided.
   * @param array $expectations
   *   - An array of expectations in the rendered output which has the following
   *     keys:
   *     - 'expected_message': The message expected in the return value of the
   *       process method for this scenario.
   *
   * @dataProvider provideGenusExistsFailedCases
   */
  public function testProcessGenusExistsFailures(array $validation_result, array $expectations) {
    // Call the process method on our validation result.
    $render_array = $this->importer->processGenusExistsFailures($validation_result);
    // Render the array we were returned.
    $rendered_markup = $this->renderer->renderRoot($render_array);
    $this->setRawContent($rendered_markup);

    // Check the render array here.
    $selected_message_title = $this->cssSelect('div.form-item label');
    $provided_message = (string) $selected_message_title[0];
    $this->assertStringContainsString($expectations['expected_message'], $provided_message, 'The message expected from processing GenusExists failures for this scenario did not match the one in the rendered output.');
    // Check for an unordered list with one item in it - the genus provided.
    $selected_list_items = $this->cssSelect('div.form-item ul li');
    $this->assertCount(1, $selected_list_items, 'We expect only one list item in the render array from processing GenusExists failures.');
    // Grab the contents of 'SimpleXMLElement Object' and assert it is our
    // genus.
    $provided_genus = (string) $selected_list_items[0];
    $this->assertEquals('Tripalus', $provided_genus, 'The render array from processing GenusExists failures did not contain the expected genus.');
  }

  /**
   * Data Provider for testProcessValidHeadersFailures().
   *
   * @return array
   *   Each scenario is an array with the following:
   *   - The validation result array that gets passed to the process method. It
   *     contains the following keys:
   *     - 'case': a developer-focused string describing the case checked.
   *     - 'valid': FALSE to indicate that validation failed.
   *     - 'failedItems': an array of items that failed with the following keys.
   *       - 'headers': A string indicating the header row is empty.
   *       - an array of column headers that was in the input file.
   *   - An array of expectations in the rendered output which has the following
   *     keys:
   *     - 'expected_message': The message expected in the return value of the
   *       process method for this scenario.
   */
  public function provideValidHeadersFailedCases() {
    $scenarios = [];

    // #0: The header row is empty.
    $scenarios[] = [
      [
        'case' => 'Header row is an empty value',
        'valid' => FALSE,
        'failedItems' => [
          'headers' => 'headers array is an empty array',
        ],
      ],
      [
        'expected_message' => 'The file has an empty row where the header was expected.',
      ],
    ];

    // #1: Correct number of headers, but there's a mismatch.
    $scenarios[] = [
      [
        'case' => 'Headers do not match expected headers',
        'valid' => FALSE,
        'failedItems' => [
          'Trait Name',
          'Trait Description',
          '',
          'Method Description',
          'Unit',
          'Type',
        ],
      ],
      [
        'expected_message' => 'One or more of the column headers in the input file does not match what was expected.',
      ],
    ];

    // #2: Incorrect number of headers
    $scenarios[] = [
      [
        'case' => 'Headers provided does not have the expected number of headers',
        'valid' => FALSE,
        'failedItems' => [
          'Trait Name',
          'Trait Description',
          'Method Short Name',
          'Method Description',
        ],
      ],
      [
        'expected_message' => 'This importer requires a strict number of 6 column headers.',
      ],
    ];

    return $scenarios;
  }

  /**
   * Tests the message processor method for ValidHeaders validator.
   *
   * @param array $validation_result
   *   The validation result array that gets passed to the process method. It
   *   contains the following keys:
   *   - 'case': a developer-focused string describing the case checked.
   *   - 'valid': FALSE to indicate that validation failed.
   *   - 'failedItems': an array of items that failed with the following keys.
   *     - 'headers': A string indicating the header row is empty.
   *     - an array of column headers that was in the input file.
   * @param array $expectations
   *   An array of the expected items in the rendered output. It has the
   *   following keys:
   *   - 'expected_message': The message expected in the return value of the
   *     process method for this scenario.
   *
   * @dataProvider provideValidHeadersFailedCases
   */
  public function testProcessValidHeadersFailures(array $validation_result, array $expectations) {

    // Call the process method on our validation result.
    $render_array = $this->importer->processValidHeadersFailures($validation_result);
    // Render the array we were returned.
    $rendered_markup = $this->renderer->renderRoot($render_array);
    $this->setRawContent($rendered_markup);

    // Check the rendered output.
    // First check that we were given the correct message.
    $selected_message_markup = $this->cssSelect('ul li div.case-message');
    $this->assertStringContainsString($expectations['expected_message'], (string) $selected_message_markup[0], 'The message expected for this scenario for ProcessValidHeadersFailures did not match the message in the render array.');
    // Check that we have a table that contains the expected 2 rows.
    $selected_table_rows = $this->cssSelect('tbody tr');
    $this->assertCount(2, $selected_table_rows, 'The rendered table by processValidHeadersFailures does not contain the expected 2 rows for this scenario.');
    // Check for the "Provided Headers" heading on the 2nd row.
    $selected_provided_headers_th = $this->cssSelect('tbody tr.provided-headers th');
    $this->assertEquals('Provided Headers', (string) $selected_provided_headers_th[0], 'The second row of the rendered table does not contain the "Provided Headers" table header for this scenario.');
    // Check that the row values are the same as what we provided.
    $selected_provided_headers_td = $this->cssSelect('tbody tr.provided-headers td');
    // If the headers row was empty, check that we have no values in row 2.
    if (array_key_exists('headers', $validation_result['failedItems'])) {
      $this->assertEmpty($selected_provided_headers_td, "The values in the \"Provided Headers\" row were expected to be empty since an empty header was provided, but are not.");
    }
    else {
      // Iterate through our provided headers to compare with what's in the
      // rendered provided headers row.
      $this->assertEquals($validation_result['failedItems'], $selected_provided_headers_td, "The header row provided does not match the second row (the \'provided headers\' row) of the rendered table.");
    }
  }

  /**
   * Data Provider for testProcessDuplicateTraitsFailures().
   *
   * @return array
   *   Each scenario is an array with the following:
   *   - The failures array that gets passed to the process method. It contains
   *     the following keys:
   *     - The line number that triggered this failed validation status.
   *       - 'case': a developer-focused string describing the case checked.
   *       - 'valid': FALSE to indicate that validation failed.
   *       - 'failedItems': array of items that failed with the following keys:
   *         - 'combo_provided': The combination of trait, method, and unit
   *           provided in the file. The keys used are the same name of the
   *           column header for the cell containing the failed value.
   *           - 'Trait Name': The trait name provided in the file.
   *           - 'Method Short Name': The method name provided in the file.
   *           - 'Unit': The unit provided in the file.
   *   - An array of expectations that we want to find in the resulting rendered
   *     output. This array is nested by the tables expected (keyed by type),
   *     in the order they are expected to show up on the page (ie. 1 array per
   *     table). Each array has the following keys:
   *     - 'expected_message': The message expected in the return value of the
   *       process method for this scenario.
   *     - 1+ arrays keyed by the line number in the input file that triggered
   *       the failed validation status, further keyed by:
   *       - 'expected_trait': The expected name of the trait that failed.
   *       - 'expected_method': The expected short name of the method of the
   *         trait combo that failed.
   *       - 'expected_unit': The expected unit of the trait combo that failed.
   */
  public function provideDuplicateTraitsFailedCases() {
    $scenarios = [];

    // #0: A duplicate trait was found at line #3 in the input file.
    $scenarios[] = [
      [
        3 => [
          'case' => 'A duplicate trait was found within the input file',
          'valid' => FALSE,
          'failedItems' => [
            'combo_provided' => [
              'Trait Name' => 'Test File Trait',
              'Method Short Name' => 'Test File Method',
              'Unit' => 'Test File Unit',
            ],
          ],
        ],
      ],
      [
        'file' => [
          'expected_message' => 'These trait-method-unit combinations occurred multiple times within your input file.',
          3 => [
            'expected_trait' => 'Test File Trait',
            'expected_method' => 'Test File Method',
            'expected_unit' => 'Test File Unit',
          ],
        ],
      ],
    ];

    // #1: A duplicate trait was found in the database on line #4.
    $scenarios[] = [
      [
        4 => [
          'case' => 'A duplicate trait was found in the database',
          'valid' => FALSE,
          'failedItems' => [
            'combo_provided' => [
              'Trait Name' => 'Test DB Trait',
              'Method Short Name' => 'Test DB Method',
              'Unit' => 'Test DB Unit',
            ],
          ],
        ],
      ],
      [
        'database' => [
          'expected_message' => 'These trait-method-unit combinations have already been imported into this site.',
          4 => [
            'expected_trait' => 'Test DB Trait',
            'expected_method' => 'Test DB Method',
            'expected_unit' => 'Test DB Unit',
          ],
        ],
      ],
    ];

    // #2: A duplicate trait was found within both the input file and database
    // on line #5.
    $scenarios[] = [
      [
        5 => [
          'case' => 'A duplicate trait was found within both the input file and the database',
          'valid' => FALSE,
          'failedItems' => [
            'combo_provided' => [
              'Trait Name' => 'Test Both Trait',
              'Method Short Name' => 'Test Both Method',
              'Unit' => 'Test Both Unit',
            ],
          ],
        ],
      ],
      [
        'file' => [
          'expected_message' => 'These trait-method-unit combinations occurred multiple times within your input file.',
          5 => [
            'expected_trait' => 'Test Both Trait',
            'expected_method' => 'Test Both Method',
            'expected_unit' => 'Test Both Unit',
          ],

        ],
        'database' => [
          'expected_message' => 'These trait-method-unit combinations have already been imported into this site.',
          5 => [
            'expected_trait' => 'Test Both Trait',
            'expected_method' => 'Test Both Method',
            'expected_unit' => 'Test Both Unit',
          ],
        ],
      ],
    ];

    // #3: All 3 possible scenarios occur in the same file:
    // - Line 2 has a duplicate in the database.
    // - Line 3 has a duplicate within the file and the database.
    // - Line 11 has a duplicate within the file.
    $scenarios[] = [
      [
        2 => [
          'case' => 'A duplicate trait was found in the database',
          'valid' => FALSE,
          'failedItems' => [
            'combo_provided' => [
              'Trait Name' => 'Test DB Trait 2',
              'Method Short Name' => 'Test DB Method 2',
              'Unit' => 'Test DB Unit 2',
            ],
          ],
        ],
        3 => [
          'case' => 'A duplicate trait was found within both the input file and the database',
          'valid' => FALSE,
          'failedItems' => [
            'combo_provided' => [
              'Trait Name' => 'Test Both Trait 3',
              'Method Short Name' => 'Test Both Method 3',
              'Unit' => 'Test Both Unit 3',
            ],
          ],
        ],
        11 => [
          'case' => 'A duplicate trait was found within the input file',
          'valid' => FALSE,
          'failedItems' => [
            'combo_provided' => [
              'Trait Name' => 'Test File Trait 11',
              'Method Short Name' => 'Test File Method 11',
              'Unit' => 'Test File Unit 11',
            ],
          ],
        ],
      ],
      [
        'database' => [
          'expected_message' => 'These trait-method-unit combinations have already been imported into this site.',
          2 => [
            'expected_trait' => 'Test DB Trait 2',
            'expected_method' => 'Test DB Method 2',
            'expected_unit' => 'Test DB Unit 2',
          ],
          3 => [
            'expected_trait' => 'Test Both Trait 3',
            'expected_method' => 'Test Both Method 3',
            'expected_unit' => 'Test Both Unit 3',
          ],
        ],
        'file' => [
          'expected_message' => 'These trait-method-unit combinations occurred multiple times within your input file.',
          3 => [
            'expected_trait' => 'Test Both Trait 3',
            'expected_method' => 'Test Both Method 3',
            'expected_unit' => 'Test Both Unit 3',
          ],
          11 => [
            'expected_trait' => 'Test File Trait 11',
            'expected_method' => 'Test File Method 11',
            'expected_unit' => 'Test File Unit 11',
          ],
        ],
      ],
    ];

    return $scenarios;
  }

  /**
   * Tests the message processor method for the DuplicateTraits validator.
   *
   * @param array $failures
   *   The failures array that gets passed to the process method. It contains
   *   the following keys:
   *   - The line number that triggered this failed validation status.
   *     - 'case': a developer-focused string describing the case checked.
   *     - 'valid': FALSE to indicate that validation failed.
   *     - 'failedItems': an array of items that failed with the following keys.
   *       - 'combo_provided': The combination of trait, method, and unit
   *         provided in the file. The keys used are the same name of the column
   *         header for the cell containing the failed value.
   *         - 'Trait Name': The trait name provided in the file.
   *         - 'Method Short Name': The method name provided in the file.
   *         - 'Unit': The unit provided in the file.
   * @param array $expectations
   *   An array containing the expected output from the process method. This
   *   array is nested by tables expected (keyed by table type), in the order
   *   they are expected to show up on the page (ie. 1 array per table). Each
   *   array has the following keys:
   *     - 'expected_message': The message expected in the return value of the
   *       process method for this scenario.
   *     - 1+ arrays keyed by the line number in the input file that triggered
   *       the failed validation status, further keyed by:
   *       - 'expected_trait': The expected name of the trait that failed.
   *       - 'expected_method': The expected short name of the method of the
   *         trait combo that failed.
   *       - 'expected_unit': The expected unit of the trait combo that failed.
   *
   * @dataProvider provideDuplicateTraitsFailedCases
   */
  public function testProcessDuplicateTraitsFailures(array $failures, array $expectations) {

    // Process our test failures array.
    $render_array = $this->importer->processDuplicateTraitsFailures($failures);
    $rendered_markup = $this->renderer->renderRoot($render_array);
    $this->setRawContent($rendered_markup);

    // Check the rendered output.
    // Loop through expectations one table at a time.
    foreach ($expectations as $table_case => $table) {
      // Check the message above this table is correct.
      $selected_message_markup = $this->cssSelect("ul li div.case-message.case-$table_case");
      $table_message = (string) $selected_message_markup[0];
      $this->assertStringContainsString($expectations[$table_case]['expected_message'], $table_message, 'The message expected for this scenario did not match the message in the render array.');

      // Pull out the table rows for this table case.
      $selected_table_rows = $this->cssSelect("table.table-case-$table_case tbody tr td");
      // Keep track of the index in $selected_table_rows. This is necessary
      // since there's no way to distinguish different rows in a table.
      // ie:
      // Row 1: $selected_table_rows[0], ..[1], ..[2], ..[3]
      // Row 2: $selected_table_rows[4], ..[5], ..[6], ..[7]
      // etc...
      $str_index = 0;
      // Loop through expectations for each row of a table.
      foreach ($table as $expected_line_no => $validation_status) {
        if ($expected_line_no == 'expected_message') {
          continue;
        }
        // Line Number.
        $line_number = (string) $selected_table_rows[$str_index];
        $this->assertEquals($expected_line_no, $line_number, "Did not get the expected line number in the rendered $table_case table from processing DuplicateTraits failures.");
        // Trait Name.
        $trait_name = (string) $selected_table_rows[$str_index + 1];
        $this->assertEquals($table[$expected_line_no]['expected_trait'], $trait_name, "Did not get the expected trait name in the rendered $table_case table from processing DuplicateTraits failures.");
        // Method Short Name.
        $method_name = (string) $selected_table_rows[$str_index + 2];
        $this->assertEquals($table[$expected_line_no]['expected_method'], $method_name, "Did not get the expected method name in the rendered $table_case table from processing DuplicateTraits failures.");
        // Unit.
        $unit_name = (string) $selected_table_rows[$str_index + 3];
        $this->assertEquals($table[$expected_line_no]['expected_unit'], $unit_name, "Did not get the expected unit in the rendered $table_case table from processing DuplicateTraits failures.");

        // Increase the selected_table_rows count by 4 so that we can pull
        // values for the next row.
        $str_index = $str_index + 4;
      }
    }
  }

}
