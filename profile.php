<?php get_header(); ?>

	<div id="container">
		<div id="content">

<?php if (have_users()) { the_user();  ?>
			<div id="profile" class="<?php sandbox_post_class() ?>">
				<h2 class="profile-title"><?php aleph_the_user_complete_name(); ?></h2>
				<div class="entry-content">
<?php aleph_the_user_avatar('<div class="user-avatar">', '</div>'); ?>
<?php aleph_the_user_description(); ?>

				</div>
			</div><!-- .post -->
<?php } else { ?>
		<h1>Sorry</h1>

		<p>We couldn't found the user you're looking for.</p>
<?php } ?>

		</div><!-- #content -->

	</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>