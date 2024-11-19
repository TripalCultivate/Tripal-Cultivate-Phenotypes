<?php

namespace Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits;

/**
 * Provides setters/getters regarding column placement within a row in a file.
 */
trait ColumnIndices {

  /**
   * Sets the array of indices for the validator to do its checks on.
   *
   * Specifically, in this context, an index is the key of the column the
   * validator should check. In the case of lists, this involves sequential
   * integers created by PHP. In the case of associative arrays, the index/key
   * will likely be a meaningful string (such as names of column headers).
   *
   * @param array $indices
   *   An array where each value is the key of the column the validator instance
   *   should act on. It must be either an integer or string. For more detail,
   *   see the definition of indices in the docblock above.
   *
   * @throws \Exception
   *   - If $indices array is empty.
   *   - If $indices array contains values that are not type string or integer.
   */
  public function setIndices(array $indices) {

    // Make sure we don't have an empty array.
    if (count($indices) === 0) {
      throw new \Exception('The ColumnIndices Trait requires a non-empty array of indices.');
    }

    // Check that our array only contains the types: integer or string.
    foreach ($indices as $value) {
      if (!(is_string($value) || (is_int($value)))) {
        throw new \Exception('The ColumnIndices Trait requires a one-dimensional array with values that are of type integer or string only.');
      }
    }

    $this->context['indices'] = $indices;
  }

  /**
   * Returns the indices of the columns this validator should act on.
   *
   * Specifically, in this context, an index is the key of the column the
   * validator should check. In the case of lists, this involves sequential
   * integers created by PHP. In the case of associative arrays, the index/key
   * will likely be a meaningful string (such as names of column headers).
   *
   * @return array
   *   A one-dimensional array containing column indices, either represented as
   *   integers or strings reflecting the name of the column headers.
   *
   * @throws \Exception
   *   - If the indices array was not configured by setIndices().
   */
  public function getIndices() {

    if (array_key_exists('indices', $this->context)) {
      return $this->context['indices'];
    }
    else {
      throw new \Exception("Cannot retrieve an array of indices as one has not been set by the setIndices() method.");
    }
  }

}
