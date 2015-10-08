<?php

//The publish to WordPress function is in a separate file


function bdn_set_doc_permissions( $gdocID = false, $post = array() ) {

	if( empty( $gdocID ) || empty( $post ) )
		return;

	//Check and see if there's a drive object stored globally
	global $driveService;
	
	//If there's not, we should set one up
	if( !is_object( $driveService ) )
		$driveService = bdn_is_user_auth();


	//If the current user selected someone else as the author, change the owner of the doc
	if( $post[ 'post_author' ] != get_current_user_id() ) {
		$user = get_user_by( 'id', $post[ 'post_author' ] );
		//Make sure it's a BDN email first, tho
		if( strpos( $user->user_email, '@' . GOOGLE_LOGIN_DOMAIN ) !== false ) {
			$changeOwner = new Google_Permission();
			$changeOwner->setValue( $user->user_email );
			$changeOwner->setType( 'user' );
			$changeOwner->setRole( 'owner' );
		}
	}

	//Anybody at BDN with link can edit
	$filePermissions = new Google_Permission();
	$filePermissions->setValue( GOOGLE_LOGIN_DOMAIN );
	$filePermissions->setType( 'domain' );
	$filePermissions->setRole( 'writer' );
	$filePermissions->setWithLink( true );

	//Exponential backoff
	$permissions = false;
	for( $i = 0; $i < 10; $i++ ) {
	
		if( !empty( $permissions ) )
			continue;
		
		sleep( $i * 2 );
	
		try {
			//Set the permissions of the doc to anyone at BDN with link
			$driveService->permissions->insert( $gdocID, $filePermissions );
			
			//If this person isn't the owner of the budget item, change the owner of the Doc
			if( $post[ 'post_author' ] != get_current_user_id() )
				$driveService->permissions->insert( $gdocID, $changeOwner, array( 'sendNotificationEmails' => false ) );
		
			$permissions = true;
		
		} catch (Exception $e) {
			//@TODO: Check if it's an error we should be retrying for
			error_log( 'DRIVE API ERROR: ' . $e->getMessage() . ' : ' . json_encode( $e ) );
			$permissions = false;
		}
	}

}


