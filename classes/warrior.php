<?php

class RACE_Warrior {
	var $user;
	var $user_ID;

	function RACE_Warrior( $login, $type = 'donor' ) { // constructor
		$this->type = $type;
		$this->set_user( $login );
		$this->class_config();
	}

	function class_config() {
		// race_maybe_update_donor_schema( $this->user_ID );
		$this->amounts = array(
			'101' => 10,
			'102' => 20,
			'103' => 50,
			'104' => 75,
			'105' => 100,
			'106' => 250,
			'107' => 500,
			'108' => 750
		);
		$this->ajax_url = RACE_THEME_ROOT_URI . '/templates/ajax.php';
		$this->response = NULL;
		$this->error    = NULL;
		$this->instance_config();
	}

	function instance_config() {
		// overwritten by subclass
	}

	function set_user( $u ) {
		// defaults
		$this->user    = NULL;
		$this->user_ID = NULL;

		// $u may be a numeric id or an object
		$user = ( is_object( $u ) ? $u : (
			is_numeric( $u )
			? get_userdata( $u )
			: get_userdatabylogin( $u )
		) );

		if ( $user ) {
			$this->user      = $user;
			$this->user_ID   = (int) $user->ID;
			$this->profile   = $user->race_profile;
			$this->full_name = $user->first_name . ' ' . $user->last_name;
			$this->nonce_key = "race-warrior-{$this->user_ID}-{$this->type}";
			$this->donor_url = get_option('home') . '/warrior/' . $user->user_nicename . '/';
		}
	}

	function valid_request() {
		return check_ajax_referer( $this->nonce_key , '_ajax_nonce', false );
	}

	function get_klass_var( $v ) {
		return $this->{$v};
	}

	function merge_default_options() {
		$this->options = array_merge( array(
			 'street' => ''
			,'city'   => ''
			,'state'  => ''
			,'zip'    => ''
			,'phone'  => ''
			,'goal'   => ''
			,'event'  => 1
		), array_filter( (array) $this->profile ) );
	}

	function amount_select( $selected = '' ) {
		$data = array_values( $this->amounts );

		if ( empty( $data ) )
			return '';

		extract( array_merge(
			array(
				'name'     => "{$this->type}[{$this->select_key}]",
				'id'       => "{$this->type}-{$this->select_key}",
				'indent'   => 5,
				'echo'     => true
			),
			array_filter( (array) $this->select_opt )
		), EXTR_SKIP );

		$t = "\n" . str_repeat("\t", $indent);
		$selected = (int) $selected;
		$index = '';
		if ( isset( $tabindex ) )
			$index = " tabindex=\"$tabindex\"";

		$dollar = "<span class=\"dollar\">\$</span>";

		$sel[] = "$dollar<select name=\"$name\" class=\"amount\" id=\"$id\"$index>";
		$sel[] = "\t<option value=\"\">....</option>";
		foreach ( $data as $value ) {
			$sel[] = "\t<option value=\"$value\">$value</option>";
		}
		$sel[] = "</select>\n";

		if ( ! empty( $selected ) ) {
			$sel = preg_replace("/value=\"$selected\"/", "value=\"$selected\" selected=\"selected\"", $sel, 1 );
		}

		$tag = implode( "$t", $sel );

		if ( $echo )
			echo $tag;
		else
			return $t . $tag;
	}

	function ajaxFormAction() {
		echo $this->get_klass_var('ajax_url');
	}

	function ajaxSpinner() {
		$spinner = '<img src="'.RACE_THEME_ROOT_URI.'/images/spinner.gif" id="spinner" alt="" />';
		echo $spinner . "\n";
	}

	function getFullName() {
		echo $this->get_klass_var('full_name');
	}

	function getUserID() {
		echo $this->get_klass_var('user_ID');
	}

	function generateNonce( $echo = true ) {
		$nonce = '<input type="hidden" name="_ajax_nonce"  value="';
		$nonce .= wp_create_nonce( $this->nonce_key ) . "\" />\n";
		if ( $echo ) echo $nonce; else return $nonce;
	}

