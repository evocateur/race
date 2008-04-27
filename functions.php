<?php
/*
	Initialization
*/
function race_theme_init() {
	if (!get_option('race_theme_email'))
		add_option('race_theme_email', 'info@racecharities.org');

	add_filter('users_join',  'race_aleph_join');
	add_filter('users_where', 'race_aleph_where');
}
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

add_action('init', 'race_theme_init');

// globalnav
function race_menu( $before = '', $after = '' ) {
	$content = '';
	$options_wp_list = 'title_li=&sort_column=menu_order&echo=0&depth=1';

	if ( get_option('show_on_front') == 'page' )
	    $options_wp_list .= '&exclude=' . get_option('page_on_front');

	$menu = wp_list_pages($options_wp_list);

	if ( $menu ) {
		$content .= '<ul class="menu-parent">';
		$content .= str_replace( array( "\r", "\n", "\t" ), '', $menu );
		$content .= "</ul>";
	}
	return $before . $content . $after;
}

function race_filter_sandbox_menu() {
	return race_menu( '<div id="menu">', '</div>' );
}

add_filter('sandbox_menu', 'race_filter_sandbox_menu');

function race_sandbox_class( $c ) {
	// remove date crap, untagged
	// remove ids of -1, or - dangling at end
	return preg_grep('/(^[ymdh]\d{2,4}|untagged|-(-1)?)$/', $c, PREG_GREP_INVERT);
}

add_filter('body_class', 'race_sandbox_class');
add_filter('post_class', 'race_sandbox_class');

// gallery  ====================================
function widget_race_gallery( $args ) {
	extract( $args, EXTR_SKIP );

	$gallery = build_race_gallery();

	if ( $gallery ) {
		echo $before_widget . "\n\t\t\t\t" . $gallery . $after_widget;
	}
}

function build_race_gallery() {
	// i love the smell of code duplication in the morning
	global $post;

	if ( is_front_page() || !is_page() )
		return false;

	extract(array(
		'orderby'    => 'menu_order ASC, ID ASC',
		'id'         => $post->ID,
		'itemtag'    => 'li',
		'captiontag' => 'p',
		'columns'    => 1,
		'size'       => 'thumbnail',
	), EXTR_SKIP);

	$id = intval( $id );
	$orderby = addslashes( $orderby );
	$attachments = get_children( "post_parent=$id&post_type=attachment&post_mime_type=image&orderby=\"{$orderby}\"" );

	if ( empty( $attachments ) )
		return '';

	$itemtag    = tag_escape( $itemtag );
	$captiontag = tag_escape( $captiontag );
	$columns    = intval( $columns );

	$output = apply_filters( 'gallery_style', '<ul class="gallery">' );

	foreach ( $attachments as $id => $attachment ) {
		$link = wp_get_attachment_link( $id, $size );
		$output .= "\n\t\t\t\t\t<{$itemtag} class='gallery-item'>";
		$output .= "\n\t\t\t\t\t\t{$link}";
		if ( $captiontag && trim( $attachment->post_excerpt ) ) {
			$output .= "\n\t\t\t\t\t\t<{$captiontag} class='gallery-caption'>{$attachment->post_excerpt}</{$captiontag}>";
		}
		$output .= "\n\t\t\t\t\t</{$itemtag}>";
	}

	$output .= "\n\t\t\t\t</ul>";

	return $output;
}

function race_gallery( $attr ) {
	// TODO: make this a more elegant choice
	return '<!-- gallery in sidebar -->';
}


// sub menu ====================================
function widget_race_submenu( $args ) {
	extract( $args, EXTR_SKIP );

	global $post;

	$list_ops = "title_li=&echo=0&sort_column=menu_order&depth=1&child_of=";

	if ( $post->post_parent ) {
		$list_ops .= $post->post_parent;
	}
	else if ( $post->slug == 'author-list' ) {
		$child = array_shift(query_posts('pagename=warriors/current'));
		$list_ops .= $child->post_parent;
	}
	else if ( $post->slug == 'author-profile' ) {
		$child = array_shift(query_posts('pagename=donations/online'));
		$list_ops .= $child->ID;
	}
	else if ( is_page() && !is_front_page() ) {
		$list_ops .= $post->ID;
	}
	else
		return '';

	$children = wp_list_pages( $list_ops );

	if ( $children ) {
		echo $before_widget . "\n"; ?>
				<ul class="submenu-parent">
					<?php echo trim( implode( "\n\t\t\t\t\t", explode( "\n", $children ) ) ) ."\n"; ?>
				</ul><?php
		echo $after_widget;
	}
}


