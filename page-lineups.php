<?php get_header(); ?>
<?php

$stories = ( !empty( $_GET[ 'clear' ] ) && current_user_can( 'edit_others_posts' ) ) ? array() : get_option( 'a1-lineups', array() );
$used = array();
?>
<wrapper>
	
	
	<script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>

	<style>
	.sortable {
		min-height: 25px;
		border-bottom: 1px solid #ccc;
		padding-bottom: 10px;
		margin-bottom: 5px;
	}
	ol li {
		margin-left: 20px;
	}
	<?php if( current_user_can( 'edit_others_posts' ) ) { ?>
		.print_lineup, .print_desks {
			width: 48%;
		}
	<?php } ?>
		.status_published {
			color: black !important;
		}
		.print_status_final-published {
			color: #aaa !important;
		}
		.status_ready-for-print {
			color: green;
			font-weight: bold;
			font-size: 1em;
		}
	</style>
	
	<style type="text/css" media="print">
		#bdn-menu, header, .print_desks{ display: none !important; }
		.print_lineup {
			width: 100%;
		}
		.description{ display: inline !important; }
	</style>
	
	<?php if( current_user_can( 'edit_others_posts' ) ) { ?>

		<script type="text/javascript">
			jQuery(function($) {
				$( ".sortable" ).sortable({
					connectWith: ".sortable",
					receive: function(event, ui) {
						var sections = <?php echo json_encode( $budget_sections ); ?>;
						var order = {};
						$.each( sections, function( slug, name ) {
							if ( !order[slug] ) order[slug] = [];
							$.each( $( '#sortable-' + slug + ' li' ), function( i, t ) {
								order[slug][i] = $(t).attr( 'wp-id' );
							});
						});
						jQuery.post( '<?php echo admin_url( 'admin-ajax.php' ); ?>', { action: 'bdn_save_lineups', lineups: order } );
					},
				}).disableSelection();
			});
		</script>

	<?php } ?>

	<?php if( current_user_can( 'edit_others_posts' ) ) { ?>
		<div class="floatleft">
			<a href="<?php echo add_query_arg( array( 'clear' => true ) ); ?>">Clear lineups</a>
		</div>
	<?php } ?>

	<div class="floatright">
		<a href="javascript: jQuery('.description').toggle();">Show/Hide descriptions</a>
	</div>
	
	
	<div class="clear"></div>
	<div class="clear-dotted"></div>
	
	<div class="print_lineup" style="float: left;">
		<?php foreach( $budget_sections as $slug => $section ) { ?>
			<h4 class="headline"><?php echo $section; ?></h4>
			<ol id="sortable-<?php echo $slug; ?>" class="sortable" slug="<?php echo $slug; ?>">
				<?php if( empty( $stories[ $slug ] ) ) {
					echo '</ol>';
					continue;
				} ?>
				<?php foreach( $stories[ $slug ] as $post_id ) { ?>
					<?php $used[] = $post_id; ?>
					<?php $post = get_post( $post_id ); ?>
					<?php $post = budget_set_up_item( $post ); ?>
					<?php setup_postdata( $post ); ?>
					<?php $terms = get_the_terms( $post->ID, 'status' ); ?>
					<li wp-id="<?php echo $post->ID; ?>" class="<?php if( !empty( $terms ) ) { $last_term = end( $terms ); foreach( $terms as $term ) { echo ' print_status_' . $term->slug; } } ?>">
						<input type="checkbox" class="check-mark-final-published" wp-id="<?php echo $post->ID; ?>" nonce="<?php echo wp_create_nonce( 'final_published-nonce-' . $post->ID ); ?>"> 
						<a href="<?php the_permalink(); ?>" target=_blank><strong><?php the_title(); ?></strong></a> -
						<?php budget_time( $post, 'g:i a' ); ?> -
						<?php echo get_user_by( 'id', $post->post_author )->display_name; ?> -
						<?php budget_length( $post, true ); ?> -
						<?php budget_importance(); ?> -
						<?php if( !empty( $terms ) ) { ?>
							<?php $last_term = end( $terms ); ?>
							<?php foreach( $terms as $term ) { ?>
								<span class="status status_<?php echo $term->slug; ?>"><?php echo $term->name; ?></span><?php if( $term != $last_term ) { ?>,<?php } ?>
							<?php } ?>
						<?php } ?> -
						<?php
						$visuals = get_post_meta( $post->ID, '_visuals_request', true );
						if( (int) $visuals > 0 ) {
							$visuals = get_post( $visuals );
							?>
							<a href="<?php echo get_permalink( $visuals->ID ); ?>" target=_blank><?php echo $visuals->post_title; ?></a>
							<?php
						} else {
							echo $visuals;
						} ?> -
						<span class="description"><?php echo get_the_excerpt(); ?></span>
					</li>
				<?php } ?>
			</ol>
		<?php } ?>
	</div>

	<?php if( current_user_can( 'edit_others_posts' ) ) { ?>
		<div class="print_desks" style="float: right;">
			<?php foreach( ( $terms = get_terms( 'desk', array( 'hide_empty' => false ) ) ) as $desk ) { ?>
				<?php if( in_array( $desk->slug, array( 'the-weekly', 'special-section-1', 'special-section-2', 'special-section-3', 'piscataquis-observer', 'aroostook-republican', 'houlton-pioneer-times', 'star-herald' ) ) ) continue; ?>
				<h4 class="headline">
					<?php echo $desk->name; ?>
				</h4>
				<ul class="sortable">
					<?php $stories = get_posts( array(
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
						'meta_query' => array(
							array(
								'key' => '_the_file_time',
								'value' => date_i18n( 'Y-m-d 23:59' ),
								'compare' => '<'
							)
						),
						'numberposts' => -1
						) ); ?>
						<?php if( ( empty( $stories ) || count( $stories ) == 0 ) ) {
							echo '</ul>';
							continue;
						} ?>
						<?php foreach( $stories as $i => $story ) {
							$stories[ $i ] = budget_set_up_item( $story );
						}
						$GLOBALS[ 'budget_orderby' ] = 'importance';
						$GLOBALS[ 'budget_order' ] = 'DESC';
						usort( $stories, 'budget_sort' );
						?>

						<?php foreach( $stories as $i => $post ) { ?>
							<?php if( in_array( $post->ID, $used ) ) continue; ?>
							<?php setup_postdata( $post ); ?>
							<li wp-id="<?php echo $post->ID; ?>">
								<input type="checkbox" class="check-mark-final-published" wp-id="<?php echo $post->ID; ?>" nonce="<?php echo wp_create_nonce( 'final_published-nonce-' . $post->ID ); ?>"> 
								<a href="<?php the_permalink(); ?>" target=_blank><strong><?php the_title(); ?></strong></a> -
								<?php budget_time( $post, 'g:i a' ); ?> -
								<?php echo get_user_by( 'id', $post->post_author )->display_name; ?> -
								<?php budget_length( $post, true ); ?> -
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
								} ?> -
								<span class="description"><?php echo get_the_excerpt(); ?></span>
							</li>
						<?php } ?>
				</ul>
			
			<?php } ?>

		</div>
	<?php } ?>

</wrapper>

<?php bdn_budget_context_menu(); ?>
<?php get_footer(); ?>