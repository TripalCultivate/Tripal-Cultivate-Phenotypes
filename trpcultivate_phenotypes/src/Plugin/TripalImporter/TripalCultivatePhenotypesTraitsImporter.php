<?php

/**
 * @file
 * Tripal Importer Plugin implementation for Tripal Cultivate Phenotypes - Traits Importer.
 */

namespace Drupal\trpcultivate_phenotypes\Plugin\TripalImporter;

use Drupal\tripal_chado\TripalImporter\ChadoImporterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Url;

use Drupal\trpcultivate_phenotypes\Service\TripalCultivatePhenotypesTraitsService;

/**
 * Tripal Cultivate Phenotypes - Traits Importer.
 *
 * An importer for traits with a defined method and unit.
 *
 * @TripalImporter(
 *   id = "trpcultivate-phenotypes-traits-importer",
 *   label = @Translation("Tripal Cultivate: Phenotypic Trait Importer"),
 *   description = @Translation("Loads Traits for phenotypic data into the system. This is useful for large phenotypic datasets to ease the upload process."),
 *   file_types = {"txt","tsv"},
 *   upload_description = @Translation("Please provide a txt or tsv data file."),
 *   upload_title = @Translation("Phenotypic Trait Data File*"),
 *   use_analysis = FALSE,
 *   require_analysis = FALSE,
 *   use_button = True,
 *   submit_disabled = FALSE,
 *   button_text = "Import",
 *   file_upload = TRUE,
 *   file_local = FALSE,
 *   file_remote = FALSE,
 *   file_required = TRUE,
 *   cardinality = 1,
 *   menu_path = "",
 *   callback = "",
 *   callback_module = "",
 *   callback_path = "",
 * )
 */
class TripalCultivatePhenotypesTraitsImporter extends ChadoImporterBase implements ContainerFactoryPluginInterface {
  // Reference the validation result summary values in Drupal storage
  // system using this variable.
  private $validation_result = 'validation_result';

  // Headers required by this importer.
  private $headers = [
    'Trait Name' => 'The name of the trait, as you would like it to appear to the user (e.g. Days to Flower)',
    'Trait Description' => 'A full description of the trait. This is recommended to be at least one paragraph.',
    'Method Short Name' => 'A full, unique title for the method (e.g. Days till 10% of plants/plot have flowers)',
    'Collection Method' => 'A full description of how the trait was collected. This is also recommended to be at least one paragraph.',
    'Unit' => 'The full name of the unit used (e.g. days, centimeters)',
    'Type' => 'One of "Qualitative" or "Quantitative".'
  ];

  // Service: Make the following services available to all stages.
  // Genus Ontology configuration service.
  protected $service_genusontology;

  // Traits service.
  protected $service_traits;

  /**
   * Injection of services through setter methods.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    // Service genus ontology.
    $service_genusontology = $container->get('trpcultivate_phenotypes.genus_ontology');
    // Service traits.
    $service_traits = $container->get('trpcultivate_phenotypes.traits');

    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tripal_chado.database'),
      $service_genusontology,
      $service_traits
    );

    // Call service setter method to set the service.
    $instance->setServiceGenusOntology($service_genusontology);
    $instance->setServiceTraits($service_traits);

    return $instance;
  }

  /**
   * Service setter method:
   * Set genus ontology configuration service.
   *
   * @param $service
   *   Service as created/injected through create method.
   */
  public function setServiceGenusOntology($service) {
    if ($service) {
      $this->service_genusontology = $service;
    }
  }

