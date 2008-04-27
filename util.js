jQuery(function($) {
	$('#header h1 a').attr('title','Click to return to RACE Charities homepage');
	$('#blog-description').html(
		$('#blog-description').text().replace(/\b([A-Z])/g, '<span class="acro">$1</span>')
	);
	$('table.zebra tr:odd').addClass('alt');

	/*
		login/signup link tomfoolery (requires wp-facebox)
	*/
	if ('undefined' != typeof WPFB) {
		var loggedin = $(document.body).hasClass('loggedin');
		var sub = $('#submenu');

		$('a[title=Signup],a[title=Login]', sub).each(function(i) {
			var iz = this.title.toLowerCase();
			var path = WPFB.site;
			switch (iz) {
			case 'signup':
				path += (loggedin)
					? '/wp-admin/profile.php'
					: '/wp-login.php?action=register&redirect_to=warriors/signup/';
				if (loggedin)
					$(this).text('Edit Profile').attr('title', 'Edit Profile');
			break;
			case 'login':
				path += '/wp-login.php' + ( loggedin
					? '?action=logout&redirect_to=warriors/'
					: '?redirect_to=warriors/login/'
				);
				if (loggedin)
					$(this).text('Logout').attr('title', 'Logout')
						.parent('li').removeClass('current_page_item');
			break;
			}
			$(this)
				.attr({
					/*'rel': 'facebox.' + iz,*/
					'href': path
				});
		});
	}
});
