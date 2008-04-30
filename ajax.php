<?php
/*
	Donor Ajax
*/
require_once('../../../wp-blog-header.php');

$response = 'alert("error")';

if ( isset( $_POST['donor'] ) && isset( $_POST['warrior_id'] ) && class_exists( 'RACE_Warrior' ) ) {
	$w_ID = intval( $_POST['warrior_id'] );
	$SEED = 'race-warrior-' . $w_ID . '-donor';
	check_ajax_referer( $SEED );
	$warrior = new RACE_Warrior( 'donor', $w_ID, false );
	$response = "";
}

die($response);
?>