<?php

function budget_sort( $a, $b ) {
	global $budget_orderby, $budget_order;

	if( empty( $budget_orderby ) ) {
		$budget_orderby = 'file_time';
		$budget_order = 'ASC';
	}
	
	if( $a->$budget_orderby == $b->$budget_orderby ) {
		return 0;
	}
	if( $budget_order == 'DESC' )
		return ( $a->$budget_orderby > $b->$budget_orderby ) ? -1 : 1;
		
	return ( $a->$budget_orderby < $b->$budget_orderby ) ? -1 : 1;
}


function budget_set_up_item( $p ) {

	$terms = get_the_terms( $p->ID, 'importance' );
	
	if(is_array($terms)){
		$importances = reset( $terms );
	}else{
		$importances = (object)array( 'name' => null );
	}
	
	$p->importance = $importances->name;
	$p->file_time = get_post_meta( $p->ID, '_the_file_time', true );
	$p->budget_length = get_post_meta( $p->ID, '_budget_length', true );
	$p->desk = get_the_terms( $p->ID, 'desk' );
	$p->statuses = get_the_terms( $p->ID, 'status' );
	$p->folders = get_the_terms( $p->ID, 'folder' );
	$p->publication = get_the_terms( $p->ID, 'publication' );

	return $p;

}

function get_budget_items( $params = array() ) {

	global $budget_orderby, $budget_order;
	
	$defaults = array(
		'orderby' => 'file_time',
		'order' => 'ASC',
	);

	$budget_orderby = 'importance';
	$budget_order = 'DESC';

	$dates = array( date_i18n( 'Y-m-d' ) );
	
	if( !empty( $_GET[ 'file_dates' ] ) ) {
		$ndates = explode( ',', $_GET[ 'file_dates' ] );
		foreach( $ndates as $i => $d ) {
			if( date( 'Y', strtotime( $d ) ) < 2000 ) {
				unset( $ndates[ $i ] );
			} else {
				$ndates[ $i ] = date( 'Y-m-d', strtotime( $d ) );
			}
		}
	}
	
	if( !empty( $ndates ) )
		$dates = $ndates;
	
	$args = array(
		'post_type' => 'doc',
		'tax_query' => array(
			array( 'taxonomy' => 'day', 'field' => 'slug', 'terms' => $dates ),
			array( 'taxonomy' => 'publication', 'field' => 'slug', 'terms' => 'bangordailynews-com' ),
		),
		'numberposts' => '-1',
	);
	
	$posts = get_posts( $args );

	foreach( $posts as $i => $p ) {
		$posts[ $i ] = budget_set_up_item( $p );
	}
	
	usort( $posts, 'budget_sort' );

	return $posts;
	
}