// spotlight ===================================
function widget_race_spotlight( $args ) {
	if ( !is_front_page() )
		return '';

	if ( $output = wp_cache_get('widget_race_spotlight', 'widget') )
		return print($output);

	ob_start();
	extract($args, EXTR_SKIP);

	$options = get_option('widget_race_spotlight');
	$title = empty($options['title']) ? __('In the Spotlight') : $options['title'];
	$tag = 'in-the-spotlight';
	if ( !$number = (int) $options['number'] )
		$number = 3;
	else if ( $number < 1 )
		$number = 1;
	else if ( $number > 5 )
		$number = 5;

	$r = new WP_Query("showposts=$number&post_status=publish&tag=$tag");
	if ( $r->have_posts() ) :
?>
		<?php echo $before_widget; ?>
			<?php echo $before_title . $title . $after_title; ?>
		<ul>
			<?php while ( $r->have_posts() ) : $r->the_post(); ?>
			<li>
				<a href="<?php the_permalink(); ?>"><?php race_get_thumb_image(); ?></a>
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

	wp_cache_add('widget_race_spotlight', ob_get_flush(), 'widget');
}

function flush_widget_race_spotlight() {
	wp_cache_delete('widget_race_spotlight', 'widget');
}

function widget_race_spotlight_control() {
	$options = $newoptions = get_option('widget_race_spotlight');
	if ( $_POST["race-spotlight-submit"] ) {
		$newoptions['title']  = strip_tags(stripslashes($_POST["race-spotlight-title"]));
		$newoptions['number'] = (int) $_POST["race-spotlight-number"];
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_race_spotlight', $options);
		flush_widget_race_spotlight();
	}
	$title = attribute_escape($options['title']);
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

$default_avatar = get_option('siteurl') . '/wp-content/uploads/default_avatar.jpg';
function race_get_thumb_image() {
	global $post, $default_avatar;
	$thumb_src  = $default_avatar; // tentative

	$thumb = get_children(array(
		'post_parent'    => $post->ID,
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'numberposts'    => 1
	));

	if ( is_array($thumb) ) {
		$thumb = array_shift( $thumb );
		if ( $thumb = wp_get_attachment_image_src($thumb->ID) )
			$thumb_src = $thumb[0];
	}

	echo "<img src=\"{$thumb_src}\" alt=\"\" />";
}

// warriors ====================================
/*	Notes re: profile.php / user-edit.php

	Actions
	-------
		personal_options_update
			after flash when redirected after POST

		profile_personal_options
			between colors and name etc (midway)

		show_user_profile
		edit_user_profile (admin)
			after everything, before "submit"

	Filters
	-------
		show_password_fields
			boolean decision to allow password change on profile
*/
function race_profile_form() {
	global $userdata;
	$defaults = array(
		'street' => '',
		'city'   => '',
		'state'  => '',
		'zip'    => '',
		'phone'  => ''
	);
	$race_opts = array_merge( $defaults,
		array_filter( (array) get_usermeta($userdata->ID, 'race_profile') )
	);
?>
	<style type="text/css">
	#profile-page #race input {
		margin: 1px 0;
		padding: 3px;
	}
	#profile-page #race label {
		float: left;
		margin-right: 0.4em;
	}
	#profile-page #race td > label {
		margin-right: 0.5em;
	}
	#profile-page #race label.center,
	#profile-page #race label.center input {
		text-align: center;
	}
	#profile-page #race label input {
		display: block;
	}
	#profile-page #race td br {
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
	</style>
	<table class="form-table" id="race">
		<tbody>
			<tr>
				<th>Mailing Address</th>
				<td id="race-mail-wrap">
					<label for="race-street">Street
					<input type="text" name="race_profile[street]" value="<?php echo $race_opts['street'] ?>" id="race-street" /></label><br />
					<label for="race-city">City
					<input type="text" name="race_profile[city]" value="<?php echo $race_opts['city']; ?>" id="race-city" /></label>
					<label for="race-state" class="center">State
					<input type="text" name="race_profile[state]" value="<?php echo $race_opts['state']; ?>" id="race-state" /></label>
					<label for="race-zip">Zip
					<input type="text" name="race_profile[zip]" value="<?php echo $race_opts['zip']; ?>" id="race-zip" /></label>
				</td>
			</tr>
			<tr>
				<th><label for="race-phone">Phone</label></th>
				<td>
					<input type="text" name="race_profile[phone]" value="<?php echo $race_opts['phone']; ?>" id="race-phone" />
					<input type="hidden" name="race_profile_update" value="1" />
				</td>
			</tr>
		</tbody>
	</table>
