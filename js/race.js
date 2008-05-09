jQuery(function($) {
	$('#header h1 a').attr('title','Click to return to RACE Charities homepage');
	$('#blog-description').html(
		$('#blog-description').text().replace(/\b([A-Z])/g, '<span class="acro">$1</span>')
	);
	$('table.zebra tr:odd').addClass('alt');

	if ($.fieldValue) {	// only pages with ajax have forms plugin included

		$.ajaxSetup({ cache: false }); // kinda cargo-cultish

		// /donations/online/warrior/?runner={login}
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
				$('#donor :submit').enable(false);
			},
			success: function(r) {
				if (!parseInt(r)) {
					alert(r);
					$('#donor :submit').enable();
					$('#donor :text:first').focus();
				} else {
					// $(this).clearForm();
					var url = "http://bmb.goemerchant.com/cart/cart.aspx" +
						"?ST=buy&Action=add&Merchant=racecharities&ItemNumber=" + r;
					var bmb = window.open(url, 'race_donation');
					if (bmb) setTimeout(function(){
						window.location = window.location.href.replace(/warrior/,'thank-you');
					}, 10);
				}
			}
		});

		// /warriors/login/
		$('#landing').ajaxForm({
			beforeSubmit: function() {
				$('#landing :submit').enable(false);
				$('#spinner').show();
			},
			success: function(r) {
				$('#spinner').hide();
				$('#landing :submit').enable();
				if ('object' == typeof WPFB) {
					$.facebox({div:'#race_message'});
				}
			}
		}).find('select, input[type=checkbox]').attr('tabindex', 1);
	}

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
