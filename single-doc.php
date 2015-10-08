<?php
remove_action( 'wp_footer', 'bdn_tophat', 1 );
get_header(); ?>

<style type="text/css">
	header{
		height: 30px;
		position: fixed;
		top: 0;
	}
	#userinfo {
		display: none;
	}
</style>

<?php if( ( $gdoc_id = get_post_meta( $post->ID, '_gdocID', true ) ) ) { ?>

	<div id="doc_wrapper">
		<iframe id="doc_iframe" src="http://docs.google.com/a/<?php echo GOOGLE_LOGIN_DOMAIN; ?>/document/d/<?php echo $gdoc_id; ?>/edit" width="100%" height="99%" scrolling="no" height="10" border="0" frameBorder="0" style="margin:0; padding:0; border:0;"></iframe>
	</div>

	<script type="text/javascript">
		jQuery(window).ready(function() {
			checkPostModified();
		});
		jQuery(window).unload(function() {
			var data = { action: 'bdn_poll_doc', wpid: <?php echo $post->ID; ?> };
			jQuery.ajax( { type: 'POST', async: false, url: '<?php echo admin_url( 'admin-ajax.php' ); ?>', data: data });
		});
		function checkPostModified() {
			var data = { action: 'bdn_poll_doc', wpid: <?php echo $post->ID; ?> };
			jQuery.post( '<?php echo admin_url( 'admin-ajax.php' ); ?>', data, function( d ) {
				jQuery( '#storyLength' ).text( d.data.words + ' words / ' + Math.round( d.data.words / <?php echo BDN_WORDS_PER_INCH; ?> ) + '"' );
			});
			setTimeout( function() { checkPostModified(); }, 30000 );
		}
	</script>

<?php } else { ?>
	
	<div style="margin-top: 100px; text-align: center;">
	
		<h3 class="headline">Apologies, looks like there was a problem</h3>
	
		<div>The system encountered an error completing your request to create a new budget item. But <strong>you don't need to start over</strong>. Please just wait a minute and then press the button below.</div>
	
		<div class="budget-top-button" style="float:none; margin: 20px auto;">
			<a href="javascript: jQuery( '#budget_line_form_<?php echo $post->ID; ?>' ).submit();">CLICK HERE</a>
		</div>
		
		<div class="budget-whoops-docs">
			<ul>
				<?php $files = $driveService->files->listFiles( array( 'maxResults' => 5, 'q' => '"' . wp_get_current_user()->user_email . '" in owners and modifiedDate > "' . date_i18n( 'Y-m-d\TH:i:s', time() - 600 ) . '"' ) ); ?>
				<?php if( !empty( $files[ 'items' ] ) && count( $files[ 'items' ] ) > 0 ) { ?>
					<div>Alternatively, you can attach one of these recently created docs to your budget item:</div>
					<?php foreach( $files[ 'items' ] as $driveFile ) { ?>
						<li><a href="<?php echo add_query_arg( array( 'action' => 'do_attach_doc', 'gdocID' => $driveFile[ 'id' ], '_nonce' => wp_create_nonce( 'attach-nonce-' . $post->ID . '-' . $driveFile[ 'id' ] ) ), get_permalink() ); ?>">Attach "<?php echo $driveFile[ 'title' ]; ?>"</a></li>
					<?php } ?>
				<?php } ?>
			</ul>
		</div>
	
	</div>
	
<?php } ?>

<?php bdn_budget_line( $post ); ?>
<?php bdn_file_item( $post ); ?>
<?php get_footer(); ?>