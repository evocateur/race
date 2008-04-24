(function($) {
	$(function() {
		$('#header h1 a').attr('title','Click to return to RACE Charities homepage');
		$('#blog-description').html(
			$('#blog-description').text().replace(/\b([A-Z])/g, '<span class="acro">$1</span>')
		);
		$('table.zebra tr:odd').addClass('alt');
	});
})(jQuery);
