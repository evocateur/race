<?php
// jQuery
function sandbox_scripts() {
	wp_enqueue_script( 'sandbox', get_bloginfo( 'stylesheet_url' ) . "/util.js", array( 'jquery' ) );
}
add_action( 'wp_print_scripts', 'sandbox_scripts' );
?>