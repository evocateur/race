<?php
/********************
 *  Initialization  *
 ********************/
define( 'RACE_DEFAULT_AVATAR', get_option('siteurl') . '/wp-content/uploads/default_avatar.jpg' );
define( 'RACE_THEME_ROOT_URI', get_stylesheet_directory_uri() );
define( 'RACE_EVENT_ID_HACK', 1 );

$RACE = array();
$RACE_widgets = $RACE['widgets'] = array();

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

	// profile
	race_maybe_hook_profile();

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
class RACE_AlephWidget  	extends AlephWidget {

	function RACE_AlephWidget( $name, $valid_pattern ) {
		$this->AlephWidget( $name );
		$this->display_title = false;
		$this->valid_pattern = $valid_pattern;
	}
	function configure() {
		$this->configure_instance();
	}
	function displayContent() {
		$this->display_instance();
	}

	function validate( $page ) {
		$this->valid_content = preg_match( $this->valid_pattern, $page->slug );
	}
	function set_args( $args ) {
		$this->instance_args = $args;
	}
	function set_user( $user ) {
		if ( ! is_object( $user ) ) {
			$user = get_userdatabylogin( $user );
		}
		$this->user_ID = intval($user->ID);
		$this->user = $user;
		$this->profile = $user->race_profile;
	}
}

class RACE_ProfileMenu  	extends RACE_AlephWidget {

	function RACE_ProfileMenu( $name ) {
		$pattern = "/^author-(list|profile)$/";
		$this->RACE_AlephWidget( $name, $pattern );
	}

	function configure_instance() {
		$is_profile = is_profile();
		if ( is_user_list() || $is_profile ) {
			global $post;
			$this->validate( $post );

			if ( $this->valid_content && $is_profile ) {
				global $user;
				$this->set_user( $user );
			}

			if ( $this->valid_content ) {
				$this->set_args( array(
					'path' => $is_profile ? 'donations/online' : 'warriors/current',
					'key'  => $is_profile ? 'ID'               : 'post_parent'
				) );
			}
		}
	}

	function build_menu( $echo = true ) {
		if ( empty( $this->instance_args ) ) {
			return '';
		}
		extract( $this->instance_args );
		$child = array_shift( query_posts( "pagename=$path" ) );
		$menu  = race_build_submenu( (int) $child->{$key} );

		if ( $this->user_ID ) {
			// attach userid queryvar to donate link
			$re = '/(donations\/online\/warrior\/)/';
			$qv = $this->user->display_name;
			$menu = preg_replace( $re, "$1?runner=$qv", $menu, 1 );
		}

		if ( $echo ) echo $menu; else return $menu;
	}

	function display_instance() {
		$this->build_menu();
	}
}

class RACE_ProgressMeter 	extends RACE_AlephWidget {

	function RACE_ProgressMeter( $name ) {
		$pattern = "/^(author-profile)$/";
		$this->RACE_AlephWidget( $name, $pattern );
	}

	function configure_instance() {
		global $post, $user;
		$this->validate( $post );

		if ( array_key_exists( 'runner', $_GET ) ) {
			$user = $_GET['runner'];
			$this->valid_content = true;
		}

		if ( $this->valid_content ) {
			global $user;
			$this->set_user( $user );

			if ( $this->user ) {
				$event = 'event_' . RACE_EVENT_ID_HACK;
				$donors = (array) $this->user->race_donors[$event];
				$progress = 0;
				foreach ($donors as $donor => $key) {
					$progress += (int) $key['amount'];
				}
				$this->set_args( array(
					'progress' => $progress,
					'goal' => $this->profile['goal'],
					'ticks' => array(
						100, 50, 75, 25
					)
				) );
			}
		}
	}

	function build_meter( $echo = true ) {
		// http://meyerweb.com/eric/css/edge/bargraph/demo.html
		/*
			<ul id="progress">
				<li class="goal">$GOAL
					<ul>
						<li class="bar" style="height: 110px;"><p>$CURRENT_PROGRESS</p></li>
					</ul>
				</li>
				<li id="ticks">
					<div class="tick" style="height: 59px;"><p>$50,000</p></div>
					<div class="tick" style="height: 59px;"><p>$40,000</p></div>
					<div class="tick" style="height: 59px;"><p>$30,000</p></div>
					<div class="tick" style="height: 59px;"><p>$20,000</p></div>
					<div class="tick" style="height: 59px;"><p>$10,000</p></div>
				</li>
			</ul>
		*/
		if ( empty( $this->instance_args ) ) {
			return '';
		}
		extract( $this->instance_args );
		$goal = (int) $goal;
		$progress = (int) $progress;
		$complete = ( $goal && $progress ) ? 100 * round( ( $progress / $goal ), 2) : 0;
		$class = '';
		if ( $complete > 100 ) {
			$complete = 100;
			$class = ' class="complete"';
		}

		$meter = array(
			"<ul id=\"progress\"$class>",
			"\t<li class=\"goal\"><p>\$$goal</p>",
			"\t\t<ul>",
			"\t\t\t<li class=\"bar\" style=\"height:$complete%;\"><p>\$$progress</p></li>",
			"\t\t</ul>",
			"\t</li>",
			"\t<li id=\"ticks\">",
			"\t</li>",
			"</ul>"
		);

		$interval = 100 / count( $ticks );
		rsort( $ticks );
		foreach ( $ticks as $tick ) {
			array_splice( $meter, -2, 0,
			"\t\t<div class=\"tick\" style=\"height:$interval%;\"><p>$tick<span>%</span></p></div>"
			);
		}

		$glue  = "\n" . str_repeat( "\t", 4 );
		$meter = implode( $glue, $meter );

		if ( $echo ) echo $glue . $meter; else return $meter;
	}

