<?php
/********************
 *  Initialization  *
 ********************/
define( 'RACE_DEFAULT_AVATAR', get_option('siteurl') . '/wp-content/uploads/default_avatar.jpg' );
define( 'RACE_THEME_ROOT_URI', get_stylesheet_directory_uri() );

add_action('init', 'race_theme_init');
add_action('widgets_init', 'race_widget_init');

function race_theme_init() {
	if (!get_option('race_theme_email'))
		add_option('race_theme_email', 'info@racecharities.org');

	wp_register_script( 'race_pages', RACE_THEME_ROOT_URI . "/lib.js",  array('jquery') );
	wp_register_script( 'race_admin', RACE_THEME_ROOT_URI . "/admin.js", array('jquery') );

	race_theme_init_hooks();
}

function race_theme_init_hooks() {
	/*
		ACTIONS
	*/
	add_action('template_redirect', 'race_template_hijack');

	// headers, footers
	add_action('wp_print_scripts', 'race_header');
	add_action('login_head', 'race_login_header');
	add_action('get_footer', 'race_footer');

	// quadrant cache
	add_action('save_post',    'race_quadrants_flush');
	add_action('deleted_post', 'race_quadrants_flush');

	// profiles
	add_action('admin_print_scripts',      'race_profile_css_js',   1);
	add_action('profile_personal_options', 'race_profile_form_top', 1);

	add_action('show_user_profile', 'race_profile_form_bottom', 9);
	add_action('edit_user_profile', 'race_profile_form_bottom', 9);
	add_action('profile_update',    'race_profile_form_process');

	/*
		FILTERS
	*/

	// aleph
	add_filter('users_join',  'race_aleph_join');
	add_filter('users_where', 'race_aleph_where');

	// dashboard nuking
	add_action('admin_head',           'race_nuke_dashboard_js',      1);
	add_filter('wp_dashboard_widgets', 'race_nuke_dashboard_widgets', 1);

	// sandbox
	add_filter('sandbox_menu', 'race_sandbox_menu');
	add_filter('body_class',   'race_sandbox_class');
	add_filter('post_class',   'race_sandbox_class');

	// wp overrides
	add_filter('register',  'race_wp_register');
	add_filter('the_title', 'race_wp_title');
}

function race_widget_init() {
	if ( !function_exists('register_sidebars') )
		return;

	$widget_ops = array(
		'classname'   => 'widget_submenu',
		'description' => "A submenu that displays children of the current page."
	);
	wp_register_sidebar_widget('submenu', 'Page Sub-Menu', 'widget_race_submenu', $widget_ops);

	$widget_ops = array(
		'classname'   => 'widget_gallery',
		'description' => "A sidebar image gallery that displays images associated with a page."
	);
	wp_register_sidebar_widget('sidegallery', 'Page Gallery', 'widget_race_gallery', $widget_ops);

	if ( is_active_widget('widget_race_gallery') ) {
		// don't clobber gallery when inactive
		add_filter('post_gallery', 'race_post_gallery');
	}

	$widget_ops = array(
		'classname'   => 'widget_spotlight',
		'description' => "In the Spotlight"
	);
	wp_register_sidebar_widget('spotlight', 'Page Spotlight', 'widget_race_spotlight', $widget_ops);
	wp_register_widget_control('spotlight', 'Page Spotlight', 'widget_race_spotlight_control');

	if ( is_active_widget('widget_race_spotlight') ) {
		// it's polite to flush
		add_action('save_post',    'widget_race_spotlight_flush');
		add_action('deleted_post', 'widget_race_spotlight_flush');
		add_action('switch_theme', 'widget_race_spotlight_flush');
	}
}


/********************
 *      Widgets     *
 ********************/

// gallery  ====================================
function widget_race_gallery( $args ) {
	extract( $args, EXTR_SKIP );

	$gallery = race_build_gallery();

	if ( $gallery ) {
		echo $before_widget . $gallery . $after_widget;
	}
}

