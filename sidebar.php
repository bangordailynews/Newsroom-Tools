<h4>Folders</h4>
<ul>
	<li><a href="<?php echo home_url(); ?>">Home</a></li>
	<?php foreach( get_terms( 'folder', array( 'hide_empty' => false ) ) as $term ) { ?>
		<?php $link = get_term_link( $term, 'folder' ); ?>
		<?php if( is_wp_error( $link ) ) continue; ?>
		<li><a href="<?php echo $link; ?>"><?php echo $term->name; ?></a></li>
	<?php } ?>
	<li><a href="<?php echo home_url( '?all' ); ?>">All</a></li>
</ul>

<h4 style="margin-top:10px;">Statuses</h4>
<ul>
	<?php foreach( get_terms( 'status', array( 'hide_empty' => false, 'orderby' => 'id' ) ) as $term ) { ?>
		<?php $link = get_term_link( $term, 'status' ); ?>
		<?php if( is_wp_error( $link ) ) continue; ?>
		<li><a href="<?php echo $link; ?>"><?php echo $term->name; ?></a> (<?php echo $term->count; ?>)</li>
	<?php } ?>
</ul>

<h4 style="margin-top:10px;">Desks</h4>
<ul>
	<?php foreach( get_terms( 'desk', array( 'hide_empty' => false ) ) as $term ) { ?>
		<?php $link = get_term_link( $term, 'desk' ); ?>
		<?php if( is_wp_error( $link ) ) continue; ?>
		<li><a href="<?php echo $link; ?>"><?php echo $term->name; ?></a></li>
	<?php } ?>
</ul>
