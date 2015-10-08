<?php
require_once 'gapi/Google_Client.php';
require_once 'gapi/contrib/Google_DriveService.php';
require_once 'gapi/contrib/Google_Oauth2Service.php';

add_action( 'wp', 'bdn_is_user_auth', 1 );
function bdn_is_user_auth() {

	global $driveService;

	$current_user_id = get_current_user_id();

	$client = new Google_Client();
	$client->setRedirectUri( home_url( '/' ) );
	$driveService = new Google_DriveService($client);
	$oauth2 = new Google_Oauth2Service($client);

	if( isset( $_GET[ 'code' ] ) ) {
		$client->authenticate( $_GET['code'] );
		$user = $oauth2->userinfo->get();
		$new_user = get_user_by( 'email', $user[ 'email' ] );
		if( $current_user_id && $new_user->ID == $current_user_id ) {
			update_user_meta( $new_user->ID, '_google_access_token', $client->getAccessToken() );
		} elseif( !$current_user_id ) {
			wp_set_current_user( $new_user->ID, $new_user->user_login );
			wp_set_auth_cookie( $new_user->ID );
			do_action( 'wp_login', $new_user->user_login );
		} else {
			die( 'Sorry, please use your BDN account' );
		}
		header( 'Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] );
		exit;
	} elseif( !is_user_logged_in() ) {
		header( 'Location: ' . $client->createAuthUrl() );
		exit;
	}

	if( $access_token = get_user_meta( $current_user_id, '_google_access_token', true ) )
		$client->setAccessToken( $access_token );

	if ($client->getAccessToken()) {
		
		if( $access_token && $access_token != $client->getAccessToken() )
			update_user_meta( $new_user->ID, '_google_access_token', $client->getAccessToken() );

	} else {
		header( 'Location: ' . $client->createAuthUrl() );
		exit;
	}
	
	return $driveService;

}

function bdn_is_user_auth2() {

	global $driveService;

	$current_user_id = get_current_user_id();

	$client = new Google_Client();
	$client->setRedirectUri( home_url( '/' ) );
	$driveService = new Google_DriveService($client);
	$oauth2 = new Google_Oauth2Service($client);

	if( !isset( $_GET['code'] ) && ( !is_user_logged_in() || ( ( $access_token = get_user_meta( $current_user_id, '_google_access_token', true ) ) && $client->setAccessToken( $access_token ) ) && !$client->getAccessToken() ) ){
	
		header( 'Location: ' . $client->createAuthUrl() );
		exit;

	}

	if( isset( $_GET['code'] ) ){

		$client->authenticate( $_GET['code'] );
		$user = $oauth2->userinfo->get();
		$new_user = get_user_by( 'email', $user['email'] );
		if( !$current_user_id ) {
			wp_set_current_user( $new_user->ID, $new_user->user_login );
			wp_set_auth_cookie( $new_user->ID );
			do_action( 'wp_login', $new_user->user_login );
		}elseif( $new_user->ID == $current_user_id ) {
                        update_user_meta( $new_user->ID, '_google_access_token', $client->getAccessToken() );
		}else{
			die( 'Sorry, please use your BDN account' );
		}

                header( 'Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] );

	}

	return $driveService;

} 