function race_build_gallery() {
	// i love the smell of code duplication in the morning
	global $post;

	if ( is_front_page() || !is_page() )
		return false;

	extract( array(
		'orderby'    => 'menu_order ASC, ID ASC',
		'id'         => $post->ID,
		'itemtag'    => 'li',
		'captiontag' => 'p',
		'columns'    => 1,
		'size'       => 'thumbnail',
	), EXTR_SKIP );

	$id = intval( $id );
	$orderby = addslashes( $orderby );
	$attachments = get_children( "post_parent=$id&post_type=attachment&post_mime_type=image&orderby=\"{$orderby}\"" );

	if ( empty( $attachments ) )
		return '';

	$itemtag    = tag_escape( $itemtag );
	$captiontag = tag_escape( $captiontag );
	$columns    = intval( $columns );

	$t = "\n" . str_repeat("\t", 4);

	$output  = $t;
	$output .= apply_filters( 'gallery_style', '<ul class="gallery">' );

	foreach ( $attachments as $id => $attachment ) {
		$link = wp_get_attachment_link( $id, $size );
		$output .= "$t\t<{$itemtag} class='gallery-item'>";
		$output .= "$t\t\t{$link}";
		if ( $captiontag && trim( $attachment->post_excerpt ) ) {
			$output .= "$t\t\t<{$captiontag} class='gallery-caption'>{$attachment->post_excerpt}</{$captiontag}>";
		}
		$output .= "$t\t</{$itemtag}>";
	}

	$output .= "$t</ul>$t";

	return $output;
}

function race_post_gallery( $attr ) {
	// filter gallery shortcode
	// TODO: make this a more elegant choice
	return '<!-- gallery in sidebar -->';
}

// sub menu ====================================
function widget_race_submenu( $args ) {
	extract( $args, EXTR_SKIP );

	global $post;
	$parent = '';

	if ( $post->post_parent ) {
		$parent .= $post->post_parent;
	}
	else if ( is_page() && !is_front_page() ) {
		$parent .= $post->ID;
	}

	$submenu = race_build_submenu( $parent );

	if ( $submenu ) {
		echo $before_widget . $submenu . $after_widget;
	}
}

function race_build_submenu( $parent = '' ) {
	$parent = (int) $parent;
	if ( empty( $parent ) ) return false;

	$output = '';
	$t = "\n" . str_repeat("\t", 4);

	$children = wp_list_pages( "title_li=&echo=0&sort_column=menu_order&depth=1&child_of=$parent" );

	if ( $children ) {
		$output .= "$t<ul class=\"submenu-parent\">";
		$output .= trim( implode( "$t\t", explode( "\n", $children ) ) );
		$output .= "$t</ul>$t";
	}

	return $output;
}

// spotlight ===================================
function widget_race_spotlight( $args ) {
	if ( !is_front_page() )
		return '';

	if ( $output = wp_cache_get( 'widget_race_spotlight', 'widget' ) )
		return print($output);

	ob_start();
	extract($args, EXTR_SKIP);

	$options = get_option( 'widget_race_spotlight' );
	$title = empty( $options['title'] ) ? __('In the Spotlight') : $options['title'];
	$tag = 'in-the-spotlight';
	if ( !$number = (int) $options['number'] )
		$number = 3;
	else if ( $number < 1 )
		$number = 1;
	else if ( $number > 5 )
		$number = 5;

	$r = new WP_Query( "showposts=$number&post_status=publish&tag=$tag" );
	if ( $r->have_posts() ) :
?>
		<?php echo $before_widget; ?>
			<?php echo $before_title . $title . $after_title; ?>
		<ul>
			<?php while ( $r->have_posts() ) : $r->the_post(); ?>
			<li>
				<a href="<?php the_permalink(); ?>"><?php race_thumb_image(); ?></a>
				<div>
					<h4><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
					<?php the_excerpt(); ?>
				</div>
			</li>
			<?php endwhile; ?>
		</ul>
		<?php echo $after_widget; ?>
<?php
		wp_reset_query();  // Restore global post data stomped by the_post().
	endif;

	wp_cache_add( 'widget_race_spotlight', ob_get_flush(), 'widget' );
}

