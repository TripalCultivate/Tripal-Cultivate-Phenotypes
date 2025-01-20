<?php

namespace Drupal\trpcultivate_phenotypes\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\tripal_chado\Database\ChadoConnection;
use Drupal\tripal\Services\TripalLogger;

/**
 * Phenotypes terms service.
 */
class TripalCultivatePhenotypesTermsService {
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
   * Holds configuration variable names and terms it maps to.
   *
   * @var array
   */
  private $terms;

  /**
   * Configuration hierarchy for configuration: terms.
   *
   * @var string
   */
  private $sysvar_terms;

  /**
   * Tripal logger service.
   *
   * @var Drupal\tripal\Services\TripalLogger
   */
  protected $logger;

  /**
   * Constructor.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TripalLogger $logger,
    ChadoConnection $chado,
  ) {
    // Configuration terms.
    $this->sysvar_terms = 'trpcultivate.phenotypes.ontology.terms';

    // Module Configuration variables.
    $module_settings = 'trpcultivate_phenotypes.settings';
    $this->config = $config_factory->getEditable($module_settings);

    // Tripal Logger service.
    $this->logger = $logger;

    // Chado database.
    $this->chado_connection = $chado;

    // Prepare array of default terms from configuration definition.
    $this->terms = $this->defineTerms();
  }

  /**
   * Define terms.
   *
   * Each term set is defined using the array structure below:
   * Format:
   *   cv - 1 name
   *   cv - 1 definition
   *   terms
   *     config_map // Maps to which configuration variable.
   *     term - 1 name
   *     term - 1 id
   *     term - 1 definition.
   *
   *     config_map
   *     term - 2 name
   *     term - 2 id
   *     term - 2 definition
   *
   *     ....
   *   ...
   *
   * @see config/schema
   *
   * @return array
   *   All configuration entity values keyed by configuration map value.
   */
  public function defineTerms() {
    $terms = [];

    // Fetch all terms in the terms config_entity and prepare an associative
    // array where each element is keyed by the configuration map value.
    $default_terms = $this->config->get('trpcultivate.default_terms.term_set');

    // Terms are not available as configuration values until job to install
    // terms has been executed. This value is null to start with.
    if ($default_terms) {
      foreach ($default_terms as $cv) {
        foreach ($cv['terms'] as $term_set) {
          // Add the cv information of the term.
          $term_set['cv'] = ['name' => $cv['name'], 'definition' => $cv['definition']];
          // Access a term by configuration map value.
          // ie: term['experiment_container'].
          $terms[$term_set['config_map']] = $term_set;
        }
      }
    }

    return $terms;
  }

  /**
   * Insert and create term configuration variable.
   *
   * @return bool
   *   True if all terms were inserted successfully and false otherwise.
   */
  public function loadTerms($schema = NULL) {
    $error = 0;
    $terms = $this->terms;

    if ($terms) {
      // Install terms.
      foreach ($terms as $config_map => $config_prop) {
        // Remove cv information.
        unset($config_prop['cv']);
        // Remove term field_label text.
        unset($config_prop['field_label']);

        [$idspace, $accession] = explode(':', $config_prop['id']);
        $query = $this->chado_connection->select('1:cvterm', 'cvt')
          ->fields('cvt', ['cvterm_id']);
        $query->join('1:dbxref', 'dbx', 'cvt.dbxref_id = dbx.dbxref_id');
        $query->join('1:db', 'db', 'dbx.db_id = db.db_id');
        $query = $query->condition('dbx.accession', $accession, '=')
          ->condition('db.name', $idspace, '=');
        $exists = $query->execute()->fetchObject();
        if (empty($exists)) {
          $cvterm = chado_insert_cvterm($config_prop, [], $schema);
          $cvterm_id = $cvterm->cvterm_id;
        }
        else {
          $cvterm_id = $exists->cvterm_id;
        }

        // Set the term id as the configuration value of the
        // term configuration variable.
        if ($cvterm_id) {
          $this->config
            ->set($this->sysvar_terms . '.' . $config_map, $cvterm_id);
        }
        else {
          // Error inserting term.
          $error = 1;
          $this->logger->error('Phenotypes Term Service could not insert term: ' . $config_prop['name'] . ' (' . $config_prop['id'] . ')');
        }
      }
    }

    if (!$error) {
      // Save all configuration values.
      $this->config->save();
    }

    return ($error) ? FALSE : TRUE;
  }

  /**
   * Retrieves the ID of the term configured for a specific role.
   *
   * It is expected that the administrator can configure these terms. As such
   * this method will pull the value from configuration rather then look it up
   * in the database.
   *
   * @param string $term_key
   *   The unique identifier for the term of interest. This should be one of:
   *   - data_collector: Data Collector.
   *   - entry: Entry Number/Information.
   *   - genus: Organism.
   *   - location: Location.
   *   - name: Name/Germplasm line.
   *   - experiment_container: Plot.
   *   - unit_to_method_relationship_type: Related - create relationships
   *     (unit - method).
   *   - method_to_trait_relationship_type: Related - create relationships
   *     (method - trait).
   *   - experiment_replicate: Planting replicate.
   *   - unit_type: Unit of measurement.
   *   - experiment_year: Year.
   *   - trait_to_synonym_relationship_type: Prefered term.
   *
   * @see schema/trpcultivate_phenotypes.schema.yml
   *
   * @return int
   *   The chado cvterm_id for the term associated with that key.
   *   0 if non-existent configuration name/key.
   */
  public function getTermId(string $term_key) {
    $id = 0;
    $term_key = trim($term_key);

    if ($term_key && $this->terms) {
      $valid_term_keys = array_keys($this->terms);
      if (!empty($term_key) && in_array($term_key, $valid_term_keys)) {
        $id = $this->config->get($this->sysvar_terms . '.' . $term_key);
      }
    }

    return $id;
  }

  /**
   * Save term configuration values.
   *
   * @param array $config_values
   *   Configuration values submitted from a form implementation.
   *   Each element is keyed by the field name which is the configuration
   *   variable name and the value being the value as set in
   *   corresponding form field resolved to id number.
   *
   * For example, the following indicates that the chado.cvterm.cvterm_id for the
   * `name` configuration variable is `1`. This resolves to the `Null` cvterm
   * when that cvterm_id is looked up in the cvterm table.
   * @code
   * $config_values['name'] = 1;
   * @endcode
   *
   * @return bool
   *   True if configuration saved successfully and False on error.
   */
  public function saveTermConfigValues($config_values) {
    $error = 0;

    if (!empty($config_values) && is_array($config_values) && $this->terms) {
      $term_keys = array_keys($this->terms);

      foreach ($config_values as $config => $value) {
        // Make sure config name exists before saving a value.
        if (in_array($config, $term_keys)) {
          $this->config
            ->set($this->sysvar_terms . '.' . $config, $value);
        }
        else {
          $this->logger->error('Error. Failed to save configuration: ' . $config . '=' . $value);
          $error = 1;
          break;
        }
      }

      // Save all configuration values.
      if ($error == 0) {
        $this->config->save();
      }
    }
    else {
      $error = 1;
    }

    return ($error > 0) ? FALSE : TRUE;
  }

}
