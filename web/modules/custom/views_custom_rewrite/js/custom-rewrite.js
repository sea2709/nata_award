(function ($) {
Drupal.behaviors.custom_rewrite = {
  attach: function (context, settings) {
    $(context).find('.custom_rewrite_field_remove_btn').on('click', function (e) {
      e.preventDefault();

      let row = $(this).closest('tr');
      row.hide();
      row.find('.custom_rewrite_field_value_text').val('');
      row.find('.custom_rewrite_field_label_text').val('');
    });
  }
};
})(jQuery);
