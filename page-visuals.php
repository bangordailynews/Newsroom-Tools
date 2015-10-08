<?php get_header(); ?>
<?php
function visuals_sort( $a, $b ) {
	$event_time_a = strtotime( $a->post_title );
	$event_time_b = strtotime( $b->post_title );
	
	//If event time isn't set, make it 16 hours before the file time of the story
	if( empty( $event_time_a ) )
		$event_time_a = strtotime( get_post_meta( $a->post_parent, '_the_file_time', true ) ) - 57600;
	
	if( empty( $event_time_b ) )
		$event_time_b = strtotime( get_post_meta( $b->post_parent, '_the_file_time', true ) ) - 57600;
	
	if( $event_time_a == $event_time_b )
		return 0;
	
	return ( $event_time_a > $event_time_b ) ? 1 : -1;
	
} ?>
<?php $visuals = get_posts( array(
		'post_type' => 'visual',
		'post_status' => 'any',
		'tax_query' => array(
			array(
				'taxonomy' => 'status',
				'field' => 'slug',
				'terms' => 'final-published',
				'operator' => 'NOT IN'
			)
		),
		'numberposts' => -1
		) );
$requests = array();

foreach( $visuals as $v ) {
	if(
		get_post_meta( $v->post_parent, '_placed', true ) ||
		!( $parent = get_post( $v->post_parent ) ) ||
		$parent->post_status == 'trash' ||
		has_term( 'final-published', 'status', $v->post_parent )
	) {
		wp_set_object_terms( $v->ID, array( 'final-published' ), 'status' );
		continue;
	}
	$requests[ $v->post_author ][] = $v;
}

ksort( $requests );


foreach( $requests as $a => $v ) {
	usort( $v, 'visuals_sort' );
	$requests[ $a ] = $v;
}
	
?>
<style type="text/css">
	.visuals td {
		font-size: 12px !important;
		padding: 4px;
		vertical-align: top;
	}
</style>
<wrapper>
	<table class="visuals">
		<tr>
			<td style="width:10%; min-width: 100px;">Event date</td>
			<td style="width:35%; min-width: 100px;">Description</td>
			<td style="width:35%; min-width: 100px;">Story</td>
			<td style="width:10%; min-width: 100px;">Due date</td>
		</tr>
		<?php foreach( $requests as $a => $posts ) { ?>
			<tr><td colspan=100><h4><?php echo ( $a > 0 ) ? get_user_by( 'id', $a )->display_name : 'Unassigned'; ?></h4></td></tr>
			<?php foreach( $posts as $post ) { ?>
				<tr>
					<td><?php echo ( empty( $post->post_title ) ) ? 'No assignment time' : date( 'D, M j g:i a', strtotime( $post->post_title ) ); ?><br>
					<?php edit_post_link( __('Edit This'), false, false, $post->ID ); ?></td>
					<td><?php echo $post->post_excerpt; ?></td>
					<td><?php echo '<strong><a href="' . get_permalink( $post->post_parent ) . '" target=_blank>' . get_the_title( $post->post_parent ) . '</a> by ' . get_user_by( 'id', get_post( $post->post_parent )->post_author )->display_name . ':</strong> ' . get_post( $post->post_parent )->post_excerpt; ?></td>
					<td><?php echo date( 'D, M j g:i a', strtotime( get_post_meta( $post->post_parent, '_the_file_time', true ) ) ); ?>
				</tr>
			<?php } ?>
		<?php } ?>
	</table>
</wrapper>

<?php bdn_budget_context_menu(); ?>
<?php get_footer(); ?>