  /**
   * Service setter method:
   * Set traits service.
   *
   * @param $service
   *   Service as created/injected through create method.
   */
  public function setServiceTraits($service) {
    if ($service) {
      $this->service_traits = $service;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function form($form, &$form_state) {
    // Always call the parent form to ensure Chado is handled properly.
    $form = parent::form($form, $form_state);

    // Validation result.
    $storage = $form_state->getStorage();

    // Full validation result summary.
    if (isset($storage[ $this->validation_result ])) {
      $validation_result = $storage[ $this->validation_result ];

      $form['validation_result'] = [
        '#type' => 'inline_template',
        '#theme' => 'result_window',
        '#data' => [
          'validation_result' => $validation_result
        ],
        '#weight' => -100
      ];
    }

    // This is a reminder to user about expected trait data.
    $phenotypes_minder = t('This importer allows for the upload of phenotypic trait dictionaries in preparation
      for uploading phenotypic data. <br /><strong>This importer Does NOT upload phenotypic measurements.</strong>');
    \Drupal::messenger()->addWarning($phenotypes_minder);

    // Field Genus:
    // Prepare select options with only active genus.
    $all_genus = $this->service_genusontology->getConfiguredGenusList();
    $active_genus = array_combine($all_genus, $all_genus);

    if (!$active_genus) {
      $phenotypes_minder = t('This module is <strong>NOT configured</strong> to import Traits for analyzed phenotypes.');
      \Drupal::messenger()->addWarning($phenotypes_minder);
    }

    // If there is only one genus, it should be the default.
    $default_genus = 0;
    if ($active_genus && count($active_genus) == 1) {
      $default_genus = reset($active_genus);
    }

    // Field genus.
    $form['genus'] = array(
      '#type' => 'select',
      '#title' => 'Genus',
      '#description' => t('The genus of the germplasm being phenotyped with the supplied traits.
        Traits in this system are specific to the genus in order to ensure they are specific enough to accurately describe the phenotypes.
        In order for genus to be availabe here is must be first configured in the Analyzed Phenotypes configuration.'),
      '#empty_option' => '- Select -',
      '#options' => $active_genus,
      '#default_value' => $default_genus,
      '#weight' => -99,
      '#required' => TRUE
    );

    // This importer does not support using file sources from existing field.
    // #access: (bool) Whether the element is accessible or not; when FALSE,
    // the element is not rendered and the user submitted value is not taken
    // into consideration.
    $form['file']['file_upload_existing']['#access'] = FALSE;

    return $form;
  }


  ///


  /**
   * {@inheritdoc}
   */
  public function formSubmit($form, &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function formValidate($form, &$form_state) {
    $form_state_values = $form_state->getValues();

    // Counter, count number of validators that failed.
    $failed_validator = 0;

    // Test validator plugin.
    $manager = \Drupal::service('plugin.manager.trpcultivate_validator');

    // Importer assets.
    // All values will be accessible to every instance of the validator Plugin.
    // This importer does not require a project and this variable is set to 0
    // instruct validators Project + Genus that relations project-genus can be ignored.
    $project = 0;
    $genus = $form_state_values['genus'];
    $file_id = $form_state_values['file_upload'];

    // Make the header columns into a simplified array where the header names
    // are the values
    $headers = array_keys($this->headers);

    // For each of the scopes that pertain to before validating the data rows of
    // a file
    $pre_data_scopes = ['GENUS', 'FILE', 'HEADERS'];

    // Array to hold all validation result for each level.
    // Each result is keyed by the scope.
    $validation = [];

    // ************************************************************************
    // @TODO: Break up this foreach loop to handle each scope differently.
    // Could approach this by iterating through the scopes 'GENUS', 'FILE', and
    // 'HEADERS' and then separately processing the file line by line and feeding
    // it to each of the EmptyCell, DuplicateTraits and ValueInList validators
    // ************************************************************************
    foreach($pre_data_scopes as $scope) {
      // Create instance of the scope-specific plugin and perform validation.
      $validator = $manager->getValidatorIdWithScope($scope);
      $instance = $manager->createInstance($validator);

      // Set other validation level to upcoming/todo if a validation failed.
      $skip = ($failed_validator > 0) ? 1 : 0;

      // Load values.
      $instance->loadAssets($project, $genus, $file_id, $headers, $skip);

      // Perform required level validation.
      $validation[ $scope ] = $instance->validate();

      // Inspect for any failed validation to halt the importer.
      if ($validation[ $scope ]['status'] == 'fail') {
        $failed_validator++;
      }
    }

    // The 'FILE ROW' scope
    // Open the file so we can iterate through the rows
    $file = File::load($file_id);
    // Open and read file in this uri.
    $file_uri = $file->getFileUri();
    $handle = fopen($file_uri, 'r');

    // Line counter.
    $line_no = 0;
    // Line check - line that has value and is not empty.
    $line_check = 0;
    // Array to hold all failed line, sorted by error type.
    $failed_rows = [];
    // Count each time a trait is process. This will be used
    // to check for any duplicate trait name in the same genus.
    $trait_count = [];

    // Take our simplified headers array and flip the array keys and values
    // This is the format that the validators will expect to know which indices
    // in the row of data to act on
    // For example: ['Trait Name'] => 0
    $header_index = array_flip($headers);

    // Begin column and row validation.
    while(!feof($handle)) {
      // Current row.
      $line = fgets($handle);

      // Skip the header for now, since it has been addressed in its own
      // 'HEADERS' scope above. Also skip any empty lines
      if ($line_no > 0 && !empty(trim($line))) {
        $line_check++;

        // Split line into an array
        $data_row = str_getcsv($line, "\t");

        /******************************
         * Validate for Empty Values in columns that are required
         ******************************/
        $check_for_empty['indices'] = [
          'Trait Name' => $header_index['Trait Name'],
          'Method Short Name' => $header_index['Method Short Name'],
          'Unit' => $header_index['Unit'],
          'Type' => $header_index['Type']
        ];

        $validator_id = 'trpcultivate_phenotypes_validator_empty_cell';
        $instance = $manager->createInstance($validator_id);
        $validation['empty_cell'] = $instance->validateRow($data_row, $check_for_empty);
        if('fail' == $validation['empty_cell']['status']) {
          $failed_status['empty_cell']['failed_lines'] = $line_no;
          $failed_validator++;
        }

        //print_r($validation['empty_cell']['status']);

        /******************************
         * Validate for the "Data Type" column to contain one of:
         * Quantitative or Qualitative
         ******************************/
        $check_for_data_type['indices'] = ['Type' => $header_index['Type']];
        $check_for_data_type['valid_values'] = ['Quantitative', 'Qualitative'];

        $validator_id = 'trpcultivate_phenotypes_validator_value_in_list';
        $instance = $manager->createInstance($validator_id);
        $validation['valid_data_type'] = $instance->validateRow($data_row, $check_for_data_type);
        if('fail' == $validation['valid_data_type']['status']) {
          $failed_status['valid_data_type']['failed_lines'] = $line_no;
          $failed_validator++;
        }
        //print_r($validation['valid_data_type']['status']);

        /*******************************
         * Validate for Duplicate Traits
         * This will check that the combination of "Trait Name",
         * "Method Short Name" and "Unit" are unique
         *******************************/
        $check_for_duplicate_traits['indices'] = [
          'Trait Name' => $header_index['Trait Name'],
          'Method Short Name' => $header_index['Method Short Name'],
          'Unit' => $header_index['Unit']
        ];

        $validator_id = 'trpcultivate_phenotypes_validator_duplicate_traits';
        $instance = $manager->createInstance($validator_id);
        $validation['duplicate_traits'] = $instance->validateRow($data_row, $check_for_duplicate_traits);
        if('fail' == $validation['duplicate_traits']['status']) {
          $failed_status['duplicate_traits']['failed_lines'] = $line_no;
          $failed_validator++;
        }
        //print "Validating Row: " . print_r($data_row, TRUE) . " and the result is: " . print_r($validation['duplicate_traits'], TRUE);
      }
      $line_no++;
    }

    // For each validator, check if 'failed_lines' has been set. If so, format the
    // validation message and set the status to 'fail'.
    if (isset($failed_status['empty_cell'])) {
      $validation['empty_cell'] = [
        'title' => 'Required columns were found to be empty',
        'status' => 'fail',
        'details' => ''
      ];
    }
    if (isset($failed_status['valid_data_type'])) {
      $validation['valid_data_type'] = [
        'title' => 'Value in column "type" was not one of "Quantitative" or "Qualitative"',
        'status' => 'fail',
        'details' => '.'
      ];
    }
    if (isset($failed_status['duplicate_traits'])) {
      $validation['duplicate_traits'] = [
        'title' => 'Identical Trait Name + Method Short Name + Unit combination found',
        'status' => 'fail',
        'details' => 'Confirmed that trait(s) on the following lines are duplicates: .'
      ];
    }

    // Save all validation results in Drupal storage to be used by
    // validation window to create summary report.
    $storage = $form_state->getStorage();
    $storage[ $this->validation_result ] = $validation;
    $form_state->setStorage($storage);

    if ($failed_validator > 0) {
      // There are issues in the submission and are detailed in the validation result window.
      // Prevent this form from submitting and reload form with all the validation errors
      // in the storage system.
      $form_state->setRebuild(TRUE);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function run() {
    // Traits service.
    $service_traits = \Drupal::service('trpcultivate_phenotypes.traits');

    // Values provided by user in the importer page.
    // Genus.
    $genus = $this->arguments['run_args']['genus'];
    // Instruct trait service that all trait assets will be contained in this genus.
    $service_traits->setTraitGenus($genus);
    // Traits data file id.
    $file_id = $this->arguments['files'][0]['fid'];
    // Load file object.
    $file = FILE::load($file_id);
    // Open and read file in this uri.
    $file_uri = $file->getFileUri();
    $handle = fopen($file_uri, 'r');

    // Line counter.
    $line_no = 0;
    // Headers.
    $headers = array_keys($this->headers);
    $headers_count = count($headers);

    while(!feof($handle)) {
      // Current row.
      $line = fgets($handle);

      if ($line_no > 0 && !empty(trim($line))) {
        // Line split into individual data point.
        $data_columns = str_getcsv($line, "\t");
        // Sanitize every data in rows and columns.
        $data = array_map(function($col) { return isset($col) ? trim(str_replace(['"','\''], '', $col)) : ''; }, $data_columns);

        // Construct trait array so that each data (value) in a line/row corresponds
        // to the column header (key).
        // ie. ['Trait Name' => data 1, 'Trait Description' => data 2 ...]
        $trait = [];

        // Fill trait metadata: name, description, method, unit and type.
        for ($i = 0; $i < $headers_count; $i++) {
          $trait[ $headers[ $i ] ] = $data[ $i ];
        }

        // Create the trait.

        // NOTE: Loading of this file is performed using a database transaction.
        // If it fails or is terminated prematurely then all insertions and updates
        // are rolled back and will not be found in the database.
        $service_traits->insertTrait($trait);

        unset($data);
      }

      // Next line;
      $line_no++;
    }

    // Close the file.
    fclose($handle);
  }

  /**
   * {@inheritdoc}
   */
  public function postRun() {

  }

  /**
   * {@inheritdoc}
   */
  public function describeUploadFileFormat() {
    // A template file has been generated and is ready for download.
    $importer_id = $this->pluginDefinition['id'];
    $column_headers = array_keys($this->headers);

    $file_link = \Drupal::service('trpcultivate_phenotypes.template_generator')
      ->generateFile($importer_id, $column_headers);

    // Additional notes to the headers.
    $notes = 'The order of the above columns is important and your file must include a header!
    If you have a single trait measured in more then one way (i.e. with multiple collection
    methods), then you should have one line per collection method with the trait repeated.';

    // Render the header notes/lists template and use the file link as
    // the value to href attribute of the link to download a template file.
    $build = [
      '#theme' => 'importer_header',
      '#data' => [
        'headers' => $this->headers,
        'notes' => $notes,
        'template_file' => $file_link
      ]
    ];

    return \Drupal::service('renderer')->renderPlain($build);
  }
}
