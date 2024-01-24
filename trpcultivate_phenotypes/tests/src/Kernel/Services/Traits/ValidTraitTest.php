<?php

/**
 * @file
 * Kernel test of Traits service.
 */

namespace Drupal\Tests\trpcultivate_phenotypes\Kernel\Services\Traits;

use Drupal\Tests\tripal_chado\Kernel\ChadoTestKernelBase;
use Drupal\tripal\Services\TripalLogger;

/**
 * Tests that a valid trait/method/unit combination can be inserted/retrieved.
 *
 * @group trpcultivate_phenotypes
 * @group services
 * @group traits
 */
class ValidTraitTest extends ChadoTestKernelBase {
  /**
   * Plugin Manager service.
   */
  protected $service_traits;

  /**
   * Tripal DBX Chado Connection object
   *
   * @var ChadoConnection
   */
  protected $chado;

  // Genus.
  private $genus;

  /**
   * Modules to enable.
   */
  protected static $modules = [
   'tripal',
   'tripal_chado',
   'trpcultivate_phenotypes'
  ];

  /**
   * Term config key to cvterm_id mapping.
   * Note: we just grabbed some random cvterm_ids that we know for sure exist.
   */
  protected array $terms = [
    'method_to_trait_relationship_type' => 100,
    'unit_to_method_relationship_type' => 200,
    'trait_to_synonym_relationship_type' => 300,
    'unit_type' => 400,
  ];