	function donorLink() {
		$url = $this->get_klass_var('donor_url');
		echo '<a href="', $url, '">Sponsor me at RACE Charities</a>';
	}

	function getResponse() {
		return ( $this->error ) ? $this->error : $this->response;
	}

	function setError( $text ) {
		$this->error = $text;
	}
}

class RACE_Warrior_Donor	extends RACE_Warrior {

	function RACE_Warrior_Donor( $user ) { // constructor
		$this->RACE_Warrior( $user );
	}

	function instance_config() {
		$this->set_links();
		$this->select_key = 'amount';
		$this->select_opt = array(
			'indent'   => 3,
			'tabindex' => 1
		);
		wp_enqueue_script( 'jquery-form' );
	}

	function set_links() {
		$this->links = array();
		$home = get_option('home');
		// TODO: merge with db options
		$defaults = array(
			'privacy' => '/home/privacy-policy/',
			'club'    => '/donations/warriors-club/',
			'warrior' => '/warriors/',
			'signup'  => '/warriors/signup/'
		);
		foreach ( $defaults as $link => $path ) {
			$this->links[$link] = $home . $path;
		}
	}

	function pageLink( $key = '', $text = 'here', $echo = true ) {
		$r = '';
		if ( array_key_exists( $key, $this->links ) ) {
			$r = "<a href=\"{$this->links[$key]}\">$text</a>";
		}
		if ( $echo ) echo $r; else return $r;
	}

	function display() {
		// TODO: modal "proceed to checkout", multiple additions possible...
		?>

<form name="donor" id="donor" action="<?php $this->ajaxFormAction(); ?>" method="POST">
<table class="warrior">
<tfoot>
	<tr>
		<td colspan="2">
			<p class="emphatic note"><strong>Note</strong>: All donations are final, no refunds. <?php $this->pageLink('privacy', 'View the privacy policy'); ?>.</p>
			<p>RACE Charities is a 501(c)3 organization with all donations being tax deductible.  Donations received will go toward Glory’s original vision of fighting cancer and advancing all early detection research and development. Becca Murfin is the Glory Gensch Fund trustee and handles the accounting as well as the donation statements and 'Thank You' letters.</p>
			<p>Donation Checks can be mailed and made payable to:</p>
			<address>
				RACE Charities, Inc.<br />
				P.O. Box 1976<br />
				Denver, CO 80201
			</address>
			<p>Each donor is added to the official <?php $this->pageLink('club', 'RACE Charities Warrior List'); ?>.</p>
			<p>Please consider joining us in our fundraising efforts as a <?php $this->pageLink('warrior', 'WARRIOR-RUNNER'); ?>. You do not have to actually run in a RACE event in order to become an offical WARRIOR-RUNNER. <?php $this->pageLink('signup', 'Sign up to become a WARRIOR-RUNNER here') ?>. Each WARRIOR-RUNNER sends their custom hyperlink to people to donate to their own specific fund-raising account.</p>
		</td>
	</tr>
</tfoot>
<tbody>
	<tr class="controls">
		<td id="pledge" class="center" colspan="2">
			Pledge
			<?php $this->amount_select(); ?>
			toward <strong><?php $this->getFullName(); ?>&#8217;s</strong> goal!
		</td>
	</tr>
	<tr>
		<th>Donor Information</th>
		<td class="detail" rowspan="2">
			<p class="emphatic">We consider the term 'donation' to have <em>several</em> meanings, not merely monetary.</p>
			<p>Sacrificing time and energy to volunteer for helping or actively participate in one of RACE’s events are considered donations. Taking time to discuss with physicians or medical administrators the importance of awareness of family history of cancer on forms or consultations as a mandatory part of patient visits is also considered a donation.</p>
			<p><em>Any</em> sacrifice that promotes the <strong><em>early detection of cancer</em></strong> is a valuable donation.</p>
		</td>
	</tr>
	<tr>
		<td>
			<label for="donor_name">Name<input type="text" name="donor[name]" value="" tabindex="1" id="donor-name" /></label><br />
			<label for="donor_email">Email<input type="text" name="donor[email]" value="" tabindex="1" id="donor-email" /></label><br />
			<label for="donor_city">City<input type="text" name="donor[city]" value="" tabindex="1" id="donor-city" /></label>
			<label for="donor_state" class="center">State<input type="text" name="donor[state]" value="" tabindex="1" id="donor-state" /></label>
		</td>
	</tr>
	<tr class="controls">
		<td class="center submit" colspan="2">
			<input type="submit" value="Submit" id="donor-submit" tabindex="10" />
			<input type="hidden" name="warrior_id" value="<?php $this->getUserID(); ?>" />
			<?php $this->generateNonce(); ?>
		</td>
	</tr>
</tbody>
</table>
</form>

<?php
	}
}