/*
 * The motherlode
 * Creates a new budget item
 * Not actually AJAX. We just pretend it is.
*/
add_action( 'wp', 'bdn_do_budget_item', 20 );
function bdn_do_budget_item() {

	if( empty( $_GET[ 'action' ] ) || $_GET[ 'action' ] != 'do_budget_item' )
		return;
	
	//Nonce, always
	if( empty( $_POST[ 'budget_item_nonce' ] ) || !wp_verify_nonce( $_POST[ 'budget_item_nonce' ], 'budget-nonce' ) )
		return;
	
	//If we're modifying a doc, get it as an array
	//Else create an empty array
	if( !empty( $_POST[ 'budget' ][ 'id' ] ) && (int) $_POST[ 'budget' ][ 'id' ] != 0 ) {
		$post_id = (int) $_POST[ 'budget' ][ 'id' ];
		$post = get_post( $post_id, ARRAY_A );

		//Check and see if there's an existing visuals request
		$visuals_request = get_post_meta( $post_id, '_visuals_request', true );

		$new = false;
	} else {
		$post = array();
		$new = true;
		$visuals_request = false;
	}
	
	//Save this so we can check if anything has changed
	$orig = $post;

	$post[ 'post_type' ] = 'doc';
	$post[ 'post_status' ] = 'publish';
	$post[ 'post_title' ] = $_POST[ 'budget' ][ 'slug' ];
	$post[ 'post_excerpt' ] = $_POST[ 'budget' ][ 'description' ];
	$post[ 'post_author' ] = (int) $_POST[ 'budget' ][ 'author' ];
	
	//This will update if $post[ 'ID' ] is set, else will create a new post and return its ID
	if( $post != $orig )
		$post_id = wp_insert_post( $post );
	
	//Set the importance and desk
	wp_set_object_terms( $post_id, (int) $_POST[ 'budget' ][ 'desk' ], 'desk' );
	wp_set_object_terms( $post_id, (int) $_POST[ 'budget' ][ 'importance' ], 'importance' );

	//If it's a new budget item, give it the default status (Story idea submitted)
	if( $new ) 
		wp_set_object_terms( $post_id, DEFAULT_STATUS, 'status' );
	
	//Get the file time
	$file_time = strtotime( $_POST[ 'budget' ][ 'time' ] );
	
	//And save off the day as a term
	wp_set_object_terms( $post_id, date( 'Y-m-d', $file_time ), 'day' );
	
	//We're keeping a full record of all the different file times, so get what we have so far and the latest file time
	$file_times = json_decode( get_post_meta( $post_id, '_budget_file_time', true ), true );
	if( !is_array( $file_times ) ) $file_times = array();
	$last_file_time = end( $file_times );
	
	//If the submitted file time does not equal the latest file time, add to the array
	if( !empty( $file_times ) || empty( $last_file_time ) || empty( $last_file_time[ 'value' ] ) || $last_file_time[ 'value' ] != $file_time ) {
		$file_times[ date_i18n( 'U' ) ] = array( 'user' => get_current_user_id(), 'value' => date( 'Y-m-d H:i', $file_time ) );
	}
	
	//Save the latest file time (for sorting purposes) and the array
	$the_file_time = end( $file_times );
	update_post_meta( $post_id, '_the_file_time', $the_file_time[ 'value' ] );
	update_post_meta( $post_id, '_budget_file_time', json_encode( $file_times ) );
	
	//Save the budget lenght
	update_post_meta( $post_id, '_budget_length', (int) $_POST[ 'budget' ][ 'length' ] );
	
	//Visuals requests! Hooray!
	//First scenario: The budget line already has a visuals request
	//and we're just editing the existing one
	if( (int) $visuals_request > 0 && $_POST[ 'budget' ][ 'visuals' ][ 'request' ] == 'request' ) {

		//Get the post and modify what needs to be modified
		$visuals = get_post( $visuals_request, true );
		$visuals[ 'post_excerpt' ] = $_POST[ 'budget' ][ 'visuals' ][ 'details' ];
		$visuals[ 'post_title' ] = $_POST[ 'budget' ][ 'visuals' ][ 'time' ];

		//Editors' visual requests go in as published,
		//reporters' go in as drafts (default)
		//But don't override the existing status
		if( current_user_can( 'edit_others_posts' ) )
			$visuals[ 'post_status' ] = 'publish';

		wp_update_post( $visuals );

		//The visuals request gets the same date as the budget line
		wp_set_object_terms( $visuals[ 'ID' ], date( 'Y-m-d', $file_time ), 'day' );
	
	//Second scenario: The budget line already has a visuals request
	//but we're canceling it.
	} elseif( (int) $visuals_request > 0 && $_POST[ 'budget' ][ 'visuals' ][ 'request' ] != 'request' ) {
		
		//Get the post and set the status to canceled (but don't delete)
		$visuals = get_post( $visuals_request, true );
		$visuals[ 'post_status' ] = 'canceled';
		wp_update_post( $visuals );
				
		//Update with the new visuals status
		update_post_meta( $post_id, '_visuals_request', $_POST[ 'budget' ][ 'visuals' ][ 'request' ] );
	
	//Third scenario: There is a new visuals request
	} elseif( (int) $visuals_request == 0 && $_POST[ 'budget' ][ 'visuals' ][ 'request' ] == 'request' ) {
		
		//Build the array to insert a new object
		$visuals = array(
			'post_type' => 'visual',
			'post_excerpt' => $_POST[ 'budget' ][ 'visuals' ][ 'details' ],
			'post_title' => $_POST[ 'budget' ][ 'visuals' ][ 'time' ],
			'post_parent' => $post_id,
		);
		
		//If it's an editor, publish it
		if( current_user_can( 'edit_others_posts' ) )
			$visuals[ 'post_status' ] = 'publish';
		
		$visuals_id = wp_insert_post( $visuals );
		
		wp_set_object_terms( $visuals_id, date( 'Y-m-d', $file_time ), 'day' );
		
		update_post_meta( $post_id, '_visuals_request', $visuals_id );

	//Last scenario: There's some other form of the request
	} else {
		update_post_meta( $post_id, '_visuals_request', $_POST[ 'budget' ][ 'visuals' ][ 'request' ] );
	}
	
	//Go either to the doc or just reload the page
	$loc = ( !empty( $_POST[ 'budget' ][ 'go' ][ 'doc' ] ) ) ? get_permalink( $post_id ) : remove_query_arg( 'action' );
	
	//If this isn't new, just redirect
	if( !$new && get_post_meta( $post_id, '_gdocID', true ) ) {
		wp_redirect( $loc );
	} else {
		
		//Check and see if there's a drive object stored globally
		global $driveService;
		
		//If there's not, we should set one up
		if( !is_object( $driveService ) )
			$driveService = bdn_is_user_auth();

		//Create a new Google Drive File
		//@TODO This should open up in the newsroom site
		$file = new Google_DriveFile();
		$file->setTitle( $post[ 'post_title' ] );
		$file->setMimeType( 'application/vnd.google-apps.document' );
		
		//Let's see what happens
		//We can fake attach a Google Doc by inserting a new DOM element with the ID of the doc we're trying to attach
		if( !empty( $_POST[ 'budget' ][ 'doc' ] ) ) {
			$createdFile = array( 'id' => $_POST[ 'budget' ][ 'doc' ] );
		} else {
			
			//Exponential backoff. If we get an error, try again after 2 seconds, then 4 seconds, then 8 seconds, etc.
			//We get a lot of 500 errors, so this catches many of them
			$tries = 0;
			for( $i = 0; $i < 10; $i++ ) {
				
				if( !empty( $createdFile[ 'id' ] ) )
					continue;
				
				$tries++;
				sleep( $i * 2 );
				
				try {
					$params = array();
					if( $i > 2 )
						$params[ 'token' ] = '';
					$createdFile = $driveService->files->insert( $file, $params );
				} catch (Exception $e) {
					if( strpos( $e->getMessage(), 'Error refreshing the OAuth2 token' ) !== false )
						delete_user_meta( get_current_user_id(), '_google_access_token' );
					//@TODO: Check if it's an error we should be retrying for
					error_log( 'DRIVE API ERROR: ' . $e->getMessage() . ' : ' . json_encode( $e ) );
					$createdFile = false;
				}
			
			}
		}
		
		//Just so we know, how many tries did this take?
		header( 'Tries: ' . $tries );
		//error_log( 'DRIVE TRIES: ' . $tries );
		
		//Save off the ID of the Doc
		update_post_meta( $post_id, '_gdocID', $createdFile[ 'id' ] );
		
		
		bdn_set_doc_permissions( $createdFile[ 'id' ], $post );
		
		//Redirect either to the doc or back to where we are now
		wp_redirect( $loc );
	
	}
	
	//Always kill the program when you're done
	exit;

}


