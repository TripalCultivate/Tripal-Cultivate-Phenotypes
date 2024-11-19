<?php

namespace Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits;

/**
 * Getters/setters for supported mime-types, file extensions and delimiters.
 */
trait FileTypes {

  /**
   * A mapping of file extensions and their supported mime-types.
   *
   * More specifically, based on the supported file extensions of the
   * current importer, a list of valid mime-types for the extension(s) is looked
   * up in this mapping.
   *
   * @var array
   */
  public static array $extension_to_mime_mapping = [
    'tsv' => ['text/tab-separated-values'],
    'csv' => ['text/csv'],
    'txt' => ['text/plain'],
  ];

  /**
   * Sets the mime-type of the input file and its supported file delimiters.
   *
   * @param string $mime_type
   *   A string that is the mime-type of the input file
   *
   *   HINT: You can get the mime-type of a file from the 'mime-type' property
   *   of a file object.
   *
   * @throws \Exception
   *   - If mime_type string is empty.
   *   - If mime_type is unsupported.
   */
  public function setFileMimeType(string $mime_type) {

    // Mime-type must not be an empty string.
    if (empty($mime_type)) {
      throw new \Exception("The setFileMimeType() setter requires a string of the input file's mime-type and must not be empty.");
    }

    // Check if mime-type is in our mapping array.
    if (!isset(self::$mime_to_delimiter_mapping[$mime_type])) {
      // Since this is checking a user-provided value, the error is going to be
      // logged and then checked by a validator so that the error can be passed
      // to the user in a friendly way.
      $this->logger->error("The setFileMimeType() setter requires a supported mime-type but '$mime_type' is unsupported.");
    }
    else {
      $this->context['file_mime_type'] = $mime_type;
    }
  }

  /**
   * Sets supported mime-types based on an importer's supported file extensions.
   *
   * @param array $extensions
   *   An array of file extensions that are supported by this importer.
   *
   *   HINT: You can get this for an instance of a Tripal Importer using:
   *   $this->plugin_definition['file_types'].
   *
   * @throws \Exception
   *   - If extensions array is an empty array.
   *   - If a file extension is not in the $extension_to_mime_mapping array.
   */
  public function setSupportedMimeTypes(array $extensions) {

    // Extensions array must not be empty.
    if (empty($extensions)) {
      throw new \Exception("The setSupportedMimeTypes() setter requires an array of file extensions that are supported by the importer and must not be empty.");
    }

    $mime_types = [];
    $invalid_ext = [];

    foreach ($extensions as $ext) {
      if (!isset(self::$extension_to_mime_mapping[$ext])) {
        array_push($invalid_ext, $ext);
        continue;
      }
      $mime_types = array_merge($mime_types, self::$extension_to_mime_mapping[$ext]);
    }

    if ($invalid_ext) {
      $invalid_ext = implode(', ', $invalid_ext);
      throw new \Exception('The setSupportedMimeTypes() setter does not recognize the following extensions: ' . $invalid_ext);
    }

    $this->context['supported_mime_types'] = $mime_types;
    $this->context['file_extensions'] = $extensions;
  }

  /**
   * Gets the supported file extensions by the current importer.
   *
   * @return array
   *   The file extensions set by the setSupportedMimeTypes() setter method.
   *
   * @throws \Exception
   *   - If supported file extensions were NOT set by setSupportedMimeTypes().
   */
  public function getSupportedFileExtensions() {

    $context_key = 'file_extensions';

    if (array_key_exists($context_key, $this->context)) {
      return $this->context[$context_key];
    }
    else {
      throw new \Exception('Cannot retrieve supported file extensions as they have not been set by setSupportedMimeTypes() method.');
    }
  }

  /**
   * Gets the supported file mime-types by the current importer.
   *
   * @return array
   *   The file mime-types set by the setSupportedMimeTypes() setter method.
   *
   * @throws \Exception
   *   - If supported file mime-types were NOT set by setSupportedMimeTypes().
   */
  public function getSupportedMimeTypes() {

    $context_key = 'supported_mime_types';

    if (array_key_exists($context_key, $this->context)) {
      return $this->context[$context_key];
    }
    else {
      throw new \Exception('Cannot retrieve supported file mime-types as they have not been set by setSupportedMimeTypes() method.');
    }
  }

  /**
   * Gets the file mime-type of the input file.
   *
   * @return string
   *   The file mime-type set by the setFileMimeType() setter method.
   *
   * @throws \Exception
   *   - If the input file mime-type was not set by setFileMimeType().
   */
  public function getFileMimeType() {

    $context_key = 'file_mime_type';

    if (array_key_exists($context_key, $this->context)) {
      return $this->context[$context_key];
    }
    else {
      throw new \Exception('Cannot retrieve the input file mime-type as it has not been set by setFileMimeType() method.');
    }
  }

}
