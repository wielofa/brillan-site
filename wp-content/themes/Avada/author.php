<?php
/**
 * Authors template.
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
	<?php
	/**
	 * Author Info Hook avada_author_info.
	 *
	 * @hooked avada_render_author_info - 10 (renders the HTML markup of the author info).
	 */
	do_action( 'avada_author_info' );
	?>

	<?php get_template_part( 'templates/blog', 'layout' ); ?>
</section>
<?php do_action( 'avada_after_content' ); ?>
<?php get_footer(); ?>
