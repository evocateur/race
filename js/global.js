(function($){
	var services = {
		facebook: {
			dest: 'http://www.facebook.com/sharer.php?',
			args: 'toolbar=0,status=0,width=626,height=436',
			keys: { u: 'u', t: 't' }
		},
		myspace: {
			dest: 'http://www.myspace.com/Modules/PostTo/Pages/?',
			args: '',
			keys: { u: 'c', t: 't' }
		}
	};
	function social_share(e) {
		var s = services[e.target.rel.split(' ').pop().toLowerCase()],
			u = s.keys['u'] + '=' + encodeURIComponent(e.target.href),
			t = s.keys['t'] + '=' + encodeURIComponent("Support My Effort to Raise Awareness of Cancer Early-On"),
			d = s.dest + u + '&' + t + '&c&l';
		window.open(d, 'share', s.args);
		return false;
	}
	// hook facebox init to pass config
	$(document)
		.bind('init.facebox', function() {
			$.extend($.facebox.settings, WPFB.options);
		})
		.bind('reveal.facebox', function() {
			if ($('#facebox div.image').length)
				return;
			$.clipboardReady(function() {
				$("#facebox div.content")
				.find("button.clipper").click(function() {
					var text = $(this).prev('.clip-me').val();
					if (text) $.clipboard(text);
					return false;
				}).end()
				.find("button.anchor-clipper").click(function() {
					var clip = $(this).prevAll('.clip-me');
					var text = clip[0].value;
					text = text.replace(/REPLACE/, clip[1].value);
					if (text) $.clipboard(text);
					return false;
				}).end()
				.find('a[rel^=share]').click(social_share);
			}, { swfpath: WPFB.site + "/wp-content/themes/race/js/jquery.clipboard.swf" });
		});
})(jQuery);

jQuery(function($) {
	$('#header h1 a').attr('title','Click to return to RACE Charities homepage');
	$('#blog-description').html(
		$('#blog-description').text().replace(/\b([A-Z])/g, '<span class="acro">$1</span>')
	);

	if ($.fieldValue) {	// only pages with ajax have forms plugin included

		$.ajaxSetup({ cache: false }); // kinda cargo-cultish

		// /donations/online/warrior/?runner={login}
		$('#donor').ajaxForm({
			beforeSubmit: function() {
				var empties = $(':input', $('#donor')).filter(function() {
					return 0 === $.trim(this.value).length;
				});
				if (empties.length) {
					alert('Please fill in all fields.');
					empties[0].focus();
					return false;
				}
				$('#donor :submit').enable(false);
			},
			success: function(r) {
				if (!parseInt(r, 10)) {
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
				if (parseInt(r, 10) === 1 & 'object' == typeof WPFB)
					$.facebox({div:'#race_profile_update'}, 'login');
				else
					alert(r);
				$('#spinner').hide();
				$('#landing :submit').enable();
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
			// bind facebox launch to custom event
			profile.bind('updated', function() {
				$.facebox({div:'#race_message'}, 'profile');
			});
			// go to login page after timeout
			$(document).bind('close.facebox', function() {
				setTimeout(function() {
					window.location = WPFB.home + '/warriors/login/';
				}, 500);
			});
			// trigger facebox hook after timeout
			setTimeout(function(){ profile.triggerHandler('updated'); }, 1000);
		}
	}
});
/*
	$.clipboardReady(function() {
		$("a").click(function() {
			$.clipboard("You clicked on a link and copied this text!");
			return false;
		});
	}, { swfpath: WPFB.site + "/wp-content/themes/race/js/jquery.clipboard.swf" });
*/