class RACE_Warrior_Pledge	extends RACE_Warrior {

	function RACE_Warrior_Pledge( $user ) { // constructor
		$this->RACE_Warrior( $user );
	}

	function instance_config() {
		$event_id = (int) $this->profile['event'];
		$this->event_id = $event_id ? $event_id : RACE_EVENT_ID_HACK;
		$this->umetakey = 'race_donors';
	}

	function add( $postage ) {
		/*
			eventually, real tables will replace the utterly hacky usermeta stash

		race_donor
			id  	PK (FK = race_donation.donor_id)
			name
			email	(unique)
			city
			state	FK (states.id)

		race_donation
			id  		PK (FK = ?)
			donor_id	FK (race_donor)
			event_id	FK (race_event)
			user_id		FK (users)
			amount	(int)

		race_event
			id  	PK (FK = usermeta->race_profile->current_event_id)
			name	IDX
			date

		*/
		$postage = array_map( 'race_escape', (array) $postage );

		// email parsing (special attention because it is effectively a unique identifier)
		$email = $postage['email'] = sanitize_email( $postage['email'] );
		if ( ! is_email( $email ) ) {
			$this->setError( 'Invalid Email' );
			return false;
		}

		$donor_key = $email;
		$event_key = 'event_' . $this->event_id;

		if ( $events = get_usermeta( $this->user_ID, $this->umetakey ) ) {
			// previous record(s)
			if ( array_key_exists( $event_key, $events ) ) {
				$donors =& $events[ $event_key ];
				// existing array
				if ( ! array_key_exists( $donor_key, (array) $donors ) ) {
					$donors[ $donor_key ] = $postage;
				} else {
					$this->setError( 'Duplicate Donor For Event/Runner' );
					return false;
				}
			} else {
				// new array
				$events[ $event_key ][ $donor_key ] = $postage;
			}
		} else {
			// completely new record
			$events[ $event_key ][ $donor_key ] = $postage;
		}

		if ( ! $this->error && $um = update_usermeta( $this->user_ID, $this->umetakey, $events ) ) {
			$this->response = $this->mapItemToAmount( $postage['amount'] );
			return true;
		} else {
			$this->setError( 'Error Updating Usermeta' );
			return false;
		}
	}

	function mapItemToAmount( $amount ) {
		if ( $needle = array_search( (int) $amount, $this->amounts ) )
			return "$needle";
		else {
			$this->setError( 'Amount not associated with a donation id' );
			return false;
		}
	}
}

class RACE_Warrior_Profile	extends RACE_Warrior {

	function RACE_Warrior_Profile( $user ) { // constructor
		$this->RACE_Warrior( $user, 'race_profile' );
	}

	function instance_config() {
		$this->select_key = 'goal';
		// overwrite parent amounts
		$this->amounts = array(
			50, 100, 150, 250, 300, 400, 500, 600, 750, 1000, 1500, 2000
		);
		$this->merge_default_options();

		if ( is_admin() ) $this->hook();
		else wp_enqueue_script('jquery-form'); // login
	}

