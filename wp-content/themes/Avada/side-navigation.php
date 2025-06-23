<?php
/**
 * Template Name: Side Navigation
 * Used for pages with a side-nav.
 *
 * @package Avada
 * @subpackage Templates
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}
?>

<?php get_header(); ?>
<section id="content" class="<?php echo esc_attr( apply_filters( 'awb_content_tag_class', '' ) ); ?>" style="<?php echo esc_attr( apply_filters( 'awb_content_tag_style', '' ) ); ?>">
	<?php while ( have_posts() ) : ?>
		<?php the_post(); ?>
		<?php $page_id = get_the_ID(); ?>
		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<?php echo fusion_render_rich_snippets_for_pages(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php avada_singular_featured_image(); ?>
			<div class="post-content">
				<?php the_content(); ?>
				<?php fusion_link_pages(); ?>
			</div>
			<?php if ( ! post_password_required() ) : ?>
				<?php if ( Avada()->settings->get( 'comments_pages' ) ) : ?>
					<?php comments_template(); ?>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	<?php endwhile; ?>
</section>
<?php do_action( 'avada_after_content' ); ?>
<?php get_footer(); ?>
