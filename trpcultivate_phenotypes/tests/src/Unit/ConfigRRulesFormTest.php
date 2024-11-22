<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Unit;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\trpcultivate_phenotypes\Form\TripalCultivatePhenotypesRSettingsForm;

/**
 * Class definition ConfigRRulesFormTest.
 *
 * @coversDefaultClass Drupal\trpcultivate_phenotypes\Form\TripalCultivatePhenotypesRSettingsForm
 * @group trpcultivate_phenotypes
 */
class ConfigRRulesFormTest extends UnitTestCase {

  private $rrulesform;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create container.
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    // Mock config since the RRules form extends configFormBase and expects
    // a configuration settings.
    $r_config_mock = $this->prophesize(Config::class);

    $r_config_mock->get('trpcultivate.phenotypes.r_config.chars')->willReturn([
      '(', ')', '/', '-', ':', ';', '%',
    ]);

    $r_config_mock->get('trpcultivate.phenotypes.r_config.words')->willReturn([
      'of', 'to', 'have', 'on', 'at',
    ]);

    $r_config_mock->get('trpcultivate.phenotypes.r_config.replace')->willReturn([
      '# = num', '/ = div', '? = unsure', '- = to',
    ]);

    // When RRules form rebuilds calling the module settings, return
    // only the R configuration settings above exclude other config.
    $all_config_mock = $this->prophesize(ConfigFactoryInterface::class);
    $all_config_mock->getEditable('trpcultivate_phenotypes.settings')
      ->willReturn($r_config_mock);

    // Isolated configuration for R Rules.
    $r_config = $all_config_mock->reveal();

    // Translation requirement of the container.
    $translation_mock = $this->prophesize(TranslationInterface::class);
    $translation = $translation_mock->reveal();

    // Typed Config Manager requirement for ConfigForm dependancy injection.
    $typed_config_mock = $this->prophesize(TypedConfigManagerInterface::class);
    $typed_config = $typed_config_mock->reveal();

    // Class RRulesForm class instance.
    $rrules_form = new TripalCultivatePhenotypesRSettingsForm($r_config, $typed_config);
    $rrules_form->setStringTranslation($translation);

    $container->set('rrules.config', $rrules_form);
    $this->rrulesform = \Drupal::service('rrules.config');
  }

  /**
   * Test submit form functionality of RRulesForm class.
   */
  /*
  public function testSubmitForm() {
  $form = [];
  $form_state = new FormState();

  $form_state->setValue('words', 'num,log');
  $form_state->setValue('chars', '#,*');
  $form_state->setValue('words', 'num,log');

  $this->rrulesform->submitForm($form, $form_state);
  }*/

  /**
   * Test build form functionality of RRulesForm class.
   */
  public function testFormId() {
    // Test if it is the RRules config form using the form id.
    $this->assertEquals('trpcultivate_phenotypes_r_settings_form', $this->rrulesform->getFormId());
  }

  /**
   * Test build form functionality of RRulesForm class.
   */
  public function testBuildForm() {
    $form = [];
    $form_state = new FormState();
    $config_form = $this->rrulesform->buildForm($form, $form_state);

    // Form theme is system configuration type.
    $this->assertEquals('system_config_form', $config_form['#theme']);
    // Field types.
    $this->assertEquals('textarea', $config_form['words']['#type']);
    $this->assertEquals('textarea', $config_form['chars']['#type']);
    $this->assertEquals('textarea', $config_form['replace']['#type']);
  }

  /**
   * Test validate functionality of RRulesForm class.
   */
  public function testValidateForm() {
    // Method formValidate requires 2 parameters $form and $form_state.
    $form_state = new FormState();
    $form = [];

    // Validation: WORDS Rule
    // words - any words at least 2 characters long and not and empty string.
    // Failed, Has validation error:
    $field = 'words';
    foreach (['R', 'r', '.', '~', '1', '       ', ' '] as $rule) {
      // Ensure we reset the form state after each iteration so tha
      // we are not accidentally keeping errors from previous iterations.
      $form_state->clearErrors();

      // Set the value in the form state -expecting an error.
      $form_state->setValue($field, $rule);

      // Call the validate and assert that there is an error.
      $this->rrulesform->validateForm($form, $form_state);
      $this->assertTrue($form_state->hasAnyErrors(),
        "We expected errors for '$rule' but there were not any.");
    }

    // Valid words:
    foreach (['Hello', 'hello', 'plant', 'seeds', 'this', 'that', 'no'] as $rule) {
      // Ensure we reset the form state after each iteration so that
      // we are not accidentally keeping errors from previous iterations.
      $form_state->clearErrors();

      $form_state->setValue($field, $rule);
      $this->rrulesform->validateForm($form, $form_state);

      $this->assertFalse($form_state->hasAnyErrors(),
        "The word '$rule' should be valid but there are form errors for some reason.");
    }

    // Validation: SPECIAL CHARACTERS Rule
    // chars - any special characters 1 character long.
    // Failed, Has validation error:
    $field = 'chars';
    foreach (['hello', ',', 'A', 'a', '1'] as $rule) {
      // Ensure we reset the form state after each iteration so that
      // we are not accidentally keeping errors from previous iterations.
      $form_state->clearErrors();

      $form_state->setValue($field, $rule);
      $this->rrulesform->validateForm($form, $form_state);

      $this->assertTrue($form_state->hasAnyErrors(),
        "We expected errors for '$rule' but there were not any.");
    }

    // Valid char:
    foreach (['~', '@', '>', '+', '-', '$', ':'] as $rule) {
      // Ensure we reset the form state after each iteration so that
      // we are not accidentally keeping errors from previous iterations.
      $form_state->clearErrors();

      $form_state->setValue($field, $rule);
      $this->rrulesform->validateForm($form, $form_state);

      $this->assertFalse($form_state->hasAnyErrors(),
        "The char '$rule' should be valid but there are form errors for some reason.");
    }

    // Validation: MATCH AND REPLACE Rule
    // match = replace - any non-whitespace value for match or replace and
    // must follow match = replace pattern.
    // Failed, Has validation error:
    $field = 'replace';
    foreach ([' ', ', = a', 'hi=hello', 'hi= hello', 'hi => hello'] as $rule) {
      // Ensure we reset the form state after each iteration so that
      // we are not accidentally keeping errors from previous iterations.
      $form_state->clearErrors();

      $form_state->setValue($field, $rule);
      $this->rrulesform->validateForm($form, $form_state);

      $this->assertTrue($form_state->hasAnyErrors(),
        "We expected errors for '$rule' but there were not any.");
    }

    // Valid words:
    foreach (['hi = hello', 'yes = no', 'a = b'] as $rule) {
      // Ensure we reset the form state after each iteration so that
      // we are not accidentally keeping errors from previous iterations.
      $form_state->clearErrors();

      $form_state->setValue($field, $rule);
      $this->rrulesform->validateForm($form, $form_state);

      $this->assertFalse($form_state->hasAnyErrors(),
        "The replacement '$rule' should be valid but there are form errors for some reason.");
    }
  }

}