  /**
   * CV and DB's configured for this genus.
   * NOTE: We will create these in the setUp.
   */
  protected array $genus_ontology_config = [
    'trait' => NULL,
    'unit'   => NULL,
    'method'  => NULL,
    'database' => NULL,
    'crop_ontology' => NULL
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() :void {
    parent::setUp();

    // Set test environment.
    \Drupal::state()->set('is_a_test_environment', TRUE);

    // Test Chado database.
    // Create a test chado instance and then set it in the container for use by our service.
    $this->chado = $this->createTestSchema(ChadoTestKernelBase::PREPARE_TEST_CHADO);
    $this->container->set('tripal_chado.database', $this->chado);
    // Remove services from the container that were initialized before the above chado.
    $this->container->set('trpcultivate_phenotypes.genus_ontology', NULL);
    $this->container->set('trpcultivate_phenotypes.terms', NULL);

    // Install module configuration.
    $this->installConfig(['trpcultivate_phenotypes']);
    $this->config = \Drupal::configFactory()->getEditable('trpcultivate_phenotypes.settings');

    // Set ontology terms in the configuration used by the terms service.
    foreach($this->terms as $config_key => $cvterm_id) {
      $this->config->set('trpcultivate.phenotypes.ontology.terms.' . $config_key, $cvterm_id);
    }

    // Create an chado organism genus.
    // This will be configured below to become the active genus.
    $genus = 'Wild Genus ' . uniqid();
    $this->genus = $genus;
    $organism_id = $this->chado->insert('1:organism')
      ->fields([
        'genus' => $genus,
        'species' => 'Wild Species',
      ])
      ->execute();
    $this->assertIsNumeric($organism_id,
      "We were unable to create the organism record in chado.");

    // Create Genus Ontology configuration.
    // First create a cv or db for each...
    $config_name = str_replace(' ', '_', strtolower($genus));
    foreach ($this->genus_ontology_config as $key => $id) {
      $name = $genus . ' ' . $key;
      $table = ($key == 'database') ? '1:db' : '1:cv';
      $id = $this->chado->insert($table)->fields(['name' => $name])->execute();
      $this->assertIsNumeric($id,
        "Unable to create a record in '$table' for $key where name = '$name'");
      $this->genus_ontology_config[$key] = $id;
    }
    // Then save the configuration set to the new ids.
    $this->config->set('trpcultivate.phenotypes.ontology.cvdbon.' . $config_name, $this->genus_ontology_config);

    // Set the traits service.
    $this->service_traits = \Drupal::service('trpcultivate_phenotypes.traits');

    // Install required dependencies - T3 legacy functions.
    $tripal_chado_path = 'modules/contrib/tripal/tripal_chado/src/api/';
    $tripal_chado_api = [
      'tripal_chado.cv.api.php',
      'tripal_chado.variables.api.php',
      'tripal_chado.schema.api.php'
    ];
    if ($handle = opendir($tripal_chado_path)) {
      while (false !== ($file = readdir($handle))) {
        if (strlen($file) > 2 && in_array($file, $tripal_chado_api)) {
          include_once($tripal_chado_path . $file);
        }
      }

      closedir($handle);
    }
  }

  /**
   * Test traits service.
   */
  public function testTraitsService() {
    // Create a trait, unit and method test records.

    // As defined by headers property in the importer.
    $headers = [
      'Trait Name',
      'Trait Description',
      'Method Short Name',
      'Collection Method',
      'Unit',
      'Type'
    ];

    // As with the traits importer, values are:
    // Trait Name, Trait Description, Method Short Name, Collection Method, Unit and Type.
    // This is a tsv string similar to a line/row in traits data file.
    $trait  = 'TraitABC'  . uniqid();
    $method = 'MethodABC' . uniqid();
    $unit   = 'UnitABC'   . uniqid();

    $insert_trait = [
      $trait,
      $trait  . ' Description',
      $method . '-SName',
      $method . ' - Pull from ground',
      $unit,
      'Quantitative'
    ];

    $line = implode("\t", $insert_trait);

    // Split tsv to data points and map to headers array where the key is the header
    // and value is the corresponding data point.
    $data_columns = str_getcsv($line, "\t");
    // Sanitize every data in rows and columns.
    $data = array_map(function($col) { return isset($col) ? trim(str_replace(['"','\''], '', $col)) : ''; }, $data_columns);

    $trait = [];
    $headers_count = count($headers);

    for ($i = 0; $i < $headers_count; $i++) {
      $trait[ $headers[ $i ] ] = $data[ $i ];
    }

    // Set genus to use by the traits service.
    $this->service_traits->setTraitGenus($this->genus);
    // Save the trait.
    $trait_assets = $this->service_traits->insertTrait($trait);

    // Trait, method and unit.
    $sql = "SELECT * FROM {1:cvterm} WHERE cvterm_id = :id LIMIT 1";

    foreach($trait_assets as $type => $value) {
      // Retrieve the cvterm with the cvterm_di returned by the service.
      $rec = $this->chado->query($sql, [':id' => $value])
        ->fetchObject();
      $this->assertIsObject($rec,
        "We were unable to retrieve the $type record from chado based on the cvterm_id $value provided by the service.");

      // The was configured in setUp and is keyed by the type.
      $expected_cv = $this->genus_ontology_config[$type];
      // Ensure it was inserted into the correct cv the genus is configured for.
      $this->assertEquals($expected_cv, $rec->cv_id,
        "Failed to insert $type into cv genus is configured.");

      // Check that the name of the cvterm is as we expect.
      $expected_name = NULL;
      if ($type == 'trait')  $expected_name = $trait['Trait Name'];
      if ($type == 'method')  $expected_name = $trait['Method Short Name'];
      if ($type == 'unit')  $expected_name = $trait['Unit'];
      $this->assertEquals($expected_name, $rec->name,
        "The name in the database for the $type did not match the one we expected.");
    }

    // Test relationships.
    $sql = "SELECT cvterm_relationship_id FROM {1:cvterm_relationship}
      WHERE subject_id = :s_id AND type_id = :t_id AND object_id = :o_id";

    // Method - trait.
    // @todo this relationship is currently in the wrong order.
    $rec = $this->chado->query($sql, [
      ':s_id' => $trait_assets['trait'],
      ':t_id' => $this->terms['method_to_trait_relationship_type'],
      ':o_id' => $trait_assets['method']
    ]);
    $this->assertNotNull($rec,
      'Failed to insert a relationship between the method and it\'s trait.');

    // Method - unit.
    // @todo this relationship is currently in the wrong order.
    $rec = $this->chado->query($sql, [
      ':s_id' => $trait_assets['method'],
      ':t_id' => $this->terms['unit_to_method_relationship_type'],
      ':o_id' => $trait_assets['unit']
    ]);
    $this->assertNotNull($rec,
      'Failed to insert a relationship between the method and it\'s unit.');

    // Test unit data type.
    $sql = "SELECT cvtermprop_id, value FROM {1:cvtermprop} WHERE cvterm_id = :c_id AND type_id = :t_id LIMIT 1";
    $data_type = $this->chado->query($sql, [
      ':c_id' => $trait_assets['unit'],
      ':t_id' => $this->terms['unit_type'],
      ])->fetchObject();

    $this->assertNotNull($data_type, 'Failed to insert unit property - additional type.');
    $this->assertEquals($data_type->value, 'Quantitative', 'Unit property - additional type does not match expected value (Quantitative).');

    // Test get trait.

    $trait_id = $trait_assets['trait'];
    $trait_name = $insert_trait[0];

    // Get trait by id.
    $t = $this->service_traits->getTrait(['id' => $trait_id]);
    $this->assertEquals($trait_name, $t->name, 'Trait not found (by trait id).');

    // Get trait by name.
    $t = $this->service_traits->getTrait(['name' => $trait_name]);
    $this->assertEquals($trait_name, $t->name, 'Trait not found (by trait name).');

    // Test get trait method.
    $method_name = $insert_trait[2];

    // Get trait method by trait id.
    $m = $this->service_traits->getTraitMethod(['id' => $trait_id]);
    $this->assertEquals($method_name, $m[0]->name, 'Trait method not found (by trait id).');

    // Get trait method by trait name.
    $m = $this->service_traits->getTraitMethod(['name' => $trait_name]);
    $this->assertEquals($method_name, $m[0]->name, 'Trait method not found (by trait name).');
    $method_id = $m[0]->cvterm_id;

    // Test get trait method unit.

    $unit_name = $insert_trait[4];
    $u = $this->service_traits->getMethodUnit($method_id);
    $this->assertEquals($unit_name, $u->name, 'Trait method unit not found.');
    $unit_id = $u->cvterm_id;

    // Test get unit data type.
    $data_type = $insert_trait[5];
    $dt = $this->service_traits->getMethodUnitDataType($unit_id);
    $this->assertEquals($data_type, $dt, 'Trait method unit data type does not match expected.');
  }
}
