<?php get_header(); ?>

	<div id="container">
		<div id="content">

<?php if ( is_user_logged_in() ) { the_post(); ?>
			<div id="login" class="<?php sandbox_post_class() ?>">
				<?php $warrior->loginLandingForm(); ?>
				<div class="entry-content">
					<?php the_content(); ?>
				</div>
				<div id="race_profile_update" style="display:none;">
					<h3>Thanks for updating your goal</h3>
					<p>Be sure to update your profile, too!</p>
					<p>You can add a picture, enter your address information, and even change your password.</p>
					<a href="<?php bloginfo('siteurl') ?>/wp-admin/profile.php"><button>Update My Profile</button></a>
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