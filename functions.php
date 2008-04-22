<?php

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

	$list_ops = "title_li=&echo=0&sort_column=menu_order&child_of=";

	if ( $post->post_parent )
		$list_ops .= $post->post_parent;
	else if ( is_page() && !is_front_page() )
		$list_ops .= $post->ID;
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
	$selected = (array) $options['posts'];

	if ( !count($selected) )
		return '';

	$wp_url = get_bloginfo('wpurl');
	$scount = count($selected);
	$r = array();

	for ( $i=0; $i < $scount; $i++ ) {
		if ( $selected[$i] > 0 && $ruser = get_userdata($selected[$i]) )
			$r[] = $ruser;
	}

	if ( !empty($r) ) :
		$rcount = count($r)
?>
		<?php echo $before_widget; ?>
			<?php echo $before_title . $title . $after_title; ?>
		<ul>
			<?php for ( $ri = 0; $ri < $rcount; $ri++ ) {
				$spot_user = $r[$ri];

				if ( $spot_user->ID == -1 ) continue;

				$etc = '';
				$blurb = explode(' ', $spot_user->description);

				if ( count($blurb) > 10 ) $etc = '...';

				$blurb = implode(' ', array_slice($blurb, 0, 10)) . $etc;
			?>
			<li>
				<?php echo get_avatar($spot_user, '48'); ?>
				<div>
					<h4><a href="<?php echo $wp_url . "/accounts/warrior/?user={$spot_user->ID}" ?>"><?php echo $spot_user->display_name; ?></a></h4>
					<?php echo wpautop( wptexturize( $blurb ) ); ?>
				</div>
			</li>
			<?php } ?>
		</ul>
		<?php echo $after_widget; ?>
<?php
	endif;
	wp_cache_add('widget_race_spotlight', ob_get_flush(), 'widget');
}

function flush_widget_race_spotlight() {
	wp_cache_delete('widget_race_spotlight', 'widget');
}

function widget_race_spotlight_control() {
	global $wpdb;

	$options = $newoptions = get_option('widget_race_spotlight');
	if ( $_POST["race-spotlight-submit"] ) {
		$newoptions['title'] = strip_tags(stripslashes($_POST["race-spotlight-title"]));
		$newoptions['posts'] = (array) $_POST["race-spotlight-posts"];
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_race_spotlight', $options);
		flush_widget_race_spotlight();
	}
	$title = attribute_escape($options['title']);
	$selected = !empty( $options['posts'] ) ? $options['posts'] : array(-1, -1, -1);

	$all_query = <<<SQL
		SELECT u.ID, u.display_name FROM $wpdb->users AS u
		-- INNER JOIN $wpdb->usermeta AS m ON (m.user_id = u.ID)
		-- WHERE m.meta_key = '{$wpdb->prefix}capabilities'
		--   AND INSTR(m.meta_value,'subscriber') > 0
		ORDER BY u.display_name
SQL;
	$all_logins = $wpdb->get_results($all_query);
	$logins = '<option value="-1">Select</option>';
	foreach ( (array) $all_logins as $login )
		$logins .= "<option value=\"{$login->ID}\">". wp_specialchars($login->display_name) ."</option>\n";
?>
			<p><label for="race-spotlight-title"><?php _e('Title:'); ?> <input class="widefat" id="race-spotlight-title" name="race-spotlight-title" type="text" value="<?php echo $title; ?>" /></label></p>
			<h3><?php _e('Users:'); ?></h3>
			<?php for ( $i=0; $i < 3; $i++ ) {
				$choice = $selected[$i];
				$choices = preg_replace("/(=\"$choice\")(>)/", "$1 selected=\"selected\"$2", $logins);
				?>
			<p>
				<select id="race-spotlight-post-<?php echo $i; ?>" name="race-spotlight-posts[]">
<?php echo $choices; ?>
				</select>
			</p>
				<?php
			} ?>
			<input type="hidden" id="race-spotlight-submit" name="race-spotlight-submit" value="1" />
<?php
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
<!--[if lte IE 6]><link rel="stylesheet" type="text/css" href="{$root}/ie.css" /><![endif]-->
HTML;

	wp_enqueue_script( 'race', $root . "/util.js", array('jquery') );
}

add_action('wp_print_scripts', 'race_header');

// footer thingy
function race_footer() {
	$base = get_option('home');
	?>

	<div id="ft">

		<?php sandbox_globalnav(); ?>

		<ul id="ft-admin" class="menu-parent"
			><li><?php wp_register('',''); ?></li
			><li><?php wp_loginout(); ?></li
			><li><a href="<?php echo $base; ?>/home/user-agreement/" title="View User Agreement">User Agreement</a></li
			><li><a href="<?php echo $base; ?>/home/privacy-policy/" title="View Privacy Policy">Privacy Policy</a></li
			><li><a href="mailto:<?php echo antispambot( get_option( 'admin_email' ) ); ?>">Contact Us</a></li
			><li><a href="<?php echo $base; ?>/" title="Go to homepage">Home</a></li
		></ul>

	</div>
	<?php
}

add_action('get_footer', 'race_footer');

// quadrant
function race_front_meta( $type='' ) {
	global $post;

	$content = '';

	$custom = get_post_custom();
	$values = $custom[$type];

	if ( count($values) > 0 ) {
		$content[] = "<ul id='$type-meta'>";

		for ( $i=0; $i < count($values); $i++ ) {
			$value = trim($values[$i]);
			$content[] = "<li>{$value}</li>";
		}
		$content[] = "</ul>";
		$content = implode($content, "\n");
	}

	echo $content;
}

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