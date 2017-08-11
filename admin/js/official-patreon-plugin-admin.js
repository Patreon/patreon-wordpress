(function ($) {
  'use strict';

  /**
   * Shows spinner on form submit
   * @return {undefined}
   */
  var showSpinner = function () {
    $('form').submit(function () {
      $(this).find('.spinner').addClass('is-active');
    });
  };

  $(document).ready(function () {
    showSpinner();
  });

})(jQuery);
