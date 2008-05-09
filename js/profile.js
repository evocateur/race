jQuery(function($){
	/*
		customizing the profile page
	*/
	var profile = $('body.wp-admin #profile-page');
	if (profile.length) {
		// hide color options, website / IM options
		$("table:has(#email) tr:gt(0), tr:has(#user_login,#nickname,#display_name), h3", profile)
			.hide();

		// resize text inputs
		$('table.form-table:not(.race) :text', profile)
			.css('width', '150px')
			.filter('#email')
				.css('width', '300px');

		// heighten bio and rewrite helper text
		$('#description', profile)
			.css('height','150px')
			.parent('td')
				.contents('[nodeType=3]')
					.remove()
				.end()
				.append(
					"Please explain in your own words what you are event training and/or raising money for.",
					'<br/>',"This is the message that viewers of your custom page will see."
				)
			.prev('th')
				.text('Main Message');

		// if updated, pop a facebox with "response"
		if (window.location.search.indexOf('updated=true') >= 0) {
			// hook facebox init to pass config
			$(document).bind('init.facebox', function() {
				$.extend($.facebox.settings, WPFB.options);
			});
			// bind facebox launch to custom event
			profile.bind('updated', function() {
				$.facebox({div:'#race_message'});
			});
			// trigger facebox hook after timeout
			setTimeout(function(){ profile.triggerHandler('updated') }, 1000);
		}
	}
});
