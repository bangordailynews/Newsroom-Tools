<?php

/*
Visuals stuff
*/

//We completely redo the columns order for the visuals stuff
add_filter( 'manage_posts_columns', 'budget_custom_columns', 1, 1 );
function budget_custom_columns( $defaults ) {
	global $post;

	//This column simply shows the Google Doc ID
	if( $post->post_type == 'doc' ) {
		$defaults[ 'has_doc' ] = 'Has Doc';
	}

	//If this isn't for visuals, return
	if( $post->post_type != 'visual' )
		return $defaults;
	
	$defaults = array(
		'title' => 'Assignment time',
		'author' => 'Photographer',
		'details' => 'Details',
		'story' => 'Story',
		'due' => 'Due',
		'date' => 'Date submitted',
		'cb' => '<input type="checkbox" />',
	);
 
    return $defaults;
}

//Perform the actions for the custom columns
add_action( 'manage_posts_custom_column', 'budget_custom_column', 10, 2);
function budget_custom_column( $column_name, $post_id ) {

	$post = get_post( $post_id );

	switch ( $column_name ) {
		case 'details':
			echo $post->post_excerpt;
			break;
		case 'story':
			echo '<strong><a href="' . get_permalink( $post->post_parent ) . '" target=_blank>' . get_the_title( $post->post_parent ) . '</a>:</strong> ' . get_post( $post->post_parent )->post_excerpt;
			break;
		case 'due':
			echo date( 'D, M j g:i a', strtotime( get_post_meta( $post->post_parent, '_the_file_time', true ) ) );
			break;
		case 'has_doc':
			echo get_post_meta( $post->ID, '_gdocID', true );
			break;
		
	}

}


//Add the fields to the profile for the defaults
add_action( 'show_user_profile', 'budget_extra_profile_fields' );
add_action( 'edit_user_profile', 'budget_extra_profile_fields' );

function budget_extra_profile_fields( $user ) { ?>

	<h3>Budget</h3>

	<table class="form-table">

		<tr>
			<th><label for="twitter">Default desk</label></th>

			<td>
				<?php wp_dropdown_categories( array( 'name' => 'default_desk', 'taxonomy' => 'desk', 'hide_empty' => false, 'selected' => ( $default_desk = get_user_meta( $user->ID, 'default_desk', true ) ) ? $default_desk : DEFAULT_DESK ) ); ?>
			</td>
		</tr>
		
		<tr>
			<th><label for="twitter">Default view</label></th>

			<td>
				<?php $default_view = ( ( $default_view = get_user_meta( $user->ID, 'default_view', true ) ) ? $default_view : 'user_docs' ); ?>
				<select name="default_view">
					<option value="user_docs" <?php echo ( $default_view == 'user_docs' ) ? 'selected="selected"' : ''; ?>>My docs</option>
					<option value="default_desk" <?php echo ( $default_view == 'default_desk' ) ? 'selected="selected"' : ''; ?>>Default desk</option>
					<option value="all" <?php echo ( $default_view == 'all' ) ? 'selected="selected"' : ''; ?>>All docs</option>
				</select>
			</td>
		</tr>
		
		<tr>
			<th><label for="assignment_editor">Assignment Editor</label></th>

			<td>
				<input type="checkbox" name="is_ae" <?php if( get_user_meta( $user->ID, 'is_ae', true ) ) { ?>checked="checked"<?php } ?>> Assignment Editor
			</td>
		</tr>

	</table>
<?php }

//Save the defaults
add_action( 'personal_options_update', 'budget_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'budget_save_extra_profile_fields' );

function budget_save_extra_profile_fields( $user_id ) {

	if ( !current_user_can( 'edit_user', $user_id ) )
		return false;

	update_usermeta( $user_id, 'default_desk', $_POST[ 'default_desk' ] );
	update_usermeta( $user_id, 'default_view', $_POST[ 'default_view' ] );
	if( !empty( $_POST[ 'is_ae' ] ) ) {
		update_usermeta( $user_id, 'is_ae', 1 );	
	} else {
		delete_usermeta( $user_id, 'is_ae' );
	}
}