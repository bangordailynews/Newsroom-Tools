<html>
	<head>
		<title><?php wp_title( '| ', true, 'right' ); ?><?php bloginfo('name'); ?></title>
		<link rel="stylesheet" type="text/css" href="http://bangordailynews.com/wp-content/themes/bdn/css/reset.css" />
		<link rel="stylesheet" type="text/css" href="http://bangordailynews.com/wp-content/themes/bdn/style.css" />
		<link rel="stylesheet" type="text/css" href="<?php bloginfo('stylesheet_url'); ?>?ver=2" />
		<style type="text/css" media="print">
			#bdn-menu, header, .print_desks{ display: none !important; }
			html{ margin-top: 0; }
		</style>
		<?php wp_head(); ?>
	</head>
<body>

<header>
	<div id="nameplate" class="floatleft" style="margin-right: 30px;">
		<h3>
			<span>
				<a href="<?php echo home_url(); ?>"><img src="http://bangordailynews.com/wp-content/themes/bdn/images/bdnmaine_50.png" style="height:25px; width:25px;" /></a>
			</span>
			<span style="font-size: 25pt; line-height: 23px; font-family: Tahoma, sans-serif; font-weight: normal; clear: left; letter-spacing: -0.02em;">
				<a href="<?php echo home_url(); ?>" style="color: #000;">Newsroom</a>
			</span>
		</h3>
	</div>

	<div class="budget-top-button">
		<a href="#" class="create-budget-line">CREATE</a>
	</div>
	
	<?php if( is_singular( 'doc' ) ) { ?>
		<div class="budget-top-button">
			<a href="#" class="edit-budget-line" wp-id="<?php echo get_the_ID(); ?>">EDIT BUDGET LINE</a>
		</div>
		
		<div class="budget-top-button">
			<a href="#" class="file-budget-item" wp-id="<?php echo get_the_ID(); ?>">FILE</a>
		</div>
		
		<?php if( current_user_can( 'edit_others_posts' ) ) { ?>
			<div class="budget-top-button">
				<a href="<?php echo add_query_arg( array( 'docToWP' => true ), get_permalink() ); ?>" class="send-to-wp" wp-id="<?php echo get_the_ID(); ?>">PUBLISH</a>
			</div>
		<?php } ?>
		
		<div class="floatleft" style="margin-left: 20px">
			<div><strong><?php the_title(); ?></strong></div>
			<div id="storyLength"></div>
		</div>
	<?php } ?>

	<div id="userinfo" class="floatright" style="text-align: right;">
		Signed in as <?php echo wp_get_current_user()->user_login; ?> (<a href="<?php echo wp_logout_url( home_url() ); ?>">sign out</a>)<br>
		<a href="http://docs.google.com" target=_blank>Back to Docs</a>
	</div>

	<div class="clear"></div>
</header>
<div class="clear-solid"></div>
<div class="clear"></div>
