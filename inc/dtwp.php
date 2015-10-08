<?php

if( !class_exists( 'IXR_Value' ) )
	require_once( ABSPATH . '/' . WPINC . '/class-IXR.php' );

add_action( 'wp', 'bdn_send_to_wp', 2 );
function bdn_send_to_wp() {

	if( !is_singular( 'doc' ) || !current_user_can( 'edit_others_posts' ) || empty( $_GET[ 'docToWP' ] ) )
		return;

	global $post, $driveService;

	$gdocID = get_post_meta( $post->ID, '_gdocID', true );
	
	if( empty( $gdocID ) )
		return;

	if( !is_object( $driveService ) )
		$driveService = bdn_is_user_auth();
		
	//Get the file info
	$file = $driveService->files->get( $gdocID );
	
	//Get the html
	$request = new Google_HttpRequest( $file[ 'exportLinks' ][ 'text/html' ], 'GET', null, null );
    $httpRequest = Google_Client::$io->authenticatedRequest( $request );
    if( $httpRequest->getResponseHttpCode() == 200 )
    	$content = get_clean_doc( $httpRequest->getResponseBody() );	
	
	$sendpost = array(
		'key' => '_gdocID',
		'value' => $gdocID,
		'post_title' => $file[ 'title' ],
		'post_content' => $content,
		'author' => get_userdata( $post->post_author )->user_login,
		'categories' => wp_get_object_terms( $post->ID, 'folder', array( 'fields' => 'names' ) ),
		'custom_fields' => array()
	);
		
	$sendpost = bdn_separate_headline( $sendpost );
	
	$client = new IXR_Client( PUBLISH_ENDPOINT );
		
	$params = array( 1, PUBLISH_USER, PUBLISH_PASS, array( $sendpost ) );
	
	if( !$client->query( 'bdn.publishOnKey', $params ) ) {
		die( 'Something went wrong - '.$client->getErrorCode().' : '.$client->getErrorMessage() );
	}
	
	$wp_posts = $client->getResponse();
	$first_post = reset( $wp_posts );

	if( empty( $wp_posts ) || empty( $first_post[ 'post_id' ] ) )
		die( 'Something went wrong' );

	$previous_ids = get_post_meta( $post->ID, '_wpID', false );
	
	foreach( $wp_posts as $wp_post ) {
		if( !in_array( $wp_post[ 'post_id' ], $previous_ids ) )
			add_post_meta( $post->ID, '_wpID', $wp_post[ 'post_id' ], false );
	}

	$status = array( 'published' );
	if( !empty( $_GET[ 'readyForPrint' ] ) )
		$status[] = 'ready-for-print';
	if( has_term( 'final-published', 'status', $post->ID ) )
		$statuses[] = 'final-published';
	wp_set_object_terms( $post->ID, $status, 'status' );
	
	if( !( $the_status_changes = get_post_meta( $post->ID, '_status_changes', true ) ) )
		$the_status_changes = array();

	$the_status_changes[] = array(
		'time' => date_i18n( 'Y-m-d H:i:s' ),
		'user' => get_current_user_id(),
		'status' => 'published'
	);
	update_post_meta( $post->ID, '_status_changes', $the_status_changes );
	
	if( get_post_meta( $post->ID, '_the_file_time', true ) > date_i18n( 'Y-m-d H:i', strtotime( '+5 hours' ) ) )
		update_post_meta( $post->ID, '_the_file_time', date_i18n( 'Y-m-d H:i' ) );
	
	?>
	<script type="text/javascript">
		var r = confirm( 'Doc published! Continue to the WordPress admin?' );
		if ( r == true ) {
			var x = LIVE_SITE_URL . 'wp-admin/post.php?post=<?php echo $first_post[ 'post_id' ]; ?>&action=edit'
		} else {
			var x = '<?php echo home_url( '/' ); ?>';
		}
		window.location = x;
	</script>
	<?php
	
	exit;
}	
	
function get_clean_doc( $content ) {
	
	require( 'purifier/HTMLPurifier.standalone.php' );
	$config = HTMLPurifier_Config::createDefault();
	$purifier = new HTMLPurifier();
	
	//New domDocument and xPath to get the content
	$dom= new DOMDocument();
	$dom->loadHTML( $content );
	$xpath = new DOMXPath( $dom );

	//PHP doesn't honor lazy matches very well, apparently, so add newlines
	$content = str_replace( '}', "}\r\n", $content );
	
	preg_match_all( '#.c(?P<digit>\d+){(.*?)font-weight:bold(.*?)}#', $content, $boldmatches );
	preg_match_all('#.c(?P<digit>\d+){(.*?)font-style:italic(.*?)}#', $content, $italicmatches);
	
	//Strip away the headers
	$body = $xpath->query('/html/body');
	
	//This is our dirty HTML
	$dirty_html = $dom->saveXml( $body->item(0) );
	
	if( !empty( $boldmatches[ 'digit' ] ) ) {
	
		foreach( $boldmatches[ 'digit' ] as $boldclass ) {
			$dirty_html = preg_replace( '#<span class="(.*?)c' . $boldclass . '(.*?)">(.*?)</span>#s', '<span class="$1c' . $boldclass . '$2"><strong>$3</strong></span>', $dirty_html );
		}
	
	}
	
	if( !empty( $italicmatches[ 'digit' ] ) ) {
	
		foreach( $italicmatches[ 'digit' ] as $italicclass ) {
			$dirty_html = preg_replace( '#<span class="(.*?)c' . $italicclass . '(.*?)">(.*?)</span>#s', '<span class="$1c' . $italicclass . '$2"><em>$3</em>', $dirty_html );
		}
	
	}
	
	$dirty_html = str_replace( "&nbsp;", " ", $dirty_html );
	
	$dirty_html = preg_replace('/\s\s+/', ' ', $dirty_html);
	
	//Run that through the purifier
	$clean_html = $purifier->purify( $dirty_html );
	
	$clean_html = str_replace( "&nbsp;", " ", $clean_html );
	$clean_html = preg_replace('/\s\s+/', ' ', $clean_html);
	
	//Return that clean shit
	return $clean_html;

}


