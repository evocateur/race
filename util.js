(function($) {
	$(function() {
		$('#blog-description').html($('#blog-description').text().replace(/\b([A-Z])/g, '<span class="acro">$1</span>'));
		$('.gallery-item a').facebox({
			loadingImage: WP_THEME_ROOT + '/facebox/loading.gif',
			closeImage:   WP_THEME_ROOT + '/facebox/closelabel.gif'
		});
		$('table.zebra tr:odd').addClass('alt');
	});
})(jQuery);
