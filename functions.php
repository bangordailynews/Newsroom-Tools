<?php

/*
 * Term IDs for the default desk (Other) and status (Story idea submitted).
*/

$budget_sections = array(
	'a1' => 'A1',
	'insidea' => 'Inside A',
	'b1' => 'B1',
	'state' => 'State',
	'first' => 'First',
	'second' => 'Second',
	'third' => 'Third',
	'biz' => 'Biz',
	'sports' => 'Sports',
	'living' => 'Living',
	'outdoors' => 'Outdoors',
	'health' => 'Health',
	'edit' => 'Edit'
);

define( 'DEFAULT_DESK', 7 );
define( 'DEFAULT_PUBLICATION', 8 );
define( 'DEFAULT_STATUS', 38 );
define( 'BDN_WORDS_PER_INCH', 30 );
define( 'GOOGLE_LOGIN_DOMAIN', 'example.com' );
define( 'PUBLISH_ENDPOINT', 'example.com/xmlrpc.php' );
define( 'PUBLISH_USER', 'example' );
define( 'PUBLISH_PASS', 'password123' );
define( 'LIVE_SITE_URL', 'https://example.com' );

global $apiConfig;
$apiConfig = array(
    // True if objects should be returned by the service classes.
    // False if associative arrays should be returned (default behavior).
    'use_objects' => false,
  
    // The application_name is included in the User-Agent HTTP header.
    'application_name' => '',

    // OAuth2 Settings, you can get these keys at https://code.google.com/apis/console
    'oauth2_client_id' => '',
    'oauth2_client_secret' => '',
    'oauth2_redirect_uri' => '',

    // The developer key, you get this at https://code.google.com/apis/console
    'developer_key' => '',
  
    // Site name to show in the Google's OAuth 1 authentication screen.
    'site_name' => '',

    // Which Authentication, Storage and HTTP IO classes to use.
    'authClass'    => 'Google_OAuth2',
    'ioClass'      => 'Google_CurlIO',
    'cacheClass'   => 'Google_FileCache',

    // Don't change these unless you're working against a special development or testing environment.
    'basePath' => 'https://www.googleapis.com',

    // IO Class dependent configuration, you only have to configure the values
    // for the class that was configured as the ioClass above
    'ioFileCache_directory'  =>
        (function_exists('sys_get_temp_dir') ?
            sys_get_temp_dir() . '/Google_Client' :
        '/tmp/Google_Client'),

    // Definition of service specific values like scopes, oauth token URLs, etc
    'services' => array(
      'oauth2' => array(
          'scope' => array(
              'https://www.googleapis.com/auth/userinfo.profile',
              'https://www.googleapis.com/auth/userinfo.email',
          )
      ),
    )
);

require( 'inc/init.php' );
require( 'inc/core.php' );
require( 'inc/gapi.php' );
require( 'inc/queries.php' );
require( 'inc/ajax.php' );
require( 'inc/wpbrowser.php' );

if( !empty( $_GET[ 'docToWP' ] ) )
	require( 'inc/dtwp.php' );

if( is_admin() )
	require( 'inc/admin.php' );


function bdn_total_query_time() {
	global $wpdb;
	$total = 0;
	foreach( $wpdb->queries as $q )
		$total += $q[ 1 ];
	return $total;
}


function budget_time_to_file( ) {
	global $post;
	$post_file_time = get_post_meta( $post->ID, '_the_file_time', true );
	if( empty( $post_file_time ) )
		return;
		
	$statuses = wp_get_object_terms( $post->ID, 'status', array( 'fields' => 'names' ) );
	if( count( array_intersect( array( 'Published', 'Final published', 'Ready for print', 'Holding' ), $statuses ) ) )
		return;
	
	$file_time = strtotime( $post_file_time );
	$current_time = current_time('timestamp');
	echo '<span class="file_time' . ( ( $file_time < $current_time ) ? ' past_due' : '' )  . '">' . human_time_diff( $file_time, $current_time ) . ' ' . ( ( $file_time < $current_time ) ? 'ago' : '' ) . '</span>';

}

function budget_modified_user() {
	global $post;
	
	if( ( $user = get_post_meta( $post->ID, '_last_modified_name', true ) ) ) {
		$user = $user;
	} else {
		$user = get_the_modified_author();
	}
	
	echo $user;
}

function budget_time( $post = false, $default = 'g:i a' ) {
	if( empty( $post ) )
		global $post;

	if( empty( $post->file_time ) )
		return;
	
	echo date( $default, strtotime( $post->file_time ) );
}

function budget_desk( $post = false ) {
	if( empty( $post ) )
		global $post;
			
	echo get_budget_item_single( $post, 'desk' );
	
}

function budget_importance( $post = false ) {
	if( empty( $post ) )
		global $post;
		
	if( empty( $post->importance ) )
		$post = budget_set_up_item( $post );

	echo $post->importance;
	
}

function budget_publication( $post = false ) {
	if( empty( $post ) )
		global $post;
			
	echo get_budget_item_single( $post, 'publication' );
	
}

function budget_length( $post = false, $inches = false ) {

	if( empty( $post ) )
		global $post;
		
	$length = $post->budget_length;
	
	echo ( $inches ) ? round( $length / BDN_WORDS_PER_INCH ) . '"' : $length . 'w';
	
}

function budget_folders( $post = false, $link = true ) {
	if( empty( $post ) )
		global $post;
			
	$folders = get_budget_item_multi( $post, 'folders' );
	
	if( !empty( $folders ) )
		echo implode( ', ', $folders );
	
}

function budget_statuses( $post = false, $link = true ) {
	if( empty( $post ) )
		global $post;
			
	$statuses = get_budget_item_multi( $post, 'statuses' );
	
	if( !empty( $statuses ) )
		echo implode( ', ', $statuses );
	
}

function get_budget_day( $post = false ) {
	if( empty( $post ) )
		global $post;
			
	return get_budget_item_single( $post, 'day' );
	
}

function get_budget_item_single( $post = false, $item = 'desk' ) {
	if( empty( $post ) )
		global $post;
		
	if( empty( $post->$item ) )
		$post = budget_set_up_item( $post );

	$item = reset( $post->$item );
	return $item->name;

}

function get_budget_item_multi( $post = false, $item = 'folders' ) {
	if( empty( $post ) )
		global $post;
		
	if( empty( $post->$item ) )
		$post = budget_set_up_item( $post );

	$items = array();
	
	foreach( $post->$item as $i ) {
		$items[] = $i->name;
	}

	return $items;

}