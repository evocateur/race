<?php
/*
	RACE Ajax
*/
require_once('../../../../wp-blog-header.php');

$race_ajax_response = '';

if ( !class_exists( 'RACE_Warrior' ) )
	die( $race_ajax_response );

if ( isset( $_POST['warrior_id'] ) ) {
	$param = (int) $_POST['warrior_id'];
	// donor
	if ( isset( $_POST['donor'] ) ) {
		$pledge = new RACE_Warrior_Pledge( $param );
		if ( $pledge->valid_request() )
			$pledge->add( $_POST['donor'] );
		else
			$pledge->setError( 'Invalid Referer' );
		$race_ajax_response = $pledge->getResponse();
	}
	// login profile
	if ( isset( $_POST['race_profile'] ) ) {
		$profile = new RACE_Warrior_Profile( $param );
		if ( $profile->valid_request() )
			$profile->form_process();
		else
			$profile->setError( 'Invalid Referer' );
		$race_ajax_response = $profile->getResponse();
	}
}

die( $race_ajax_response );

?>