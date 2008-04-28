jQuery(function($){
	/*
		customizing the profile page
	*/
	var profile = $('body.wp-admin #profile-page');
	if (profile.length) {
		// resize text inputs
		$('table.form-table:not(.race) input[type=text]', profile)
			.css('width', '150px')
			.filter('#email')
				.css('width', '300px');
		// hide color options, website / IM options
		var hidem = [
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