	function display_instance() {
		$this->build_meter();
	}
}

$RACE_widgets['warrior']  = new RACE_ProfileMenu(  'Warrior Sidebar' );
$RACE_widgets['progress'] = new RACE_ProgressMeter( 'Progress Meter' );
}

class RACE_Warrior {
	var $user_ID;

	function RACE_Warrior( $login, $type = 'donor' ) { // constructor
		$this->type = $type;
		$this->set_user( $login );
		$this->class_config();
	}

	function class_config() {
		race_maybe_update_donor_schema( $this->user_ID ); // TEMPORARY
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
		$this->instance_config();
	}

	function instance_config() {
		// overwritten by subclass
	}

	function set_user( $u ) {
		// defaults
		$this->user    = NULL;
		$this->user_ID = NULL;

		// $u may be a numeric id
		$user = ( is_numeric( $u ) )
			? get_userdata( $u )
			: get_userdatabylogin( $u );

		if ( $user ) {
			$this->user      = $user;
			$this->user_ID   = (int) $user->ID;
			$this->profile   = $user->race_profile;
			$this->full_name = $user->first_name . ' ' . $user->last_name;
			$this->nonce_key = "race-warrior-{$this->user_ID}-{$this->type}";
		}
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
		$this->ajax_url = RACE_THEME_ROOT_URI . '/ajax.php';
		wp_enqueue_script( 'jquery-form' );
		add_action( 'wp_print_scripts', array( &$this, 'hook_css') );
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

	/*
		Accessors
	*/
	function ajaxNonce( $echo = true ) {
		$nonce = wp_create_nonce( $this->nonce_key );
		if ( $echo ) echo $nonce; else return $nonce;
	}

	function formAction( $echo = true ) {
		$r = $this->ajax_url;
		if ( $echo ) echo $r; else return $r;
	}

	function fullName( $echo = true ) {
		$r = $this->full_name;
		if ( $echo ) echo $r; else return $r;
	}

	function pageLink( $key = '', $text = 'here', $echo = true ) {
		$r = '';
		if ( array_key_exists( $key, $this->links ) ) {
			$r = "<a href=\"{$this->links[$key]}\">$text</a>";
		}
		if ( $echo ) echo $r; else return $r;
	}

	/*
		Output
	*/
	function hook_css() {
?>
<style type="text/css">
	#donor table.warrior {
		line-height: 20px;
		font-size: 11px;
		margin-top: 2em;
		margin-bottom: 20px;
		width: 100%;
	}
	#donor table.warrior input {
		margin: 1px 0;
		padding: 2px;
	}
	#donor table.warrior label {
		float: left;
		margin-right: 0.4em; /* IE */
	}
	#donor table.warrior td > label {
		margin-right: 0.5em;
	}
	#donor table.warrior td.center,
	#donor table.warrior label.center,
	#donor table.warrior label.center input {
		text-align: center;
	}
	#donor table.warrior label input {
		display: block;
	}
	#donor table.warrior td br {
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
	#donor table.warrior tr.controls {
		font-size: 1.6em;
	}
	#donor table.warrior tr.controls td.submit {
		padding-top: 1em;
	}
	#donor table.warrior th {
		padding-top: 0.3em;
		font-size: 1.25em;
	}
	#donor table.warrior th,
	#donor table.warrior tr > td {
		white-space: nowrap;
	}
	#donor table.warrior tr td {
		vertical-align: top;
	}
	#donor table.warrior tr p {
		white-space: normal;
		margin: 0.3em 0 0.7em;
	}
	#donor tfoot td {
		padding-top: 2.5em;
	}
	#donor #pledge { padding-bottom: 1em; }
</style>
<?php
	}

	function display() {
		// TODO: modal "proceed to checkout", multiple additions possible...
		?>

<form name="donor" id="donor" action="<?php $this->formAction(); ?>" method="POST">
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
			<input type="hidden" name="_ajax_nonce"  value="<?php $this->ajaxNonce(); ?>" />
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
		$this->umetakey = 'race_donors';
		$this->response = NULL;
		$this->error    = NULL;
	}

	function valid_request() {
		return check_ajax_referer( $this->nonce_key , '_ajax_nonce', false );
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
		$event_key = 'event_' . RACE_EVENT_ID_HACK;

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
				$events[ $event_key ][ $donor_key  ] = $postage;
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

	function getResponse() {
		return ( $this->error ) ? $this->error : $this->response;
	}

	function setError( $text ) {
		$this->error = $text;
	}
}

class RACE_Warrior_Profile	extends RACE_Warrior {

