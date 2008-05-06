<?php get_header(); ?>

	<div id="container">
		<div id="content">

			<div id="donate" class="<?php sandbox_post_class() ?>">
<?php $race_donor->display(); ?>
			</div><!-- #donate -->

		</div><!-- #content -->

	</div><!-- #container -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>