/*
 * Save it when someone files an item
 * Not actually an ajax call. The URL is: site/?action=do_file_item
 * We catch it before anything else happens
*/
add_action( 'wp', 'bdn_do_file_item', 20 );
function bdn_do_file_item() {

	if( empty( $_GET[ 'action' ] ) || $_GET[ 'action' ] != 'do_file_item' )
		return;
	
	//Always use a nonce
	if( empty( $_POST[ 'file_item_nonce' ] ) || !wp_verify_nonce( $_POST[ 'file_item_nonce' ], 'file-nonce' ) )
		return;
		
	if( empty( $_POST[ 'file' ][ 'id' ] ) || (int) $_POST[ 'file' ][ 'id' ] == 0 )
		return;
		
	$post_id = (int) $_POST[ 'file' ][ 'id' ];
	
	//Set the status
	if( !empty( $_POST[ 'file' ][ 'status' ] ) && (int) $_POST[ 'file' ][ 'status' ] != 0 ) {
		//These need to either be slugs or integers (term IDs)
		$statuses = array( (int) $_POST[ 'file' ][ 'status' ] );
		//If it's published or final published, that's sticky published
		if( has_term( 'published', 'status', $post_id ) )
			$statuses[] = 'published';
		if( has_term( 'final-published', 'status', $post_id ) )
			$statuses[] = 'final-published';
		wp_set_object_terms( $post_id, $statuses, 'status' );
		
		if( !( $the_status_changes = get_post_meta( $post_id, '_status_changes', true ) ) )
			$the_status_changes = array();
	
		$the_status_changes[] = array(
			'time' => date_i18n( 'Y-m-d H:i:s' ),
			'user' => get_current_user_id(),
			'status' => get_term( (int) $_POST[ 'file' ][ 'status' ], 'status' )->slug
		);
		update_post_meta( $post_id, '_status_changes', $the_status_changes );
		
	}
	
	//Map all the folder IDs to ints
	if( !empty( $_POST[ 'tax_input' ][ 'folder' ] ) )
		wp_set_object_terms( $post_id, array_map('intval', $_POST[ 'tax_input' ][ 'folder' ] ), 'folder' );

	wp_redirect( remove_query_arg( 'action' ) );
	exit;

}

