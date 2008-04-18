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

// theme widget init ===========================
function race_widget_init() {
	if ( !function_exists( 'register_sidebars' ) )
		return;

	$widget_ops = array(
		'classname'   => 'widget_submenu',
		'description' => "A submenu that displays children of the current page."
	);

	wp_register_sidebar_widget( 'submenu', 'Page Sub-menu', 'widget_race_submenu', $widget_ops );

	$widget_ops['classname']   = 'widget_gallery';
	$widget_ops['description'] = "A sidebar image gallery using shortcodes for post";

	wp_register_sidebar_widget( 'sidegallery', 'Page Gallery', 'widget_race_gallery', $widget_ops );
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
		include(STYLESHEETPATH . '/front.php');
		exit;
	}
}
add_action( 'template_redirect', 'race_template_hijack' );

?>