function widget_race_spotlight_control() {
	$options = $newoptions = get_option( 'widget_race_spotlight' );
	if ( $_POST["race-spotlight-submit"] ) {
		$newoptions['title']  = strip_tags( stripslashes( $_POST["race-spotlight-title"] ) );
		$newoptions['number'] = (int) $_POST["race-spotlight-number"];
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option( 'widget_race_spotlight', $options );
		widget_race_spotlight_flush();
	}
	$title = attribute_escape( $options['title'] );
	if ( !$number = (int) $options['number'] )
		$number = 3;
?>
			<p>
				<label for="race-spotlight-title"><?php _e('Title:'); ?> <input class="widefat" id="race-spotlight-title" name="race-spotlight-title" type="text" value="<?php echo $title; ?>" /></label>
			</p>
			<p>
				<label for="race-spotlight-number"><?php _e('Number of Spotlight posts:'); ?> <input style="width: 25px; text-align: center;" id="race-spotlight-number" name="race-spotlight-number" type="text" value="<?php echo $number; ?>" /></label>
				<br />
				<small><?php _e('(3 default, no more than 5)'); ?></small>
			</p>
			<input type="hidden" id="race-spotlight-submit" name="race-spotlight-submit" value="1" />
<?php
}

function widget_race_spotlight_flush() {
	wp_cache_delete( 'widget_race_spotlight', 'widget' );
}

// quadrants ===================================
function race_quadrants() {
	if ( $content = wp_cache_get( 'theme_race_quadrants' ) )
		return print( $content );

	ob_start();

	$q = new WP_Query( "showposts=4&category_name=quadrant" );

	if ( $q->have_posts() ) : ?>
	<div id="quadrant">
		<ul class="xoxo">
<?php while ( $q->have_posts() ) : $q->the_post(); ?>
			<li>
				<h4><?php the_title(); ?></h4>
				<?php race_thumb_image(); ?>

				<?php the_content(); ?>
			</li>
<?php endwhile; ?>
		</ul>
	</div>
<?php
		wp_reset_query();
	endif;

	wp_cache_add( 'theme_race_quadrants', ob_get_flush() );
}

function race_quadrants_flush() {
	wp_cache_delete( 'theme_race_quadrants' );
}

// aleph
if (class_exists('AlephWidget')) {
class RaceProfileWidget extends AlephWidget {
	var $menu;
	var $user;
	var $user_ID;

	function RaceProfileWidget( $name ) {
		$this->AlephWidget( $name );
		$this->display_title = false;
	}

	function configure() {
		if ( is_user_list() || is_profile() ) {
			global $post;
			$this->checkPage( $post );

			if ( $this->valid_content && is_profile() ) {
				global $user;
				$this->setUser( $user );
			}

			if ( $this->valid_content ) {
				$this->setMenu( array(
					'path' => is_profile() ? 'donations/online' : 'warriors/current',
					'key'  => is_profile() ? 'ID'               : 'post_parent'
				) );
			}
		}
	}

	function checkPage( $page ) {
		$this->valid_content = preg_match("/^author-(list|profile)$/", $page->slug );
	}

	function setUser( $user ) {
		$this->user_ID = intval($user->ID);
		$this->user = $user;
	}

	function setMenu( $args ) {
		extract( $args );
		$child = array_shift( query_posts( "pagename=$path" ) );
		$this->menu = race_build_submenu( (int) $child->{$key} );
	}

	function getMenu( $echo = true ) {
		$menu = $this->menu;
		if ( $this->user_ID ) {
			// attach userid queryvar to donate link
			$re = '/(donations\/online\/warrior\/)/';
			$qv = $this->user->display_name;
			$menu = preg_replace( $re, "$1?$qv", $menu, 1 );
		}
		if ( $echo ) echo $menu; else return $menu;
	}

	function displayContent() {
		$this->getMenu();
		if ( $this->user ) { ?>
		<ul id="profile-sidebar">
			<li><!-- thermometer --></li>
		</ul>
		<?php }
	}
}
global $race_widgets;
$race_widgets = array();
$race_widgets['warrior'] = new RaceProfileWidget( 'Warrior Sidebar' );
}

