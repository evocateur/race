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
	}

	/*
		customizing the profile page
	*/
	var profile = $('body.wp-admin #profile-page');
	if (profile.length) {
		// retitle
		$('h2', profile).text('Profile Options');
		// resize text inputs
		$('table.form-table:not(#race) input[type=text]', profile)
			.css('width', '150px')
			.filter('#email')
				.css('width', '300px');
		// hide color options, website / IM options
		var hidem = [
			"table.form-table:first",
			"table.form-table:has(#email) tr:gt(0)",
			"tr:has(#user_login,#nickname,#display_name)",
			"h3"
		]
		$(hidem.join(','), profile).hide();
		// heighten bio and rewrite helper text
		var txt = [
			"Please explain in your own words what you are event training and/or raising money for.",
			"This is the message that viewers of your custom page will see."
		];
		$('#description', profile)
			.css('height','150px')
			.parent('td')
				.contents('[nodeType=3]')
					.remove().end()
				.append(txt[0], '<br/>', txt[1])
			.prev('th')
				.text('Main Message');
	}
});
