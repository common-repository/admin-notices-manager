"use strict";

/**
 * Pointer helper.
 *
 * @since 1.0.0
 */
jQuery(document).ready(function ($) {
  function open_pointer(i) {
    if (wpws_pointers[i]) {
      var pointer = wpws_pointers[i];
      var options = $.extend(pointer.options, {
        close: function close() {
          $.post(ajaxurl, {
            pointer: pointer.pointer_id,
            action: 'wpws_dismiss_wp_pointer'
          }); //	open next pointer if available

          open_pointer(i + 1);
        }
      }); //	open the pointer

      var pp = $(pointer.target).first().pointer(options);
      $('html, body').animate({
        // scroll page to pointer
        scrollTop: pp.offset().top - 30
      }, 300, function () {
        // when scroll complete
        var $widget = pp.pointer('widget');
        pp.pointer('open');
      });
    }
  } //	open the first pointer


  open_pointer(0);
});