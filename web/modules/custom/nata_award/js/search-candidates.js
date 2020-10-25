(function($) {
  // Argument passed from InvokeCommand.
  $.fn.showLoadMoreCandidatesBtn = function() {
    $('#load-more-1-wrapper').show();
    $('#load-more-2-wrapper').show();
  };

  $.fn.hideLoadMoreCandidatesBtn = function() {
    $('#load-more-1-wrapper').hide();
    $('#load-more-2-wrapper').hide();
  };
})(jQuery);
