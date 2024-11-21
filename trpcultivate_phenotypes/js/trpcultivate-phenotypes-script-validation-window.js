/**
 * @file
 * Behaviors specific to validation result window, including how it displays,
 * and interacts with user.
 *
 * @see css/trpcultivate-phenotypes-style-result-window.css
 * @see templates/trpcultivate-phenotypes-template-result-window.html
 *
 */

(function ($, Drupal) {
  Drupal.behaviors.validationWindow = {
    attach: function (context, settings) {

      // Inspect each rendered validation item in the DOM to see if failed
      // details would required a visual cue indicating more items are
      // available for scroll.

      // Each detail window can grow vertically up to 200 px in height.
      // Any window exceeding this limit will have overflowing content to be
      // hidden, requiring the use of the scrollbar to view concealed content.
      // A visual cue element is added to partially reveal item at the bottom
      // edge of the details window.
      var heightLimit = 200;

      // Reference each details wrapper created per validation item.
      $('.tcp-result-window-details-wrapper', context).each(function () {
        var currWindow = $(this);

        if (currWindow.height() >= heightLimit) {
          currWindow
            .addClass('tcp-details-scroll')
            .append('<div class="tcp-details-partial-reveal"></div>');
        }
      });

      ///
    }
  }
}(jQuery, Drupal));
