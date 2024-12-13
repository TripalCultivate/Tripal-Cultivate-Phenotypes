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
   * Tests the message processor method for the GenusExists validator.
   */
  public function testProcessGenusExistsFailures() {

    // Trigger case of genus doesn't exist.
    $validation_result = [];
    $validation_result['valid'] = FALSE;
    $validation_result['case'] = 'Genus does not exist';
    $validation_result['failedItems']['genus_provided'] = 'Tripalus';

    $render_array = $this->importer->processGenusExistsFailures($validation_result);
    $rendered_markup = $this->renderer->renderRoot($render_array);
    $this->setRawContent($rendered_markup);

    // Check the render array here.
    $selected_list_items = $this->cssSelect('div.form-item ul li');
    $this->assertCount(1, $selected_list_items, 'We expect only one list item in the render array from processing GenusExists failures.');
    // Grab the contents of 'SimpleXMLElement Object' and assert it is our
    // genus.
    $provided_genus = (string) $selected_list_items[0];
    $this->assertEquals('Tripalus', $provided_genus, 'The render array from processing GenusExists failures did not contain the expected genus.');

    // Trigger case of genus exists but it not configured.
    $validation_result = [];
    $validation_result['valid'] = FALSE;
    $validation_result['case'] = 'Genus exists but is not configured';
    $validation_result['failedItems']['genus_provided'] = 'Tripalus';

    $render_array = $this->importer->processGenusExistsFailures($validation_result);
    $rendered_markup = $this->renderer->renderRoot($render_array);
    $this->setRawContent($rendered_markup);

    // Check the render array here.
    $selected_list_items = $this->cssSelect('div.form-item ul li');
    $this->assertCount(1, $selected_list_items, 'We expect only one list item in the render array from processing GenusExists failures.');
    // Grab the contents of 'SimpleXMLElement Object' and assert it is our
    // genus.
    $provided_genus = (string) $selected_list_items[0];
    $this->assertEquals('Tripalus', $provided_genus, "The render array from processing GenusExists failures did not contain the expected genus.");
  }

  /**
   * Tests the message processor method for the GenusExists validator.
   */
  public function testDuplicateTraitsFailures() {

    // Setup a duplicate in the file at line #3.
    $failures = [];
    $failures[3]['valid'] = FALSE;
    $failures[3]['case'] = 'A duplicate trait was found within the input file';
    $failures[3]['failedItems']['combo_provided']['Trait Name'] = 'Test Trait';
    $failures[3]['failedItems']['combo_provided']['Method Short Name'] = 'Test Method Name';
    $failures[3]['failedItems']['combo_provided']['Unit'] = 'Test Unit';

    // Process our test failures array.
    $render_array = $this->importer->processDuplicateTraitsFailures($failures);
    $rendered_markup = $this->renderer->renderRoot($render_array);
    $this->setRawContent($rendered_markup);

    // Check the render array.
    // First check the message above the table.
    $selected_message_markup = $this->cssSelect('ul li div.case-message');
    $table_message = (string) $selected_message_markup[0];
    $this->assertStringContainsString('multiple times within your input file', $table_message);
    // Second, check the table has 4 columns which contain our test elements.
    $selected_table_rows = $this->cssSelect('tbody tr td');
    // Line number: 3
    $line_number = (string) $selected_table_rows[0];
    $this->assertEquals(3, $line_number, "Did not get the expected line number in the rendered table from processing DuplicateTraits failures.");
    // Trait Name: Test Trait
    $trait_name = (string) $selected_table_rows[1];
    $this->assertEquals($failures[3]['failedItems']['combo_provided']['Trait Name'], $trait_name, "Did not get the expected trait name in the rendered table from processing DuplicateTraits failures.");
    // Method Short Name: Test Method Name
    $method_name = (string) $selected_table_rows[2];
    $this->assertEquals($failures[3]['failedItems']['combo_provided']['Method Short Name'], $method_name, "Did not get the expected method name in the rendered table from processing DuplicateTraits failures.");
    // Unit: Test Unit
    $unit_name = (string) $selected_table_rows[3];
    $this->assertEquals($failures[3]['failedItems']['combo_provided']['Unit'], $unit_name, "Did not get the expected unit in the rendered table from processing DuplicateTraits failures.");
  }

}
