<?php get_header(); ?>

	<div id="container">
		<div id="content">

	<?php if (have_users()) {  ?>

			<h2>Warriors</h2>

<?php while (have_users()){ the_user(); ?>
			<div class="user">
				<h4 class="user-title" id="user-<?php aleph_the_user_ID(); ?>"><?php aleph_the_user_profile_link(get_avatar(aleph_get_user_ID())); ?></h4>
				<p class="metadata">
				<?php aleph_the_user_complete_name('<strong class="user-name">', '</strong>'); ?>
				</p>
			</div>
<?php } ?>

		<p><?php previous_users_link("&laquo; Previous Users"); ?> <?php next_users_link("Next Users &raquo;"); ?></p>

	<?php } else { ?>
		<h1>Sorry</h1>
		<p>We haven't found users under that criteria.</p>

	<?php } // End if users ?>

		</div><!-- #content -->

	</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>