class RACE_Warrior {
	var $user_ID;

	function RACE_Warrior( $login, $collection ) {
		$this->login = $login;
		$this->collection  = $collection;
		$this->init();
	}

	function init() {
		$this->user = get_userdatabylogin( $this->login );
		$this->user_ID = (int) $this->user->ID;
		$this->profile = $this->user->race_profile;
		$this->full_name = $this->user->first_name . ' ' . $this->user->last_name;
		$this->amounts = array(
			10, 20, 50, 75, 100, 250, 500, 750
		);
		$this->load_links( array(
			'privacy' => '/home/privacy-policy/',
			'club'    => '/donations/warriors-club/',
			'warrior' => '/warriors/',
			'signup'  => '/warriors/signup/'
		));
		add_action( 'wp_print_scripts', array( &$this, 'hook_css') );
	}

	function load_links( $links ) {
		$this->links = array();
		$home = get_option('home');
		foreach ( $links as $link => $path ) {
			$this->links[$link] = $home . $path;
		}
	}

	function amount_select( $key, $indent, $tabindex ) {
		$opts = array(
			'name'     => "{$this->collection}[$key]",
			'id'       => "{$this->collection}-$key",
			'indent'   => $indent,
			'tabindex' => $tabindex
		);
		$data = $this->amounts;
		race_amount_select( $data, '', $opts );
	}

	function fullName() {
		echo $this->full_name;
	}

	function pageLink( $key = '', $text = 'here', $echo = true ) {
		$link = '';
		if ( array_key_exists( $key, $this->links ) ) {
			$link = "<a href=\"{$this->links[$key]}\">$text</a>";
		}
		if ( $echo )
			echo $link;
		else
			return $link;
	}

	function hook_css() {
?>
<style type="text/css">
	#donate table.warrior {
		line-height: 20px;
		font-size: 11px;
		margin-top: 2em;
		margin-bottom: 20px;
		width: 100%;
	}
	#donate table.warrior input {
		margin: 1px 0;
		padding: 2px;
	}
	#donate table.warrior label {
		float: left;
		margin-right: 0.4em; /* IE */
	}
	#donate table.warrior td > label {
		margin-right: 0.5em;
	}
	#donate table.warrior td.center,
	#donate table.warrior label.center,
	#donate table.warrior label.center input {
		text-align: center;
	}
	#donate table.warrior label input {
		display: block;
	}
	#donate table.warrior td br {
		clear: both;
	}
	#donor-name,
	#donor-email { width: 15em; }
	#donor-city  { width: 12em; }
	#donor-state { width:  2em; }
	#donor-amount, #donor-submit {
		vertical-align: middle;
	}
	#donor-amount { font-size: 1.0em; }
	#donor-submit { font-size: 1.2em; }
	#donate table.warrior tr.controls {
		font-size: 1.6em;
	}
	#donate table.warrior tr.controls td.submit {
		padding-top: 1em;
	}
	#donate table.warrior th {
		padding-top: 0.3em;
		font-size: 1.25em;
	}
	#donate table.warrior th,
	#donate table.warrior tr > td {
		white-space: nowrap;
	}
	#donate table.warrior tr td {
		vertical-align: top;
	}
	#donate table.warrior tr p {
		white-space: normal;
		margin: 0.3em 0 0.7em;
	}
	#donate tfoot td {
		padding-top: 2.5em;
	}
	#donate #pledge { padding-bottom: 1em; }
</style>
<?php
	}

	function displayDonorForm() {
		?>

<form name="donor_info" id="donor-info" action="" method="POST">
<table id="donor" class="warrior">
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
			<?php $this->amount_select( 'amount', 3, 1 ); ?>
			toward <strong><?php $this->fullName(); ?>&#8217;s</strong> goal!
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
			<input type="hidden" name="warrior_id" value="<?php echo $this->user_ID; ?>" />
		</td>
	</tr>
</tbody>
</table>
</form>

<?php
	}
}


