<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Kernel;

use Drupal\Tests\tripal_chado\Kernel\ChadoTestKernelBase;
use Drupal\tripal_chado\Database\ChadoConnection;

/**
 * Test Tripal Cultivate Phenotypes Terms service.
 *
 * @group trpcultivate_phenotypes
 */
class ServiceTermTest extends ChadoTestKernelBase {

  /**
   * Term service.
   *
   * @var object
   */
  protected $service;

  /**
   * Modules to enable.
   */
  protected static $modules = [
    'system',
    'tripal',
    'tripal_layout',
    'tripal_chado',
    'trpcultivate',
    'trpcultivate_phenotypes',
  ];

  /**
   * Configuration.
   *
   * @var config_entity
   */
  private $config;

  /**
   * A Database query interface for querying Chado using Tripal DBX.
   *
   * @var \Drupal\tripal_chado\Database\ChadoConnection
   */
  protected ChadoConnection $chado_connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set test environment.
    \Drupal::state()->set('is_a_test_environment', TRUE);

    // Create a test chado instance and then set it in the container for use by our service.
    $this->chado_connection = $this->createTestSchema(ChadoTestKernelBase::PREPARE_TEST_CHADO);

    // Install module configuration.
    $this->installConfig(['trpcultivate_phenotypes']);
    $this->config = \Drupal::configFactory()->getEditable('trpcultivate_phenotypes.settings');

    $this->prepareEnvironment(['TripalTerm']);

    $this->installConfig('trpcultivate');
    trpcultivate_install_terms();

    // Term service.
    $this->service = \Drupal::service('trpcultivate_phenotypes.terms');
  }

  /**
   *
   */
  public function testTermService() {
    // Class was created.
    $this->assertNotNull($this->service);

    // Test defineTerms().
    $define_terms = $this->service->defineTerms();
    $keys = array_keys($define_terms);

    $this->assertNotNull($define_terms);
    $this->assertIsArray($define_terms,
      "We expected defineTerms() to return an array but it did not.");

    // Compare what was defined and the pre-defined terms in the
    // config settings file.
    $term_set = $this->config->get('trpcultivate.default_terms.term_set');
    foreach ($term_set as $id => $terms) {
      foreach ($terms['terms'] as $term) {
        $this->assertNotNull($term['config_map']);
        $this->assertArrayHasKey($term['config_map'], $define_terms,
          "The config_map retrieved from config should match one of the keys from defineTerms().");
      }
    }

    // Test loadTerms().
    $is_loaded = $this->service->loadTerms();
    $this->assertTrue($is_loaded,
      "We expect loadTerms() to return TRUE to indicate it successfully loaded the terms.");

    // Test getTermId().
    $expected = [];
    foreach ($keys as $key) {
      $id = $this->service->getTermId($key);
      $this->assertNotNull($id,
        "We should have been able to retrieve the term based on the config_map value but we were not.");
      $this->assertGreaterThan(0, $id,
        "We expect the value returned from getTermId() to be a valid cvterm_id.");

      // Keep track of our expectations.
      // mapping of config key => [cvterm_id, expected cvterm name].
      $expected[$key] = [
        'cvterm_id' => $id,
        'name' => $define_terms[$key]['name'],
      ];
    }

    $not_valid_keys = [':p', -1, 0, 'abc', 999999, '', 'lorem_ipsum', '.'];
    foreach ($not_valid_keys as $n) {
      // Invalid config name key, will return 0 value.
      $v = $this->service->getTermId($n);
      $this->assertEquals($v, 0);
    }

    // Test values matched to what was loaded into the table.
    $chado = $this->chado_connection;
    foreach ($expected as $config_key => $expected_deets) {
      $expected_cvterm_name = $expected_deets['name'];
      $cvterm_id = $expected_deets['cvterm_id'];
      $query = $chado->select('1:cvterm', 'cvt')
        ->fields('cvt', ['name'])
        ->condition('cvt.cvterm_id', $cvterm_id);
      $cvterm_name = $query->execute()->fetchField();
      $this->assertNotNull($cvterm_name,
        "We should have been able to retrieve the term $expected_cvterm_name using the id $cvterm_id but could not.");
      $this->assertEquals($expected_cvterm_name, $cvterm_name,
        "The name of the cvterm with the id $cvterm_id did not match the one we expected based on the config key $config_key.");
    }

    // #Test saveTermConfigValues().
    // With the loadTerms above, each term configuration was set with
    // a term id number that matches a term in chado.cvterm. This test
    // will set all terms configuration to null (id: 1).
    // This would have came from form submit method.
    $config_values = [];
    foreach (array_keys($define_terms) as $key) {
      $config_values[$key] = 1;
    }

    $is_saved = $this->service->saveTermConfigValues($config_values);
    $this->assertTrue($is_saved,
      "We expected the saveTermConfigValues() method to return TRUE.");

    foreach ($config_values as $key => $set_id) {
      // Test if all config got nulled.
      $retrieved_id = $this->service->getTermId($key);
      $this->assertEquals($set_id, $retrieved_id,
        "We expected the retrieved id to match the one we set it to but it did not.");
    }

    // Nothing to save.
    $not_saved = $this->service->saveTermConfigValues([]);
    $this->assertFalse($not_saved,
      "We should not be able to call saveTermConfigValues() with an empty array.");
  }

}
