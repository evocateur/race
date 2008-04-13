(function($) {
	$(function() {
		$('.gallery-item a').facebox({
			loadingImage: WP_THEME_ROOT + '/facebox/loading.gif',
			closeImage:   WP_THEME_ROOT + '/facebox/closelabel.gif'
		});
		$('table.zebra tr:odd').addClass('alt');
	});
})(jQuery);
