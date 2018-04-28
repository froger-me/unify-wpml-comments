<?php
/**
Plugin Name: Unify WMPL Comments
Plugin URI: https://github.com/froger-me/unify-wpml-comments
Description: Display all the comments on posts (including custom ones), no matter the language.
compatible with WooCommerce product reviews.
Version: 1.0
Author: Alexandre Froger
Author URI: https://froger.me/
WC tested up to: 3.3.4
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! defined( 'U_WMPL_C_PLUGIN_PATH' ) ) {
	define( 'U_WMPL_C_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'U_WMPL_C_PLUGIN_URL' ) ) {
	define( 'U_WMPL_C_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

function uc_wpml_run() {
	require_once dirname( __FILE__ ) . '/inc/class-unify-wpml-comments.php';

	$wp_uc = new Unify_WPML_Comments();
}
add_action( 'plugins_loaded', 'uc_wpml_run', 10, 0 );
