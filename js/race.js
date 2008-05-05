jQuery(function($) {
	$('#header h1 a').attr('title','Click to return to RACE Charities homepage');
	$('#blog-description').html(
		$('#blog-description').text().replace(/\b([A-Z])/g, '<span class="acro">$1</span>')
	);
	$('table.zebra tr:odd').addClass('alt');

	if ($.fieldValue) {	// only donor page has forms plugin included

		$.ajaxSetup({ cache: false }); // kinda cargo-cultish

		// ajaxify form
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
						window.location = window.location.href.replace(/warrior/,'thank-you');
					}, 10);
				}
			}
		});
	}
});
