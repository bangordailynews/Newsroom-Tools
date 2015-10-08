<?php

class BDN_InDesign {

	//API key for making calls from Indesign
	//Set this.
	var $api_key = 'apikey';

	/*
	 * Initialize our class
	 *
	 */
	function BDN_InDesign() {
		
		//Hook into admin-ajax to return a JSON object for the WP Browser
		add_action( 'wp_ajax_wp-browser-search', array( &$this, 'wp_browser_search' ) );
		add_action( 'wp_ajax_nopriv_wp-browser-search', array( &$this, 'wp_browser_search' ) );

		//Save a notification
		add_action( 'wp_ajax_wp-browser-notify', array( &$this, 'wp_browser_notify' ) );
		add_action( 'wp_ajax_nopriv_wp-browser-notify', array( &$this, 'wp_browser_notify' ) );


	}
	
	/*
	 * Return a JSON response for the WP Browser
	 * 
	 *
	 */
	function wp_browser_search() {
	
		if( $_GET[ 'apiKey' ] != $this->api_key )
			wp_send_json_error( array( 'error' => 'Invalid API Key' ) );

		global $budget_sections;

		if( !empty( $_GET[ 'filter_list' ] ) ) {
		
			wp_send_json_success( array_keys( $budget_sections ) );
			
		}

		//Build our args for the search
		$args = array(
			'numberposts' => 20,
			'post_type' => array( 'doc' ),
			//Show modified posts up top
			'orderby' => 'modified'
		);

		//Yay, a query!
		if( !empty( $_GET[ 's' ] ) ) {
			//$args[ 's' ] = '"' . $_GET[ 's' ] . '"';
			
			//We don't need to specially query by slug because the slug is the post title!
		
			//@TODO: Do we need to validate?
			$args[ 's' ] = $_GET[ 's' ];
		
		}
		
		if( !empty( $_GET[ 'filter' ] ) && !empty( $budget_sections[ $_GET[ 'filter' ] ] ) ) {
		
			$lineups = get_option( 'a1-lineups', array() );
			
			if( isset( $lineups[ $_GET[ 'filter' ] ] ) ) {
			
				$args[ 'post__in' ] = $lineups[ $_GET[ 'filter' ] ];
			
			}
		
		}
		
		if( empty( $posts ) )
			$posts = get_posts( $args );
	
		$response = array();
	
		foreach( $posts as $post ) {
	
			$status = false;
			
			if( ( $last_placed_meta = get_post_meta( $post->ID, '_placed', true ) ) != false && !empty( $last_placed_meta ) ) {
				$status = $last_placed_meta;
			} elseif( ( $last_placed_page = get_post_meta( $post->ID, '_placed_page', true ) ) !== false && !empty( $last_placed_page ) ) {
				$status = 'Placed ' . $last_placed_page . ' ' . date( 'M j Y g:i a', strtotime( get_post_meta( $post->ID, '_placed_datetime', true ) ) );
			} else {
				$statuses = get_budget_item_multi( $post, 'statuses' );
	
				if( !empty( $statuses ) )
					$status = implode( ', ', $statuses );
			}
	
			$wp_ids = get_post_meta( $post->ID, '_wpID', false );
			sort( $wp_ids );
			//@TODO: Status
			//@TODO: Run this from newsroom instead
			//@TODO: Has the story been placed already
			//@TODO: MD5 of contents?
			$response[] = array(
				'post_id' => end( $wp_ids ),
				'author' => get_user_by( 'id', $post->post_author )->display_name,
				'slug' => $post->post_title,
				'depth' => round( str_word_count( strip_tags( $post->post_content ) ) / 30 ),
				'status' => $status,
			);
		}
	
		wp_send_json_success( $response );

	}
	
	
	/*
	 * Search for posts by the slug
	 *
	 */
	function get_stories_by_slug( $slug = false ) {

		if( empty( $slug ) )
			return false;

		//Poor man's validation: If there's any nonalphanumeric characters, return false
		if( $slug != preg_replace( "/[^a-zA-Z0-9]/", "", $slug ) )
			return false;

		return get_page_by_title( $slug, OBJECT, 'doc' );

	}
	
	function wp_browser_notify() {
	
		if( $_GET[ 'apiKey' ] != $this->api_key )
			wp_send_json_error( array( 'error' => 'Invalid API Key' ) );
	
		if( empty( $_GET[ 'postId' ] ) )
			return;
		
		$posts = get_posts( array( 'post_type' => array( 'doc' ), 'post_status' => 'any', 'meta_key' => '_wpID', 'meta_value' => $_GET[ 'postId' ] ) );
		
		if( empty( $posts ) )
			wp_send_json_error( array( 'error' => 'No posts matched that ID' ) );
		
		foreach( $posts as $post ) {
			if( !empty( $_GET[ 'date' ] ) )
				add_post_meta( $post->ID, '_placed_datetime', date( 'Y-m-d H:i:s', strtotime( $_GET[ 'date' ] ) ) );
			if( !empty( $_GET[ 'page' ] ) )
				add_post_meta( $post->ID, '_placed_page', preg_replace('/[^0-9a-zA-Z-_. ]/', '', $_GET[ 'page' ] ) );
			if( !empty( $_GET[ 'docName' ] ) )
				add_post_meta( $post->ID, '_placed_file', preg_replace('/[^0-9a-zA-Z-_. ]/', '', $_GET[ 'docName' ] ) );
			wp_set_object_terms( $post->ID, array( 'published', 'final-published' ), 'status' );
		}
		
		wp_send_json_success();
	
	}


}

new BDN_InDesign;