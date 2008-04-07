// jQuery suckerfish
(function($) {
	/*if ($.browser.msie) $(function() {
		$('#nav li').hover(
			function() { $(this).addClass('hover'); },
			function() { $(this).removeClass('hover'); }
		);
	});*/
	$(function() {
		$('table.zebra tr:odd').addClass('alt');
	});
})(jQuery);