	function RACE_Warrior_Profile( $user ) { // constructor
		$this->RACE_Warrior( $user, 'race_profile' );
	}

	function instance_config() {
		$this->hook();
		$this->select_key = 'goal';
		// overwrite parent amounts
		$this->amounts = array(
			50, 100, 150, 250, 300, 400, 500, 600, 750, 1000, 1500, 2000
		);
		$this->options = array_merge( array(
			 'street' => ''
			,'city'   => ''
			,'state'  => ''
			,'zip'    => ''
			,'phone'  => ''
			,'goal'   => ''
			,'total'  => ''
		), array_filter( (array) $this->profile ) );
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
		span.dollar {
			font-size: 1.5em;
			vertical-align: middle;
		}
	</style>
	<?php
	}

	function form_top() {
		$is_profile = defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE;
		$title = $is_profile ? 'Profile Options' : 'Edit User';
?>
<script type="text/javascript">
(function(){
    var pp = document.getElementById('profile-page'), ft = pp.getElementsByTagName('TABLE'), h2 = pp.getElementsByTagName('H2');
    if (ft[0] && ft[0].className == 'form-table') ft[0].style.display = 'none';
    if (h2[0] && h2[0].firstChild.nodeType == 3)  h2[0].replaceChild(document.createTextNode("<?php echo $title; ?>"), h2[0].firstChild);
})();
</script>
<?php
		// alas the odiousness of inline script (to hide color options)
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
					<?php $this->amount_select( $this->options['goal'] ); ?>
					<label for="race_reset_progress" class="inline"><input type="checkbox" name="race_reset_progress" value="1" id="race_reset_progress" />Reset Current Progress</label>
					<span class="total">( <?php echo $this->total_pledged(); ?> )</span>
				</td>
			</tr>
		</tbody>
	</table>
	<?php }

	function form_bottom() { ?>
	<table class="form-table race" id="race-bottom">
		<tbody>
			<tr>
				<th>Mailing Address</th>
				<td id="race-mail-wrap">
					<label for="race-street">Street
					<input type="text" name="race_profile[street]" value="<?php echo $this->options['street'] ?>" id="race-street" /></label><br />
					<label for="race-city">City
					<input type="text" name="race_profile[city]" value="<?php echo $this->options['city']; ?>" id="race-city" /></label>
					<label for="race-state" class="center">State
					<input type="text" name="race_profile[state]" value="<?php echo $this->options['state']; ?>" id="race-state" /></label>
					<label for="race-zip">Zip
					<input type="text" name="race_profile[zip]" value="<?php echo $this->options['zip']; ?>" id="race-zip" /></label>
				</td>
			</tr>
			<tr>
				<th><label for="race-phone">Phone</label></th>
				<td>
					<input type="text" name="race_profile[phone]" value="<?php echo $this->options['phone']; ?>" id="race-phone" />
					<input type="hidden" name="race_profile_update" value="1" />
				</td>
			</tr>
		</tbody>
	</table>
	<?php }

	function form_process( $uid ) {
		if ( isset( $_POST['race_profile_update'] ) ) {
			$posted = array_merge( array(
				'street' => '',
				'city'   => '',
				'state'  => '',
				'zip'    => '',
				'phone'  => '',
				'goal'   => ''
			), maybe_unserialize( $_POST['race_profile'] ));

			$postage = array_map( 'race_escape', $posted);

			update_usermeta( $uid, 'race_profile', $postage );

			if ( isset( $_POST['race_reset_progress'] ) ) {
				// TODO: reset fundraising progress processing
			}
		}
	}

	function total_pledged() {
		$total = (int) $this->options['total'];
		// TODO: actually find total pledged
		return wp_sprintf( '<strong>$%d</strong> pledged so far', $total );
	}
}

function race_maybe_hook_profile() {
	if ( is_admin() ) {
		$id = 0;
		if ( defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE ) {
			$id = (int) $GLOBALS['userdata']->ID;
		} elseif ( !empty( $_GET['user_id'] ) ) {
			$id = (int) $_GET['user_id'];
		}

		if ( $id ) {
			global $RACE;
			$RACE['profile'] = new RACE_Warrior_Profile( $id );
		}
	}
}

function race_maybe_update_donor_schema( $user_ID ) { // TEMPORARY
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
}


/********************
 *     Utilities    *
 ********************/

function race_escape( $v ) {
	return $GLOBALS['wpdb']->escape( wp_specialchars( trim($v) ) );
}

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
	$home = get_option('home');
	echo <<<HTML
	<script type="text/javascript">
	function ale(f){var o=window.onload;if(typeof o!='function'){window.onload=f;}else{window.onload=function(){o();f();};}}
	function patch_redirect() {
		var r = document.forms[0]['redirect_to'];
		if (r && r.value == 'wp-admin/') r.value = '$home/warriors/login/';
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

function race_template_hijack() {
	if ( is_front_page() ) {
		include( STYLESHEETPATH . '/root.php' );
		exit;
	}
	if ( is_page('warrior') && $login = $_GET['runner'] ) {
		$race_donor = new RACE_Warrior_Donor( $login );
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