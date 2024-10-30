<?php

namespace Drupal\trpcultivate_phenotypes\TripalCultivateValidator;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for data validator plugin.
 */
interface TripalCultivatePhenotypesValidatorInterface extends PluginInspectionInterface {

  /**
   * Return the name of the validator.
   *
   * @return string.
   */
  public function getValidatorName();

  /**
   * Returns the input types supported by this validator.
   * These are defined in the class annotation docblock.
   *
   * @return array
   *   The input types supported by this validator.
   */
  public function getSupportedInputTypes();

  /**
   * Confirms whether the given inputType is supported by this validator.
   *
   * @param string $input_type
   *   The input type to check.
   * @return boolean
   *   True if the input type is supported and false otherwise.
   */
  public function checkInputTypeSupported(string $input_type);

  /**
   * Validates the metadata associated with an importer.
   *
   * This should never validate the file even though it will likely be passed in
   * with the other form values.
   *
   * @param array $form_values
   *  An array of values from the submitted form where each key maps to a form
   *  element and the value is what the user entered.
   *
   * @return array
   *  An array of information about the validity of the data passed in.
   *  The supported keys are:
   *  - 'case': a developer code describing the case triggered
   *      (i.e. no record in chado matching project name). If the data is
   *      is valid then this is not required but could be 'data verified'.
   *  - 'valid': a boolean indicating the data is valid (TRUE) or not (FALSE)
   *  - 'failedItems': an array of information to customize messages for the UI.
   *      For example, if this validator checks a specific set of form elements,
   *      then this array should be keyed by the form element key and the value
   *      matches what was provided by the user input in form_values.
   */
  public function validateMetadata(array $form_values);

  /**
   * Validates the file associated with an importer.
   *
   * This should validate the file object (e.g. it exists, is readable) but
   * should not validate the contents in any way.
   *
   * @param array $filename
   *  The full path and filename with extension of the file to validate.
   *
   * @return array
   *  An array of information about the validity of the data passed in.
   *  The supported keys are:
   *  - 'case': a developer code describing the case triggered
   *      (i.e. no record in chado matching project name). If the data is
   *      is valid then this is not required but could be 'data verified'.
   *  - 'valid': a boolean indicating the data is valid (TRUE) or not (FALSE)
   *  - 'failedItems': an array of information to customize messages for the UI.
   *      For example, if this validator checks the permissions of the file, then
   *      this array might contain the permissions the file actually had that
   *      did not match what was expected.
   */
  public function validateFile(string $filename, int $fid);

  /**
   * Validates rows within the data file submitted to an importer.
   *
   * @param array $row_values
   *  An array of values from a single row/line in the file where each element
   *  is a single column.
   *
   * @return array
   *  An array of information about the validity of the data passed in.
   *  The supported keys are:
   *  - 'case': a developer code describing the case triggered
   *      (i.e. no record in chado matching project name). If the data is
   *      is valid then this is not required but could be 'data verified'.
   *  - 'valid': a boolean indicating the data is valid (TRUE) or not (FALSE)
   *      'failedItems': an array of the items that failed validation. For example,
   *      if this validator validates that a number of indices are not empty, then
   *      this will be an array of indices that were empty. Another example is
   *      if this validator checks that a number of indices have values in a
   *      specific list, then this array would use the index as the key and
   *      the value the column actually had, for each failed column.
   */
  public function validateRow(array $row_values);

  /**
   * Validates rows within the data file submitted to an importer.
   *
   * Note: This should only be used when validating the format of the row.
   * If you are validating the content of the columns then you should use
   * validateRow() instead.
   *
   * NOTE: Currently this method assumes it will not be passed empty lines or
   * comment lines... to the point it will throw an exception if it is. We should
   * rethink this at a later point. Example: Many file formats have comments. These
   * are not data rows and will not split properly BUT we may want to validate them
   * in other ways and at a mimimum we may not want to say they are an error ;-p
   *
   * @param string $raw_row
   *  A single line or row extracted from the data file
   *  containing data entries, values or column headers, with each value
   *  delimited by a character specified by the importer class.
   *
   * @return array
   *  An array of information about the validity of the data passed in.
   *  The supported keys are:
   *  - 'case': a developer code describing the case triggered
   *      (i.e. no record in chado matching project name). If the data is
   *      is valid then this is not required but could be 'data verified'.
   *  - 'valid': a boolean indicating the data is valid (TRUE) or not (FALSE)
   *  - 'failedItems': an array of the items that failed validation. For example,
   *      if this validator validates that a number of indices are not empty, then
   *      this will be an array of indices that were empty. Another example is
   *      if this validator checks that a number of indices have values in
   *      a specific list, then this array would use the index as the key and
   *      the value the column actually had, for each failed column.
   */
  public function validateRawRow(string $raw_row);

  /**
   * Given an array of values (that represents a single row in an input file),
   * check that the list in $indices is within the range of keys in $row_values.
   *
   * @param array $row_values
   *   The contents of the file's row where each value within a cell is
   *   stored as an array element
   * @param array $indices
   *   A one dimensional array of indices which correspond to which indices in
   *   $row_values the validator instance should act on.
   *
   * @throws
   *   - An exception if $indices is an empty array
   *   - An exception if $indices has more values than $row_values
   *   - An exception if any of the indices in $indices is out of bounds
   *     of the keys for $row_values
   */
  public function checkIndices($row_values, $indices);

  /**
   * Traits, method and unit may be created/inserted through
   * the phenotypic data importer using the configuration allow new.
   * This method will fetch the value set for allow new configuration.
   *
   * @return boolean
   *   True, allow trait, method and unit detected in data importer to be created. False will trigger
   *   validation error and will not permit creation of terms.
   */
  public function getConfigAllowNew();

  /**
   * Split or explode a data file line/row values into an array using a delimiter.
   *
   * More specifically, the file is split based on the appropriate delimiter
   * for the mime type passed in. For example, the mime type text/tab-separated-values
   * maps to the tab (i.e. "\t") delimiter.
   *
   * By using this mapping approach we can actually support a number of different
   * file types with different delimiters for the same importer while keeping
   * the performance hit to a minimum. Especially as in many cases this is a
   * one-to-one mapping. If it is not a one-to-one mapping then we loop through
   * the options.
   *
   * @param string $row
   *   A line in the data file which has not yet been split into columns.
   * @param string $mime_type
   *   The mime type of the file currently being validated or imported (i.e. the
   *   mime type of the file this line is from).
   *
   * @return array
   *   An array containing the values extracted from the line after splitting it based
   *   on a delimiter value.
   */
  public static function splitRowIntoColumns(string $row, string $mime_type);

  /**
   * Gets the list of delimiters supported by the input file's mime-type that
   * was provided to the setter.
   *
   * NOTE: This method is static to allow for it to also be used by the static
   * method splitRowIntoColumns().
   *
   * @param string $mime_type
   *   A string that is the mime-type of the input file.
   *
   *   HINT: You can get the mime-type of a file from the 'mime-type' property
   *   of a file object.
   *
   * @return array
   *   The list of delimiters that are supported by the file mime-type.
   */
  public static function getFileDelimiters(string $mime_type);
}
