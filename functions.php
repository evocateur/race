<?php

add_filter('post_gallery', 'race_gallery');

function race_gallery($attr) {
	return '<!-- gallery in sidebar -->';
}

function widget_race_gallery($args) {
	extract($args);
	
	$gallery = build_race_gallery();
	
	if ($gallery) {
		echo $before_widget . "
				" . $gallery . $after_widget;
	}
}

function build_race_gallery() {
	global $post;

	extract(array(
		'orderby'    => 'menu_order ASC, ID ASC',
		'id'         => $post->ID,
		'itemtag'    => 'li',
		'icontag'    => 'span',
		'captiontag' => 'p',
		'columns'    => 1,
		'size'       => 'thumbnail',
	));

	$id = intval($id);
	$orderby = addslashes($orderby);
	$attachments = get_children("post_parent=$id&post_type=attachment&post_mime_type=image&orderby=\"{$orderby}\"");

	if ( empty($attachments) ) {
		return '';
	}

	// $listtag = tag_escape($listtag);
	$itemtag = tag_escape($itemtag);
	$captiontag = tag_escape($captiontag);
	$columns = intval($columns);
	// $itemwidth = $columns > 0 ? floor(100/$columns) : 100;
	
	$output = apply_filters('gallery_style', "<ul class='gallery'>");

	foreach ( $attachments as $id => $attachment ) {
		$link = wp_get_attachment_link($id, $size);
		$output .= "
					<{$itemtag} class='gallery-item'>";
		$output .= "
						{$link}";
		if ( $captiontag && trim($attachment->post_excerpt) ) {
			$output .= "
						<{$captiontag} class='gallery-caption'>{$attachment->post_excerpt}</{$captiontag}>";
		}
		$output .= "
					</{$itemtag}>";
		/*
		if ( $columns > 0 && ++$i % $columns == 0 )
			$output .= '<br style="clear: both" />';
		*/
	}

	$output .= "
				</ul>";

	return $output;
}


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

// jQuery
function race_util_scripts() {
	wp_enqueue_script('sandbox', get_bloginfo('stylesheet_directory') . "/util.js", array('jquery'));
}

add_action('widgets_init', 'race_widget_init');

add_action('wp_print_scripts', 'race_util_scripts');

?>