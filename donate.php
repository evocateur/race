<?php get_header(); ?>

	<div id="container">
		<div id="content">

			<div id="donate" class="<?php sandbox_post_class() ?>">
<?php $warrior->displayDonorForm(); ?>
			</div><!-- #donate -->

		</div><!-- #content -->

	</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>