<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\trpcultivate_phenotypes\Form\TripalCultivatePhenotypesWatermarkSettingsForm;

/**
 * Class definition ConfigWatermarkFormTest.
 *
 * @coversDefaultClass Drupal\trpcultivate_phenotypes\Form\TripalCultivatePhenotypesWatermarkSettingsForm
 * @group trpcultivate_phenotypes
 */
class ConfigWatermarkFormTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create container.
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    // Mock config since the Watermark form extends configFormBase and expects
    // a configuration settings. Called in validateForm().
    $watermark_config_mock = $this->prophesize(Config::class);
    $watermark_config_mock->get('trpcultivate.phenotypes.watermark')->willReturn([
      'charts' => FALSE,
      'image' => NULL,
      'file_ext' => ['png', 'gif'],
    ]);
    $watermark_config_mock->get('trpcultivate.phenotypes.directory.watermark')
      ->willReturn('public://TripalCultivatePhenotypes/watermark/');

    // When watermark form rebuilds calling the module settings, return
    // only the watertmark configuration settings above exclude other config.
    $all_config_mock = $this->prophesize(ConfigFactoryInterface::class);
    $all_config_mock->getEditable('trpcultivate_phenotypes.settings')
      ->willReturn($watermark_config_mock);

    // Isolated configuration for watermark configuration.
    $watermark_config = $all_config_mock->reveal();

    // Typed Config Manager requirement for ConfigForm dependancy injection.
    $typed_config_mock = $this->prophesize(TypedConfigManagerInterface::class);
    $typed_config = $typed_config_mock->reveal();

    // Class WatermarkForm class instance.
    $watermark_form = new TripalCultivatePhenotypesWatermarkSettingsForm($watermark_config, $typed_config);

    // Requirement of the container.
    // -- Translation.
    $mock = $this->prophesize(TranslationInterface::class);
    $translation = $mock->reveal();
    $watermark_form->setStringTranslation($translation);
    // -- Messenger.
    $mock = $this->prophesize(MessengerInterface::class);
    $messenger = $mock->reveal();
    $watermark_form->setMessenger($messenger);
    // -- File URL Generator.
    $mock = $this->prophesize(FileUrlGeneratorInterface::class);
    $fileGenerator = $mock->reveal();
    $container->set('file_url_generator', $fileGenerator);

    $container->set('watermark.config', $watermark_form);
  }

  /**
   * Test build form functionality of RRulesForm class.
   */
  public function testBuildForm() {
    $watermark = \Drupal::service('watermark.config');

    $form = [];
    $form_state = new FormState();
    $config_form = $watermark->buildForm($form, $form_state);

    // Form theme is system configuration type.
    $this->assertEquals('system_config_form', $config_form['#theme']);
    // Field types.
    $this->assertEquals('radios', $config_form['charts']['#type']);
  }

  /**
   * Test validate functionality of WatermarkForm class.
   */
  public function testValidateForm() {
    $watermark = \Drupal::service('watermark.config');

    // Test if it is the watermark config form using the form id.
    $this->assertEquals('trpcultivate_phenotypes_watermark_settings_form', $watermark->getFormId());

    // Method formValidate requires 2 parameters $form and $form_state.
    $form_state = new FormState();
    $form = [];

    // Validate that when choosing to watermark, an image file is required.
    $form_state->setValue('charts', 1);
    $form_state->setValue('file', []);
    $watermark->validateForm($form, $form_state);
    $this->assertTrue($form_state->hasAnyErrors());

    $form_state->clearErrors();

    // Validate that when choosing not to watermark,
    // an image file is not required.
    $form_state->setValue('charts', 0);
    $form_state->setValue('file', []);
    $watermark->validateForm($form, $form_state);
    $this->assertFalse($form_state->hasAnyErrors());
  }

}
