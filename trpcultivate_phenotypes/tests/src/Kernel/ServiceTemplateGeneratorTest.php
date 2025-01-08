<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Kernel;

use Drupal\Tests\tripal_chado\Kernel\ChadoTestKernelBase;
use Drupal\user\Entity\User;

/**
 * Tests associated with the template file generator service.
 *
 * @group trpcultivate_phenotypes
 * @group template_generate
 */
class ServiceTemplateGeneratorTest extends ChadoTestKernelBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'file',
    'user',
    'system',
    'tripal',
    'tripal_chado',
    'trpcultivate_phenotypes',
  ];

  /**
   * Configuration entity.
   *
   * @var object
   */
  protected $config;


  /**
   * The TripalCultivatePhenotypes File Template Service.
   *
   * @var Drupal\trpcultivate_phenotypes\Service\TripalCultivatePhenotypesFileTemplateService
   */
  protected $service_FileTemplate;

  /**
   * A test user.
   *
   * @var Drupal\user\Entity\User
   */
  protected $user;

  /**
   * File system interface.
   *
   * @var Drupal\Core\File\FileSystemInterface
   */
  protected $file_system;

  /**
   * {@inheritdoc}
   */
  protected function setUp() :void {
    parent::setUp();

    // Ensure we see all logging in tests.
    \Drupal::state()->set('is_a_test_environment', TRUE);

    // Setup file configuration and schema.
    $this->installConfig(['file', 'trpcultivate_phenotypes']);
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');

    $this->file_system = \Drupal::service('file_system');
    $this->config = \Drupal::service('config.factory');
    $this->service_FileTemplate = \Drupal::service('trpcultivate_phenotypes.template_generator');

    // Create a user.
    $this->user = User::create([
      'name' => 'user-collector',
      'roles' => ['authenticated user'],
    ]);
    $this->user->save();

    \Drupal::currentUser()->setAccount($this->user);
  }

  /**
   * Data Provider: provide various test scenarios of file format and headers.
   *
   * @return array
   *   Each test file scenario is an array with the following values:
   *   - A string, humnan-readable short description of the test scenario.
   *   - A string, the importer plugin id.
   *   - An array, the list of headers that will become the header row in file.
   *   - An array, file properties with the following keys:
   *     - 'extension': the file extension of the template file.
   *     - 'mime': the MIME type of template file.
   *     - 'delimiter': the delimiter used to separate values (ie. headers).
   *   - An array of expected values, with the following keys:
   *     - 'file_filename': the expected filename of the template file.
   *     - 'file_content': the expected content (header row) of the file.
   */
  public function provideParametersForFileTemplateGenerator() {
    return [
      // #0: A tsv file.
      [
        'a tsv file',
        'my-importer',
        ['Header A', 'Header B', 'Header C'],
        [
          'extension' => 'tsv',
          'mime' => 'text/tab-separated-values',
          'delimiter' => "\t",
        ],
        [
          'file_filename' => "my-importer-data-collection-template-file-user-collector.tsv",
          'file_content' => implode("\t", ['Header A', 'Header B', 'Header C']),
        ],
      ],

      // #1: A csv file.
      [
        'a csv file',
        'another-importer',
        ['Header E', 'Header F', 'Header G'],
        [
          'extension' => 'csv',
          'mime' => 'text/csv',
          'delimiter' => ",",
        ],
        [
          'file_filename' => "another-importer-data-collection-template-file-user-collector.csv",
          'file_content' => "Header E,Header F,Header G",
        ],
      ],

      // #2: A txt file.
      [
        'a txt file',
        'basic-importer',
        ['Header X', 'Header Y', 'Header Z'],
        [
          'extension' => 'txt',
          'mime' => 'text/txt',
          'delimiter' => "<delimiter>",
        ],
        [
          'file_filename' => "basic-importer-data-collection-template-file-user-collector.txt",
          'file_content' => "Header X<delimiter>Header Y<delimiter>Header Z",
        ],
      ],
    ];
  }

  /**
   * Test file template generator service.
   *
   * @param string $scenario
   *   Humnan-readable short description of the test scenario.
   * @param string $importer_id
   *   The importer plugin id.
   * @param array $column_headers
   *   The list of headers that will become the header row in file.
   * @param array $file_properties
   *   File properties with the following keys:
   *     - 'extension': the file extension of the template file.
   *     - 'mime': the MIME type of template file.
   *     - 'delimiter': the delimiter used to separate values (ie. headers).
   * @param array $expected
   *   An array of expected values, with the following keys:
   *     - 'file_filename': the expected filename of the template file.
   *     - 'file_content': the expected content (header row) of the file.
   *
   * @dataProvider provideParametersForFileTemplateGenerator
   */
  public function testTemplateGeneratorService($scenario, $importer_id, $column_headers, $file_properties, $expected) {

    // Generate the template file.
    $link = $this->service_FileTemplate->generateFile($importer_id, $column_headers, $file_properties);

    // Assert a link has been created.
    $this->assertNotNull($link, 'Failed to generate template file link.');

    // Assert that a file has been created in the the configured directory
    // for template files.
    $dir_templates = $this->config->get('trpcultivate_phenotypes.settings')
      ->get('trpcultivate.phenotypes.directory.template_file');

    $file_system = $this->file_system->realpath($dir_templates);

    $template_file = (string) reset(array_diff(scandir($file_system), ['..', '.']));

    // Filename is the expected file name.
    $this->assertEquals(
      $expected['file_filename'],
      $template_file,
      'The filename of the template file does not match expected file name in scenario ' . $scenario
    );

    // The filename contains the importer id and username.
    $this->assertStringContainsString(
      $importer_id,
      $template_file,
      'The filename of the template file is expected to contain the importer id in scenario ' . $scenario
    );

    $this->assertStringContainsString(
      $this->user->getAccountName(),
      $template_file,
      'The filename of the template file is expected to contain the importer id in scenario ' . $scenario
    );

    // The template file has the headers.
    // Assert that the headers were inserted into the file as the header row.
    $file_contents = fopen($file_system . '/' . $template_file, 'r');
    if ($file_contents) {
      $header_row = trim(fgets($file_contents), "\n");
      fclose($file_contents);
    }

    $this->assertEquals(
      $expected['file_content'],
      $header_row,
      'The template file does not contain the expected column headers in scenario' . $scenario
    );
  }

}
