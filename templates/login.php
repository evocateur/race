<?php get_header(); ?>

	<div id="container">
		<div id="content">

<?php if ( is_user_logged_in() ) { ?>
			<div id="login" class="<?php sandbox_post_class() ?>">
				<h2 class="login-title"><span>Your Profile</span></h2>
				<div class="entry-content">
					<div class="user-image">
						<?php $warrior->theUserPhoto(); ?>
					</div>
					<?php $warrior->loginLandingForm(); ?>
					<p>Login page content.</p>
				</div>
			</div><!-- .post -->
<?php } else { ?>
			<div id="missing-login-error">
				<h1>Whoops</h1>
				<p>Please log in to view this page.</p>
			</div>
<?php } ?>

		</div><!-- #content -->

	</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>