<?php get_header(); ?>

	<div id="container">
		<div id="content">

	<?php if ( have_users() ) {  ?>

		<h2>Current Warriors for Life</h2>

		<ul class="user-list">
<?php while ( have_users() ) { the_user();
		$profile_url = aleph_get_user_profile_url();
		?>
			<li class="user">
				<a class="user-avatar" href="<?php echo $profile_url; ?>"><?php aleph_the_user_avatar(); ?></a>
				<h4 class="user-title" id="user-<?php aleph_the_user_ID(); ?>">
					<a href="<?php echo $profile_url; ?>"><?php aleph_the_user_complete_name('', ''); ?></a>
				</h4>
			</li>
<?php } ?>
		</ul>
		<p><?php previous_users_link("&laquo; Previous"); ?> <?php next_users_link("Next &raquo;"); ?></p>

	<?php } else { ?>
		<h1>Sorry</h1>
		<p>We haven't found users under that criteria.</p>

	<?php } // End if users ?>

		</div><!-- #content -->

	</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>