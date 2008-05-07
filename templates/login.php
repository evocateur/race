<?php get_header(); ?>

	<div id="container">
		<div id="content">

<?php if ( is_user_logged_in() ) { the_post(); ?>
			<div id="login" class="<?php sandbox_post_class() ?>">
				<h2 class="login-title"><span>Your Profile</span></h2>
				<div class="user-image">
					<?php $warrior->theUserPhoto(); ?>
				</div>
				<?php $warrior->loginLandingForm(); ?>
				<div class="entry-content">
					<?php the_content(); ?>
				</div>
			</div><!-- .post -->
<?php } else { ?>
			<div id="missing-login-error">
				<h1>Whoops</h1>
				<p>Please log in to view this page.</p>
			</div>
<?php } ?>
<?php edit_post_link(__('Edit', 'sandbox'),'<span class="edit-link">','</span>') ?>

		</div><!-- #content -->

	</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>