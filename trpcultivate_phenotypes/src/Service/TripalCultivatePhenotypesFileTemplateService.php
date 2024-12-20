<?php

namespace Drupal\trpcultivate_phenotypes\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\file\Entity\File;
use Drupal\trpcultivate_phenotypes\TripalCultivateValidator\ValidatorTraits\FileTypes;

/**
 * Generate data collection template file used in the importer.
 */
class TripalCultivatePhenotypesFileTemplateService {

  /**
   * Validator Traits required by this validator.
   *
   * - FileTypes: Gets an array of all supported MIME types the importer is
   *   configured to process.
   */
  use FileTypes;

  /**
   * Module configuration.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Drupal user account.
   *
   * @var Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * Constructor.
   *
   * @param Drupal\Core\Config\ConfigFactoryInterface $config
   *   Configuration interface.
   * @param Drupal\Core\Session\AccountInterface $user
   *   Account interface.
   */
  public function __construct(ConfigFactoryInterface $config, AccountInterface $user) {
    // Set the configuration.
    $this->config = $config->get('trpcultivate_phenotypes.settings');

    // Set the current user.
    $this->user = $user;
  }

  /**
   * Generate template file.
   *
   * @param string $importer_id
   *   String, The plugin ID annotation definition used to prefix the filename.
   * @param array $column_headers
   *   An array of column headers to be written into the template file as the
   *   column header row.
   *
   * @return string
   *   Abosolute path to the template file.
   */
  public function generateFile($importer_id, $column_headers) {
    // Fetch the configuration relating to directory for housing data collection
    // template file. This directory had been setup during install and had / at
    // the end as defined. @see config install and schema.
    $dir_template_file = $this->config->get('trpcultivate.phenotypes.directory.template_file');

    // @TODO: support for multiple file types.
    // About the template file:
    // File extension.
    $file_extension = 'tsv';
    // MIME: TSV type file.
    $filemime = 'text/tab-separated-values';

    // Personalize the filename by appending display name of the current user,
    // but first sanitize it by replacing all spaces into a dash character.
    $display_name = $this->user->getDisplayName() ?? 'anonymous-user';
    $user_display_name = str_replace(' ', '-', $display_name);

    // Filename: importer id - data collection template file - username . (TSV).
    $filename = $importer_id . '-data-collection-template-file-' . $user_display_name . '.' . $file_extension;

    // Create the file.
    $file = File::create([
      'filename' => $filename,
      'filemime' => $filemime,
      'uri' => $dir_template_file . $filename,
    ]);

    // Mark file for deletion during a Drupal maintenance.
    $file->set('status', 0);

    // Write the contents: headers into the file created and serve the path back
    // to the calling Importer as value to the href attribute of link to
    // download a template file. File uri of the created file.
    $fileuri = $file->getFileUri();

    // Before we can write contents, we need to ensure the upper level folders
    // exist.
    if (!file_exists($dir_template_file)) {
      mkdir($dir_template_file, 0777, TRUE);
    }

    // Convert the headers array into a tsv string value and post into the first
    // line of the file.
    $fileheaders = implode("\t", $column_headers) . "\n# DELETE THIS LINE --- START DATA HERE AND USE TAB KEY #";
    file_put_contents($fileuri, $fileheaders);

    // Save.
    $file->save();

    return $file->createFileUrl();
  }

}
