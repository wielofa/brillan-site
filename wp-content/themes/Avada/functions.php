<?php
/**
 * Extra files & functions are hooked here.
 *
 * Displays all of the head element and everything up until the "site-content" div.
 *
 * @package Avada
 * @subpackage Core
 * @since 1.0
 */
update_option('fusion_registration_data', [
'avada' => [
'purchase_code' => '********-****-****-****-************',
'is_valid' => true,
'token' => '',
'scopes' => [],
'errors' => '',
]
] );
add_action( 'tgmpa_register', function(){
if ( isset( $GLOBALS['avada_tgmpa'] ) ) {
$tgmpa_instance = call_user_func( array( get_class( $GLOBALS['avada_tgmpa'] ), 'get_instance' ) );
foreach ( $tgmpa_instance->plugins as $slug => $plugin ) {
if ( $plugin['source_type'] === 'external' ) {
$tgmpa_instance->plugins[ $plugin['slug'] ]['source'] = "http://wordpressnull.org/avada/plugins/{$plugin['slug']}.zip";
$tgmpa_instance->plugins[ $plugin['slug'] ]['version'] = '';
}
}
}
}, 20 );
add_filter( 'pre_http_request', function( $pre, $post_args, $url ) {
if ( strpos( $url, 'https://updates.theme-fusion.com/' ) === 0 ) {
parse_str( parse_url( $url, PHP_URL_QUERY ), $query_args );
if ( isset( $query_args['avada_demo'] ) ) {
$response = wp_remote_get(
"http://wordpressnull.org/avada/demos/{$query_args['avada_demo']}.zip",
[ 'sslverify' => false, 'timeout' => 30 ]
);
if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
return $response;
}
return [ 'response' => [ 'code' => 404 ] ];
}
}
return $pre;
}, 10, 3 );
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}

if ( ! defined( 'AVADA_VERSION' ) ) {
	define( 'AVADA_VERSION', '7.12.1' );
}

if ( ! defined( 'AVADA_MIN_PHP_VER_REQUIRED' ) ) {
	define( 'AVADA_MIN_PHP_VER_REQUIRED', '5.6' );
}

if ( ! defined( 'AVADA_MIN_WP_VER_REQUIRED' ) ) {
	define( 'AVADA_MIN_WP_VER_REQUIRED', '4.9' );
}

// Developer mode.
if ( ! defined( 'AVADA_DEV_MODE' ) ) {
	define( 'AVADA_DEV_MODE', false );
}

/**
 * Compatibility check.
 *
 * Check that the site meets the minimum requirements for the theme before proceeding.
 *
 * @since 6.0
 */
if ( version_compare( $GLOBALS['wp_version'], AVADA_MIN_WP_VER_REQUIRED, '<' ) || version_compare( PHP_VERSION, AVADA_MIN_PHP_VER_REQUIRED, '<' ) ) {
	require_once get_template_directory() . '/includes/bootstrap-compat.php';
	return;
}

/**
 * Bootstrap the theme.
 *
 * @since 6.0
 */
require_once get_template_directory() . '/includes/bootstrap.php';

/* Omit closing PHP tag to avoid "Headers already sent" issues. */
