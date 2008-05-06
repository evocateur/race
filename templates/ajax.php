<?php
/*
	Donor Ajax
*/
require_once('../../../wp-blog-header.php');

if ( isset( $_POST['donor'] ) && isset( $_POST['warrior_id'] ) && class_exists( 'RACE_Warrior' ) ) {

	$pledge = new RACE_Warrior_Pledge( (int) $_POST['warrior_id'] );

	if ( $pledge->valid_request() )
		$pledge->add( $_POST['donor'] );
	else
		$pledge->setError( 'Invalid Referer' );
}

die( $pledge->getResponse() );

?>