	function get( $key, $echo = true ) {
		if ( $ok = $this->options[ "$key" ] )
			if ( $echo ) echo $ok; else return $ok;
		return '';
	}

	function hook() {
		add_action('admin_print_scripts',      array( &$this, 'css_js' ),   1);

		add_action('profile_personal_options', array( &$this, 'form_top' ), 1);
		add_action('edit_user_profile',        array( &$this, 'form_top' ), 1);

		add_action('edit_user_profile', array( &$this, 'form_bottom' ), 9);
		add_action('show_user_profile', array( &$this, 'form_bottom' ), 9);

		add_action('profile_update',    array( &$this, 'form_process' ) );
	}

	function css_js() {
		$link = '<link rel="stylesheet" type="text/css" href="';
		$link .= RACE_THEME_ROOT_URI . '/css/profile.css" />';
		echo "$link\n";
	}

	function form_top() {
		$title = ( defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE ) ? 'Profile Options' : 'Edit User';
		// alas the odiousness of inline script (to hide color options)
		if ( is_admin() ):
?>
<script type="text/javascript">
(function(){
    var pp = document.getElementById('profile-page'), ft = pp.getElementsByTagName('TABLE'), h2 = pp.getElementsByTagName('H2');
    if (ft[0] && ft[0].className == 'form-table') ft[0].style.display = 'none';
    if (h2[0] && h2[0].firstChild.nodeType == 3)  h2[0].replaceChild(document.createTextNode("<?php echo $title; ?>"), h2[0].firstChild);
})();
</script>
<?php
		endif;
		/*
			TODO: "reset current progress" amount
				(changing events until target reset in-place)

			TODO: "reset target" of fundraising (different events)
					means seperated tracking

			TODO: "remaining" ? (diff target total)
		*/
?>
	<table class="form-table race" id="race-top">
		<tbody>
			<tr>
				<th><label for="race-goal">Fundraising Goal</label></th>
				<td>
					<?php $this->amount_select( $this->get('goal', false) ); ?>
					<span class="total">( <?php echo $this->totalPledged(); ?> )</span>
					<label for="race_reset_pledges" class="inline"><input type="checkbox" name="race_reset_pledges" value="1" id="race_reset_pledges" />Reset Pledges</label>
				</td>
			</tr>
		</tbody>
	</table>
<?php
		$this->successMessage(); // hidden initially
	}

	function form_bottom() {
?>
	<table class="form-table race" id="race-bottom">
		<tbody>
			<tr>
				<th>Mailing Address</th>
				<td id="race-mail-wrap">
					<label for="race-street">Street
					<input type="text" name="race_profile[street]" value="<?php $this->get('street'); ?>" id="race-street" /></label><br />
					<label for="race-city">City
					<input type="text" name="race_profile[city]" value="<?php $this->get('city'); ?>" id="race-city" /></label>
					<label for="race-state" class="center">State
					<input type="text" name="race_profile[state]" value="<?php $this->get('state'); ?>" id="race-state" /></label>
					<label for="race-zip">Zip
					<input type="text" name="race_profile[zip]" value="<?php $this->get('zip'); ?>" id="race-zip" /></label>
				</td>
			</tr>
			<tr>
				<th><label for="race-phone">Phone</label></th>
				<td>
					<input type="text" name="race_profile[phone]" value="<?php $this->get('phone'); ?>" id="race-phone" />
					<input type="hidden" name="race_profile_update" value="1" />
				</td>
			</tr>
		</tbody>
	</table>
<?php
	}

