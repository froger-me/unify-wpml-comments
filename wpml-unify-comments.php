<?php
/**
Plugin Name: WPML Unify Comments
Plugin URI: https://froger.me/
Description: Display all the comments on posts (including custom ones), no matter the language ;
compatible with Woocommerce product reviews.
Version: 1.0
Author: Alexandre Froger
Author URI: https://froger.me/
WC tested up to: 3.3.4
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( ! defined( 'WPML_UC_PLUGIN_PATH' ) ) {
	define( 'WPML_UC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WPML_UC_PLUGIN_URL' ) ) {
	define( 'WPM_LUC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

function wpml_uc_run() {
	require_once dirname( __FILE__ ) . '/inc/class-wpml-unify-comments.php';

	$wp_uc = new WPML_Unify_Comments();
}
add_action( 'plugins_loaded', 'wpml_uc_run', 10, 0 );