/*
 * Spike a story
*/
add_action( 'wp', 'bdn_do_spike_item', 20 );
function bdn_do_spike_item() {

	if( empty( $_GET[ 'action' ] ) || $_GET[ 'action' ] != 'do_spike_item' )
		return;
	
	global $post;

	//Always use a nonce
	if( empty( $_GET[ '_nonce' ] ) || !wp_verify_nonce( $_GET[ '_nonce' ], 'spike-nonce-' . $post->ID ) )
		return;	
	
	if( $post->post_type != 'doc' )	
		return;
	
	if( (int) get_post_meta( $post->ID, '_visuals_request', true ) > 0 ) {
		$visual = get_post( get_post_meta( $post->ID, '_visuals_request', true ) );
		wp_trash_post( $visual->ID );
	}
	
	wp_trash_post( $post->ID );

	wp_redirect( $_GET[ 'page' ] );
	exit;
	
}

/*
 * Attach a Google Doc to an existing budget line
*/
add_action( 'wp', 'bdn_do_attach_doc', 20 );
function bdn_do_attach_doc() {

	if( empty( $_GET[ 'action' ] ) || $_GET[ 'action' ] != 'do_attach_doc' )
		return;
	
	global $post;

	//Always use a nonce
	if( empty( $_GET[ '_nonce' ] ) || !wp_verify_nonce( $_GET[ '_nonce' ], 'attach-nonce-' . $post->ID . '-' . $_GET[ 'gdocID' ] ) )
		return;
	
	if( $post->post_type != 'doc' )	
		return;
	
	$existing_doc_id = get_post_meta( $post->ID, '_gdocID', true );
	if( !empty( $existing_doc_id ) )
		return;
	
	update_post_meta( $post->ID, '_gdocID', $_GET[ 'gdocID' ] );
	
	$the_post_array = get_post( $post->ID, ARRAY_A );
	
	bdn_set_doc_permissions( $_GET[ 'gdocID' ], $the_post_array );

	wp_redirect( get_permalink() );
	exit;
	
}

/*
 * Mark a story final published
*/
add_action( 'wp', 'bdn_do_mark_final_published', 20 );
function bdn_do_mark_final_published() {

	if( empty( $_GET[ 'action' ] ) || $_GET[ 'action' ] != 'do_mark_final_published' )
		return;
	
	global $post;

	//Always use a nonce
	if( empty( $_GET[ '_nonce' ] ) || !wp_verify_nonce( $_GET[ '_nonce' ], 'final_published-nonce-' . $post->ID ) )
		return;	
	
	if( $post->post_type != 'doc' )	
		return;
		
	bdn_mark_final_published( $post->ID );
	
	wp_redirect( $_GET[ 'page' ] );
	exit;
	
}

add_action( 'wp_ajax_bdn_mark_final_published', 'bdn_ajax_mark_final_published' );
function bdn_ajax_mark_final_published() {

	if( empty( $_REQUEST[ 'wpid' ] ) || (int) $_REQUEST[ 'wpid' ] == 0 )
		wp_send_json_error();
	
	$post_id = (int) $_REQUEST[ 'wpid' ];
	
	//Always use a nonce
	if( empty( $_REQUEST[ '_nonce' ] ) || !wp_verify_nonce( $_REQUEST[ '_nonce' ], 'final_published-nonce-' . $post_id ) )
		return;

	bdn_mark_final_published( $post_id );
	
	wp_send_json_success();

}

function bdn_mark_final_published( $post_id ) {

	$statuses = array( 'final-published' );
	if( has_term( 'published', 'status', $post_id ) )
		$statuses[] = 'published';

	wp_set_object_terms( $post_id, $statuses, 'status' );
	
	if( (int) get_post_meta( $post_id, '_visuals_request', true ) > 0 ) {
		wp_set_object_terms( get_post_meta( $post_id, '_visuals_request', true ), 'final-published', 'status' );
	}

}

