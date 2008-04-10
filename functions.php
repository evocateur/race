<?php

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
}

// jQuery
function race_util_scripts() {
	wp_enqueue_script('sandbox', get_bloginfo('stylesheet_directory') . "/util.js", array('jquery'));
}

add_action('init', 'race_widget_init');

add_action('wp_print_scripts', 'race_util_scripts');

?>