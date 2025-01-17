<?php

namespace Drupal\Tests\trpcultivate_phenocollect\Functional;

use Drupal\Core\Url;
use Drupal\Tests\tripal_chado\Functional\ChadoTestBrowserBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group TripGeno Genetics
 * @group Installation
 */
class InstallTest extends ChadoTestBrowserBase {

  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['help', 'trpcultivate_phenotypes', 'trpcultivate_phenocollect'];

  /**
   * The name of your module in the .info.yml
   */
  protected static $module_name = 'Phenotypic Data Collection';

  /**
   * The machine name of this module.
   */
  protected static $module_machinename = 'trpcultivate_phenocollect';

  /**
   * A small excert from your help page.
   * Do not cross newlines.
   */
  protected static $help_text_excerpt = 'tools to backup and upload phenotypic data while it is being collected in a access controlled environment.';

  /**
   * Tests that a specific set of pages load with a 200 response.
   */
  public function testLoad() {
    $session = $this->getSession();

    // Ensure we have an admin user.
    $user = $this->drupalCreateUser(['access administration pages', 'administer modules']);
    $this->drupalLogin($user);

    $context = '(modules installed: ' . implode(',', self::$modules) . ')';

    // Front Page.
    $this->drupalGet(Url::fromRoute('<front>'));
    $status_code = $session->getStatusCode();
    $this->assertEquals(200, $status_code, "The front page should be able to load $context.");

    // Extend Admin page.
    $this->drupalGet('admin/modules');
    $status_code = $session->getStatusCode();
    $this->assertEquals(200, $status_code, "The module install page should be able to load $context.");
    $this->assertSession()->pageTextContains( self::$module_name );

  }

  /**
   * Tests the module overview help.
   */
  public function testHelp() {
    $session = $this->getSession();

    $some_extected_text = self::$help_text_excerpt;

    // Ensure we have an admin user.
    $permissions = ['access administration pages','administer modules', 'access help pages'];
    $user = $this->drupalCreateUser($permissions);
    $this->drupalLogin($user);

    $context = '(modules installed: ' . implode(',', self::$modules) . ')';

    // Call the hook to ensure it is returning text.
    $name = 'help.page.' . $this::$module_machinename;
    $match = $this->createStub(\Drupal\Core\Routing\RouteMatch::class);
    $hook_name = self::$module_machinename . '_help';
    $output = $hook_name($name, $match);
    $this->assertNotEmpty($output, "The help hook should return output $context.");
    $this->assertStringContainsString($some_extected_text, $output);

    // Help Page.
    $this->drupalGet('admin/help');
    $status_code = $session->getStatusCode();
    $this->assertEquals(200, $status_code, "The admin help page should be able to load $context.");
    $this->assertSession()->pageTextContains(self::$module_name);
    $this->drupalGet('admin/help/' . self::$module_machinename);
    $status_code = $session->getStatusCode();
    $this->assertEquals(200, $status_code, "The module help page should be able to load $context.");
    $this->assertSession()->pageTextContains($some_extected_text);
  }

}
