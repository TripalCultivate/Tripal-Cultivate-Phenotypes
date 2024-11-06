<?php

namespace Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits;

/**
 * Provides setters/getters regarding headers in an input file.
 */
trait Headers {
  /**
   * The key in the context array used to reference and retrieve headers.
   *
   * @var string
   */
  // phpcs:ignore
  private string $context_key = 'headers';

  /**
   * Expected types of headers, currently 'required' and 'optional'.
   *
   * Each type will have its own getter method.
   *
   * @var array
   */
  private array $types = [
    'required',
    'optional',
  ];

  /**
   * Sets the headers.
   *
   * @param array $headers
   *   A list of header definitions defined by the importer (usually a
   *   protected property at the top of the importer class). Each item in this
   *   list is an associative array describing a single header column. Each
   *   header item must consist of a header name (key: 'name'), and type
   *   (key: 'type', supported values: 'required', 'optional').
   *
   *   NOTE: Headers must be listed in order! The resulting headers array that
   *   will be set by this method is zero-based indexed and reflects the order
   *   of the headers array parameter and is unaltered by setters and getters.
   *
   * @throws \Exception
   *   - If an empty array is provided.
   *   - If 'name' and/or 'type' key is missing.
   *   - If 'name' and/or 'type' key has a missing value.
   *   - If the value for 'type' is not one of 'required' or 'optional'.
   */
  public function setHeaders(array $headers) {

    // Headers array must not be empty.
    if (empty($headers)) {
      throw new \Exception('The Headers Trait requires an array of headers and must not be empty.');
    }

    // Required keys that each header element must possess and
    // cannot be set to an empty value. The 'type' key's value must be
    // one of the 'type' values defined by the types property.
    $required_header_keys = [
      'name',
      'type',
    ];

    // For each header element, check that required keys exist and have a value.
    $context_headers = [];

    foreach ($headers as $index => $header) {
      foreach ($required_header_keys as $key) {
        // Could not find a required key.
        if (!isset($header[$key])) {
          throw new \Exception('Headers Trait requires the header key: ' . $key . ' when defining headers.');
        }

        // Key is set but value is empty.
        if (empty(trim($header[$key]))) {
          throw new \Exception('Headers Trait requires the header key: ' . $key . ' to be have a value.');
        }

        // The value of the 'type' key is not one of the required values.
        if ($key == 'type' && !in_array($header[$key], $this->types)) {
          $str_types = implode(', ', $this->types);
          throw new \Exception('Headers Trait requires the header key: ' . $key . ' value to be one of [' . $str_types . '].');
        }
      }

      // With this element's type already verified, create an array entry where
      // it contains only the 'name' and 'type' keys. This is needed because the
      // input $headers array may contain additional keys.
      $context_headers[$index] = [
        'name' => $header['name'],
        'type' => $header['type'],
      ];
    }

    $this->context[$this->context_key] = $context_headers;
  }

  /**
   * Get required headers for this importer.
   *
   * @return array
   *   All headers of type 'required', keyed by the index (column order) from
   *   the headers array and header name as the value.
   *   NOTE: the array is zero-based indexed.
   *
   * @throws \Exception
   *   - If headers were not set by setHeaders().
   */
  public function getRequiredHeaders() {
    return $this->getHeaders(['required']);
  }

  /**
   * Get optional headers for this importer.
   *
   * @return array
   *   All headers of type 'optional', keyed by the index (column order) from
   *   the headers array and header name as the value.
   *   NOTE: the array is zero-based indexed.
   *
   * @throws \Exception
   *   - If headers were not set by setHeaders().
   */
  public function getOptionalHeaders() {
    return $this->getHeaders(['optional']);
  }

  /**
   * Get headers of a specified type(s).
   *
   * @param array $types
   *   A list of header types to get. Can be one or more of the following
   *   (default is BOTH required and optional):
   *   - 'required'
   *   - 'optional'.
   *
   * @return array
   *   A list of headers matching the type(s) defined by the 'types' parameter.
   *   For example, if the 'types' parameter includes 'required' and 'optional',
   *   then the resulting array will contain all headers in order of index (this
   *   is assuming there are only 'required' and 'optional' types supported).
   *
   *   The returned array is keyed by the zero-based index (column order) based
   *   on the original headers array that was given to the setHeaders() method.
   *   The values are the name of the header column.
   *
   * @throws \Exception
   *   - If headers were not set by setHeaders().
   *   - If an unrecognized header type is requested in the 'types' parameter.
   */
  public function getHeaders(array $types = ['required', 'optional']) {

    // Check that the headers have been set by the setHeaders() method.
    if (!array_key_exists($this->context_key, $this->context)) {
      throw new \Exception('Cannot retrieve headers from the context array as one has not been set by setHeaders() method.');
    }

    $valid_types = $this->types;

    // This use of array_filter will pull out any unrecognized types by
    // comparing the 'types' parameter to the types property defined
    // at the top of this class.
    $invalid_types = array_filter($types, function ($type) use ($valid_types) {
      return !in_array($type, $valid_types);
    });

    // If any unrecognized types are detected, throw an exception.
    if (!empty($invalid_types)) {
      $str_invalid_types = implode(', ', $invalid_types);
      $str_valid_types = implode(', ', $valid_types);

      throw new \Exception('Cannot retrieve invalid header types: ' . $str_invalid_types . '. Use one of valid types: [' . $str_valid_types . ']');
    }

    // Prepare set of the importer headers where each header type matches
    // the header type requested.
    $requested_headers = [];

    foreach ($this->context[$this->context_key] as $index => $header) {
      if (in_array($header['type'], $types)) {
        $requested_headers[$index] = $header['name'];
      }
    }

    return $requested_headers;
  }

}
