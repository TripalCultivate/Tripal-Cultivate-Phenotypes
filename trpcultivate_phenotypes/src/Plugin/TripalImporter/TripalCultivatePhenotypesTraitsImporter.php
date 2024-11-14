<?php

namespace Drupal\trpcultivate_phenotypes\Plugin\TripalImporter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Drupal\tripal_chado\Database\ChadoConnection;
use Drupal\tripal_chado\TripalImporter\ChadoImporterBase;
use Drupal\trpcultivate_phenotypes\Service\TripalCultivatePhenotypesGenusOntologyService;
use Drupal\trpcultivate_phenotypes\Service\TripalCultivatePhenotypesTraitsService;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\TripalCultivatePhenotypesValidatorBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tripal Cultivate Phenotypes - Traits Importer.
 *
 * An importer for traits with a defined method and unit.
 *
 * @TripalImporter(
 *   id = "trpcultivate-phenotypes-traits-importer",
 *   label = @Translation("Tripal Cultivate: Phenotypic Trait Importer"),
 *   description = @Translation("Loads Traits for phenotypic data into the system. This is useful for large phenotypic datasets to ease the upload process."),
 *   file_types = {"tsv"},
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

  /**
   * Headers required by this importer.
   *
   * @var array
   *
   * The following keys are required:
   * - 'name': The column header name as it should appear in the input file.
   * - 'description': A user-friendly description of the header that will be
   *   displayed to the user through the form.
   * - 'type': one of "required" or "optional" to indicate whether the column
   *   needs to have values present or not.
   *
   * NOTE: Order MUST reflect the desired order of headers in the input file.
   */
  private $headers = [
    [
      'name' => 'Trait Name',
      'description' => 'The name of the trait, as you would like it to appear to the user (e.g. Days to Flower)',
      'type' => 'required',
    ],
    [
      'name' => 'Trait Description',
      'description' => 'A full description of the trait. This is recommended to be at least one paragraph.',
      'type' => 'required',
    ],
    [
      'name' => 'Method Short Name',
      'description' => 'A full, unique title for the method (e.g. Days till 10% of plants/plot have flowers)',
      'type' => 'required',
    ],
    [
      'name' => 'Collection Method',
      'description' => 'A full description of how the trait was collected. This is also recommended to be at least one paragraph.',
      'type' => 'required',
    ],
    [
      'name' => 'Unit',
      'description' => 'The full name of the unit used (e.g. days, centimeters)',
      'type' => 'required',
    ],
    [
      'name' => 'Type',
      'description' => 'One of "Qualitative" or "Quantitative".',
      'type' => 'required',
    ],
  ];

  /**
   * A Database query interface for querying Chado using Tripal DBX.
   *
   * @var Drupal\tripal_chado\Database\ChadoConnection
   */
  protected ChadoConnection $chado_connection;

  /**
   * Genus Ontology service.
   *
   * @var \Drupal\trpcultivate_phenotypes\Service\TripalCultivatePhenotypesGenusOntologyService
   */
  protected TripalCultivatePhenotypesGenusOntologyService $service_PhenoGenusOntology;

  /**
   * Traits service.
   *
   * @var \Drupal\trpcultivate_phenotypes\Service\TripalCultivatePhenotypesTraitsService
   */
  protected TripalCultivatePhenotypesTraitsService $service_PhenoTraits;

  /**
   * Used to reference the validation result summary in the form.
   *
   * @var string
   */
  private $validation_result = 'validation_result';

  /**
   * Constructs the traits importer.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param Drupal\tripal_chado\Database\ChadoConnection $chado_connection
   *   The connection to the Chado database.
   * @param \Drupal\trpcultivate_phenotypes\Service\TripalCultivatePhenotypesGenusOntologyService $service_PhenoGenusOntology
   *   The genus ontology service.
   * @param \Drupal\trpcultivate_phenotypes\Service\TripalCultivatePhenotypesTraitsService $service_PhenoTraits
   *   The traits service.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    ChadoConnection $chado_connection,
    TripalCultivatePhenotypesGenusOntologyService $service_PhenoGenusOntology,
    TripalCultivatePhenotypesTraitsService $service_PhenoTraits,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $chado_connection);

    // Call service setter method to set the service.
    $this->setServiceGenusOntology($service_PhenoGenusOntology);
    $this->setServiceTraits($service_PhenoTraits);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('tripal_chado.database'),
      $container->get('trpcultivate_phenotypes.genus_ontology'),
      $container->get('trpcultivate_phenotypes.traits'),
    );
  }

  /**
   * Configure all the validators this importer uses.
   *
   * @param array $form_values
   *   An array of the importer form values provided to formValidate.
   * @param string $file_mime_type
   *   A string of the MIME type of the input file, usually grabbed from the
   *   file object using $file->getMimeType()
   *
   * @return array
   *   A listing of configured validator objects first keyed by
   *   their inputType. More specifically,
   *   - [inputType]: and array of validator instances. Not an
   *     associative array although the keys do indicate what
   *     order they should be run in.
   */
  public function configureValidators(array $form_values, string $file_mime_type) {

    $validators = [];

    // Setup the plugin manager.
    $manager = \Drupal::service('plugin.manager.trpcultivate_validator');

    // Grab the genus from our form to use in configuring some validators.
    $genus = $form_values['genus'];

    // Make the header columns into a simplified array for easy reference:
    // - Keyed by the column header name.
    // - Values are the column header's position in the $headers property (ie.
    //   its index if we assume no keys were assigned).
    $header_index = [];
    $headers = $this->headers;
    foreach ($headers as $i => $column_details) {
      $header_index[$column_details['name']] = $i;
    }

    // -----------------------------------------------------
    // Metadata
    // - Genus exists and is configured
    $instance = $manager->createInstance('genus_exists');
    $validators['metadata']['genus_exists'] = $instance;

    // -----------------------------------------------------
    // File level
    // - File exists and is the expected type
    $instance = $manager->createInstance('valid_data_file');
    // Set supported mime-types using the valid file extensions (file_types) as
    // defined in the annotation for this importer on line 28.
    $supported_file_extensions = $this->plugin_definition['file_types'];
    $instance->setSupportedMimeTypes($supported_file_extensions);
    $validators['file']['valid_data_file'] = $instance;

    // -----------------------------------------------------
    // Raw row level
    // - File rows are properly delimited
    $instance = $manager->createInstance('valid_delimited_file');
    // Count the number of columns and configure it for this validator. We want
    // this number to be strict = TRUE, thus no extra columns are allowed.
    $num_columns = count($this->headers);
    $instance->setExpectedColumns($num_columns, TRUE);
    // Set the MIME type of this input file.
    $instance->setFileMimeType($file_mime_type);
    $validators['raw-row']['valid_delimited_file'] = $instance;

    // -----------------------------------------------------
    // Header Level
    // - All column headers match expected header format
    $instance = $manager->createInstance('valid_headers');
    // Use our $headers property to configure what we expect for a header in the
    // input file.
    $instance->setHeaders($this->headers);
    // Configure the expected number of columns and set it to be strict.
    $instance->setExpectedColumns($num_columns, TRUE);
    $validators['header-row']['valid_header'] = $instance;

    // -----------------------------------------------------
    // Data Row Level
    // - All data row cells in columns 0,2,4 are not empty
    $instance = $manager->createInstance('empty_cell');
    $indices = [
      $header_index['Trait Name'],
      $header_index['Method Short Name'],
      $header_index['Unit'],
      $header_index['Type'],
    ];
    $instance->setIndices($indices);
    $validators['data-row']['empty_cell'] = $instance;

    // - The column 'Type' is one of "Qualitative" and "Quantitative"
    $instance = $manager->createInstance('value_in_list');
    $instance->setIndices([$header_index['Type']]);
    $instance->setValidValues([
      'Quantitative',
      'Qualitative',
    ]);
    $validators['data-row']['valid_data_type'] = $instance;

    // - The combination of Trait Name, Method Short Name and Unit is unique
    $instance = $manager->createInstance('duplicate_traits');
    // Set the logger since this validator uses a setter (setConfiguredGenus)
    // which may log messages.
    $instance->setLogger($this->logger);
    $instance->setConfiguredGenus($genus);
    $instance->setIndices([
      'Trait Name' => $header_index['Trait Name'],
      'Method Short Name' => $header_index['Method Short Name'],
      'Unit' => $header_index['Unit'],
    ]);
    $validators['data-row']['duplicate_traits'] = $instance;

    return $validators;
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
    if (isset($storage[$this->validation_result])) {
      $validation_result = $storage[$this->validation_result];

      $form['validation_result'] = [
        '#type' => 'inline_template',
        '#theme' => 'result_window',
        '#data' => [
          'validation_result' => $validation_result,
        ],
        '#weight' => -100,
      ];
    }

    // This is a reminder to user about expected trait data.
    $phenotypes_minder = t('This importer allows for the upload of phenotypic trait dictionaries in preparation
      for uploading phenotypic data. <br /><strong>This importer Does NOT upload phenotypic measurements.</strong>');
    \Drupal::messenger()->addWarning($phenotypes_minder);

    // Field Genus:
    // Prepare select options with only active genus.
    $all_genus = $this->service_PhenoGenusOntology->getConfiguredGenusList();
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
    $form['genus'] = [
      '#type' => 'select',
      '#title' => 'Genus',
      '#description' => t('The genus of the germplasm being phenotyped with the supplied traits.
        Traits in this system are specific to the genus in order to ensure they are specific enough to accurately describe the phenotypes.
        In order for genus to be available here, it must be first configured in the Analyzed Phenotypes configuration.'),
      '#empty_option' => '- Select -',
      '#options' => $active_genus,
      '#default_value' => $default_genus,
      '#weight' => -99,
      '#required' => TRUE,
    ];

    // This importer does not support using file sources from existing field.
    // #access: (bool) Whether the element is accessible or not; when FALSE,
    // the element is not rendered and the user submitted value is not taken
    // into consideration.
    $form['file']['file_upload_existing']['#access'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formSubmit($form, &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function formValidate($form, &$form_state) {

    $form_values = $form_state->getValues();

    $file_id = $form_values['file_upload'];

    // Load our file object.
    $file = File::load($file_id);

    // Get the mime type which is used to validate the file and split the rows.
    $file_mime_type = $file->getMimeType();

    // Configure the validators.
    $validators = $this->configureValidators($form_values, $file_mime_type);

    // A FLAG to keep track if any validator fails.
    // We will only continue to the next input-type if all validators of the
    // current input-type pass.
    $failed_validator = FALSE;

    // Keep track of failed items. This is a nested array keyed as follows:
    // - The unique name of a validator instance, which maps to the second level
    //   of the $validators array.
    //   - For row-level input-type validators, this is further keyed by the
    //     row number that the failure for this validator instance occurred.
    // The value (level 1 for non row-level validators, level 2 for row-level
    // validators) is the validation results array returned by the validator.
    $failures = [];

    // ************************************************************************
    // Metadata Validation
    // ************************************************************************
    foreach ($validators['metadata'] as $validator_name => $validator) {
      // Set failures for this validator name to an empty array to signal that
      // this validator has been run.
      $failures[$validator_name] = [];
      // Validate metadata input value.
      $result = $validator->validateMetadata($form_values);

      // Check if validation failed and save the results if it did.
      if (array_key_exists('valid', $result) && $result['valid'] === FALSE) {
        $failed_validator = TRUE;
        $failures[$validator_name] = $result;
      }
    }

    // Check if any previous validators failed before moving on to the next
    // input-type validation.
    if ($failed_validator === FALSE) {
      // **********************************************************************
      // File Validation
      // **********************************************************************
      foreach ($validators['file'] as $validator_name => $validator) {
        // Set failures for this validator name to an empty array to signal that
        // this validator has been run.
        $failures[$validator_name] = [];
        $result = $validator->validateFile('', $file_id);

        // Check if validation failed and save the results if it did.
        if (array_key_exists('valid', $result) && $result['valid'] === FALSE) {
          $failed_validator = TRUE;
          $failures[$validator_name] = $result;
        }
      }
    }

    // Check if any previous validators failed before moving on to the next
    // input-type validation.
    if ($failed_validator === FALSE) {

      // Open and read file in this uri.
      $file_uri = $file->getFileUri();
      $handle = fopen($file_uri, 'r');

      // Line counter.
      $line_no = 0;

      // Begin column and row validation.
      while (!feof($handle)) {
        // This variable will indicate if the validator has failed. It is set to
        // FALSE for every row to indicate that the line is valid to start with,
        // then execute the tests below to prove otherwise.
        $row_has_failed = FALSE;

        // Current row.
        $line = fgets($handle);
        $line_no++;
        // Skip this line if its empty, but line numbers should remain accurate.
        if (empty(trim($line))) {
          continue;
        }

        // ********************************************************************
        // Raw Row Validation
        // ********************************************************************
        foreach ($validators['raw-row'] as $validator_name => $validator) {
          // Set failures for this validator name to an empty array to signal
          // that this validator has been run.
          if (!array_key_exists($validator_name, $failures)) {
            $failures[$validator_name] = [];
          }

          $result = $validator->validateRawRow($line);

          // Check if validation failed and save the results if it did.
          if (array_key_exists('valid', $result) && $result['valid'] === FALSE) {
            $row_has_failed = TRUE;
            $failures[$validator_name][$line_no] = $result;
          }
        }

        // If any raw-row validators failed, skip further validation and move
        // on to the next row in the data file.
        if ($row_has_failed === TRUE) {
          $failed_validator = TRUE;
          continue;
        }

        // ********************************************************************
        // Header Row Validation
        // ********************************************************************
        if ($line_no == 1) {
          // Split line into an array of values.
          $header_row = TripalCultivatePhenotypesValidatorBase::splitRowIntoColumns($line, $file_mime_type);

          foreach ($validators['header-row'] as $validator_name => $validator) {
            // Set failures for this validator name to an empty array to signal
            // that this validator has been run.
            if (!array_key_exists($validator_name, $failures)) {
              $failures[$validator_name] = [];
            }

            $result = $validator->validateRow($header_row);

            // Check if validation failed and save the results if it did.
            if (array_key_exists('valid', $result) && $result['valid'] === FALSE) {
              $row_has_failed = TRUE;
              $failures[$validator_name] = $result;
            }
          }

          // If any header-row validators failed, skip validation of the data
          // rows.
          if ($row_has_failed === TRUE) {
            $failed_validator = TRUE;
            break;
          }
        }

        // ********************************************************************
        // Data Row Validation
        // ********************************************************************
        elseif ($line_no > 1) {
          // Split line into an array using the delimiter supported by this
          // importer when it was configured.
          $data_row = TripalCultivatePhenotypesValidatorBase::splitRowIntoColumns($line, $file_mime_type);

          // Call each validator on this row of the file.
          foreach ($validators['data-row'] as $validator_name => $validator) {
            // Set failures for this validator name to an empty array to signal
            // that this validator has been run, but ONLY if it doesn't exist.
            // (ie. this validator may have already failed on a previous row, so
            // we don't want to overwrite previous validation failures.)
            if (!array_key_exists($validator_name, $failures)) {
              $failures[$validator_name] = [];
            }
            $result = $validator->validateRow($data_row);
            // Check if validation failed.
            if (array_key_exists('valid', $result) && $result['valid'] === FALSE) {
              $row_has_failed = TRUE;
              $failed_validator = TRUE;
              $failures[$validator_name][$line_no] = $result;
            }
          }
        }
      }
      // Close the file.
      fclose($handle);
    }

    $validation_feedback = $this->processValidationMessages($failures);

    // Save all validation results in Drupal storage to create a summary report.
    $storage = $form_state->getStorage();
    $storage[$this->validation_result] = $validation_feedback;
    $form_state->setStorage($storage);

    if ($failed_validator === TRUE) {
      // Prevent this form from submitting and reload form with all the
      // validation failures in the storage system.
      $form_state->setRebuild(TRUE);
    }
  }

  /**
   * Configures and processes validation messages for the user.
   *
   * @param array $failures
   *   An array containing the return values from any failed validators, keyed
   *   by the unique name assigned to each validator-input type combination, and
   *   further keyed by row number IF the validator was run on each data row.
   *
   * @return array
   *   An array of feedback to provide to the user. It summarizes the validation
   *   results reported by the validators in formValidate (i.e. $failures). This
   *   array is keyed by a string that is associated with a line in the validate
   *   UI. Specifically:
   *   - 'validation_line': A string associated with a line that will be
   *     displayed to the user in the validate UI
   *     - 'title': A user-focussed message describing the validation that took
   *       place.
   *     - 'details': A user-focussed message describing the failure that
   *       occurred and any relevant details to help the user fix it.
   *     - 'status': One of: 'todo', 'pass', 'fail'.
   *     - 'raw_results': A nested array keyed by validator name, which contains
   *       the raw return values when validation failed. Essentially, the
   *       contents of $failures['validator_name'].
   */
  public function processValidationMessages($failures) {
    // Array to hold all the user feedback. Currently this includes an entry for
    // each validator. However, in future designs we may combine more then one
    // validator into a single line in the validate UI and, thus, a single entry
    // in this array. Everything is set to status of 'todo' to start and will
    // only change to one of 'pass' or 'fail' if the $failures[] array is
    // defined for that validator, indicating that validation did take place.
    // IMPORTANT: Order matters here and is not necessarily reflective of the
    // order that validators are run in. Think of these validators as being in 2
    // groups: Validators that get run once, and ones that get run for every
    // line in the input file.
    $messages = [
      // ----------------------- Validators run once ---------------------------
      // ----------------------------- METADATA --------------------------------
      'genus_exists' => [
        'title' => 'The genus is valid',
        'status' => 'todo',
        'details' => '',
      ],
      // ------------------------------- FILE ----------------------------------
      'valid_data_file' => [
        'title' => 'File is valid and not empty',
        'status' => 'todo',
        'details' => '',
      ],
      // ---------------------------- HEADER ROW -------------------------------
      'valid_header' => [
        'title' => 'File has all of the column headers expected',
        'status' => 'todo',
        'details' => '',
      ],
      // --------------------- Validators run per row --------------------------
      // ----------------------------- RAW ROW ---------------------------------
      'valid_delimited_file' => [
        'title' => 'Row is properly delimited',
        'status' => 'todo',
        'details' => '',
      ],
      // ----------------------------- DATA ROW --------------------------------
      'empty_cell' => [
        'title' => 'Required cells contain a value',
        'status' => 'todo',
        'details' => '',
      ],
      'valid_data_type' => [
        'title' => 'Values in required cells are valid',
        'status' => 'todo',
        'details' => '',
      ],
      'duplicate_traits' => [
        'title' => 'All trait-method-unit combinations are unique',
        'status' => 'todo',
        'details' => '',
      ],
    ];

    // @todo an alternative way to identify the validators input type.
    $raw_row_validators = [
      'valid_delimited_file',
    ];

    $raw_row_failed = FALSE;

    foreach (array_keys($messages) as $validator_name) {
      // Check if this validator exists in the failures array, which indicates
      // it was run. If it was not run then continue as it is already marked.
      // @todo in $messages above.
      if (!array_key_exists($validator_name, $failures)) {
        continue;
      }

      // ----------------------------- PASS ----------------------------------
      if (empty($failures[$validator_name])) {
        // Check if $failures[$validator_name] is empty, which indicates there
        // are no errors to report for this validator.
        // If raw row validation fails at any point, make sure the data row
        // validators are not set to 'pass' and remain as 'todo' since they
        // haven't been run on every line. This approach works because the
        // order of the validators in the default messages array ensures
        // that the raw row validators are checked for failures directly
        // before the data row validators. It also assumes that only data row
        // validators are after raw row validators in the messages array.
        if (!$raw_row_failed) {
          $messages[$validator_name]['status'] = 'pass';
        }
      }

      // ----------------------------- FAIL ----------------------------------
      elseif (array_key_exists('case', $failures[$validator_name])) {
        // Check if $failures[$validator_name] contains one of the results
        // keys, indicating that this is not a row-level validator and therefore
        // doesn't keep track of line numbers.
        // @todo Update the message to not use the 'case' string by default
        // and to incorporate the 'failed_details'.
        $messages[$validator_name]['status'] = 'fail';

        $case_message = $failures[$validator_name]['case'];
        $messages[$validator_name]['details'] = $case_message;
        $messages[$validator_name]['raw_results'] = $failures[$validator_name];
      }
      else {
        // @todo Check if this is a validator that keeps track of line numbers.
        // @assumption: Only row-level validators enter this else
        // block since BOTH:
        // a) $failures[$validator_name] is not empty
        // b) $failures[$validator_name]['case'] is not set
        // It would be better to validate that we have line numbers (integers)
        // then leave the else {} for anything outside of these options to throw
        // an exception for the developer. Reminder that:
        // $failures[$validator_name]['valid'] and
        // $failures[$validator_name]['failures']
        // also are valid but this scenario should have already been caught by
        // the previous if block.
        // @todo Update this current approach to not report only the first
        // failure, but instead collect all the cases and failedItems and
        // formulate one concise, helpful feedback message.
        // phpcs:ignore
        // foreach ($failures[$validator_name] as $line_no => $validator_results) {
        $messages[$validator_name]['status'] = 'fail';

        $first_failed_row = array_key_first($failures[$validator_name]);

        // A row failed raw-row validation, therefore data-row validators should
        // remain set as 'todo' UNLESS failed.
        if (in_array($validator_name, $raw_row_validators)) {
          $raw_row_failed = TRUE;
        }
        $case_message = $failures[$validator_name][$first_failed_row]['case'] . ' at row #: ' . $first_failed_row;
        $messages[$validator_name]['details'] = $case_message;
        $messages[$validator_name]['raw_results'] = $failures[$validator_name];
      }
    }

    return $messages;
  }

  /**
   * {@inheritDoc}
   */
  public function run() {
    // Traits service.
    $service_traits = \Drupal::service('trpcultivate_phenotypes.traits');

    // Values provided by user in the importer page.
    $genus = $this->arguments['run_args']['genus'];
    // Tell trait service that all trait assets will be contained in this genus.
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
    // Only the header names are needed, so pull them out into a new array.
    $headers = array_column($this->headers, 'name');
    $headers_count = count($headers);

    while (!feof($handle)) {
      // Current row.
      $line = fgets($handle);

      if ($line_no > 0 && !empty(trim($line))) {
        // Line split into individual data point.
        $data_columns = str_getcsv($line, "\t");
        // Sanitize every data in rows and columns.
        $data = array_map(function ($col) {
          return isset($col) ? trim(str_replace(['"', '\''], '', $col)) : '';
        }, $data_columns);

        // Build trait array so that each data (value) in a line/row corresponds
        // to the column header (key).
        // ie. ['Trait Name' => data 1, 'Trait Description' => data 2 ...].
        $trait = [];
        // Fill trait metadata: name, description, method, unit and type.
        for ($i = 0; $i < $headers_count; $i++) {
          $trait[$headers[$i]] = $data[$i];
        }

        // Create the trait.
        // NOTE: Loading of this file is performed using a database transaction.
        // If it fails or is terminated prematurely, then all insertions and
        // updates are rolled back and will not be found in the database.
        $service_traits->insertTrait($trait);

        unset($data);
      }

      // Next line.
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
    // Only the header names are needed for making the template file, so pull
    // them out into a new array.
    $column_headers = array_column($this->headers, 'name');

    $file_link = \Drupal::service('trpcultivate_phenotypes.template_generator')
      ->generateFile($importer_id, $column_headers);

    // Additional notes to the headers.
    $notes = 'The order of the above columns is important and your file must include a header!
    If you have a single trait measured in more than one way (i.e. with multiple collection
    methods), then you should have one line per collection method with the trait repeated.';

    // Render the header notes/lists template and use the file link as
    // the value to href attribute of the link to download a template file.
    $build = [
      '#theme' => 'importer_header',
      '#data' => [
        'headers' => $this->headers,
        'notes' => $notes,
        'template_file' => $file_link,
      ],
    ];

    return \Drupal::service('renderer')->renderPlain($build);
  }

  /**
   * Set phenotype genus ontology configuration service.
   *
   * @param Drupal\trpcultivate_phenotypes\Service\TripalCultivatePhenotypesGenusOntologyService $service
   *   The PhenoGenoOntology service as created/injected through create method.
   */
  public function setServiceGenusOntology(TripalCultivatePhenotypesGenusOntologyService $service) {
    if ($service) {
      $this->service_PhenoGenusOntology = $service;
    }
  }

  /**
   * Set phenotype traits service.
   *
   * @param \Drupal\trpcultivate_phenotypes\Service\TripalCultivatePhenotypesTraitsService $service
   *   The PhenoTraits service as created/injected through create method.
   */
  public function setServiceTraits($service) {
    if ($service) {
      $this->service_PhenoTraits = $service;
    }
  }

}