function bdn_clean_content($post_content) {
		$post_content = str_replace( array( "\r\n", "\n\n", "\r\r", "\n\r" ), "\n", $post_content );
		$post_content = preg_replace('/<div(.*?)>/', '<div>', $post_content);
		$post_content = preg_replace('/<p(.*?)>/', '<p>', $post_content);

		$post_content = str_replace( '<strong></strong>', '', $post_content );
		$post_content = str_replace( '<strong><a href="#cmnt_ref', '<a href="#cmnt_ref', $post_content );
		
		//Match all the comments into an array. We're doing this before anything else because the </div> is importqnt
		preg_match_all( '/<div><p><a href="#cmnt_ref[\d+]">\[[\w]\]<\/a>(.*?)<\/div>/', $post_content, $comments, PREG_PATTERN_ORDER);
		$comments = implode( "\r\n\r\n", $comments[1] );
	
		//Take out the comments
		$post_content = preg_replace( '/<a href="#cmnt_ref(\d+)">(.*?)<\/div>/', '', $post_content );
		$post_content = preg_replace( '/<a href="#cmnt_ref(\d+)" name="cmnt(\d+)">(.*?)<\/div>/', '', $post_content );
		//Take out the comment refers
		$post_content = preg_replace( '/<a href="#cmnt(.*?)<\/a>/', '', $post_content );
		
		$post_content = str_replace( '<div>','<p>',$post_content );
		$post_content = str_replace( '</div>', '</p>',$post_content );
		
		$post_content = strip_tags( $post_content, '<strong><b><i><em><a><u><br><p><ol><ul><li><h1><h2><h3><h4><h5>' );
		
		//Match empty lines
		$post_content = preg_replace( '/<a (.*?)>&nbsp<\/a>/', ' ', $post_content );

		
		//Trying to avoid extra lines
		$post_content = str_replace( array( "&nbsp;", " " ), " ", $post_content );
		$post_content = str_replace( '<a ', ' <a ', $post_content );
		$post_content = preg_replace('!\s+!', ' ', $post_content);
		
		$post_content = str_replace( '--','&mdash;',$post_content );
		$post_content = str_replace( '<br><br>','<p>',$post_content );
		$post_content = str_replace( '<br>&nbsp;&nbsp;&nbsp;', '\n\n', $post_content );
		$post_content = str_replace( '<br>
&nbsp;&nbsp;&nbsp;','\n\n',$post_content);
		$post_content = str_replace( '<br><br>', '\n\n', $post_content );
		$post_content = trim( $post_content );
		$pees = explode( '<p>', $post_content );
		$trimmed = array();
		foreach( $pees as $p )
			$trimmed[] = trim( $p );
		$post_content = implode( '<p>', $trimmed );
		$post_content = preg_replace( "/<p><\/p>/", '', $post_content );
		
		return array( 'content' => $post_content, 'comments' => $comments );
}
	

function bdn_separate_headline( $post_array ) {	

	$original_content = $post_array['post_content'];

	$all_content = bdn_clean_content( $original_content );
	$searchforpipe = strpos( $all_content[ 'content' ], '|');
	if( $searchforpipe === false ) {
		$post_content = $all_content[ 'content' ];
		$post_title = false;
	} else {
		list( $post_title, $post_content, $comments ) = explode("|", $all_content[ 'content' ]);
		$post_title = strip_tags( $post_title );
	}
	$slug = $post_array['post_title'];
	if( substr( trim( $post_content ), 0, 4 ) == '</p>' )
		$post_content = substr( trim( $post_content ), 4 );
	$post_array['post_content'] = $post_content;
	$post_array['post_title'] = trim( $post_title );
	$post_array['custom_fields'] = array_merge( $post_array['custom_fields'], array( 'slug' => $slug, '_gdocs_comments' => $all_content[ 'comments' ] . "\r\n<span><p>" .  $comments . "</p></span>" ) );
	$lines = explode( "</span>", $post_array['custom_fields']['_gdocs_comments'] );
	foreach( $lines as $line ) {
		if( substr( trim( strip_tags( strtolower( $line ) ) ), 0, 7 ) == "byline:" )
			$post_array['custom_fields']['byline'] = trim( str_ireplace( 'byline:', '', strip_tags( $line ) ) );
		if( substr( trim( strip_tags( strtolower( $line ) ) ), 0, 4 ) == "dnp:" )
			$post_array['custom_fields']['_dnp'] = trim( str_ireplace( 'dnp:', '', strip_tags( $line ) ) );
		if( substr( trim( strip_tags( strtolower( $line ) ) ), 0, 7 ) == "format:" )
			$post_array['custom_fields']['_format'] = trim( str_ireplace( 'format:', '', strip_tags( $line ) ) );

	}
	if( !empty( $post_array['custom_fields']['byline'] ) && substr( $post_array['custom_fields']['byline'], 0, 3 ) == 'By ' )
		$post_array['custom_fields']['byline'] = substr( $post_array['custom_fields']['byline'], 3 );
	return $post_array;

}