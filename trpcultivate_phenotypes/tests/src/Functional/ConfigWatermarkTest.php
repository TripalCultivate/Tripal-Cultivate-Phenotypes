<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Class definition ConfigWatermarkTest.
 *
 * Functional test of Watermark charts configuration page.
 *
 * @group trpcultivate_phenotypes_config_watermark
 */
class ConfigWatermarkTest extends BrowserTestBase {

  /**
   * Default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enabled.
   *
   * @var array
   */
  protected static $modules = [
    'tripal',
    'tripal_chado',
    'trpcultivate_phenotypes',
  ];

  /**
   * Admin user with admin privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $admin_user;

  /**
   * Test watermark configuration page.
   *
   * NOTE: unable to test file upload field, This test only when
   * choosing not to watermark any charts.
   *
   * @see tests/src/unit/ConfigWatermarkFormTest.php
   */
  public function testForm() {
    // Ensure we see all logging in tests.
    \Drupal::state()->set('is_a_test_environment', TRUE);

    // Setup admin user account.
    $admin_user = $this->drupalCreateUser([
      'administer site configuration',
      'administer tripal',
    ]);

    // Login admin user.
    $this->drupalLogin($admin_user);

    // Access watermark configuration page.
    $this->drupalGet('admin/tripal/extension/tripal-cultivate/phenotypes/watermark');
    $session = $this->assertSession();

    $session->statusCodeEquals(200);
    $session->pageTextContains('Configure Tripal Cultivate Phenotypes: Watermark Chart');

    // Update configuration settings.
    // Do not watermark any charts.
    $update_watermark = [
      'charts' => '0',
      // 'file' => '' test could not detect file field. ??
    ];

    // Fields and default value.
    $session->fieldExists('charts');
    $session->fieldValueEquals('charts', '0');

    // Could not seem to find this field in the form.
    // $session->fieldExists('file');
    // $session->fieldValueEquals('file', '');.
    // Submit form.
    $this->submitForm($update_watermark, 'Save configuration');

    // Assert configuration saved.
    $this->assertSession()->responseContains('The configuration options have been saved.');

    // Assert Fields reflect the updated configuration.
    $session->fieldValueEquals('charts', $update_watermark['charts']);

    $this->drupalLogout();
  }

}
