<?php
	/* Single Post Template */
	get_header();
?>

	<div class="row content">
		<?php get_template_part('templates/content', 'single'); ?>
	</div>

<?php
	get_footer();
?>
