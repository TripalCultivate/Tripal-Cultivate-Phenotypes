<?php

namespace Drupal\Tests\trpcultivate_phenotypes\Kernel\Display\DisplayValidationResultWindowTest;

use Drupal\Core\Url;
use Drupal\Tests\tripal_chado\Kernel\ChadoTestKernelBase;

/**
 * Tests Tripal Cultivate Phenotypes Validation Result Window.
 *
 * @group trpcultivate_phenotypes
 * @group displays
 */
class DisplayValidationResultWindowTest extends ChadoTestKernelBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'tripal',
    'tripal_chado',
    'trpcultivate_phenotypes',
  ];

  /**
   * Drupal render service.
   *
   * @var Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set test environment.
    \Drupal::state()->set('is_a_test_environment', TRUE);

    $this->renderer = $this->container->get('renderer');
  }

  /**
   * Data Provider: Provides the template with a set of validation item values.
   *
   * @return array
   *   Each scenario/element is an array with the following values.
   *   - A string, human-readable short description of the test scenario.
   *   - One or more validation status arrays. Each array contains the following
   *     keys:
   *     - 'title': Validation title text that is paired with appropriate status
   *        icon in the markup output.
   *     - 'status': The status of the validation that indicates if it passed
   *        (pass), failed (fail) or upcoming task (todo).
   *     - 'details': A Drupal render array that will generate the required
   *        markup to output any failed items, only if 'status'
   *        is set to 'fail'.
   *   - The expected class name an item is tagged with. Each validation item
   *     corresponds to one class name based on the value of the status key.
   */
  public function provideValidationResultRenderArray() {

    return [
      // #0: A passing validation.
      [
        'passing validator',
        [
          [
            'title' => 'Validation Title Text - Pass',
            'status' => 'pass',
            'details' => [],
          ],
        ],
        ['tcp-validate-pass'],
      ],
      // #1: An upcoming validation.
      [
        'upcoming validator',
        [
          [
            'title' => 'Validation Title Text - Todo',
            'status' => 'todo',
            'details' => [],
          ],
        ],
        ['tcp-validate-todo'],
      ],
      // #2: A failed validator: one item without bullet point.
      [
        'failed and one item without bullet point',
        [
          [
            'title' => 'Validation Title Text - Fail',
            'status' => 'fail',
            'details' => [
              '#type' => 'item',
              '#title' => 'Validator Case Message',
              '#markup' => 'Failed Item Name: Failed Item Value',
            ],
          ],
        ],
        ['tcp-validate-fail'],
      ],
      // #3: A failed validator: one item with bullet point.
      [
        'failed and one item with bullet point',
        [
          [
            'title' => 'Validation Title Text - Fail',
            'status' => 'fail',
            'details' => [
              '#type' => 'item',
              '#title' => 'Validator Case Message',
              'items' => [
                '#theme' => 'item_list',
                '#type' => 'ul',
                '#items' => [
                  'Failed Item #1',
                ],
              ],
            ],
          ],
        ],
        ['tcp-validate-fail'],
      ],
      // #4: A failed validator: many items with bullet points.
      [
        'failed and many items with bullet points',
        [
          [
            'title' => 'Validation Title Text - Fail',
            'status' => 'fail',
            'details' => [
              '#type' => 'item',
              '#title' => 'Validator Case Message',
              'items' => [
                '#theme' => 'item_list',
                '#type' => 'ul',
                '#items' => [
                  'Failed Item #1',
                  'Failed Item #2',
                  'Failed Item #3',
                  'Failed Item #4',
                  'Failed Item #5',
                  'Failed Item #6',
                  'Failed Item #7',
                  'Failed Item #8',
                  'Failed Item #9',
                  'Failed Item #10',
                  'Failed Item #11',
                  'Failed Item #12',
                  'Failed Item #13',
                  'Failed Item #14',
                  'Failed Item #15',
                ],
              ],
            ],
          ],
        ],
        ['tcp-validate-fail'],
      ],
      // #5: A failed validator: one row in a table element.
      [
        'failed and one row in a table element',
        [
          [
            'title' => 'Validation Title Text - Fail',
            'status' => 'fail',
            'details' => [
              '#type' => 'html_tag',
              '#tag' => 'ul',
              'lists' => [
                [
                  '#type' => 'html_tag',
                  '#tag' => 'li',
                  'table' => [
                    '#type' => 'table',
                    '#caption' => 'Validator Case Message',
                    '#header' => ['Header 1', 'Header 2'],
                    '#attributes' => [],
                    '#rows' => [
                      ['Row 1 - Value 1', 'Row 1 - Value 2'],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
        ['tcp-validate-fail'],
      ],
      // #6: Failed validator: one row in table element with wrapping CSS rule.
      [
        'failed and one row in a table element with attributes',
        [
          [
            'title' => 'Validation Title Text - Fail',
            'status' => 'fail',
            'details' => [
              '#type' => 'html_tag',
              '#tag' => 'ul',
              'lists' => [
                [
                  '#type' => 'html_tag',
                  '#tag' => 'li',
                  'table' => [
                    '#type' => 'table',
                    '#caption' => 'Validator Case Message',
                    '#header' => ['Header 1', 'Header 2'],
                    '#attributes' => ['class' => ['tcp-raw-row']],
                    '#rows' => [
                      ['Row #1 - Value #1', 'Row #1 - Value #2'],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
        ['tcp-validate-fail'],
      ],
      // #7: A failed validator: many rows in a table element.
      [
        'failed and many rows in a table element',
        [
          [
            'title' => 'Validation Title Text - Fail',
            'status' => 'fail',
            'details' => [
              '#type' => 'html_tag',
              '#tag' => 'ul',
              'lists' => [
                [
                  '#type' => 'html_tag',
                  '#tag' => 'li',
                  'table' => [
                    '#type' => 'table',
                    '#caption' => 'Validator Case Message',
                    '#header' => ['Header 1', 'Header 2', 'Header 3'],
                    '#attributes' => [],
                    '#rows' => [
                      ['Row #1 - Value #1', 'Row #1 - Value #2', 'Row #1 - Value #3'],
                      ['Row #2 - Value #1', 'Row #2 - Value #2', 'Row #2 - Value #3'],
                      ['Row #3 - Value #1', 'Row #3 - Value #2', 'Row #3 - Value #3'],
                      ['Row #4 - Value #1', 'Row #4 - Value #2', 'Row #4 - Value #3'],
                      ['Row #5 - Value #1', 'Row #5 - Value #2', 'Row #5 - Value #3'],
                      ['Row #6 - Value #1', 'Row #6 - Value #2', 'Row #6 - Value #3'],
                      ['Row #7 - Value #1', 'Row #7 - Value #2', 'Row #7 - Value #3'],
                      ['Row #8 - Value #1', 'Row #8 - Value #2', 'Row #8 - Value #3'],
                      ['Row #9 - Value #1', 'Row #9 - Value #2', 'Row #9 - Value #3'],
                      ['Row #10 - Value #1', 'Row #10 - Value #2', 'Row #10 - Value #3'],
                      ['Row #11 - Value #1', 'Row #11 - Value #2', 'Row #11 - Value #3'],
                      ['Row #12 - Value #1', 'Row #12 - Value #2', 'Row #12 - Value #3'],
                      ['Row #13 - Value #1', 'Row #13 - Value #2', 'Row #13 - Value #3'],
                      ['Row #14 - Value #1', 'Row #14 - Value #2', 'Row #14 - Value #3'],
                      ['Row #15 - Value #1', 'Row #15 - Value #2', 'Row #15 - Value #3'],
                    ],
                  ],
                ],
              ],
            ],
          ],
        ],
        ['tcp-validate-fail'],
      ],
      // #7: An image.
      [
        'an image',
        [
          [
            'title' => 'Validation Title Text - Fail',
            'status' => 'fail',
            'details' => [
              '#theme' => 'image',
              '#style_name' => 'thumbnail',
              '#uri' => 'public://logo.svg',
            ],
          ],
        ],
        ['tcp-validate-fail'],
      ],
      // #8: A link type Drupal render array.
      [
        'a link',
        [
          [
            'title' => 'Validation Title Text - Fail',
            'status' => 'fail',
            'details' => [
              '#type' => 'link',
              '#title' => 'KnowPulse',
              '#url' => Url::fromUri('https://knowpulse.usask.ca'),
            ],
          ],
        ],
        ['tcp-validate-fail'],
      ],
      // #9: Pass, fail and todo concurrent validation items.
      [
        'concurrent validation items',
        [
          [
            'title' => 'Validation Title Text - Pass',
            'status' => 'pass',
            'details' => [],
          ],
          [
            'title' => 'Validation Title Text - Fail',
            'status' => 'fail',
            'details' => [
              '#type' => 'item',
              '#title' => 'Validator Case Message',
              'items' => [
                '#theme' => 'item_list',
                '#type' => 'ul',
                '#items' => [
                  'Failed Item #1',
                ],
              ],
            ],
          ],
          [
            'title' => 'Validation Title Text - Todo',
            'status' => 'todo',
            'details' => [],
          ],
        ],
        [
          'tcp-validate-pass',
          'tcp-validate-fail',
          'tcp-validate-todo',
        ],
      ],
      // #10: Malformed render array.
      [
        'malformed render array',
        [
          [
            'title' => 'Validation Title Text - Fail',
            'status' => 'fail',
            'details' => [
              '#not_my_type' => 'not_a_type',
              '#not_a_property' => 'some property',
              '#markup' => 'Lorem ipsum dolor sit amet',
              'table' => [
                '#type' => 'table',
                '#captionzzzz' => 'Validator Case Message',
                '#header-array' => ['Header 1', 'Header 2'],
                '#row' => [
                  ['Row #1 - Value #1', 'Row #1 - Value #2', 'Row #1 - Value #3', 'Row #1 - Value #4'],
                ],
              ],
            ],
          ],
        ],
        ['tcp-validate-fail'],
      ],
    ];
  }

  /**
   * Test result window display.
   *
   * @param string $scenario
   *   A string, human-readable short description of the test scenario.
   * @param array $validation_result_input
   *   One or more validation status arrays. Each array contains the following
   *     keys:
   *     - 'title': Validation title text that is paired with appropriate status
   *        icon in the markup output.
   *     - 'status': The status of the validation that indicates if it passed
   *        (pass), failed (fail) or upcoming task (todo).
   *     - 'details': A Drupal render array that will generate the required
   *        markup to output any failed items, only if 'status'
   *        is set to 'fail'.
   * @param array $expected_class
   *   The expected class name an item is tagged with. Each validation item
   *   corresponds to one class name based on the value of the status key.
   *
   * @dataProvider provideValidationResultRenderArray
   */
  public function testResultWindowDisplay(string $scenario, array $validation_result_input, array $expected_class) {

    $validation_window = [
      '#type' => 'inline_template',
      '#theme' => 'result_window',
      '#data' => [
        'validation_result' => $validation_result_input,
      ],
    ];

    // This is now the validation result window markup with all validation
    // result details rendered.
    $validation_window_markup = $this->renderer->renderRoot($validation_window);

    // For every validation item in a test scenario, test that:
    // 1. The number of validation items matches the number of list markup (li).
    // Get all list item element used to wrap every validation item.
    preg_match_all('/<li class="tcp-validate-(?:pass|fail|todo)" title="[^"]*"?>/', $validation_window_markup, $matches);

    $this->assertEquals(
      count($validation_result_input),
      count($matches[0]),
      'The number of validation items do not match the number of list markup items in scenario ' . $scenario
    );

    foreach ($validation_result_input as $i => $validation_item) {
      // 2. The validation item has been set the correct status-based class.
      $class_name = $expected_class[$i];
      $this->assertStringContainsString(
        $class_name,
        $validation_window_markup,
        'The class name ' . $class_name . ' of validation input #' . $i . ' in scenario ' . $scenario . ', was not found in the validation window markup'
      );

      // 3. The order of validation item is as the order it was in the array.
      $title_text = $validation_item['title'];
      $this->assertStringContainsString(
        $title_text,
        $matches[0][$i],
        'The title text ' . $title_text . ' of validation input #' . $i . ' in scenario ' . $scenario . ', was not in the expected order in the validation window markup'
      );

      // 4. The validation item has the correct title text.
      $this->assertStringContainsString(
        $title_text,
        $validation_window_markup,
        'The title text ' . $title_text . ' of validation input #' . $i . ' in scenario ' . $scenario . ', was not found in the validation window markup'
      );

      // 5. The details rendered markup is present in the overall markup output.
      $details_markup = $this->renderer->renderRoot($validation_item['details']);
      $this->assertStringContainsString(
        $details_markup,
        $validation_window_markup,
        'The details markup of validation input #' . $i . ' in scenario ' . $scenario . ', was not found in the validation window markup'
      );
    }
  }

}
