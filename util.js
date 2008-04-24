(function($) {
	$(function() {
		$('#header h1 a').attr('title','Click to return to RACE Charities homepage');
		$('#blog-description').html(
			$('#blog-description').text().replace(/\b([A-Z])/g, '<span class="acro">$1</span>')
		);
		$('table.zebra tr:odd').addClass('alt');
		/* hiding login/signup links when already logged in
		$('body.loggedin #submenu li.page_item').filter(function(i) {
			return !$('a', this).attr('title').search(/^(signup|login)$/i);
		}).css('display','none');
		//*/
	});
})(jQuery);
