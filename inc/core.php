<?php
function bdn_get_modified_time() { 
	global $post;
	$time = strtotime( $post->post_modified );
	if( date_i18n( 'U' ) - 86400 < $time ) {
		return date( 'g:i a', $time );
	} else {
		return date( 'M j', $time );
	}
}

function bdn_budget_context_menu() { ?>
	<script type="text/javascript">
	jQuery( 'tr, li' ).bind( 'contextmenu',function( e ) {
		jQuery( '#context-menu' ).remove();
		var wpid = false;
		jQuery( e.currentTarget.attributes ).each( function( i, n ) {
			if( n.name == 'wp-id' ) {
				wpid = n.value;
			}
		});
		if( wpid ) {
			e.preventDefault();
			var data = { action: 'bdn_context_menu', wpid: wpid, page: '<?php echo home_url( str_replace( strstr( str_replace( '://', '', get_option( 'home' ) ), '/' ), '', $_SERVER['REQUEST_URI'] ) ); ?>' };
			jQuery.post( '<?php echo admin_url( 'admin-ajax.php' ); ?>', data, function( d ) {
				jQuery( d.data.editBudgetLine ).appendTo( 'body' );
				jQuery( d.data.fileItem ).appendTo( 'body' );
				jQuery( '<div id="context-menu">'
						+ '<div class="menu-item"><strong>' + d.data.slug + '</strong></div>'
						+ '<div class="menu-item"><a href="' + d.data.permalink + '" target=_blank>Open</a></div>'
						+ '<div class="menu-item"><a href="#" class="edit-budget-line" wp-id="' + wpid + '">Edit Budget Line</a></div>'
						+ '<div class="menu-item"><a href="#" class="file-doc" wp-id="' + wpid + '">File</a></div>'
						+ ( ( d.data.wpID ) ? '<div class="clear-solid"></div><div class="menu-item"><a href="<?php echo LIVE_SITE_URL; ?>?p=' + d.data.wpID + '" target=_blank>View on site</a></div>' : '' )
						<?php if( current_user_can( 'edit_others_posts' ) ) { ?>
							+ ( ( d.data.wpID ) ? '<div class="menu-item"><a href="<?php echo LIVE_SITE_URL; ?>wp-admin/post.php?post=' + d.data.wpID + '&action=edit" target=_blank>Edit in WordPress</a></div>' : '' )
						<?php } ?>
						+ '<div class="clear-solid"></div>'
						+ '<div class="menu-item"><a href="' + d.data.markfinal + '">Mark Final Published</a></div>'
						+ '<div class="menu-item"><a href="' + d.data.spike + '">Spike</a></div>'
						+ '<div class="clear-solid"></div>'
						+ '<div class="menu-item"><a href="#" class="create-budget-line">Create</a></div>'
					+ '</div>'
				).appendTo( 'body' ).css({ top: e.pageY + "px", left: e.pageX + "px" });
				jQuery(document).bind( 'click', function( e ) { jQuery( '#context-menu' ).remove(); });
				jQuery( 'a.edit-budget-line' ).click(function( e ) { e.preventDefault(); jQuery( '#budget_line_' + jQuery(this).attr('wp-id') ).show( 'fast' ); window.scrollTo( 0, 0); });
				jQuery( 'a.file-doc' ).click(function( e ) { e.preventDefault(); jQuery( '#file_item_' + jQuery(this).attr('wp-id') ).show( 'fast' ); window.scrollTo( 0, 0); });
				jQuery( 'a.organize-doc' ).click(function( e ) { e.preventDefault(); jQuery( '#organize_item_' + jQuery(this).attr('wp-id') ).show( 'fast' ); window.scrollTo( 0, 0); });
				jQuery( 'a.create-budget-line' ).click(function( e ) { e.preventDefault(); jQuery( '#budget_line' ).show( 'fast' );  window.scrollTo( 0, 0); });
			});
		}
		// create and show menu
	});
	</script>
<?php }

add_action( 'wp_footer', 'bdn_footer_js_stuff' );
function bdn_footer_js_stuff() {
	global $post;
	?>
	<script type="text/javascript">
		jQuery(document).keyup(function(e) {
			if (e.keyCode == 27)
				jQuery( '.bdn-budget-item, .bdn-file-item, .bdn-publish-item' ).hide( 'fast' );
		});
		jQuery( 'a.edit-budget-line' ).click(function( e ) {
			e.preventDefault();
			jQuery( '#budget_line_' + jQuery(this).attr('wp-id') ).show( 'fast' );
		});
		jQuery( 'a.create-budget-line' ).click(function( e ) {
			e.preventDefault();
			jQuery( '#budget_line' ).show( 'fast' );
		});
		jQuery( 'input.check-mark-final-published' ).change(function( e ) {
			var wpid = jQuery(this).attr('wp-id');
			jQuery.post( '<?php echo admin_url( 'admin-ajax.php' ); ?>', { action: 'bdn_mark_final_published', wpid: jQuery(this).attr('wp-id'), '_nonce': jQuery(this).attr('nonce') }, function( d ) {
				jQuery('li[wp-id="' + wpid + '"]').addClass( 'print_status_final-published' );
			});
		});
		<?php if( is_singular( 'doc' ) ) { ?>
			jQuery( 'a.file-budget-item' ).click( function( e ) {
				e.preventDefault();
				jQuery( '#file_item_' + jQuery(this).attr('wp-id') ).show( 'fast' );
			});
		<?php } ?>
		jQuery( 'a.send-to-wp' ).click( function( e ) {
			e.preventDefault();
			var link = e.currentTarget.href;
			jQuery(
				'<div class="bdn-publish-item" id="publish-item-' + jQuery(this).attr('wp-id') + '">'
					+ '<div class="background"></div>'
					+ '<div class="item-form-wrapper"><div class="createbox" style="text-align:center;">'
						+ '<h4>Is this ready for print?</h4>'
						+ '<div class="budget-top-button"><a href="' + link + '&readyForPrint=true" class="edit-budget-line" wp-id="8">YES</a></div>'
						+ '<div class="budget-top-button"><a href="' + link + '" class="edit-budget-line" wp-id="8">NO</a></div>'
						+ '<div class="clear">&nbsp;</div>'
						+ '<div style="text-align:left; padding-left:5px; margin-bottom: 5px;"><input type="checkbox" onclick="javascript:alert( this.checked )"> Split off into a new post</div>'
						+ '<div><strong>Doc ID:</strong> <?php echo get_post_meta( $post->ID, '_gdocID', true ); ?></div>'
					+ '</div></div>'
				+'</div>'
			).appendTo( 'body' );
			jQuery( '#publish-item-' + jQuery(this).attr('wp-id') ).show( 'fast' );
		});
	</script>
	<?php
}

add_action( 'wp_footer', 'bdn_budget_line' );
function bdn_budget_line( $post, $ajax = false ) {

	//Default fields for the budget
	$defaults = array(
		'slug' => '',
		'length' => '',
		'time' => '',
		'description' => '',
		'importance' => 0,
		'author' => get_current_user_id(),
		'desk' => ( $default_desk = get_user_meta( get_current_user_id(), 'default_desk', true ) ) ? $default_desk : DEFAULT_DESK,
		'visuals_request' => false,
		'visuals_details' => '',
		'visuals_time' => '',
	);

	//If we have a post, get the data
	if( !empty( $post ) ) { 
		$args = array(
			'slug' => $post->post_title,
			'length' => get_post_meta( $post->ID, '_budget_length', true ),
			'description' => $post->post_excerpt,
			'author' => $post->post_author,
			'visuals_request' => get_post_meta( $post->ID, '_visuals_request', true ),
		);
		
		if( (int) $args[ 'visuals_request' ] > 0 && ( $visuals = get_post( $args[ 'visuals_request' ] ) ) && $visuals->post_type == 'visual' ) {
			$args[ 'visuals_details' ] = $visuals->post_excerpt;
			$args[ 'visuals_time' ] = $visuals->post_title;
		}

		$budget_file_time = json_decode( get_post_meta( $post->ID, '_budget_file_time', true ), true );
		if( is_array( $budget_file_time ) ) {
			$budget_file_time = end( $budget_file_time );
			if( !empty( $budget_file_time[ 'value' ] ) )
				$args[ 'time' ] = date( 'Y-m-d g:i a', strtotime( $budget_file_time[ 'value' ] ) );
		}
		
		foreach( array( 'desk', 'importance' ) as $tax ) {
			$terms = get_the_terms( $post->ID, $tax );
			if( is_array( $terms ) ) {
				$term = reset( $terms );
				$args[ $tax ] = $term->term_id;
			}
		}
		
	}
	
	$args = wp_parse_args( $args, $defaults );

	$form_id = 'budget_line_form' . ( ( empty( $post ) ) ? '' : '_' . $post->ID );
	$wrapper_id = 'budget_line' . ( ( empty( $post ) ) ? '' : '_' . $post->ID );
	$action_url = add_query_arg( array( 'action' => 'do_budget_item' ), ( ( empty( $ajax ) ) ? false : $ajax ) );

?>
	<div class="bdn-budget-item" id="<?php echo $wrapper_id; ?>">
		<div class="background"></div>
		<div class="item-form-wrapper">
			<div class="createbox">
				<form id="<?php echo $form_id; ?>" action="<?php echo $action_url; ?>" method="POST">
					
					<h3 class="headline"><?php echo ( empty( $post ) ) ? 'Create a' : 'Edit'; ?> budget line</h3>
					
					<?php wp_nonce_field( 'budget-nonce', 'budget_item_nonce' ); ?>
					
					<?php if( !empty( $post ) ) { ?>
						<input type="hidden" name="budget[id]" value="<?php echo $post->ID; ?>">
					<?php } ?>
					
					<div class="createbox-form-field">
						<input type="text" autocomplete="off" id="budget_slug" name="budget[slug]" class="required_field" value="<?php echo $args[ 'slug' ]; ?>" placeholder="Slug">
					</div>
					
					<div class="createbox-form-field">
						<div style="position: absolute; height: 0; width: 0;">
							<div style="position: relative; height: 10px; top: 14px; left: 300px; width: 95px; text-align: right;">
								<div style="color: #5d5d5d; font-size: 20px; font-weight: 800;">
									<span id="inch_count"><?php echo ( $args[ 'length' ] > 0 ) ? round( $args[ 'length' ] / BDN_WORDS_PER_INCH ) : 0; ?></span>"
								</div>
							</div>
						</div>
					
						<input type="text" autocomplete="off" id="budget_length" name="budget[length]" class="required_field" value="<?php echo $args[ 'length' ]; ?>" placeholder="Word Count">
					</div>
					
					<div class="createbox-form-field">
						<input type="text" autocomplete="off" id="budget_file_time<?php echo ( ( empty( $post ) ) ? '' : $post->ID ); ?>" name="budget[time]" class="required_field" value="<?php echo $args[ 'time' ]; ?>" placeholder="File date/time">
					</div>
					
					<div class="createbox-form-field">
						<textarea rows="3" id="budget_description" placeholder="Description" class="required_field" name="budget[description]"><?php echo $args[ 'description' ]; ?></textarea>
					</div>
					
					<div class="createbox-form-field">
						<?php wp_dropdown_categories( array( 'selected' => $args[ 'importance' ], 'id' => 'budget_importance', 'class' => 'required_field', 'name' => 'budget[importance]', 'taxonomy' => 'importance', 'hide_empty' => false, 'show_option_all' => 'Importance' ) ); ?>
					</div>
					
					<div class="createbox-form-field">
						<strong>Visuals:</strong>
						<input type="radio" name="budget[visuals][request]" value="" <?php if( empty( $args[ 'visuals_request' ] ) ) { ?>checked="checked"<?php } ?>>None
						<input type="radio" name="budget[visuals][request]" value="file" <?php if( $args[ 'visuals_request' ] == 'file' ) { ?>checked="checked"<?php } ?>>Already in Merlin
						<input type="radio" name="budget[visuals][request]" value="reporter" <?php if( $args[ 'visuals_request' ] == 'reporter' ) { ?>checked="checked"<?php } ?>>Reporter
						<input type="radio" name="budget[visuals][request]" id="visuals_request" value="request" <?php if( (int) $args[ 'visuals_request' ] > 0 ) { ?>checked="checked"<?php } ?>>Request
					</div>
					
					<div id="visuals_request_form" <?php if( (int) $args[ 'visuals_request' ] == 0 ) { ?>style="display:none;"<?php } ?>>
						<div class="createbox-form-field">
							<textarea rows="3" id="budget_visuals_request" placeholder="Visuals details, such as contact info, time and place" name="budget[visuals][details]"><?php echo $args[ 'visuals_details' ]; ?></textarea>
						</div>
						
						<div class="createbox-form-field">
							<input type="text" id="budget_visuals_time<?php echo ( ( empty( $post ) ) ? '' : $post->ID ); ?>" name="budget[visuals][time]" value="<?php echo $args[ 'visuals_time' ]; ?>" placeholder="Visuals event date and time">
						</div>
					</div>
					
					<div id="create_advanced" style="display:none;">
						<div class="createbox-form-field">
							<?php wp_dropdown_users( array( 'name' => 'budget[author]', 'selected' => $args[ 'author' ] ) ); ?>
						</div>
						
						<div class="createbox-form-field">
							<?php wp_dropdown_categories( array( 'name' => 'budget[desk]', 'taxonomy' => 'desk', 'hide_empty' => false, 'selected' => $args[ 'desk' ] ) ); ?>
						</div>
					</div>
					<div style="text-align: right;">
						<a href="javascript: jQuery( '#<?php echo $form_id; ?> #create_advanced' ).show( 'fast' ); jQuery( '#<?php echo $form_id; ?> #advanced_link' ).hide( 'fast' );" id="advanced_link">Advanced &rarr;</a><br>
						<div id="submit_buttons"><input type="submit" name="budget[go][stay]" value="Submit and stay" /> or <input type="submit" name="budget[go][doc]" value="Go to doc" /></div>
						<div id="submit_message"></div>
					</div>
					<div class="clear"></div>
				</form>
			</div>
		</div>
	</div>
	<script type="text/javascript">
		jQuery( '#<?php echo $form_id; ?> :radio' ).click(function() {
			if( jQuery( this ).attr( 'name' )  == 'budget[visuals][request]' ) {
				if( jQuery( this ).val() == 'request' ) {
					jQuery( '#<?php echo $form_id; ?> #visuals_request_form' ).show( 'fast' );
				} else {
					jQuery( '#<?php echo $form_id; ?> #visuals_request_form' ).hide( 'fast' );
				}
			}
		});
		jQuery( '#<?php echo $form_id; ?>' ).submit( function( e ) {
			var submit = true;
			jQuery( '#<?php echo $form_id; ?> .required_field').each(function() {
				if( jQuery( this ).val() == ( '' || 0 ) ) {
					jQuery( '#<?php echo $form_id; ?> #' + this.id ).css( 'border-color', '#ff0000' );
					e.preventDefault();
					submit = false;
				}
			});
			if( submit ) {
				jQuery( '#<?php echo $form_id; ?> #submit_buttons' ).hide( 'fast' );
				jQuery( '#<?php echo $form_id; ?> #submit_message' ).html( '<h4>Please wait...</h4>' );
			}
		});
		jQuery( '#<?php echo $form_id; ?> #budget_length' ).keypress(function( e ) {
			var k = e.which;
			if( k < 48 || k >= 58 )
				e.preventDefault();
		});
		jQuery( '#<?php echo $form_id; ?> #budget_length' ).keyup( function() {
			jQuery( '#<?php echo $form_id; ?> #inch_count' ).html( Math.round( jQuery( this ).val() / <?php echo BDN_WORDS_PER_INCH; ?> ) );
		});
		<?php if( !$ajax ) { ?>jQuery(window).ready(function() {<?php } ?>							
			jQuery( '#<?php echo $form_id; ?> #budget_file_time<?php echo ( ( empty( $post ) ) ? '' : $post->ID ); ?>' ).datetimepicker({
				minDate: 0,
				dateFormat: 'yy-mm-dd',
				inline: true,  
				showOtherMonths: true,  
				dayNamesMin: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
				controlType: 'select',
				timeFormat: 'h:mm tt',
				<?php if( empty( $post ) ) { ?>
					hour: <?php echo date_i18n( 'H', date_i18n( 'U' ) + 3600 ); ?>,
					minute: 0,
				<?php } ?>
			});
			jQuery( '#<?php echo $form_id; ?> #budget_visuals_time<?php echo ( ( empty( $post ) ) ? '' : $post->ID ); ?>' ).datetimepicker({
				minDate: 0,
				dateFormat: 'yy-mm-dd',
				inline: true,  
				showOtherMonths: true,  
				dayNamesMin: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
				controlType: 'select',
				timeFormat: 'HH:mm',
				<?php if( empty( $post ) ) { ?>
					hour: <?php echo date_i18n( 'H', date_i18n( 'U' ) + 3600 ); ?>,
					minute: 0,
				<?php } ?>
			});
		<?php if( !$ajax ) { ?>});<?php } ?>
	</script>
<?php
}


if( is_singular() )
	add_action( 'wp_footer', 'bdn_file_item' );

function bdn_file_item( $post, $ajax = false ) {

	if( !function_exists( 'wp_terms_checklist' ) )
		require_once( ABSPATH . '/wp-admin/includes/template.php' );

	//Default fields for the budget
	$defaults = array(
		'status' => DEFAULT_STATUS
	);

	$args = array();
	//If we have a post, get the data
	if( !empty( $post ) ) { 
		$terms = get_the_terms( $post->ID, 'status' );
		if( is_array( $terms ) ) {
			foreach( $terms as $term ) {
				if( count( $terms ) > 1 && $term->slug == 'published' )
					continue;
				$args[ 'status' ] = $term->term_id;
			}
		}
	}
	
	$args = wp_parse_args( $args, $defaults );

	$form_id = 'file_item_form' . ( ( empty( $post ) ) ? '' : '_' . $post->ID );
	$wrapper_id = 'file_item' . ( ( empty( $post ) ) ? '' : '_' . $post->ID );
	$action_url = add_query_arg( array( 'action' => 'do_file_item' ), ( ( empty( $ajax ) ) ? false : $ajax ) );

?>
	<div class="bdn-file-item" id="<?php echo $wrapper_id; ?>">
		<div class="background"></div>
		<div class="item-form-wrapper">
			<div class="createbox">
				<form id="<?php echo $form_id; ?>" action="<?php echo $action_url; ?>" method="POST">
					
					<h3 class="headline">File <?php echo $post->post_title; ?></h3>
					
					<?php wp_nonce_field( 'file-nonce', 'file_item_nonce' ); ?>

					<input type="hidden" name="file[id]" value="<?php echo $post->ID; ?>">

					<div class="createbox-form-field">
						<?php wp_dropdown_categories( array( 'selected' => $args[ 'status' ], 'id' => 'file_status', 'class' => 'required_field', 'name' => 'file[status]', 'taxonomy' => 'status', 'hide_empty' => false ) ); ?>
					</div>
					
					<div class="createbox-form-field">
						<ul class="file-folders">
							<?php wp_terms_checklist( $post->ID, array( 'taxonomy' => 'folder', 'checked_ontop' => false ) ); ?>
						<ul>
					</div>
										
					<div style="text-align: right;">
						<input type="submit" value="Submit" />
					</div>
					<div class="clear"></div>
				</form>
			</div>
		</div>
	</div>
<?php
}