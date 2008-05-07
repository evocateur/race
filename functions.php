<?php
/********************
 *  Initialization  *
 ********************/
define( 'RACE_DEFAULT_AVATAR', get_option('siteurl') . '/wp-content/uploads/default_avatar.jpg' );
define( 'RACE_THEME_ROOT_URI', get_stylesheet_directory_uri() );
define( 'RACE_EVENT_ID_HACK', 1 );

$RACE = array();
$RACE['widgets'] = array();

add_action('init', 'race_theme_init');
add_action('widgets_init', 'race_widget_init');

function race_theme_init() {
	if (!get_option('race_theme_email'))
		add_option('race_theme_email', 'info@racecharities.org');

	wp_register_script( 'race_pages', RACE_THEME_ROOT_URI . "/js/race.js",    array('jquery') );
	wp_register_script( 'race_admin', RACE_THEME_ROOT_URI . "/js/profile.js", array('jquery') );

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

	// race menus
	add_filter('race_menu',    'race_filter_menu');
	add_filter('race_submenu', 'race_filter_submenu');
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
	$children = apply_filters( 'race_submenu', explode( "\n", $children ) );

	if ( count( $children ) > 1 ) {
		$output .= "$t<ul class=\"submenu-parent\">$t\t";
		$output .= trim( implode( "$t\t", $children ) );
		$output .= "$t</ul>";
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


require_once 'classes/warrior.php';
require_once 'classes/widgets.php';

$RACE['widgets']['warrior']  = new RACE_ProfileMenu(  'Warrior Sidebar' );
$RACE['widgets']['progress'] = new RACE_ProgressMeter( 'Progress Meter' );


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
	$menu = apply_filters( 'race_menu', $menu );

	if ( $menu ) {
		$content .= '<ul class="menu-parent">';
		$content .= str_replace( array( "\r", "\n", "\t" ), '', $menu );
		$content .= "</ul>";
	}
	return $before . $content . $after . "\n";
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

function race_sum_donations( $user ) {
	if ( ! $user ) return false;
	$total = 0;
	if ( array_key_exists( 'race_donors', $user ) ) {
		$eid = (int) $user->race_profile->event;
		$event = 'event_' . ( $eid ? $eid : RACE_EVENT_ID_HACK );
		$donors = (array) $user->race_donors[ $event ];
		foreach ($donors as $donor => $key) {
			$total += (int) $key['amount'];
		}
	}
	return $total;
}


// ACTIONS

function race_header() {
	if ( is_admin() )
		return '';
	// link lib.js, conditional ie stylesheet inside header (non-admin)
	$root = RACE_THEME_ROOT_URI;

	// because IE is a friggin retard
	echo <<<HTML
<!--[if lte IE 6]><link rel="stylesheet" type="text/css" href="{$root}/css/ie.css" /><![endif]-->\n
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
		include(STYLESHEETPATH . '/templates/root.php');	exit;
	}
	global $pagename;
	if ( 'warrior' == $pagename && $login = $_GET['runner'] ) {
		$race_donor = new RACE_Warrior_Donor( $login );
		include(STYLESHEETPATH . '/templates/donate.php');	exit;
	}
	if ( 'login' == $pagename && is_user_logged_in() ) {
		global $userdata;
		$warrior = new RACE_Warrior_Profile( $userdata );
		include(STYLESHEETPATH . '/templates/login.php');	exit;
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

function race_filter_menu( $menu ) {
	global $pagename;
	if ( in_array( $pagename, array( 'author-list', 'author-profile' ) ) ) {
		$menu_array = explode( "\n", $menu );
		$menu_array = preg_replace(
			'/^(.*class="[^"]+)(".*\/warriors\/".*)/',
			"$1 current_page_ancestor$2",
			$menu_array
		);
		$menu = implode( "\n", $menu_array );
	}
	return $menu;
}
function race_filter_submenu( $menu ) {
	global $pagename;
	$pagenames = array(
		 'login'
		,'warrior'
		,'thank-you'
		,'warriors'
		,'author-list'
		,'author-profile'
	);
	if ( in_array( $pagename, $pagenames ) ) {
		switch ($pagename) {
			// donation before + after
			case 'thank-you':
			case 'warrior':
				$runner = ( array_key_exists( 'runner', $_GET ) )
					? 'warrior/' . $_GET['runner'] . '/' : '';
				$regexen = array(
					'/Donate/i', '/donations\/online\/warrior\//',
					'/\scurrent_page_item/' // removed
				);
				$replace = array( 'Back', "$runner" );
				$menu = preg_replace( $regexen, $replace, $menu );
				// fall through
			// warrior profile
			case 'author-profile':
				array_splice( $menu, -2, 1 );
			break;
			// login landing
			case 'login':
				$menu = preg_replace( '/\scurrent_page_item/', '', $menu );
				// fall through
			// warrior accounts + user list
			case 'author-list':
			case 'warriors':
				$logged_in = is_user_logged_in();
				$site = get_option('siteurl');
				$home = preg_quote( get_option('home'), '/');
				$req  = ( $logged_in
					? $_SERVER['REQUEST_URI']
					: preg_replace('/^(.*\/warriors).*$/', "$1/login/", $_SERVER['REQUEST_URI'])
				);
				$regexen = array(
					"/$home\/warriors\/signup\//",
					"/$home\/warriors\/login\//"
				);
				$replace = array(
					"$site" . ( $logged_in
						? "/wp-admin/profile.php"
						: "/wp-login.php?action=register"
					),
					"$site/wp-login.php?" . ( $logged_in
						? "action=logout&redirect_to=$req"
						: "redirect_to=$req"
					)
				);
				if ( $logged_in ) {
					array_push( $regexen, '/Signup/',  '/Login/' );
					array_push( $replace, 'Edit Profile', 'Logout' );
				}
				$menu = preg_replace( $regexen, $replace, $menu );
			break;
		}
	}
	return $menu;
}

?>