(function($) {
	$(function() {
		$('#header h1 a').attr('title','Click to return to RACE Charities homepage');
		$('#blog-description').html(
			$('#blog-description').text().replace(/\b([A-Z])/g, '<span class="acro">$1</span>')
		);
		$('table.zebra tr:odd').addClass('alt');

		//* login/signup link tomfoolery (requires wp-facebox)
		if ('undefined' != typeof WPFB) {
			var loggedin = $(document.body).hasClass('loggedin');

			$('#submenu li.page_item a').filter(function(i) {
				return !$(this).attr('title').search(/^(signup|login)$/i);
			}).each(function(i) {
				var iz = this.title.toLowerCase();
				var path = WPFB.site;
				switch (iz) {
				case 'signup':
					path += (loggedin) ? '/wp-admin/profile.php' : '/wp-login.php?action=register';
					if (loggedin) $(this).text('Profile');
				break;
				case 'login':
					path += '/wp-login.php' + (loggedin ? '?action=logout' : '');
					if (loggedin) $(this).text('Logout');
				break;
				}
				$(this)
					.attr({
						/*'rel': 'facebox.' + iz,*/
						'href': path
					});
			});
		}
		//*/
	});
})(jQuery);