/*
 * Return a JSON array with the things needed to build a context menu
 * Called when someone right clicks on an item
 * @TODO should we use a nonce? Probs
*/
add_action( 'wp_ajax_bdn_context_menu', 'bdn_context_menu');
function bdn_context_menu() {
	if( empty( $_REQUEST[ 'wpid' ] ) || (int) $_REQUEST[ 'wpid' ] == 0 )
		wp_send_json_error();
	
	$post_id = (int) $_REQUEST[ 'wpid' ];
	
	global $starttime;
	
	//Get the HTML for the budget line and file item screens for this item
	ob_start();
	bdn_budget_line( get_post( $post_id ), $_REQUEST[ 'page' ] );
	$budget_line = ob_get_contents();
	ob_end_clean();
	
	ob_start();
	bdn_file_item( get_post( $post_id ), $_REQUEST[ 'page' ] );
	$file_item = ob_get_contents();
	ob_end_clean();
	
	wp_send_json_success( array(
		//The page to redirect to
		'slug' => get_the_title( $post_id ),
		'page' => $_REQUEST[ 'page' ],
		'permalink' => get_permalink( $post_id ),
		'folders' => get_the_terms( $post_id, 'folder' ),
		'status' => get_the_terms( $post_id, 'status' ),
		'desk' => get_the_terms( $post_id, 'desk' ),
		'spike' => add_query_arg( array( 'action' => 'do_spike_item', '_nonce' => wp_create_nonce( 'spike-nonce-' . $post_id ), 'page' => urlencode( $_REQUEST[ 'page' ] ) ), get_permalink( $post_id ) ),
		'markfinal' => add_query_arg( array( 'action' => 'do_mark_final_published', '_nonce' => wp_create_nonce( 'final_published-nonce-' . $post_id ), 'page' => urlencode( $_REQUEST[ 'page' ] ) ), get_permalink( $post_id ) ),
		//Trim is really important here. jQuery will throw up if the first character isn't a <
		'editBudgetLine' => trim( $budget_line ),
		'fileItem' => trim( $file_item ),
		'wpID' => get_post_meta( $post_id, '_wpID', true ),
		'timeItTook' => ( (float) reset( explode( ' ', microtime() ) ) + (float) end( explode( ' ', microtime() ) ) ) - $starttime,
		'mysqlTime' => bdn_total_query_time(),
	) );
}


/*
 * Poll the Doc and check last modified, save off content
 * Called in single-doc.php every 30 seconds and before the
 * user navigates off the page
 * @TODO should we use a nonce? Probs
*/
add_action( 'wp_ajax_bdn_poll_doc', 'bdn_poll_doc' );
function bdn_poll_doc() {

	if( empty( $_REQUEST[ 'wpid' ] ) || (int) $_REQUEST[ 'wpid' ] == 0 )
		wp_send_json_error();
	
	$post_id = (int) $_REQUEST[ 'wpid' ];
	$post = get_post( $post_id );
	
	//See if we're already authenticated into Drive
	global $driveService, $starttime;
	
	list( $usec, $sec ) = explode( ' ', microtime() );
	$drivestart = ( (float) $usec + (float) $sec );
	$inittime = $drivestart - $starttime;
		
	//If the $driveService global isn't set up, auth into teh Goog
	if( !is_object( $driveService ) )
		$driveService = bdn_is_user_auth();
	
	//Get the details about the doc attached to this post
	try { 
		$file = $driveService->files->get( get_post_meta( $post_id, '_gdocID', true ) );
	} catch (Exception $e) {
		if( strpos( $e->getMessage(), 'Error refreshing the OAuth2 token' ) !== false )
			delete_user_meta( get_current_user_id(), '_google_access_token' );
		wp_send_json_error( array( 'reauth' => true ) );
	}
	
	//Get the text of the doc. If the HTTP response code is good, get the response body
	$request = new Google_HttpRequest( $file[ 'exportLinks' ][ 'text/plain' ], 'GET', null, null );
    $httpRequest = Google_Client::$io->authenticatedRequest( $request );
    if( $httpRequest->getResponseHttpCode() == 200 )
    	$content = $httpRequest->getResponseBody();
    
    list( $usec, $sec ) = explode( ' ', microtime() );
	$driveend = ( (float) $usec + (float) $sec );
	$drivetime = $driveend - $drivestart;
    
    //Set up globals to store the doc modified times that Goog returned
    global $this_post_modified_gmt, $this_post_modified;
    
    //Goog returns a GMT doc modified time
	$gmt_created_time = strtotime( $file[ 'modifiedDate' ] );
	$this_post_modified_gmt = date_i18n( 'Y-m-d H:i:s', $gmt_created_time, true );
	
	//Convert that to EST
	date_default_timezone_set('America/New_York');
	$this_post_modified = date( 'Y-m-d H:i:s', $gmt_created_time );
	
	//If nothing has changed, just send a success response (with the wordcount)
	if( $content == $post->post_content && $this_post_modified == $post->post_modified )
    	wp_send_json_success( array( 'words' => str_word_count( $post->post_content ) ) );
    
    //We're still here, which means something's been modified
    //We can't force post_modified using wp_update_post, so we're going to do an end-run around
    //WordPress and change it right before we perform the $wpdb->update call.
    //So, grab the globals we just set and override the data we're about to send in wp_update_post
    add_filter( 'wp_insert_post_data', function( $data ) { global $this_post_modified_gmt, $this_post_modified; $data[ 'post_modified' ] = $this_post_modified; $data[ 'post_modified_gmt' ] = $this_post_modified_gmt; return $data; } );
	
	//Update the post content
	wp_update_post( array( 'ID' => $post_id, 'post_content' => $content ) );
	//Save the name of the last person to modify the doc
	update_post_meta( $post_id, '_last_modified_name', $file[ 'lastModifyingUserName' ] );
	
	$time_spent = get_post_meta( $post_id, '_time_spent', true );
	if( empty( $time_spent ) ) $time_spent = array();
	$time_spent[ $file[ 'lastModifyingUserName' ] ] += 30;
	update_post_meta( $post_id, '_time_spent', $time_spent );
	
	//If someone's working on this, change the status to 'In Progress'
	if( str_word_count( $content ) > 50 && ( has_term( 'story-idea-submitted', 'status', $post_id ) || has_term( 'editor-approved', 'status', $post_id ) ) ) {
		wp_set_object_terms( $post_id, 'in-progress', 'status' );

		if( !( $the_status_changes = get_post_meta( $post_id, '_status_changes', true ) ) )
			$the_status_changes = array();
	
		$the_status_changes[] = array(
			'time' => date_i18n( 'Y-m-d H:i:s' ),
			'user' => get_current_user_id(),
			'status' => 'in-progress'
		);
		update_post_meta( $post_id, '_status_changes', $the_status_changes );
		
	}

	list( $usec, $sec ) = explode( ' ', microtime() );
	$endtime = ( (float) $usec + (float) $sec );
	
	//Success!
	wp_send_json_success(
		array(
			'words' => str_word_count( $content ),
			'timeItTook' => array(
				'init' => $inittime,
				'drive' => $drivetime,
				'processing' => $endtime - $driveend,
				'total' => $endtime - $starttime,
				'mysql_time' => bdn_total_query_time(),
			)
		)
	);
	
}


