<?php

namespace Drupal\trpcultivate_phenotypes\TripalCultivateValidator;

use Drupal\Component\Plugin\PluginBase;
use Drupal\tripal\Services\TripalLogger;

/**
 * The base class for validator plugins.
 */
abstract class TripalCultivatePhenotypesValidatorBase extends PluginBase implements TripalCultivatePhenotypesValidatorInterface {

  /**
   * A context array with key-value pairs set by ValidatorTraits.
   *
   * An associative array containing the needed context, which is dependant
   * on the validator. For example, instead of validating each cell by default,
   * a validator may need a list of indices corresponding to the columns in
   * the row that the validator should act on. This might look like:
   * $context['indices'] => [1,3,5]
   */
  protected array $context = [];

  /**
   * A mapping of supported file mime-types and their supported delimiters.
   *
   * More specifically, the file is split based on the appropriate delimiter
   * for the mime-type passed in. For example, the mime-type
   * "text/tab-separated-values" maps to the tab (i.e. "\t") delimiter.
   *
   * By using this mapping approach, we can actually support a number of
   * file types with different delimiters for the same importer while keeping
   * the performance hit to a minimum. Especially since in many cases this is a
   * one-to-one mapping. If it is not a one-to-one mapping, then we loop through
   * the options.
   *
   * @var array
   */
  public static array $mime_to_delimiter_mapping = [
    'text/tab-separated-values' => ["\t"],
    'text/csv' => [','],
    'text/plain' => ["\t", ','],
  ];

  /**
   * The TripalLogger service.
   *
   * This is used to report status and errors to both site users and
   * administrators through the server log.
   *
   * @var Drupal\tripal\Services\TripalLogger
   */
  public TripalLogger $logger;

