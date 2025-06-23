<?php
/**
 * Dynamic-css helpers.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://avada.com
 * @package    Avada
 * @subpackage Core
 */

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

/**
 * Takes care of adding custom fonts using @font-face.
 *
 * @param string $css The CSS.
 * @param array  $custom_fonts Custom fonts to insert if needed.
 * @return string
 */
function avada_custom_fonts_font_faces( $css = '', $custom_fonts = [] ) {
	$font_face = '';
	$options   = get_option( Avada::get_option_name(), [] );

	if ( empty( $custom_fonts ) && isset( $options['custom_fonts'] ) ) {
		$custom_fonts = $options['custom_fonts'];
	}

	// Make sure we have titles for our fonts.
	if ( isset( $custom_fonts['name'] ) && is_array( $custom_fonts['name'] ) ) {
		foreach ( $custom_fonts['name'] as $key => $label ) {
			// Make sure we have some files to work with.
			$process = false;
			foreach ( [ 'woff2', 'woff', 'ttf', 'eot', 'svg' ] as $filetype ) {
				if ( ! $process && isset( $custom_fonts[ $filetype ] ) && isset( $custom_fonts[ $filetype ][ $key ] ) ) {
					$process = true;
				}
			}
			// If we don't have any files to process then skip this item.
			if ( ! $process || empty( $label ) ) {
				continue;
			}

			$firstfile      = true;
			$font_face     .= '@font-face{';
				$font_face .= 'font-family:';
				// If font-name has a space, then it must be wrapped in double-quotes.
			if ( false !== strpos( $label, ' ' ) ) {
				$font_face .= '"' . $label . '";';
			} else {
				$font_face .= $label . ';';
			}
			// Start adding sources.
			$font_face .= 'src:';

			// Add .woff2 file.
			if ( isset( $custom_fonts['woff2'] ) && isset( $custom_fonts['woff2'][ $key ]['url'] ) && isset( $custom_fonts['woff2'][ $key ]['url'] ) && '' !== $custom_fonts['woff2'][ $key ]['url'] ) {
				$font_face .= 'url("' . str_replace( [ 'http://', 'https://' ], '//', $custom_fonts['woff2'][ $key ]['url'] ) . '") format("woff2")';
				$firstfile  = false;
			}
			// Add .woff file.
			if ( isset( $custom_fonts['woff'] ) && isset( $custom_fonts['woff'][ $key ] ) && isset( $custom_fonts['woff'][ $key ]['url'] ) && '' !== $custom_fonts['woff'][ $key ]['url'] ) {
				$font_face .= ( $firstfile ) ? '' : ',';
				$font_face .= 'url("' . str_replace( [ 'http://', 'https://' ], '//', $custom_fonts['woff'][ $key ]['url'] ) . '") format("woff")';
				$firstfile  = false;
			}
			// Add .ttf file.
			if ( isset( $custom_fonts['ttf'] ) && isset( $custom_fonts['ttf'][ $key ] ) && isset( $custom_fonts['ttf'][ $key ]['url'] ) && '' !== $custom_fonts['ttf'][ $key ]['url'] ) {
				$font_face .= ( $firstfile ) ? '' : ',';
				$font_face .= 'url("' . str_replace( [ 'http://', 'https://' ], '//', $custom_fonts['ttf'][ $key ]['url'] ) . '") format("truetype")';
				$firstfile  = false;
			}
			// Add .eot file.
			if ( isset( $custom_fonts['eot'] ) && isset( $custom_fonts['eot'][ $key ] ) && isset( $custom_fonts['eot'][ $key ]['url'] ) && '' !== $custom_fonts['eot'][ $key ]['url'] ) {
				$font_face .= ( $firstfile ) ? '' : ',';
				$font_face .= 'url("' . str_replace( [ 'http://', 'https://' ], '//', $custom_fonts['eot'][ $key ]['url'] ) . '?#iefix") format("embedded-opentype")';
				$firstfile  = false;
			}
			// Add .svg file.
			if ( isset( $custom_fonts['svg'] ) && isset( $custom_fonts['svg'][ $key ] ) && isset( $custom_fonts['svg'][ $key ]['url'] ) && '' !== $custom_fonts['svg'][ $key ]['url'] ) {
				$font_face .= ( $firstfile ) ? '' : ',';
				$font_face .= 'url("' . str_replace( [ 'http://', 'https://' ], '//', $custom_fonts['svg'][ $key ]['url'] ) . '") format("svg")';
				$firstfile  = false;
			}

			$font_face_display = Avada()->settings->get( 'font_face_display' );
			$font_face_display = ( 'block' === $font_face_display ) ? 'block' : 'swap';

			$font_face .= ';font-weight: normal;font-style: normal;';
			$font_face .= 'font-display: ' . $font_face_display . ';';
			$font_face .= '}';
		}
	}

	return $font_face . $css;
}
add_filter( 'avada_dynamic_css', 'avada_custom_fonts_font_faces' );

