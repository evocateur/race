(function($) {
	$(function() {
		$('#blog-description').html(
			$('#blog-description').text().replace(/\b([A-Z])/g, '<span class="acro">$1</span>')
		);
		$('table.zebra tr:odd').addClass('alt');
	});
})(jQuery);