<?php
}

function race_profile_process( $uid ) {
	if ( isset( $_POST['race_profile_update'] ) ) {
		$postage = array(
			'street' => '',
			'city'   => '',
			'state'  => '',
			'zip'    => '',
			'phone'  => ''
		);
		$posted = maybe_unserialize($_POST['race_profile']);

		foreach ( $posted as $k => $v ) {
			$postage[$k] = wp_specialchars( trim($v) );
		}

		update_usermeta( $uid, 'race_profile', $postage );
	}
}

add_action('show_user_profile', 'race_profile_form', 9);
add_action('edit_user_profile', 'race_profile_form', 9);

add_action('profile_update', 'race_profile_process');

// theme widget init ===========================
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
		add_filter('post_gallery', 'race_gallery');
	}

	$widget_ops = array(
		'classname'   => 'widget_spotlight',
		'description' => "In the Spotlight"
	);
	wp_register_sidebar_widget('spotlight', 'Page Spotlight', 'widget_race_spotlight', $widget_ops);
	wp_register_widget_control('spotlight', 'Page Spotlight', 'widget_race_spotlight_control');

	if ( is_active_widget('widget_race_spotlight') ) {
		// it's polite to flush
		add_action('save_post',    'flush_widget_race_spotlight');
		add_action('deleted_post', 'flush_widget_race_spotlight');
		add_action('switch_theme', 'flush_widget_race_spotlight');
	}
}

add_action('widgets_init', 'race_widget_init');


// theme utilities =============================

// link util.js, conditional ie stylesheet inside header
function race_header() {
	$root = get_stylesheet_directory_uri();

	// because IE is a friggin retard
	echo <<<HTML
<!--[if lte IE 6]><link rel="stylesheet" type="text/css" href="{$root}/ie.css" /><![endif]-->\n
HTML;

	wp_enqueue_script( 'race', $root . "/util.js", array('jquery') );
}

add_action('wp_print_scripts', 'race_header');

/*
function race_login_header() {
	echo <<<HTML
	<script type="text/javascript">
	function ale(f){var o=window.onload;if(typeof o!='function'){window.onload=f;}else{window.onload=function(){o();f();};}}
	function patch_redirect() {
		var r = document.forms[0]['redirect_to'];
		if (r && r.value == 'wp-admin/') r.value = 'warriors/login/';
	}
	ale(patch_redirect);
	</script>\n
HTML;
}

add_action('login_head', 'race_login_header');
*/

// footer thingy
function race_footer() {
	$base = get_option('home');
	$email = get_option('race_theme_email');
	if (empty($email))
		$email = get_option('admin_email');
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

add_action('get_footer', 'race_footer');

// change text of wp_register links
function race_filter_register( $arg ) {
	return preg_replace(
		array('/Register/','/Site Admin/'),
		array('Become A Warrior','Dashboard'),
		$arg
	);
}

add_filter('register', 'race_filter_register');

// remove 'protected: ' and 'private: ' from the_title
function race_filter_title( $arg ) {
	return preg_replace( array('/^Protected: /', '/^Private: /'), '', $arg );
}

add_filter('the_title', 'race_filter_title');

// quadrants
function race_quadrants() {
	if ( $content = wp_cache_get('theme_race_quadrants') )
		return print($content);

	ob_start();

	$q = new WP_Query("showposts=4&category_name=quadrant");

	if ( $q->have_posts() ) : ?>
	<div id="quadrant">
		<ul class="xoxo">
<?php while ( $q->have_posts() ) : $q->the_post(); ?>
			<li>
				<h4><?php the_title(); ?></h4>
				<?php race_get_thumb_image(); ?>

				<?php the_content(); ?>
			</li>
<?php endwhile; ?>
		</ul>
	</div>
<?php
		wp_reset_query();
	endif;

	wp_cache_add('theme_race_quadrants', ob_get_flush());
}

function flush_race_quadrants() {
	wp_cache_delete('theme_race_quadrants');
}

add_action('save_post',    'flush_race_quadrants');
add_action('deleted_post', 'flush_race_quadrants');

// manhandle static front page to use stylesheet_dir template
function race_template_hijack() {
	$uri  = ( isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://';
	$uri .= $_SERVER['HTTP_HOST'];
	$uri .= $_SERVER['REQUEST_URI'];

	if ( $uri == trailingslashit(get_option('siteurl')) ) {
		include(STYLESHEETPATH . '/root.php');
		exit;
	}
}

add_action('template_redirect', 'race_template_hijack');

?>