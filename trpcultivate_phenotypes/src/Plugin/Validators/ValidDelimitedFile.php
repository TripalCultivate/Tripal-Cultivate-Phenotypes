<?php

namespace Drupal\trpcultivate_phenotypes\Plugin\Validators;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\TripalCultivatePhenotypesValidatorBase;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits\ColumnCount;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits\FileTypes;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validate that a line in a data file is properly delimited.
 *
 * @TripalCultivatePhenotypesValidator(
 *   id = "valid_delimited_file",
 *   validator_name = @Translation("Valid Delimited File Validator"),
 *   input_types = {"raw-row"}
 * )
 */
class ValidDelimitedFile extends TripalCultivatePhenotypesValidatorBase implements ContainerFactoryPluginInterface {

  /*
   * This validator requires the following validator traits:
   * - FileTypes: get the MIME type of the input file (getFileMimeType)
   * - ColumnCount: get the expected number of columns (getExpectedColumns)
   */
  use FileTypes;
  use ColumnCount;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
  }

  /**
   * Perform validation of a raw row in a data file.
   *
   * Checks include:
   * - Line is not empty.
   * - It has some delimiter used to separate values.
   * - When split, the number of values returned is equal to the expected number
   *   of values in getExpectedColumns().
   *
   * @param string $raw_row
   *   A line in the data file that is not processed (ie. split by delimiter).
   *
   * @return array
   *   An associative array with the following keys.
   *   - 'case': a developer focused string describing the case checked.
   *   - 'valid': TRUE if the raw row is properly delimited, FALSE otherwise.
   *   - 'failedItems': an array of items that failed with any of the following
   *      keys. This is an empty array if row is properly delimited.
   *      - 'raw_row': The raw row or a string indicating the row is empty.
   */
  public function validateRawRow(string $raw_row) {

    // Parameter check, verify that raw row is not an empty string.
    if (empty(trim($raw_row))) {
      return [
        'case' => 'Raw row is empty',
        'valid' => FALSE,
        'failedItems' => [
          'raw_row' => 'is an empty string value',
        ],
      ];
    }

    // Get the expected number of columns.
    $expected_columns = $this->getExpectedColumns();

    // Get the supported delimiters based on the input file's mime type.
    $input_file_mime_type = $this->getFileMimeType();
    $input_file_type_delimiters = $this->getFileDelimiters($input_file_mime_type);

    // Check the row includes at least one delimiter returned by
    // getFileDelimiters().
    $delimiters_used = [];
    foreach ($input_file_type_delimiters as $delimiter) {
      if (strpos($raw_row, $delimiter)) {
        array_push($delimiters_used, $delimiter);
      }
    }

    // Not one of the supported delimiters was detected in the raw row.
    if (empty($delimiters_used)) {

      // Validation passes if the raw row is not delimited and the expected
      // number of columns is set to 1.
      if ($expected_columns['number_of_columns'] == 1) {
        return [
          'case' => 'Raw row has expected number of columns',
          'valid' => TRUE,
          'failedItems' => [],
        ];
      }

      return [
        'case' => 'None of the delimiters supported by the file type was used',
        'valid' => FALSE,
        'failedItems' => [
          'raw_row' => $raw_row,
        ],
      ];
    }

    // With the list of delimiters identified in the raw row, try each delimiter
    // separately to see if number of values is the expected number of columns.
    // Store every delimiter that failed into the failed delimiters array.
    $delimiters_failed = [];

    foreach ($delimiters_used as $delimiter) {
      $columns = TripalCultivatePhenotypesValidatorBase::splitRowIntoColumns($raw_row, $input_file_mime_type);

      if ($expected_columns['strict']) {
        // A strict comparison - exact match only.
        if (count($columns) != $expected_columns['number_of_columns']) {
          array_push($delimiters_failed, $delimiter);
        }
      }
      else {
        // Not a strict comparison - at least x number of columns.
        if (count($columns) < $expected_columns['number_of_columns']) {
          array_push($delimiters_failed, $delimiter);
        }
      }
    }

    // If the failed delimiters array contains the same number of delimiters
    // attempted, then every delimiter failed to split the line as required.
    if ($delimiters_used == $delimiters_failed) {
      return [
        'case' => 'Raw row is not delimited',
        'valid' => FALSE,
        'failedItems' => [
          'raw_row' => $raw_row,
        ],
      ];
    }

    return [
      'case' => 'Raw row is delimited',
      'valid' => TRUE,
      'failedItems' => [],
    ];
  }

}