/********************
 *     Utilities    *
 ********************/

function race_menu( $before = '', $after = '' ) {
	$content = '';
	$options_wp_list = 'title_li=&sort_column=menu_order&echo=0&depth=1';

	if ( get_option( 'show_on_front' ) == 'page' )
	    $options_wp_list .= '&exclude=' . get_option( 'page_on_front' );

	$menu = wp_list_pages( $options_wp_list );

	if ( $menu ) {
		$content .= '<ul class="menu-parent">';
		$content .= str_replace( array( "\r", "\n", "\t" ), '', $menu );
		$content .= "</ul>";
	}
	return $before . $content . $after;
}

function race_thumb_image() {
	global $post;
	$thumb_src = RACE_DEFAULT_AVATAR; // tentative

	$thumb = get_children( array(
		'post_parent'    => $post->ID,
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'numberposts'    => 1
	) );

	if ( is_array($thumb) ) {
		$thumb = array_shift( $thumb );
		if ( $thumb = wp_get_attachment_image_src( $thumb->ID ) )
			$thumb_src = $thumb[0];
	}

	echo "<img src=\"{$thumb_src}\" alt=\"\" />";
}

function race_amount_select( $data, $selected = '', $args = array() ) {
	if ( empty( $data ) )
		return '';

	extract( array_merge(
		array(
			'name'     => 'race_profile[goal]',
			'id'       => 'race-goal',
			'indent'   => 5,
			'echo'     => true
		),
		$args
	), EXTR_SKIP );

	// dollarize $data for display
	foreach ( $data as $datum ) {
		$key = str_pad( '$' . $datum, 4, ' ', STR_PAD_LEFT );
		$option["$key"] = $datum;
	}
	$t = "\n" . str_repeat("\t", $indent);
	$pad = '&nbsp;';
	$selected = (int) $selected;
	$index = '';
	if ( isset( $tabindex ) )
		$index = " tabindex=\"$tabindex\"";

	$sel[] = "<select name=\"$name\" class=\"amount\" id=\"$id\"$index>";
	$sel[] = "\t<option value=\"\">....</option>";
	foreach ( $option as $name => $value ) {
		$name = strtr( $name, array( ' ' => '&nbsp;' ));
		$sel[] = "\t<option value=\"$value\">$name</option>";
	}
	$sel[] = "</select>\n";

	if ( ! empty( $selected ) ) {
		$sel = preg_replace("/$selected/", "$selected\" selected=\"selected", $sel, 1 );
	}

	$tag = implode( "$t", $sel );

	if ( $echo )
		echo $tag;
	else
		return $t . $tag;
}


// ACTIONS

function race_header() {
	if ( is_admin() )
		return '';
	// link lib.js, conditional ie stylesheet inside header (non-admin)
	$root = RACE_THEME_ROOT_URI;

	// because IE is a friggin retard
	echo <<<HTML
<!--[if lte IE 6]><link rel="stylesheet" type="text/css" href="{$root}/ie.css" /><![endif]-->\n
HTML;

	wp_enqueue_script( 'race_pages' );
}

function race_login_header() {
	echo <<<HTML
	<script type="text/javascript">
	function ale(f){var o=window.onload;if(typeof o!='function'){window.onload=f;}else{window.onload=function(){o();f();};}}
	function patch_redirect() {
		var r = document.forms[0]['redirect_to'];
		if (r && r.value == 'wp-admin/') r.value = 'warriors/login/';
	}
	ale(patch_redirect);
	function registered_blurb() {
		var p = document.getElementsByTagName('P')[0];
		if (p && p.className && p.className == 'message') {
			var t = p.firstChild && p.firstChild.nodeType == 3 ? p.firstChild : null;
			if (t && t.nodeValue && (/registration complete/i).test(t.nodeValue)) {
				p.replaceChild(document.createTextNode(
					"Registration complete. Please login to continue."
				), t);
			}
		}
	}
	ale(registered_blurb);
	</script>\n
HTML;
}

