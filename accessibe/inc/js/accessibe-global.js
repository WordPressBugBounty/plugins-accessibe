/**
 * accessiBe
 * https://accessibe.com/
 */

jQuery(document).ready(function ($) {
  if (typeof accessibe_pointers != "undefined") {
    $.each(accessibe_pointers, function (index, pointer) {
      if (index.charAt(0) == "_") {
        return true;
      }
      $(pointer.target)
        .pointer({
          content: "<h3>Web Accessibility by accessiBe</h3><p>" + pointer.content + "</p>",
          pointerWidth: 380,
          position: {
            edge: pointer.edge,
            align: pointer.align,
          },
          close: function () {
            $.get(ajaxurl, {
              _ajax_nonce: accessibe_vars.run_tool_nonce,
              action: "accessibe_dismiss_pointer",
            });
          },
        })
        .pointer("open");
    });
  }
}); // on ready