	function form_process() {
		if ( isset( $_POST['race_profile_update'] ) ) {
			$posted = array_merge( $this->options,
				maybe_unserialize( $_POST['race_profile'] )
			);

			if ( isset( $_POST['race_reset_pledges'] ) ) {
				// TODO: reset fundraising progress processing
			}

			$postage = array_map( 'race_escape', $posted);

			if ( $success = update_usermeta( $this->user_ID, 'race_profile', $postage ) ) {
				if ( 'ajax' == $_POST['race_profile_update'] ) {
					$this->response = $this->successMessage( true );
				}
			} else {
				$this->setError('Unable to update');
			}
		}
	}

	function loginLandingForm() {
		// allow goal reset / change from login landing page
?>
<div class="floaty rounded">
	<h4>Fundraising Goal</h4>
<form name="landing" id="landing" method="POST" accept-charset="utf-8" action="<?php $this->ajaxFormAction(); ?>">
<?php $this->form_top(); ?>

	<div class="controls">
		<input type="submit" value="Submit" id="landing-submit" tabindex="10" />
		<?php $this->ajaxSpinner(); ?>
		<input type="hidden" name="warrior_id" value="<?php $this->getUserID(); ?>" />
		<input type="hidden" name="race_profile_update" value="ajax" />
		<?php $this->generateNonce(); ?>
		<span class="message">Updated</span>
	</div>
</form>
</div>
<?php
	}

	function successMessage( $ajax = false ) {
		// display after profile update
		$wurl = $this->donor_url;
		$content = <<<HTML
	<div id="race_message" style="display:none;">
		<p>Thank you for becoming a RACE Warrior! We greatly appreciate your efforts in helping raise money to save lives. Your custom Warrior Page URL link is provided below. Clicking the link will take you to your custom Warrior Page. If the link does not automatically launch your Browser, please “copy” the link and “paste” it into your Browser’s address bar.</p>
		<p class="custom-url">$wurl</p>
		<p>Please use this link to share with your friends, family, and coworkers. E-mail blasts are excellent ways to get your message out to a lot of people in the shortest amount of time. You can also “paste” the link into any website which sends messages such as MySpace, FaceBook, etc. Recipients who access your Warrior Page will be able to read your message, view your photo, see your targeted rund raising goal progress, scroll through your list of donors, and most importantly be able to click the DONATE HERE button, which will walk them through the Online donation steps. Each of your donors’ amounts will be credited toward your goal. All donors will receive an automated Thank you e-mail from the system and you too will be notified via e-mail as soon as a donation is posted to your account.</p>
		<p>You can also login to your account at any time to edit any of the settings and/or reset your goal after an event is completed as a way to prepare for the next fund raising event you plan to participate in. Once again, on behalf of the entire RACE Community, we thank you very much for helping us in this crusade and look forward to seeing you at an upcoming RACE event soon!</p>
	</div>
HTML;
		if ( $ajax ) return 1; else echo $content;
	}

	function theUserPhoto() {
		if ( function_exists( 'aleph_get_userphoto_image' ) ) {
			echo '<img src="'. aleph_get_userphoto_image( $this->user ) ."\" alt=\"User Photo\" />\n";
		}
	}

	function totalPledged( $format = true ) {
		$total = race_sum_donations( $this->user );
		if ( $format )
			return wp_sprintf( '<strong>$%d</strong> pledged so far', $total );
		return $total;
	}
}


/*function race_maybe_update_donor_schema( $user_ID ) {
	$new_key = 'race_donors';
	$event_key = 'event_' . RACE_EVENT_ID_HACK;

	$old_key = $new_key . '_for_warrior_' . $user_ID;
	$old_prefix = $event_key . '_donor_';

	if ( $old = get_usermeta( $user_ID, $old_key ) ) {
		$new = array();
		foreach ($old as $k => $v) {
			if ( preg_match( "/^$old_prefix/", $k ) ) {
				$new[ $v['email'] ] = $v;
			}
		}
		if ( count( $new ) ) {
			$events = array( "$event_key" => $new );
			if ( $um = update_usermeta( $user_ID, $new_key, $events ) ) {
				delete_usermeta( $user_ID, $old_key );
			}
		}
	}
}*/

?>