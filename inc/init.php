<?php
add_action( 'wp_enqueue_scripts', 'budget_enqueue' );
function budget_enqueue() {
	//Deregister jquery, which has jQuery upgrade attached, and re-register it with jQuery core
	wp_deregister_script( 'jquery' );
	wp_register_script( 'jquery', false, array( 'jquery-core' ) );
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_script( 'jquery-ui-timepicker', get_bloginfo('stylesheet_directory') . '/js/jquery-ui-timepicker.js', 'jquery-ui-datepicker', false, true );
	wp_enqueue_style( 'jquery-style', get_bloginfo('stylesheet_directory') . '/css/datepicker.css' );
}

//The magic that controls the queries
add_action( 'pre_get_posts', 'bdn_browse_docs' );
function bdn_browse_docs( ) {

	global $wp_query;

	if( is_singular() || is_404() || is_admin() )
		return false;
	
	if( empty( $wp_query->query_vars['post_type'] ) ) {
		$wp_query->query_vars['post_type'] = array( 'doc' );
		$wp_query->query_vars['orderby'] = 'modified';
	}
	
	if( is_home() && !isset( $_GET[ 'all' ] ) && ( $default_view = get_user_meta( get_current_user_id(), 'default_view', true ) ) ) {
		if( $default_view == 'default_desk' ) {
			$default_desk = (int) get_user_meta( get_current_user_id(), 'default_desk', true );
			$term = get_term( $default_desk, 'desk' );
			if( !empty( $term ) && !is_wp_error( $term ) )
				$wp_query->query_vars['desk'] = $term->slug;
		} elseif( $default_view == 'user_docs' ) {
			$user = wp_get_current_user();
			$wp_query->query_vars['author_name'] = $user->user_login;
		}
	}
	
	if( !empty( $_GET[ 'hide-final-published' ] ) ) {
		$wp_query->query_vars['tax_query'][] = array(
			'taxonomy' => 'status',
			'field' => 'slug',
			'terms' => array( 'final-published' ),
			'operator' => 'NOT IN'
		);
	}

}


//If presented with a Google Doc ID, redirect to the budget item
if( !empty( $_GET[ 'docID' ] ) )
	add_action( 'wp', 'budget_redirect_from_doc_id' );

function budget_redirect_from_doc_id() {

	if( empty( $_GET[ 'docID' ] ) )
		return;
		
	global $wpdb;
	
	$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM " . $wpdb->postmeta . " WHERE meta_key = '_gdocID' AND meta_value = '%s'", $_GET[ 'docID' ] ) );

	if( empty( $post_id ) )
		return;
	
	wp_redirect( get_permalink( $post_id ) );
	exit;
	
}

//Add our custom post types
add_action( 'init', 'budget_custom_post_type' );
function budget_custom_post_type() {
	$args = array(
		'labels' => array(
			'name' => 'Docs',
			'singular_name' => 'Doc',
		),
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true, 
		'show_in_menu' => true, 
		'query_var' => true,
		'rewrite' => array( 'slug' => 'doc' ),
		'capability_type' => 'post',
		'has_archive' => true, 
		'hierarchical' => false,
		'menu_position' => null,
		'supports' => array( 'title', 'author', 'custom-fields', 'excerpt', 'editor' )
	);
    register_post_type( 'doc', $args );
    
    $args = array(
		'labels' => array(
			'name' => 'Visuals',
			'singular_name' => 'Visual',
		),
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true, 
		'show_in_menu' => true, 
		'query_var' => true,
		'rewrite' => array( 'slug' => 'visual' ),
		'capability_type' => 'post',
		'has_archive' => true, 
		'hierarchical' => false,
		'menu_position' => null,
		'supports' => array( 'title', 'author', 'custom-fields', 'excerpt', 'editor' )
	);
    register_post_type( 'visual', $args );
    
}

//Add our custom taxonomies
add_action( 'init', 'budget_custom_tax' );
function budget_custom_tax() {
	register_taxonomy(
		'desk',
		'doc',
		array(
		  'label' => __('Desks'),
		  'labels' => array(
				'name' => _x( 'Desks', 'taxonomy general name' ),
				'singular_name' => _x( 'Desk', 'taxonomy singular name' ),
			  ),
			  'hierarchical' => true,
		)
	);
	register_taxonomy(
		'folder',
		'doc',
		array(
			'label' => __( 'Folders' ),
			'labels' => array(
				'name' => _x( 'Folders', 'taxonomy general name' ),
				'singular_name' => _x( 'Folder', 'taxonomy singular name' ),
			),
			'hierarchical' => true,
		)
	);
	register_taxonomy(
		'status',
		array( 'doc', 'visual' ),
		array(
			'label' => __( 'Statuses' ),
			'labels' => array(
				'name' => _x( 'Statuses', 'taxonomy general name' ),
				'singular_name' => _x( 'Status', 'taxonomy singular name' ),
			),
			'hierarchical' => true,
		)
	);
	register_taxonomy(
		'importance',
		'doc',
		array(
			'label' => __( 'Importances' ),
			'labels' => array(
				'name' => _x( 'Importances', 'taxonomy general name' ),
				'singular_name' => _x( 'Importance', 'taxonomy singular name' ),
			),
			'hierarchical' => true,
		)
	);
	register_taxonomy(
		'day',
		array( 'doc', 'visual' ),
		array(
			'label' => __( 'Days' ),
			'labels' => array(
				'name' => _x( 'Days', 'taxonomy general name' ),
				'singular_name' => _x( 'Day', 'taxonomy singular name' ),
			),
			'hierarchical' => true,
		)
	);

}