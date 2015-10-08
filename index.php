<?php get_header(); ?>
<div id="budget-wrapper">
	<div style="float: left; width: 20%;">
		<?php get_sidebar(); ?>
	</div>
	<div style="float: left; width: 80%;">
	
		<h4 class="headline floatleft"><?php wp_title( '', true, 'right' ); ?></h4>

		<div class="floatright">
			<select name="status" onchange="javascript: window.location = window.location + (/\?/.test(window.location) ? '&' : '?') + 'status=' + jQuery( this ).val();">
				<option>Show only with status...</option>
				<?php foreach( get_terms( 'status', array( 'orderby' => 'id', 'hide_empty' => false ) ) as $status ) { ?>
					<option value="<?php echo $status->slug; ?>" <?php if( !empty( $_GET[ 'status' ] ) && $_GET[ 'status' ] == $term->slug ) { ?>selected="selected"<?php } ?>><?php echo $status->name; ?></option>
				<?php } ?>
			</select>

			<?php if( !empty( $_GET[ 'hide-final-published' ] ) ) { ?>
				<a href="<?php echo add_query_arg( array( 'hide-final-published' => false ) ); ?>">Show final published</a> |
			<?php } else { ?>
				<a href="<?php echo add_query_arg( array( 'hide-final-published' => true ) ); ?>">Hide final published</a> |
			<?php } ?>
			
			<a href="<?php echo home_url( '?all' ); ?>">View all</a> |
			<a href="<?php echo get_author_posts_url( get_current_user_id() ); ?>">Mine</a>

		</div>
		<div class="clear"></div>
		<table width="100%">
			<tr>
				<td>Title</td>
				<td>Folders</td>
				<td>Owner</td>
				<td>Last modified</td>
				<td>Due in</td>
			</tr>
	
		<?php if ( have_posts() ) : ?>

			<?php while ( have_posts() ) : the_post(); ?>
				<tr wp-id="<?php the_ID(); ?>">
					<td>
						<strong><a href="<?php the_permalink(); ?>" target=_blank><?php the_title(); ?></a></strong>
						
						<?php $terms = get_the_terms( get_the_ID(), 'status' ); ?>
						<?php if( !empty( $terms ) ) { ?>
							<?php $last_term = end( $terms ); ?>
							<?php foreach( $terms as $term ) { ?>
								<span class="status status_<?php echo $term->slug; ?>"><?php echo $term->name; ?></span><?php if( $term != $last_term ) { ?>,<?php } ?>
							<?php } ?>
						<?php } ?>
					</td>
					<td>
						<?php $terms = get_the_terms( get_the_ID(), 'folder' ); ?>
						<?php if( !empty( $terms ) ) { ?>
							<?php $last_term = end( $terms ); ?>
							<?php foreach( $terms as $term ) { ?>
								<span class="folder folder_<?php echo $term->slug; ?>"><?php echo $term->name; ?></span><?php if( $term != $last_term ) { ?>,<?php } ?>
							<?php } ?>
						<?php } ?>
					</td>
					<td><?php the_author(); ?></td>
					<td><?php echo bdn_get_modified_time(); ?> <span class="lastmodified_name"><?php budget_modified_user(); ?></span></td>
					<td><?php budget_time_to_file( $post ); ?></td>
				</tr>
			<?php endwhile; ?>
			
		<?php else : ?>
			<tr><td colspan=100><strong>Nothing here yet.</strong></td></tr>
		<?php endif; ?>
		
		</table>
		<div class="navigation" style="text-align:right;"><?php posts_nav_link(); ?></div>

	</div>
	<div class="clear"></div>
</div>

<?php bdn_budget_context_menu(); ?>
<?php get_footer(); ?>