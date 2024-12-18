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
   *   - Expectations
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
   *
   * @dataProvider provideGenusExistsFailedCases
   */
  public function testProcessGenusExistsFailures(array $validation_result) {

    // Call the process method on our validation result.
    $render_array = $this->importer->processGenusExistsFailures($validation_result);
    // Render the array we were returned.
    $rendered_markup = $this->renderer->renderRoot($render_array);
    $this->setRawContent($rendered_markup);
    // Check the render array here. We expect an unordered list with one item in
    // it - the genus provided.
    $selected_list_items = $this->cssSelect('div.form-item ul li');
    $this->assertCount(1, $selected_list_items, 'We expect only one list item in the render array from processing GenusExists failures.');
    // Grab the contents of 'SimpleXMLElement Object' and assert it is our
    // genus.
    $provided_genus = (string) $selected_list_items[0];
    $this->assertEquals('Tripalus', $provided_genus, 'The render array from processing GenusExists failures did not contain the expected genus.');
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
   *   - Expectations array
   *     - 'expected_message': The message expected in the return valie of the
   *       process method for this scenario.
   *     - 'expected_line_no': The line number expected to be in the output.
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
        0 => [
          'expected_message' => 'These trait-method-unit combinations occurred multiple times within your input file.',
          'expected_line_no' => 3,
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
        0 => [
          'expected_message' => 'These trait-method-unit combinations have already been imported into this site.',
          'expected_line_no' => 4,
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
              'Trait Name' => 'Test Multi Trait',
              'Method Short Name' => 'Test Multi Method',
              'Unit' => 'Test Multi Unit',
            ],
          ],
        ],
      ],
      [
        0 => [
          'expected_message' => 'These trait-method-unit combinations occurred multiple times within your input file.',
          'expected_line_no' => 5,
        ],
        1 => [
          'expected_message' => 'These trait-method-unit combinations have already been imported into this site.',
          'expected_line_no' => 5,
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
   *   An array containing the expected output from the process method. It
   *   should contain the following keys:
   *   - 'expected_message': The message expected in the return valie of the
   *     process method for this scenario.
   *   - 'expected_line_no': The line number expected to be in the output.
   *
   * @dataProvider provideDuplicateTraitsFailedCases
   */
  public function testProcessDuplicateTraitsFailures(array $failures, array $expectations) {

    // Process our test failures array.
    $render_array = $this->importer->processDuplicateTraitsFailures($failures);
    $rendered_markup = $this->renderer->renderRoot($render_array);
    $this->setRawContent($rendered_markup);

    // Check the render array.
    // First check the message above the table.
    $selected_message_markup = $this->cssSelect('ul li div.case-message');
    foreach ($selected_message_markup as $index => $case_message) {
      $table_message = (string) $case_message[0];
      $this->assertStringContainsString($expectations[$index]['expected_message'], $table_message, 'The message expected for this scenario did not match the message in the render array.');
    }

    // Second, check that each table has 4 columns with our test elements.
    $selected_table_rows = $this->cssSelect('tbody tr td');
    foreach ($expectations as $index => $expected) {
      // Multiply the index by 4 so that we are pulling values for the correct
      // row. This is because $selected_table_rows increments for each cell but
      // does not indicate the row #. Thus:
      // Row 0: $selected_table_rows[0], ..[1], ..[2], ..[3]
      // Row 1: $selected_table_rows[4], ..[5], ..[6], ..[7]
      // etc...
      $index = $index * 4;
      // Line Number.
      $line_number = (string) $selected_table_rows[$index];
      $expected_line_no = $expected['expected_line_no'];
      $this->assertEquals($expected_line_no, $line_number, "Did not get the expected line number in the rendered table from processing DuplicateTraits failures.");
      // Trait Name.
      $trait_name = (string) $selected_table_rows[$index + 1];
      $this->assertEquals($failures[$expected_line_no]['failedItems']['combo_provided']['Trait Name'], $trait_name, "Did not get the expected trait name in the rendered table from processing DuplicateTraits failures.");
      // Method Short Name.
      $method_name = (string) $selected_table_rows[$index + 2];
      $this->assertEquals($failures[$expected_line_no]['failedItems']['combo_provided']['Method Short Name'], $method_name, "Did not get the expected method name in the rendered table from processing DuplicateTraits failures.");
      // Unit.
      $unit_name = (string) $selected_table_rows[$index + 3];
      $this->assertEquals($failures[$expected_line_no]['failedItems']['combo_provided']['Unit'], $unit_name, "Did not get the expected unit in the rendered table from processing DuplicateTraits failures.");

    }
  }

}
