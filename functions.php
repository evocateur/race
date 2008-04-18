<?php
// gallery  ====================================
function widget_race_gallery( $args ) {
	extract( $args );

	$gallery = build_race_gallery();

	if ( $gallery ) {
		echo $before_widget . "\n\t\t\t\t" . $gallery . $after_widget;
	}
}

function build_race_gallery() {
	// i love the smell of code duplication in the morning
	global $post;

	if ( is_front_page() )
		return false;

	extract(array(
		'orderby'    => 'menu_order ASC, ID ASC',
		'id'         => $post->ID,
		'itemtag'    => 'li',
		'captiontag' => 'p',
		'columns'    => 1,
		'size'       => 'thumbnail',
	));

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
	return '<!-- gallery in sidebar -->';
}

add_filter('post_gallery', 'race_gallery');


// sub menu ====================================
function widget_race_submenu( $args ) {
	extract( $args );

	global $post;

	$list_ops = "title_li=&echo=0&sort_column=menu_order&child_of=";

	if ( $post->post_parent )
		$list_ops .= $post->post_parent;
	else
		$list_ops .= $post->ID;

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
function widget_race_spotlight($args) {
	if ( $output = wp_cache_get('widget_race_spotlight', 'widget') )
		return print($output);

	ob_start();
	extract($args);
	$options = get_option('widget_race_spotlight');
	$title = empty($options['title']) ? __('In the Spotlight') : $options['title'];
	if ( !$number = (int) $options['number'] )
		$number = 3;
	else if ( $number < 1 )
		$number = 1;
	else if ( $number > 15 )
		$number = 15;

	$r = new WP_Query("showposts=$number&what_to_show=posts&nopaging=0&post_status=publish");
	if ($r->have_posts()) :
?>
		<?php echo $before_widget; ?>
			<?php echo $before_title . $title . $after_title; ?>
			<ul>
			<?php  while ($r->have_posts()) : $r->the_post(); ?>
			<li><a href="<?php the_permalink() ?>"><?php if ( get_the_title() ) the_title(); else the_ID(); ?> </a></li>
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

add_action('save_post', 'flush_widget_race_spotlight');
add_action('deleted_post', 'flush_widget_race_spotlight');
add_action('switch_theme', 'flush_widget_race_spotlight');

function widget_race_spotlight_control() {
	$options = $newoptions = get_option('widget_race_spotlight');
	if ( $_POST["race-spotlight-submit"] ) {
		$newoptions['title'] = strip_tags(stripslashes($_POST["race-spotlight-title"]));
		$newoptions['number'] = (int) $_POST["race-spotlight-number"];
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('widget_race_spotlight', $options);
		flush_widget_race_spotlight();
	}
	$title = attribute_escape($options['title']);
	if ( !$number = (int) $options['number'] )
		$number = 5;
?>

			<p><label for="race-spotlight-title"><?php _e('Title:'); ?> <input class="widefat" id="race-spotlight-title" name="race-spotlight-title" type="text" value="<?php echo $title; ?>" /></label></p>
			<p>
				<label for="race-spotlight-number"><?php _e('Number of posts to show:'); ?> <input style="width: 25px; text-align: center;" id="race-spotlight-number" name="race-spotlight-number" type="text" value="<?php echo $number; ?>" /></label>
				<br />
				<small><?php _e('(at most 15)'); ?></small>
			</p>
			<input type="hidden" id="race-spotlight-submit" name="race-spotlight-submit" value="1" />
<?php
}


// theme widget init ===========================
function race_widget_init() {
	if ( !function_exists( 'register_sidebars' ) )
		return;

	$widget_ops = array(
		'classname'   => 'widget_submenu',
		'description' => "A submenu that displays children of the current page."
	);
	wp_register_sidebar_widget( 'submenu', 'Page Sub-Menu', 'widget_race_submenu', $widget_ops );

	$widget_ops = array(
		'classname'   => 'widget_gallery',
		'description' => "A sidebar image gallery that displays images associated with a page."
	);
	wp_register_sidebar_widget( 'sidegallery', 'Page Gallery', 'widget_race_gallery', $widget_ops );

	$widget_ops = array(
		'classname'   => 'widget_spotlight',
		'description' => "In the Spotlight"
	);
	wp_register_sidebar_widget( 'spotlight', 'Page Spotlight', 'widget_race_spotlight', $widget_ops );
	wp_register_widget_control( 'spotlight', 'Page Spotlight', 'widget_race_spotlight_control' );
}

add_action( 'widgets_init', 'race_widget_init' );

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

add_action( 'wp_print_scripts', 'race_header' );

// footer thingy
function race_footer() {
	$base = get_option('home');
	?>

	<div id="meta-footer">

		<?php sandbox_globalnav(); ?>

		<p>
			<?php wp_loginout(); echo "\n"; ?>
			<span class="meta-sep">|</span>
			<?php wp_register('',''); echo "\n"; ?>
			<span class="meta-sep">|</span>
			<a href="<?php echo $base; ?>/home/user-agreement/">User Agreement</a>
			<span class="meta-sep">|</span>
			<a href="<?php echo $base; ?>/home/privacy-policy/">Privacy Policy</a>
			<span class="meta-sep">|</span>
			<a href="mailto:<?php echo antispambot( get_option( 'admin_email' ) ); ?>">Contact Us</a>
			<span class="meta-sep">|</span>
			<a href="<?php echo $base; ?>/">Home</a>
		</p>

	</div>
	<?php
}

add_action ( 'get_footer', 'race_footer' );

// spotlight or quadrant
function race_front_meta( $type='' ) {
	global $post;

	$content = '';

	$custom = get_post_custom();
	$values = $custom[$type];

	if ( count($values) > 0 ) {
		$content[] = "<ul id='$type-meta'>";

		for ($i=0; $i < count($values); $i++) {
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
	$uri  = ( isset($_SERVER['HTTPS'] ) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://';
	$uri .= $_SERVER['HTTP_HOST'];
	$uri .= $_SERVER['REQUEST_URI'];

	if ($uri == trailingslashit(get_option('siteurl'))) {
		include(STYLESHEETPATH . '/root.php');
		exit;
	}
}

add_action( 'template_redirect', 'race_template_hijack' );

?>