  /**
   * {@inheritdoc}
   */
  public function getValidatorName() {
    return $this->pluginDefinition['validator_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedInputTypes() {
    return $this->pluginDefinition['input_types'];
  }

  /**
   * {@inheritdoc}
   */
  public function checkInputTypeSupported(string $input_type) {
    $supported_types = $this->getSupportedInputTypes();

    if (in_array($input_type, $supported_types)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateMetadata(array $form_values) {
    $plugin_name = $this->getValidatorName();
    throw new \Exception("Method validateMetadata() from base class called for $plugin_name. If this plugin wants to support this type of validation then they need to override it.");
  }

  /**
   * {@inheritdoc}
   */
  public function validateFile(int|null $fid) {
    $plugin_name = $this->getValidatorName();
    throw new \Exception("Method validateFile() from base class called for $plugin_name. If this plugin wants to support this type of validation then they need to override it.");
  }

  /**
   * {@inheritdoc}
   */
  public function validateRow(array $row_values) {
    $plugin_name = $this->getValidatorName();
    throw new \Exception("Method validateRow() from base class called for $plugin_name. If this plugin wants to support this type of validation then they need to override it.");
  }

  /**
   * {@inheritdoc}
   */
  public function validateRawRow(string $raw_row) {
    $plugin_name = $this->getValidatorName();
    throw new \Exception("Method validateRawRow() from base class called for $plugin_name. If this plugin wants to support this type of validation then they need to override it.");
  }

  /**
   * {@inheritdoc}
   */
  public function checkIndices($row_values, $indices) {

    // Report if the indices array is empty.
    if (!$indices) {
      throw new \Exception(
        'An empty indices array was provided.'
      );
    }

    // Get the potential range by looking at $row_values.
    $num_values = count($row_values);
    // Count our indices array.
    $num_indices = count($indices);
    if ($num_indices > $num_values) {
      throw new \Exception(
        'Too many indices were provided (' . $num_indices . ') compared to the number of cells in the provided row (' . $num_values . ').'
      );
    }

    // Pull out just the keys from $row_values and compare with $indices.
    $row_keys = array_keys($row_values);
    $result = array_diff($indices, $row_keys);
    if ($result) {
      $invalid_indices = implode(', ', $result);
      throw new \Exception(
        'One or more of the indices provided (' . $invalid_indices . ') is not valid when compared to the indices of the provided row.'
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigAllowNew() {
    $allownew = \Drupal::config('trpcultivate_phenotypes.settings')
      ->get('trpcultivate.phenotypes.ontology.allownew');

    return $allownew;
  }

  /**
   * {@inheritdoc}
   */
  public static function splitRowIntoColumns(string $row, string $mime_type) {

    $mime_to_delimiter_mapping = self::$mime_to_delimiter_mapping;

    // Ensure that the mime type is in our delimiter mapping.
    if (!array_key_exists($mime_type, $mime_to_delimiter_mapping)) {
      throw new \Exception('The mime type "' . $mime_type . '" passed into splitRowIntoColumns() is not supported. We support the following mime types:' . implode(', ', array_keys($mime_to_delimiter_mapping)) . '.');
    }

    // Determine the delimiter we should use based on the mime type.
    $supported_delimiters = self::getFileDelimiters($mime_type);

    $delimiter = NULL;
    // If there is only one supported delimiter then simply split the row!
    if (count($supported_delimiters) === 1) {
      $delimiter = end($supported_delimiters);
      $columns = str_getcsv($row, $delimiter);
    }

    // @todo Address in issue #118.
    // Otherwise we will have to try to determine which one is "right"?!?
    // Points to remember in the future:
    // - We can't use the one that splits into the most columns as a text column
    // could include multiple commas which could overpower the overall number of
    // tabs in a tab-delimited plain text file.
    // - It would be good to confirm we are getting the same number of columns
    // for each line in a file but since this needs to be a static method we
    // would pass that information in.
    // - If we try to check for the same number of columns as expected, we have
    // to remember that researchers routinely add "Comments" columns to the end,
    // sometimes without a header.
    // - If going based on the number of columns in the header, the point above
    // still impacts this, plus this method is called when splitting the header
    // before any validators run!
    else {

      throw new \Exception("We don't currently support splitting mime types with multiple delimiter options as its not trivial to choose the correct one.");

      /*
      $results = [];
      $counts = [];
      foreach ($supported_delimiters as $delimiter) {
        $results[$delimiter] = str_getcsv($row, $delimiter);
        $counts[$delimiter] = count($results[$delimiter]);
      }

      // Now lets choose the one with the most columns --shrugs-- not ideal
      // but I'm not sure there is a better option. asort() is from smallest
      // to largest preserving the keys so we want to choose the last element.
      asort($counts);
      $winning_delimiter = array_key_last($counts);
      $columns = $results[ $winning_delimiter ];
      $delimiter = $winning_delimiter;
       */
    }

    // Now lets double check that we got some values...
    if (count($columns) == 1 && $columns[0] === $row) {
      // The delimiter failed to split the row and returned the original row.
      throw new \Exception('The data row or line provided could not be split into columns. The supported delimiter(s) are "' . implode('", "', $supported_delimiters) . '".');
    }

    // Sanitize values.
    foreach ($columns as &$value) {
      if ($value) {
        $value = trim(str_replace(['"', '\''], '', $value));
      }
    }

    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public static function getFileDelimiters(string $mime_type) {

    // Check if mime type is an empty string.
    if (empty($mime_type)) {
      throw new \Exception("The getFileDelimiters() getter requires a string of the input file's mime-type and must not be empty.");
    }

    // Grab the delimiters for this mime-type.
    if (array_key_exists($mime_type, self::$mime_to_delimiter_mapping)) {
      return self::$mime_to_delimiter_mapping[$mime_type];
    }
    else {
      throw new \Exception('Cannot retrieve file delimiters for the mime-type provided: ' . $mime_type);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setLogger(TripalLogger $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogger() {
    if (!empty($this->logger)) {
      return $this->logger;
    }
    else {
      throw new \Exception('Cannot retrieve the Tripal Logger property as one has not been set for this validator using the setLogger() method.');
    }
  }

}
