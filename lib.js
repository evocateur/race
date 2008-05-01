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
		var sub = $('ul.submenu-parent');

		$('a[title=Signup],a[title=Login]', sub).each(function(i) {
			var iz = this.title.toLowerCase();
			var path = WPFB.site;
			var loc = location.pathname.replace(/(.*)(warriors\/)(.*\/)/, "$1$2");
			switch (iz) {
			case 'signup':
				path += ( loggedin
					? '/wp-admin/profile.php'
					: '/wp-login.php?action=register'
				);
				if (loggedin)
					$(this).text('Edit Profile').attr('title', 'Edit Profile');
			break;
			case 'login':
				path += '/wp-login.php' + ( loggedin
					? '?action=logout&redirect_to='+ loc
					: '?redirect_to='+ loc +'login/'
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

		// highlight ancestor on profile/userlist pages
		$(':has(.profile,.userlist) #menu li:has(a[href*=/warriors/])')
			.addClass('current_page_parent');

		// donor page
		if ($.fieldValue) {	// has forms plugin included

			// create "back" link from submenu "Donate" (redundant)
			var login = window.location.search
				? window.location.search.split('?').pop() + '/'
				: ''; // should never happen
			$('#submenu li.current_page_item')
				.removeClass('current_page_item')
				.find('a:first-child')
					.text('Back')
					.attr({
						href: WPFB.home + '/warrior/' + login
					});

		}
	}
});
