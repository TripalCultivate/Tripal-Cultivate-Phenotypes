/**
 * @file
 * Behaviours specific to validation result window.
 */

(function ($) {
  Drupal.behaviors.validationWindow = {
    attach: function (context, settings) {

      // Inspect each validation item to see if details require
      // visual cue to scroll.

      // Each detail window can only grow vertically up to 200px in height
      // and details that require more than this set height will have sections
      // to be concealed and will have to use the scroll bar.

      $('.tcp-result-window > ul li > div').each(function() {
        var detailsWindowHeight = $(this).height();

        var setClass = (detailsWindowHeight >= 200) ? 'tcp-content-partial-reveal' : 'tcp-content-full-view';
        $(this).find('.tcp-scroll-visual-cue').addClass(setClass);
      });

      ///
    }
  }
}(jQuery));
