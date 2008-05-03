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
		$(':has(.profile,.userlist)')
			.find('#menu li:has(a[href*=/warriors/])')
				.addClass('current_page_parent')
			.end()
		.filter('.profile')
			.find('#warrior-sidebar ul.submenu-parent li:last-child')
				.hide();

		// donor page
		if ($.fieldValue) {	// has forms plugin included

			// create "back" link from submenu "Donate" (redundant)
			var login = window.location.search
				? window.location.search.split('=').pop().replace(/\./,'') + '/'
				: ''; // should never happen
			$('li', sub)
				.filter(':not(.current_page_item)')
					.hide()
				.end()
				.filter('.current_page_item')
					.removeClass('current_page_item')
					.find('a:first-child')
						.text('Back')
						.attr({
							href: WPFB.home + '/warrior/' + login
						});

			// ajaxify form
			$.ajaxSetup({ cache: false });
			$('#donor').ajaxForm({
				beforeSubmit: function() {
					var empties = $(':input', $('#donor')).filter(function() {
						return 0 === $.trim(this.value).length
					});
					if (empties.length) {
						alert('Please fill in all fields.');
						empties[0].focus();
						return false;
					}
					$(':submit').enable(false);
				},
				success: function(r) {
					if (!parseInt(r)) {
						alert(r);
						$(':submit').enable();
						$(':text:first').focus();
					} else {
						// $(this).clearForm();
						var url = "http://bmb.goemerchant.com/cart/cart.aspx" +
							"?ST=buy&Action=add&Merchant=racecharities&ItemNumber=" + r;
						var bmb = window.open(url, 'race_donation');
						if (bmb) setTimeout(function(){
							window.location = WPFB.home + '/donations/online/thankyou/'
						}, 10);
					}
				}
			});
		}
	}
});
