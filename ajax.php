<?php
/*
	Donor Ajax
*/
require_once('../../../wp-blog-header.php');

if ( isset( $_POST['donor'] ) && isset( $_POST['warrior_id'] ) && class_exists( 'RACE_Warrior' ) ) {

	$w_ID = intval( $_POST['warrior_id'] );
	$SEED = 'race-warrior-' . $w_ID . '-donor';

	if ( check_ajax_referer( $SEED, '_ajax_nonce', false ) ) {
		$warrior = new RACE_Warrior( 'donor', $w_ID, false );
		$warrior->savePledge( $_POST );
	}
	else $warrior->setError( 'Invalid Referer' );
}

die( $warrior->getResponse() );

?>