<?php get_header(); ?>

	<div id="container">
		<div id="content">

<?php if (have_users()) { the_user(); ?>
			<div id="profile" class="<?php sandbox_post_class() ?>">
				<h2 class="profile-title"><?php aleph_the_user_complete_name(); ?></h2>
				<div class="entry-content" id="user-<?php aleph_the_user_ID(); ?>">
<?php aleph_the_user_photo('<div class="user-image">', '</div>'); ?>
<?php aleph_the_user_description(); ?>
				</div>
			</div><!-- .post -->
<?php } else { ?>
		<h1>Sorry</h1>

		<p>We couldn't found the user you're looking for.</p>
<?php } ?>
		<div id="race-summary">
			<h4>RACE Summary</h4>
			<p>RACE Charities is a non-profit organization dedicated to raising awarenes of cancer through early detection.  RACE organizes events around the country in efforts to raise awareness of the importance of early detection of cancer, especially for young people with a family history of cancer.  RACE was founded by Glory Gensch (pictured on top of Page) who passed away from colon cancer at the age of 23.  Glory’s legacy and mission lives on through RACE Charities, whose fundraising campaign is focused on raising money to donate directly to nationally renowned early detection research institutes around the world.  Glory, as well as all RACE volunteers and members, believe that if our efforts can save just one life, then it is well worth the sacrifice!</p>
		</div>

		</div><!-- #content -->

	</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>