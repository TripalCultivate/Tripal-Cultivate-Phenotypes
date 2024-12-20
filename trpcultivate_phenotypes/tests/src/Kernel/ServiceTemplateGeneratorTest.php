<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Kernel;

use Drupal\Tests\tripal_chado\Kernel\ChadoTestKernelBase;

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
   * Undocumented variable
   *
   * @var [type]
   */
  protected $config;

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

    $this->config = \Drupal::service('config.factory');
  }

  /**
   * Test template generator.
   */
  public function testTemplateGeneratorService() {
    $template_generator = \Drupal::service('trpcultivate_phenotypes.template_generator');

    // Generate a template file.
    $plugin_id = 'doesnt-nned-to-be-real';
    $column_headers = ['Header A', 'Header B', 'Header C'];
    $link = $template_generator->generateFile($plugin_id, $column_headers);

    // Assert a link has been created.
    $this->assertNotNull($link, 'Failed to generate template file link.');

    // Assert that a file has been created in the the configured directory
    // for template files.
    $dir_templates = $this->config->get('trpcultivate_phenotypes.settings')
      ->get('trpcultivate.phenotypes.directory.template_file');

    $file_system = \Drupal::service('file_system')
      ->realpath($dir_templates);

    $template_file = reset(array_diff(scandir($file_system), ['..', '.']));

    $this->assertStringContainsString(
      $plugin_id,
      $template_file,
      'Test could not find a template file in the directory configured for template files'
    );

    // Assert that the headers were inserted into the file as the header row.
    $file_contents = fopen($file_system . '/' . $template_file, 'r');
    if ($file_contents) {
      $header_row = trim(fgets($file_contents), "\n");
      fclose($file_contents);
    }

    $this->assertEquals(
      $header_row,
      implode("\t", $column_headers),
      'The template file does not contain the expected column headers'
    );
  }

}