function race_footer() {
	$base = get_option( 'home' );
	$email = get_option( 'race_theme_email' );
	if ( empty( $email ) )
		$email = get_option( 'admin_email' );
	$email = antispambot( $email );

	$list = '<li>';
	$list .= implode('</li><li>', array(
		"<a href=\"$base/home/user-agreement/\" title=\"View User Agreement\">User Agreement</a>",
		"<a href=\"$base/home/privacy-policy/\" title=\"View Privacy Policy\">Privacy Policy</a>",
		"<a href=\"mailto:$email\">Contact Us</a>",
		"<a href=\"$base/\" title=\"Go to homepage\">Home</a>"
	));
	$list .= '</li>';
	?>

	<div id="ft">
		<?php echo race_menu(); ?>

		<ul id="ft-admin" class="menu-parent"><?php
			wp_register();
			echo '<li>'; wp_loginout(); echo '</li>';
			echo $list;
		?></ul>
	</div>
	<?php
}

function race_profile_css_js() {
	wp_enqueue_script( 'race_admin' );
?>
<style type="text/css">
	#profile-page table.race input {
		margin: 1px 0;
		padding: 3px;
	}
	/* bottom */
	#profile-page table.race label {
		float: left;
		margin-right: 0.4em; /* IE */
	}
	#profile-page table.race td > label {
		margin-right: 0.5em;
	}
	#profile-page table.race label.center,
	#profile-page table.race label.center input {
		text-align: center;
	}
	#profile-page table.race label input {
		display: block;
	}
	#profile-page table.race td br {
		clear: both;
	}
	#profile-page #race-street { width: 20em; }
	#profile-page #race-city   { width: 12em; }
	#profile-page #race-state  { width:  2em; }
	#profile-page #race-zip    { width:  4em; }
	#profile-page #race-phone  { width:  8em; }
	#race-mail-wrap {
		padding-bottom: 1.5em;
	}
	/* top */
	#profile-page table.race select {
		margin-right: 2em;
	}
	#profile-page table.race label.inline {
		float: none;
		display: inline-block;
	}
	#profile-page table.race label.inline input {
		display: inline-block;
		margin-right: 0.5em;
		vertical-align: -0.4em; /* IE */
	}
	#profile-page table.race label.inline > input {
		vertical-align: middle;
	}
	#profile-page #race-top td span.total {
		display: inline-block;
		font-size: 1.5em;
	}
	/* neuroses */
	#profile-page #race-top td span.total,
	#profile-page table.race label.inline {
		vertical-align: -0.35em;
	}
	/* patch 2.5 default */
	body.wp-admin #profile-page table.form-table td > #pass-strength-result {
		margin-bottom: 0.5em;
	}
	body.wp-admin #profile-page div.color-option {
		display: none !important;
	}
</style>
<?php
}

function race_profile_form_top() {
	// alas the odiousness of inline script (to hide color options)
?>
<script type="text/javascript">
(function(){
    var pp = document.getElementById('profile-page'), ft = pp.getElementsByTagName('TABLE'), h2 = pp.getElementsByTagName('H2');
    if (ft[0] && ft[0].className == 'form-table') ft[0].style.display = 'none';
    if (h2[0] && h2[0].firstChild.nodeType == 3)  h2[0].replaceChild(document.createTextNode("Profile Options"), h2[0].firstChild);
})();
</script>
<?php
	global $userdata;
	$defaults = array(
		'goal'  => '',
		'total' => 0
	);
	$opts = array_merge( $defaults, array_filter( (array) $userdata->race_profile ) );
	$total = wp_sprintf( '<strong>$%d</strong> pledged so far', (int) $opts['total'] );
	$goals = array(
		50, 100, 150, 250, 300, 400, 500, 600, 750, 1000, 1500, 2000
	);
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
					<?php race_amount_select( $goals, $opts['goal'] ); ?>
					<label for="race_reset_progress" class="inline"><input type="checkbox" name="race_reset_progress" value="1" id="race_reset_progress" />Reset Current Progress</label>
					<span class="total">( <?php echo $total; ?> )</span>
				</td>
			</tr>
		</tbody>
	</table>
<?php
}

