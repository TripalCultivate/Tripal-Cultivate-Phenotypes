<?php

namespace Drupal\trpcultivate_phenotypes\Plugin\Validators;

use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\TripalCultivatePhenotypesValidatorBase;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits\ColumnCount;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits\Headers;

/**
 * Validate that all expected column headers exist.
 *
 * @TripalCultivatePhenotypesValidator(
 *   id = "valid_headers",
 *   validator_name = @Translation("Header Row Validator"),
 *   input_types = {"header-row"}
 * )
 */
class ValidHeaders extends TripalCultivatePhenotypesValidatorBase {

  /**
   * Validator Traits required by this validator.
   *
   * - Headers: get expected headers (getHeaders)
   * - ColumnCount: get the expected number of columns (getExpectedColumns)
   */
  use Headers;
  use ColumnCount;

  /**
   * Validate the header row.
   *
   * Checks include:
   * - Each header value is not empty.
   * - No expected header is missing.
   * - The order of headers defined by the Importer should match the input.
   *
   * @param array $headers
   *   An array created by splitting the first line of the data file into
   *   values. The index of each value represents the order it appears in.
   *
   * @return array
   *   An associative array with the following keys.
   *   - 'case': a developer-focused string describing the case checked.
   *   - 'valid': TRUE if the header is valid, FALSE otherwise.
   *   - 'failedItems': an array of items that failed with any of the following
   *      keys. This is an empty array if the header passed validation.
   *      - 'headers': A string indicating the header row is empty.
   *      - the headers input array.
   */
  public function validateRow($headers) {
    $input_headers = $headers;

    // Parameter check, verify that the headers array input is not empty.
    if (empty($headers)) {
      // Headers array is an empty array.
      return [
        'case' => 'Header row is an empty value',
        'valid' => FALSE,
        'failedItems' => [
          'headers' => 'headers array is an empty array',
        ],
      ];
    }

    // Get the list of expected headers.
    $expected_headers = $this->getHeaders();

    // Compare expected headers and input headers. Return a failed
    // validation status on the first instance of a mismatch.
    foreach ($expected_headers as $header) {
      $cur_input_header = array_shift($input_headers);

      if ($cur_input_header && $header != trim($cur_input_header)) {
        return [
          'case' => 'Headers do not match expected headers',
          'valid' => FALSE,
          'failedItems' => $headers,
        ];
      }
    }

    // Get the expected number of columns and strict comparison flag.
    $expected_columns = $this->getExpectedColumns();

    if ($expected_columns['strict'] && $expected_columns['number_of_columns'] != count($headers)) {
      // The importer specified a strict requirement for number of columns in
      // the input file, but the header has more or less than that amount.
      return [
        'case' => 'Headers provided does not have the expected number of headers',
        'valid' => FALSE,
        'failedItems' => $headers,
      ];
    }

    // At this point the headers input array is valid.
    return [
      'case' => 'Headers exist and match expected headers',
      'valid' => TRUE,
      'failedItems' => [],
    ];
  }

}
