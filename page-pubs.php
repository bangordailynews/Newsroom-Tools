<?php get_header(); ?>
<style type="text/css" media="print">
	.upcoming, .already_published, input[radio]{ display: none; }
</style>
<wrapper>
	<table class="budget" style="width:100%">
		<thead>
			<tr>
				<td>Slug</td>
				<td>File</td>
				<td>Length</td>
				<td>Importance</td>
				<td>Status</td>
				<td>Visuals</td>
			</tr>
		</thead>
		<?php foreach( ( array( 'the-weekly', 'special-section-1', 'special-section-2', 'special-section-3', 'special-section-outdoors' ) ) as $desk ) { ?>
			<?php $desk = get_term_by( 'slug', $desk, 'desk' ); ?>
			<thead>
				<tr>
					<td colspan="100%">
						<h3>
							<?php echo $desk->name; ?>
						</h3>
					</td>
				</tr>
			</thead>
			
			<?php $stories = get_posts( array(
				'numberposts' => -1,
				'desk' => $desk->slug,
				'post_type' => 'doc',
				'tax_query' => array(
					array(
						'taxonomy' => 'status',
						'field' => 'slug',
						'terms' => 'final-published',
						'operator' => 'NOT IN'
					)
				),
			 ) ); ?>
			<?php foreach( $stories as $i => $story ) {
				$stories[ $i ] = budget_set_up_item( $story );
			}
			usort( $stories, 'budget_sort' );
			?>
				
			<?php if( empty( $stories ) || count( $stories ) == 0 ) { ?>
				<tr>
					<td colspan="100%">
						<small>Nothing budgeted</small>
					</td>
				</tr>
			<?php } else { ?>
				<?php foreach( $stories as $i => $post ) { ?>
					<?php setup_postdata( $post ); ?>
					<?php $status = false; ?>
					<?php if( strtotime( $post->file_time ) < ( time() - 60 * 60 * 24 ) ) {
						$status = 'OVERDUE';
					} elseif( strtotime( $post->file_time ) > ( time() + 60 * 60 * 36 ) ) {
						$status = 'Upcoming';
					} ?>
					<tr class="<?php echo ( $status == 'OVERDUE' && in_array( 'Published', get_budget_item_multi( $post, 'statuses' ) ) ) ? 'already_published' : ''; ?> <?php echo ( $status != 'Upcoming' ) ? 'noborder' : 'upcoming'; ?><?php echo ( $i % 2 ) ? ' alt' : ''; ?>" wp-id="<?php the_ID(); ?>">
						<td colspan="100%">
							<input type="radio">
						
							<?php echo ( $status ) ? $status . ':' : ''; ?>
							<?php the_title(); ?> -
						<?php if( $status == 'Upcoming' ) { ?>
								<?php echo get_the_excerpt(); ?>
						<?php } else { ?>
							<?php budget_time( $post, 'm/d g:i a' ); ?> -
							<?php budget_length(); ?> -
							<?php budget_importance(); ?> -
							<?php budget_statuses(); ?> -

								<?php
									$visuals = get_post_meta( $post->ID, '_visuals_request', true );
									if( (int) $visuals > 0 ) {
										$visuals = get_post( $visuals );
										?>
										<a href="<?php echo get_permalink( $visuals->ID ); ?>" target=_blank><?php echo $visuals->post_title; ?></a>
										<?php
									} else {
										echo $visuals;
									} ?>
								
							-
						<?php } ?>
					<?php if( $status != 'Upcoming' ) { ?>
						<?php echo get_the_excerpt(); ?>
					<?php } ?>
					</tr>
				<?php } ?>
			<?php } ?>
			
		<?php } ?>
	</table>
</wrapper>

<?php bdn_budget_context_menu(); ?>
<?php get_footer(); ?>