/**
 * Ajax function to add custom fonts using @font-face.
 *
 * @return void
 */
function avada_custom_fonts_font_faces_ajax() {
	if ( isset( $_POST['custom_fonts'] ) ) {
		check_ajax_referer( 'fusion_load_nonce', 'fusion_load_nonce' );
		$custom_fonts = wp_unslash( $_POST['custom_fonts'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		echo avada_custom_fonts_font_faces( '', $custom_fonts ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	wp_die();
}
add_action( 'wp_ajax_avada_custom_fonts_font_faces', 'avada_custom_fonts_font_faces_ajax' );
add_action( 'wp_ajax_nopriv_avada_custom_fonts_font_faces', 'avada_custom_fonts_font_faces_ajax' );

/**
 * Avada body, h1, h2, h3, h4, h5, h6 typography.
 */

/**
 * CSS classes that inherit Avada's body typography settings.
 *
 * @return array
 */
function avada_get_body_typography_elements() {
	$typography_elements = [];

	// CSS classes that inherit body font size.
	$typography_elements['size'] = [
		'body',
		'.sidebar .slide-excerpt h2',
		'.fusion-footer-widget-area .slide-excerpt h2',
		'#slidingbar-area .slide-excerpt h2',
		'.jtwt .jtwt_tweet',
		'.sidebar .jtwt .jtwt_tweet',
		'.project-content .project-info h4',
		'.fusion-project-details-tb .project-info h4',
		'.gform_wrapper label',
		'.gform_wrapper .gfield_description',
		'.fusion-footer-widget-area ul',
		'#slidingbar-area ul',
		'.fusion-blog-layout-timeline .fusion-timeline-date',
		'.review blockquote q',
		'.fusion-body .tribe-common .tribe-common-b2',
		'.fusion-body .tribe-common .tribe-common-b3',
		'.fusion-body #main .tribe-events .datepicker',

	];
	// CSS classes that inherit body font color.
	$typography_elements['color'] = [
		'body',
		'.post .post-content',
		'.sidebar .jtwt',
		'.fusion-body .fusion-wrapperr .meta',
		'.review blockquote div',
		'.search input',
		'.project-content .project-info h4',
		'.fusion-project-details-tb .project-info h4',
		'.title-row',
		'.fusion-rollover .price .amount',
		'.fusion-blog-timeline-layout .fusion-timeline-date',
		'.sidebar .widget_nav_menu li',
		'.sidebar .widget_categories li',
		'.sidebar .widget_product_categories li',
		'.sidebar .widget_meta li',
		'.sidebar .widget .recentcomments',
		'.sidebar .widget_recent_entries li',
		'.sidebar .widget_archive li',
		'.sidebar .widget_pages li',
		'.sidebar .widget_links li',
		'.sidebar .widget_layered_nav li',
		'.sidebar .widget_product_categories li',
		'.fusion-main-menu .fusion-custom-menu-item-contents',
		'.fusion-body .tribe-block__tickets__registration__tickets__header',
		'.fusion-body .tribe-events .tribe-events-calendar-list__event-datetime',
		'.fusion-body .tribe-common .tribe-common-b2',
		'.fusion-body .tribe-common .tribe-common-b3',
		'.fusion-body .fusion-wrapper #main .tribe-common .tribe-common-h6--min-medium',
		'.fusion-body #main .tribe-common .tribe-events-c-day-marker__date',
	];
	// CSS classes that inherit body font.
	$typography_elements['family'] = [
		'body',
		'#nav ul li ul li a',
		'#sticky-nav ul li ul li a',
		'.more',
		'.avada-container h3',
		'.meta .fusion-date',
		'.review blockquote q',
		'.review blockquote div strong',
		'.fusion-load-more-button',
		'.ei-title h3',
		'.comment-form input[type="submit"]',
		'.fusion-page-title-captions h3',
		'.fusion-image-wrapper .fusion-rollover .fusion-rollover-content .fusion-rollover-title',
		'.fusion-image-wrapper .fusion-rollover .fusion-rollover-content .fusion-rollover-categories',
		'.fusion-image-wrapper .fusion-rollover .fusion-rollover-content a',
		'.fusion-image-wrapper .fusion-rollover .fusion-rollover-content .price',
		'#wrapper #nav ul li ul li > a',
		'#wrapper #sticky-nav ul li ul li > a',
		'.ticket-selector-submit-btn[type=submit]',
		'.gform_page_footer input[type=button]',
		'.fusion-main-menu .sub-menu',
		'.fusion-main-menu .sub-menu li a',
		'.fusion-megamenu-wrapper li .fusion-megamenu-title-disabled',
		'.fusion-megamenu-wrapper .fusion-megamenu-submenu > a:hover',
		'.fusion-megamenu-widgets-container',
		'.fusion-body .tribe-common .tribe-common-b2',
		'.fusion-body .tribe-common .tribe-common-b3',
		'.fusion-body .fusion-wrapper #main .tribe-common .tribe-common-h6--min-medium',
		'.fusion-body #main .tribe-common .tribe-events-c-day-marker__date',
		'.fusion-body #main .tribe-events .datepicker',
	];

	// CSS classes that inherit body font.
	$typography_elements['line-height'] = [
		'body',
		'#nav ul li ul li a',
		'#sticky-nav ul li ul li a',
		'.more',
		'.avada-container h3',
		'.meta .fusion-date',
		'.review blockquote q',
		'.review blockquote div strong',
		'.ei-title h3',
		'.comment-form input[type="submit"]',
		'.fusion-page-title-captions h3',
		'.fusion-image-wrapper .fusion-rollover .fusion-rollover-content .fusion-rollover-title',
		'.fusion-image-wrapper .fusion-rollover .fusion-rollover-content .fusion-rollover-categories',
		'.fusion-image-wrapper .fusion-rollover .fusion-rollover-content a',
		'.fusion-image-wrapper .fusion-rollover .fusion-rollover-content .price',
		'#wrapper #nav ul li ul li > a',
		'#wrapper #sticky-nav ul li ul li > a',
		'.single-tribe_events #tribe-events-content .tribe-events-event-meta dt',
		'.ticket-selector-submit-btn[type=submit]',
		'.gform_page_footer input[type=button]',
		'.fusion-main-menu .sub-menu',
		'.fusion-main-menu .sub-menu li a',
		'.fusion-megamenu-wrapper li .fusion-megamenu-title-disabled',
		'.fusion-megamenu-wrapper .fusion-megamenu-submenu > a:hover',
		'.fusion-megamenu-widgets-container',
		'.fusion-accordian .panel-body',
		'#side-header .fusion-contact-info',
		'#side-header .header-social .top-menu',
		'.fusion-body .tribe-common .tribe-common-b2',
		'.fusion-body .tribe-common .tribe-common-b3',
		'.fusion-body .fusion-wrapper #main .tribe-common .tribe-common-h6--min-medium',
		'.fusion-body #main .tribe-common .tribe-events-c-day-marker__date',
	];

	return $typography_elements;
}

/**
 * CSS classes that inherit Avada's H1 typography settings.
 *
 * @return array
 */
function avada_get_h1_typography_elements() {
	$typography_elements = [];

	// CSS classes that inherit h1 size.
	$typography_elements['size'] = [
		'h1',
		'.post-content h1',
		'.fusion-modal h1',
		'.fusion-widget-area h1',
		'.search-page-search-form h1',
		'.fusion-tb-page-title-bar h1',
		'.fusion-tb-footer h1',
	];
	// CSS classes that inherit h1 font family.
	$typography_elements['family'] = [
		'h1',
		'.post-content h1',
		'.fusion-page-title-bar h1',
		'.fusion-modal h1',
		'.fusion-widget-area h1',
		'.fusion-title h1',
		'.search-page-search-form h1',
		'.fusion-tb-page-title-bar h1',
		'.fusion-tb-footer h1',
	];
	// CSS classes that inherit h1 color.
	$typography_elements['color'] = [
		'h1',
		'.post-content h1',
		'.title h1',
		'.fusion-post-content h1',
		'.fusion-modal h1',
		'.fusion-widget-area h1',
		'.search-page-search-form h1',
		'.fusion-tb-page-title-bar h1',
		'.fusion-tb-footer h1',
	];

	return $typography_elements;
}

/**
 * CSS classes that inherit Avada's H2 typography settings.
 *
 * @return array
 */
function avada_get_h2_typography_elements() {
	$typography_elements = [];

	// CSS classes that inherit h2 size.
	$typography_elements['size'] = [
		'h2',
		'.post-content h2',
		'.post h2',
		'h2.entry-title',
	];
	// CSS classes that inherit h2 color.
	$typography_elements['color'] = [
		'h2',
		'.search-page-search-form h2',
		'.fusion-post-content h2',
	];
	// CSS classes that inherit h2 font family.
	$typography_elements['family'] = [
		'h2',
		'.post h2',
		'.post-content h2',
		'.ei-title h2',
		'.main-flex .slide-content h2',
	];

	return $typography_elements;
}

/**
 * CSS classes that inherit Avada's H3 typography settings.
 *
 * @return array
 */
function avada_get_h3_typography_elements() {
	$typography_elements = [];

	// CSS classes that inherit h3 font family.
	$typography_elements['family'] = [
		'h3',
		'.post-content h3',
		'.project-content h3',
		'.sidebar .widget h3',
		'.main-flex .slide-content h3',
		'.fusion-author .fusion-author-title',
		'.fusion-header-tagline',
	];
	// CSS classes that inherit h3 size.
	$typography_elements['size'] = [
		'h3',
		'.post-content h3',
		'.project-content h3',
		'.fusion-widget-area h3',
		'.fusion-author .fusion-author-title',
	];

	// CSS classes that inherit h3 color.
	$typography_elements['color'] = [
		'h3',
		'.post-content h3',
		'.sidebar .widget h3',
		'.project-content h3',
		'.fusion-title h3',
		'.title h3',
		'.fusion-post-content h3',
		'.fusion-author .fusion-author-title',
	];

	// CSS classes that inherit body font.
	$typography_elements['line-height'] = [
		'#tab-reviews #reviews #comments > h2',
	];

	return $typography_elements;
}

/**
 * CSS classes that inherit Avada's H4 typography settings.
 *
 * @return array
 */
function avada_get_h4_typography_elements() {
	$typography_elements = [];

	// CSS classes that inherit h4 size.
	$typography_elements['size'] = [
		'h4',
		'.post-content h4',
		'.fusion-rollover .fusion-rollover-content .fusion-rollover-title',
		'.fusion-carousel-title',
		'.fusion-tabs-widget .fusion-tabs-nav ul li a',
		'.fusion-theme-sharing-box h4',
		'.fusion-sharing-box h4',
		'.fusion-tabs .nav-tabs > li .fusion-tab-heading',
		'.fusion-widget-area h4',
		'.tribe-events-single ul.tribe-related-events li .tribe-related-events-title',
	];
	// CSS classes that inherit h4 color.
	$typography_elements['color'] = [
		'h4',
		'.post-content h4',
		'.project-content .project-info h4',
		'.fusion-project-details-tb .project-info h4',
		'.fusion-theme-sharing-box h4',
		'.fusion-title h4',
		'.title h4',
		'.fusion-content-widget-area .fusion-tabs-widget .fusion-tabs-nav ul li a',
		'.fusion-content-widget-area .fusion-tabs-widget .fusion-tabs-widget-content .fusion-date-box',
		'.fusion-carousel-title',
		'.fusion-tabs .nav-tabs > li .fusion-tab-heading',
		'.fusion-post-content h4',
		'.fusion-widget-area h4',
		'.tribe-events-single ul.tribe-related-events li .tribe-related-events-title',
	];
	// CSS classes that inherit h4 font family.
	$typography_elements['family'] = [
		'h4',
		'.post-content h4',
		'table th',
		'.fusion-megamenu-title',
		'.fusion-carousel-title',
		'.fusion-tabs-widget .fusion-tabs-nav ul li a',
		'.fusion-theme-sharing-box h4',
		'.project-content .project-info h4',
		'.fusion-project-details-tb .project-info h4',
		'.fusion-image-wrapper .fusion-rollover .fusion-rollover-content .fusion-rollover-title',
		'.fusion-image-wrapper .fusion-rollover .fusion-rollover-content .fusion-rollover-title a',
		'.fusion-widget-area h4',
		'.fusion-title h4',
		'.tribe-events-single ul.tribe-related-events li .tribe-related-events-title',
	];

	$typography_elements['line-height'] = [
		'h4',
		'.project-content .project-info .project-terms',
		'.fusion-project-details-tb .project-info .project-terms',
		'.project-info-box span',
		'.fusion-tb-page-title-bar h4',
		'.fusion-tb-footer h4',
		'.tribe-events-single ul.tribe-related-events li .tribe-related-events-title',
	];

	return $typography_elements;
}

/**
 * CSS classes that inherit Avada's H5 typography settings.
 *
 * @return array
 */
function avada_get_h5_typography_elements() {
	$typography_elements = [];

	// CSS classes that inherit h5 size.
	$typography_elements['size'] = [
		'h5',
		'.post-content h5',
		'.fusion-widget-area h5',
	];
	// CSS classes that inherit h5 color.
	$typography_elements['color'] = [
		'h5',
		'.post-content h5',
		'.fusion-title h5',
		'.title h5',
		'.fusion-post-content h5',
		'.fusion-widget-area h5',
	];
	// CSS classes that inherit h5 font family.
	$typography_elements['family'] = [
		'h5',
		'.post-content h5',
		'.fusion-widget-area h5',
		'.fusion-title h5',
	];

	return $typography_elements;
}

/**
 * CSS classes that inherit Avada's H6 typography settings.
 *
 * @return array
 */
function avada_get_h6_typography_elements() {
	$typography_elements = [];

	// CSS classes that inherit h6 size.
	$typography_elements['size'] = [
		'h6',
		'.post-content h6',
		'.fusion-widget-area h6',
	];
	// CSS classes that inherit h6 color.
	$typography_elements['color'] = [
		'h6',
		'.post-content h6',
		'.fusion-title h6',
		'.title h6',
		'.fusion-post-content h6',
		'.fusion-widget-area h6',
	];
	// CSS classes that inherit h6 font family.
	$typography_elements['family'] = [
		'h6',
		'.post-content h6',
		'.fusion-widget-area h6',
		'.fusion-title h6',
	];

	return $typography_elements;
}

/**
 * CSS classes that inherit Avada's post title typography settings.
 *
 * @return array
 */
function avada_get_post_title_typography_elements() {
	$typography_elements = [];

	// CSS classes that inherit post title size.
	$typography_elements['size'] = [
		'#wrapper #main .post > h2.fusion-post-title',
		'#wrapper #main .post > .fusion-post-title-meta-wrap > h2.fusion-post-title',
		'#wrapper #main .fusion-post-content > .blog-shortcode-post-title',
		'#wrapper #main .fusion-post-content > h2.fusion-post-title',
		'#wrapper #main .fusion-portfolio-content > h2.fusion-post-title',
		'#wrapper #main .post > h1.fusion-post-title',
		'#wrapper #main .post > .fusion-post-title-meta-wrap > h1.fusion-post-title',
		'#wrapper #main .fusion-post-content > h1.fusion-post-title',
		'#wrapper #main .fusion-portfolio-content > h1.fusion-post-title',
		'.single-product #main .product h1.product_title',
		'.single-product #main .product h2.product_title',
		'#main .fusion-woocommerce-quick-view-container .product_title',
	];

	// CSS classes that inherit post title color.
	$typography_elements['color'] = [
		'#wrapper #main .post > h2.fusion-post-title',
		'#wrapper #main .post > .fusion-post-title-meta-wrap > h2.fusion-post-title',
		'#wrapper #main .fusion-post-content > .blog-shortcode-post-title',
		'#wrapper #main .fusion-post-content > h2.fusion-post-title',
		'#wrapper #main .fusion-portfolio-content > h2.fusion-post-title',
		'#wrapper #main .post > h1.fusion-post-title',
		'#wrapper #main .post > .fusion-post-title-meta-wrap > h1.fusion-post-title',
		'#wrapper #main .fusion-post-content > h1.fusion-post-title',
		'#wrapper #main .fusion-portfolio-content > h1.fusion-post-title',
	];

	// CSS classes that inherit post title font family.
	$typography_elements['family'] = [
		'#wrapper #main .post > h2.fusion-post-title',
		'#wrapper #main .post > .fusion-post-title-meta-wrap > h2.fusion-post-title',
		'#wrapper #main .fusion-post-content > .blog-shortcode-post-title',
		'#wrapper #main .fusion-post-content > h2.fusion-post-title',
		'#wrapper #main .fusion-portfolio-content > h2.fusion-post-title',
		'#wrapper #main .post > h1.fusion-post-title',
		'#wrapper #main .post > .fusion-post-title-meta-wrap > h1.fusion-post-title',
		'#wrapper #main .fusion-post-content > h1.fusion-post-title',
		'#wrapper #main .fusion-portfolio-content > h1.fusion-post-title',
		'.single-product #main .product h1.product_title',
		'.single-product #main .product h2.product_title',
		'#main .fusion-woocommerce-quick-view-container .product_title',
	];

	return $typography_elements;
}

/**
 * CSS classes that inherit Avada's post title extras typography settings.
 *
 * @return array
 */
function avada_get_post_title_extras_typography_elements() {
	$typography_elements = [];

	// CSS classes that inherit post title extra size.
	$typography_elements['size'] = [
		'#wrapper #main .about-author .fusion-title h3',
		'#wrapper #main #comments .fusion-title h3',
		'#wrapper #main #respond .fusion-title h3',
		'#wrapper #main .related-posts .fusion-title h3',
		'#wrapper #main .related.products .fusion-title h3',
		'.woocommerce-container .up-sells .fusion-title h3',
		'#wrapper #main .about-author .fusion-title h2',
		'#wrapper #main #comments .fusion-title h2',
		'#wrapper #main #respond .fusion-title h2',
		'#wrapper #main .related-posts .fusion-title h2',
		'#wrapper #main .related.products .fusion-title h2',
		'.single-product .woocommerce-tabs .fusion-woocommerce-tab-title',
		'#tab-reviews #reviews #comments > h2',
	];
	// CSS classes that inherit post title extra color.
	$typography_elements['color'] = [
		'#wrapper #main .about-author .fusion-title h3',
		'#wrapper #main #comments .fusion-title h3',
		'#wrapper #main #respond .fusion-title h3',
		'#wrapper #main .related-posts .fusion-title h3',
		'#wrapper #main .related.products .fusion-title h3',
		'.woocommerce-container .up-sells .fusion-title h3',
		'#wrapper #main .about-author .fusion-title h2',
		'#wrapper #main #comments .fusion-title h2',
		'#wrapper #main #respond .fusion-title h2',
		'#wrapper #main .related-posts .fusion-title h2',
		'#wrapper #main .related.products .fusion-title h2',
		'.single-product .woocommerce-tabs .fusion-woocommerce-tab-title',
		'#tab-reviews #reviews #comments > h2',
	];
	// CSS classes that inherit post title extra font family.
	$typography_elements['family'] = [
		'#wrapper #main .about-author .fusion-title h3',
		'#wrapper #main #comments .fusion-title h3',
		'#wrapper #main #respond .fusion-title h3',
		'#wrapper #main .related-posts .fusion-title h3',
		'#wrapper #main .related.products .fusion-title h3',
		'.woocommerce-container .up-sells .fusion-title h3',
		'#wrapper #main .about-author .fusion-title h2',
		'#wrapper #main #comments .fusion-title h2',
		'#wrapper #main #respond .fusion-title h2',
		'#wrapper #main .related-posts .fusion-title h2',
		'#wrapper #main .related.products .fusion-title h2',
		'.single-product .woocommerce-tabs .fusion-woocommerce-tab-title',
		'#tab-reviews #reviews #comments > h2',
	];

	return $typography_elements;
}