function race_profile_form_bottom() {
	global $userdata;
	$defaults = array(
		'street' => '',
		'city'   => '',
		'state'  => '',
		'zip'    => '',
		'phone'  => ''
	);
	$opts = array_merge( $defaults, array_filter( (array) $userdata->race_profile ) );
?>
	<table class="form-table race" id="race-bottom">
		<tbody>
			<tr>
				<th>Mailing Address</th>
				<td id="race-mail-wrap">
					<label for="race-street">Street
					<input type="text" name="race_profile[street]" value="<?php echo $opts['street'] ?>" id="race-street" /></label><br />
					<label for="race-city">City
					<input type="text" name="race_profile[city]" value="<?php echo $opts['city']; ?>" id="race-city" /></label>
					<label for="race-state" class="center">State
					<input type="text" name="race_profile[state]" value="<?php echo $opts['state']; ?>" id="race-state" /></label>
					<label for="race-zip">Zip
					<input type="text" name="race_profile[zip]" value="<?php echo $opts['zip']; ?>" id="race-zip" /></label>
				</td>
			</tr>
			<tr>
				<th><label for="race-phone">Phone</label></th>
				<td>
					<input type="text" name="race_profile[phone]" value="<?php echo $opts['phone']; ?>" id="race-phone" />
					<input type="hidden" name="race_profile_update" value="1" />
				</td>
			</tr>
		</tbody>
	</table>
<?php
}

function race_profile_form_process( $uid ) {
	if ( isset( $_POST['race_profile_update'] ) ) {
		global $wpdb;
		$postage = array(
			'street' => '',
			'city'   => '',
			'state'  => '',
			'zip'    => '',
			'phone'  => '',
			'goal'   => ''
		);
		$posted = maybe_unserialize( $_POST['race_profile'] );

		foreach ( $posted as $k => $v ) {
			$postage[$k] = $wpdb->escape( wp_specialchars( trim($v) ) );
		}

		update_usermeta( $uid, 'race_profile', $postage );

		if ( isset( $_POST['race_reset_progress'] ) ) {
			// TODO: reset fundraising progress processing
		}
	}
}

function race_template_hijack() {
	global $post;
	if ( is_front_page() ) {
		include( STYLESHEETPATH . '/root.php' );
		exit;
	}
	if ( is_page() && 'warrior' == $post->post_name && $login = array_shift(array_keys( $_GET )) ) {
		$warrior = new RACE_Warrior( $login, 'donor' );
		include( STYLESHEETPATH . '/donate.php' );
		exit;
	}
}


// FILTERS

function race_aleph_join( $join ) {
	global $wpdb;
	$join .= " INNER JOIN {$wpdb->usermeta} AS m ON (m.user_id = {$wpdb->users}.ID)";
	return $join;
}
function race_aleph_where( $where ) {
	global $wpdb;
	$where .= " AND m.meta_key = '{$wpdb->prefix}capabilities'";
	$where .= " AND INSTR(m.meta_value,'subscriber') > 0";
	return $where;
}

function race_nuke_dashboard_js() {
	remove_action('admin_head', 'index_js');
}
function race_nuke_dashboard_widgets() {
	return array();
}

function race_sandbox_menu() {
	return race_menu( '<div id="menu">', '</div>' );
}
function race_sandbox_class( $c ) {
	// remove date crap, untagged
	// remove ids of -1, or - dangling at end
	$c = preg_grep('/(^[ymdh]\d{2,4}|untagged|-(-1)?)$/', $c, PREG_GREP_INVERT);

	// aleph
	if ( is_profile() )
		$c[] = "profile";
	else if ( is_user_list() )
		$c[] = "userlist";

	return $c;
}

function race_wp_register( $arg ) {
	// change text of wp_register links
	return preg_replace(
		array('/Register/','/Site Admin/'),
		array('Become A Warrior','Dashboard'),
		$arg
	);
}
function race_wp_title( $arg ) {
	// remove 'protected: ' and 'private: ' from the_title
	return preg_replace( array('/^Protected: /', '/^Private: /'), '', $arg );
}

?>