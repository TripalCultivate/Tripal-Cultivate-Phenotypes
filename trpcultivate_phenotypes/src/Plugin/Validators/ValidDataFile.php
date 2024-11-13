<?php

namespace Drupal\trpcultivate_phenotypes\Plugin\Validators;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\TripalCultivatePhenotypesValidatorBase;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits\FileTypes;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validate data file.
 *
 * @TripalCultivatePhenotypesValidator(
 *   id = "valid_data_file",
 *   validator_name = @Translation("Valid Data File Validator"),
 *   input_types = {"file"}
 * )
 */
class ValidDataFile extends TripalCultivatePhenotypesValidatorBase implements ContainerFactoryPluginInterface {

  /*
   * Validator Traits required by this validator.
   *
   * - FileTypes: Gets an array of all supported MIME types the importer is
   *   configured to process.
   */
  use FileTypes;

  /**
   * Entity Type Manager service.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $service_EntityTypeManager;

  /**
   * Constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Set the Entity type manager service.
    $this->service_EntityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Validate that the input file is a valid file.
   *
   * Checks include:
   * - Parameter filename or file id is valid.
   * - Has Drupal File Id number assigned and can be loaded.
   * - File extension and mime type are configured by the importer.
   * - File exists and is not empty.
   * - File can be opened.
   *
   * @param string $filename
   *   The full path to a file within the file system (Absolute file path).
   * @param int $fid
   *   [OPTIONAL] The unique identifier (fid) of a file that is managed by
   *   Drupal File System.
   *
   * @return array
   *   An associative array with the following keys.
   *   - 'case': a developer-focused string describing the case checked.
   *   - 'valid': TRUE if the file passes validity checks, FALSE otherwise.
   *   - 'failedItems': an array of items that failed with any of the following
   *      keys. This is an empty array if the data row input was valid.
   *     - 'filename': The provided name of the file.
   *     - 'fid': The fid of the provided file.
   *     - 'mime': The mime type of the input file if it is not supported.
   *     - 'extension': The extension of the input file if not supported.
   *
   * @throws \Exception
   *   - If parameters $filename and $fid are both provided, but do not point to
   *     the same file object.
   */
  public function validateFile(string $filename, int|null $fid = NULL) {

    // Parameter check, verify that the filename/file path is valid.
    if (empty($filename) && is_null($fid)) {
      return [
        'case' => 'Filename is empty string',
        'valid' => FALSE,
        'failedItems' => [
          'filename' => '',
          'fid' => $fid,
        ],
      ];
    }

    // Parameter check, verify the file id number is not 0 or a negative value.
    if (!is_null($fid) && $fid <= 0) {
      return [
        'case' => 'Invalid file id number',
        'valid' => FALSE,
        'failedItems' => [
          'filename' => $filename,
          'fid' => $fid,
        ],
      ];
    }

    // Holds the file object when file is a managed file.
    $file_object = NULL;

    // Load file object.
    if (is_numeric($fid) && $fid > 0) {
      // Load the file object by fid number.
      $file_object = $this->service_EntityTypeManager
        ->getStorage('file')
        ->load($fid);

      // Verify that the filename (if provided) matches the filename in the file
      // object returned by the file id.
      if (!empty($filename) && $file_object->getFileName() != pathinfo($filename, PATHINFO_FILENAME)) {
        throw new \Exception('The filename provided does not match the filename set in the file object.');
      }
    }
    elseif ($filename) {
      // Locate the file entity by uri and load the file object using the
      // returned file id number that matched.
      $file_object = $this->service_EntityTypeManager
        ->getStorage('file')
        ->loadByProperties(['uri' => $filename]);
      $file_object = reset($file_object);
    }

    // Check that the file input provided returned a file object.
    if (!$file_object) {
      if (is_null($fid)) {
        return [
          'case' => 'Filename failed to load a file object',
          'valid' => FALSE,
          'failedItems' => [
            'filename' => $filename,
          ],
        ];
      }
      else {
        return [
          'case' => 'File id failed to load a file object',
          'valid' => FALSE,
          'failedItems' => [
            'fid' => $fid,
          ],
        ];
      }
    }

    // File object loaded successfully. Any subsequent failed checks will
    // reference the filename and file id from the established file object.
    $file_filename = $file_object->getFileName();
    $file_fid = $file_object->id();

    // Check that the file is not blank by inspecting the file size.
    $file_size = $file_object->getSize();
    if (!$file_size) {
      return [
        'case' => 'The file has no data and is an empty file',
        'valid' => FALSE,
        'failedItems' => [
          'filename' => $file_filename,
          'fid' => $file_fid,
        ],
      ];
    }

    // Check that both the file MIME type and file extension are supported.
    $file_mime_type = $file_object->getMimeType();
    $file_extension = pathinfo($file_filename, PATHINFO_EXTENSION);

    // Get the supported MIME types and file extensions values.
    $supported_file_extensions = $this->getSupportedFileExtensions();
    $supported_mime_types = $this->getSupportedMimeTypes();

    if (!in_array($file_mime_type, $supported_mime_types)) {
      if (in_array($file_extension, $supported_file_extensions)) {
        // The file extension is supported but the MIME type is not.
        return [
          'case' => 'Unsupported file MIME type',
          'valid' => FALSE,
          'failedItems' => [
            'mime' => $file_mime_type,
            'extension' => $file_extension,
          ],
        ];
      }
      else {
        // Both MIME type and file extension are not supported.
        return [
          'case' => 'Unsupported file mime type and unsupported extension',
          'valid' => FALSE,
          'failedItems' => [
            'mime' => $file_mime_type,
            'extension' => $file_extension,
          ],
        ];
      }
    }

    // Check that the file can be opened.
    $file_uri = $file_object->getFileUri();
    $file_handle = @fopen($file_uri, 'r');

    if (!$file_handle) {
      return [
        'case' => 'Data file cannot be opened',
        'valid' => FALSE,
        'failedItems' => [
          'filename' => $file_filename,
          'fid' => $file_fid,
        ],
      ];
    }

    fclose($file_handle);

    // Validator response values if data file is valid.
    return [
      'case' => 'Data file is valid',
      'valid' => TRUE,
      'failedItems' => [],
    ];
  }

}
