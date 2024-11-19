<?php

namespace Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits;

/**
 * Provides setters/getters regarding number of columns in a data row.
 */
trait ColumnCount {

  /**
   * Set a number of required columns.
   *
   * @param int $number_of_columns
   *   The number of columns that are anticipated in a data row.
   * @param bool $strict
   *   Indicates whether the value $number_of_columns is the minimum
   *   number of columns required (FALSE - default) or if it is strictly the
   *   only acceptable number of columns (TRUE).
   *
   * @throws \Exception
   *   - If the number of columns to set is less than or equals to 0.
   */
  public function setExpectedColumns(int $number_of_columns, bool $strict = FALSE) {

    $context_key = 'column_count';

    if ($number_of_columns <= 0) {
      throw new \Exception('setExpectedColumns() in validator requires an integer value greater than zero.');
    }

    $this->context[$context_key] = [
      'number_of_columns' => $number_of_columns,
      'strict'  => $strict,
    ];
  }

  /**
   * Get the number of required columns.
   *
   * @return array
   *   An array with the following keys:
   *   - 'number_of_columns': the number of expected columns.
   *   - 'strict': strict comparison flag.
   *
   * @throws \Exception
   *   - If the column number was not configured by setExpectedColumns().
   */
  public function getExpectedColumns() {

    $context_key = 'column_count';

    if (array_key_exists($context_key, $this->context)) {
      return $this->context[$context_key];
    }
    else {
      throw new \Exception('Cannot retrieve the number of expected columns as one has not been set by setExpectedColumns().');
    }
  }

}
