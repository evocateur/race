<?php
// gallery  ====================================
function widget_race_gallery($args) {
	extract($args);

	$gallery = build_race_gallery();

	if ($gallery) {
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

	$id = intval($id);
	$orderby = addslashes($orderby);
	$attachments = get_children("post_parent=$id&post_type=attachment&post_mime_type=image&orderby=\"{$orderby}\"");

	if ( empty($attachments) )
		return '';

	$itemtag = tag_escape($itemtag);
	$captiontag = tag_escape($captiontag);
	$columns = intval($columns);

	$output = apply_filters('gallery_style', '<ul class="gallery">');

	foreach ( $attachments as $id => $attachment ) {
		$link = wp_get_attachment_link($id, $size);
		$output .= "\n\t\t\t\t\t<{$itemtag} class='gallery-item'>";
		$output .= "\n\t\t\t\t\t\t{$link}";
		if ( $captiontag && trim($attachment->post_excerpt) ) {
			$output .= "\n\t\t\t\t\t\t<{$captiontag} class='gallery-caption'>{$attachment->post_excerpt}</{$captiontag}>";
		}
		$output .= "\n\t\t\t\t\t</{$itemtag}>";
	}

	$output .= "\n\t\t\t\t</ul>";

	return $output;
}

function race_gallery($attr) {
	return '<!-- gallery in sidebar -->';
}

add_filter('post_gallery', 'race_gallery');

// sub menu ====================================
function widget_race_submenu($args) {
	extract($args);

	global $post;

	$list_ops = "title_li=&echo=0&sort_column=menu_order&child_of=";

	if ($post->post_parent)
		$list_ops .= $post->post_parent;
	else
		$list_ops .= $post->ID;

	$children = wp_list_pages($list_ops);

	if ($children) {
		echo $before_widget . "\n"; ?>
				<ul class="submenu-parent">
					<?php echo trim(implode("\n\t\t\t\t\t", explode("\n", $children))) . "\n"; ?>
				</ul><?php
		echo $after_widget;
	}
}

// theme widget init ===========================
function race_widget_init() {
	if (!function_exists('register_sidebars'))
		return;

	$widget_ops = array(
		'classname'    =>  'widget_submenu',
		'description'  =>  "A submenu that displays children of the current page."
	);

	wp_register_sidebar_widget('submenu', 'Page Sub-menu', 'widget_race_submenu', $widget_ops);

	$widget_ops['classname'] = 'widget_gallery';
	$widget_ops['description'] = "A sidebar image gallery using shortcodes for post";

	wp_register_sidebar_widget('sidegallery', 'Page Gallery', 'widget_race_gallery', $widget_ops);
}

add_action('widgets_init', 'race_widget_init');

// theme utilities =============================
function race_header() {
	wp_enqueue_script('race', get_bloginfo('stylesheet_directory') . "/util.js", array('jquery'));
}

add_action('wp_print_scripts', 'race_header');

function race_footer() {
	$root = get_bloginfo('stylesheet_directory');
	
	echo <<<HTML
\n\t<img src="{$root}/images/glorybg.png" id="glory" alt="Glory Gensch" />\n
HTML;
}

add_action('get_footer', 'race_footer');
?>