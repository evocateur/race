<?php get_header() ?>

	<div id="container">
		<div id="content">

<?php the_post() ?>
			<div id="post-<?php the_ID(); ?>" class="<?php sandbox_post_class() ?>">
				<h2 class="entry-title"><?php the_title(); ?></h2>
				<div class="entry-content">
<?php the_content() ?>

<?php wp_link_pages("\t\t\t\t\t<div class='page-link'>".__('Pages: ', 'sandbox'), "</div>\n", 'number'); ?>

<?php edit_post_link(__('Edit', 'sandbox'),'<span class="edit-link">','</span>') ?>

				</div>
			</div><!-- .post -->

		</div><!-- #content -->

		<div id="quadrant">
<?php race_front_meta('quadrant'); ?>
		</div>

	</div><!-- #container -->

<div id="spotlight" class="sidebar">
	<h3>In the Spotlight</h3>
<?php race_front_meta('spotlight'); ?>
</div><!-- #spotlight -->

<?php get_sidebar() ?>
<?php get_footer() ?>