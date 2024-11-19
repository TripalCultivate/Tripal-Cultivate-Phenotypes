<?php

namespace Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits;

/**
 * Provides getters/setters regarding validating values in a set of columns.
 */
trait ValidValues {

  /**
   * Sets a list of values that should be allowed for a cell being validated.
   *
   * NOTE: Often this setter is combined with the setIndices() setter which
   * indicates which columns should have one of these values.
   *
   * @param array $valid_values
   *   A one-dimensional array of values that are allowed within the cell(s)
   *   that are being validated in a file row.
   *
   * @throws \Exception
   *   - If $valid_values array is empty.
   *   - If $valid_values array contains values not of type string or integer.
   */
  public function setValidValues(array $valid_values) {

    // Make sure we don't have an empty array.
    if (count($valid_values) === 0) {
      throw new \Exception('The ValidValues Trait requires a non-empty array to set valid values.');
    }

    // Check if we have a multidimentsional array or an array of objects.
    foreach ($valid_values as $value) {
      if (!(is_string($value) || (is_int($value)))) {
        throw new \Exception('The ValidValues Trait requires a one-dimensional array with values that are of type integer or string only.');
      }
    }

    $this->context['valid_values'] = $valid_values;
  }

  /**
   * Returns a list of allowed values for cell(s) being validated.
   *
   * Specifically, the cell must contain one of the values in this list.
   *
   * @return array
   *   A one-dimensional array containing valid values
   *
   * @throws \Exception
   *   - If an array of valid values was not set by setValidValues().
   */
  public function getValidValues() {

    if (array_key_exists('valid_values', $this->context)) {
      return $this->context['valid_values'];
    }
    else {
      throw new \Exception("Cannot retrieve an array of valid values as one has not been set by the setValidValues() method.");
    }
  }

}