/*
 * Takes a list of WordPress IDs and the zones they're in and saves them
 * as an option.
 * Called by page-lineups.php whenever anyone drags something
 * @TODO should we use a nonce? Probs
*/
add_action( 'wp_ajax_bdn_save_lineups', 'bdn_save_lineups' );
function bdn_save_lineups() {

	if( empty( $_POST[ 'lineups' ] ) || !current_user_can( 'edit_others_posts' ) )
		wp_send_json_error();
	
	$lineup = ( empty( $_POST[ 'lineup' ] ) ? 'a1' : $_POST[ 'lineup' ] );

	//Sanitize the post IDs
	$lineups = array();
	foreach( $_POST[ 'lineups' ] as $s => $ps ) {
		foreach( $ps as $p ) {
			if( (int) $p > 0 )
				$lineups[ $s ][] = (int) $p;
		}
	}
	
	//Update the site option
	update_option( $lineup . '-lineups', $lineups );
	
	//Success!
	wp_send_json_success();

}



/*
* Let's check 
*/
if( !empty( $_GET[ 'check_for_placed' ] ) )
	add_action( 'wp', 'bdn_check_for_placed', 0 );
function bdn_check_for_placed() {

	global $wpdb;

	if( !class_exists( 'IXR_Value' ) )
		require_once( ABSPATH . '/' . WPINC . '/class-IXR.php' );

	$client = new IXR_Client( PUBLISH_ENDPOINT );
		
	$params = array( 1, PUBLISH_USER, PUBLISH_PASS, get_option( 'last_placed_meta_id' ) );
	
	if( !$client->query( 'bdn.getPlaced', $params ) ) {
		die( 'Something went wrong - '.$client->getErrorCode().' : '.$client->getErrorMessage() );
	}
	
	$keys = $client->getResponse();

	if( empty( $keys ) )
		exit;
	
	foreach( $keys as $k ) {
		$p = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM " . $wpdb->postmeta . " WHERE meta_key = '_wpID' AND meta_value = %d", $k[ 'post_id' ] ) );

		if( $p ) { 
			update_post_meta( $p, '_placed', $k[ 'meta_value' ] );
			wp_set_object_terms( $p, array( 'published', 'final-published' ), 'status' );
		}
	}
	
	$last = reset( $keys );
	update_option( 'last_placed_meta_id', $last[ 'meta_id' ] );
	
	exit;
	
}
