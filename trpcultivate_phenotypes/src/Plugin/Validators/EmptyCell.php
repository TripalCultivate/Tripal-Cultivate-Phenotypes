<?php

namespace Drupal\trpcultivate_phenotypes\Plugin\Validators;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\TripalCultivatePhenotypesValidatorBase;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits\ColumnIndices;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validate empty cells of an importer.
 *
 * @TripalCultivatePhenotypesValidator(
 *   id = "empty_cell",
 *   validator_name = @Translation("Empty Cell Validator"),
 *   input_types = {"header-row", "data-row"},
 * )
 */
class EmptyCell extends TripalCultivatePhenotypesValidatorBase implements ContainerFactoryPluginInterface {

  /*
   * Validator Traits required by this validator.
   *
   * - ColumnIndices: Gets an array of indices corresponding to the cells in
   *   $row_values to validate.
   */
  use ColumnIndices;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * Validate the values within the cells of this row.
   *
   * @param array $row_values
   *   An array of values from a single row/line in the file where each value
   *   is a single column.
   *
   * @return array
   *   An associative array with the following keys.
   *   - 'case': a developer focused string describing the case checked.
   *   - 'valid': FALSE if any of the cells being checked are empty and TRUE
   *     otherwise.
   *   - 'failedItems': an array of items that failed with the following keys.
   *     This is an empty array if the data row input was valid.
   *     - 'empty_indices': A list of indices in the row which were checked and
   *       found to be empty.
   */
  public function validateRow($row_values) {

    // Grab our indices.
    $indices = $this->getIndices();

    // Check the indices provided are valid in the context of the row.
    // Will throw an exception if there's a problem.
    $this->checkIndices($row_values, $indices);

    $empty = FALSE;
    $failed_indices = [];
    // Iterate through our array of row values.
    foreach ($row_values as $index => $cell) {
      // Only validate the values in which their index is also within our
      // context array of indices.
      if (in_array($index, $indices)) {
        // Trim the contents of our cell in case we have whitespace.
        $cell = trim($cell);
        // Check if our content is empty and report an error if it is.
        if (!isset($cell) || empty($cell)) {
          $empty = TRUE;
          array_push($failed_indices, $index);
        }
      }
    }
    // Report if empty values were found that should not be empty.
    if ($empty) {
      $validator_status = [
        'case' => 'Empty value found in required column(s)',
        'valid' => FALSE,
        'failedItems' => [
          'empty_indices' => $failed_indices,
        ],
      ];
    }
    else {
      $validator_status = [
        'case' => 'No empty values found in required column(s)',
        'valid' => TRUE,
        'failedItems' => [],
      ];
    }
    return $validator_status;
  }

}
