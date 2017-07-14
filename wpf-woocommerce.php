<?php
/*
Plugin Name: wpFortify for WooCommerce
Plugin URI: http://wordpress.org/plugins/wpf-woocommerce/
Description: wpFortify provides a hosted SSL checkout page for Stripe payments. A free wpFortify account is required for this plugin to work.
Version: 2.6.6
Author: wpFortify
Author URI: https://wpfortify.com

	Adapted from WooCommerce Stripe Gateway by Mike Jolley.
	Copyright: © 2009-2015 WooThemes.
	License: GPLv2+
	License URI: http://www.gnu.org/licenses/gpl-2.0.html

*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main wpFortify class which sets the gateway up for us
 */
class WPF_WC_Gateway {

	/**
	 * Constructor
	 */
	public function __construct() {

		// Define
		define( 'WPF_WC_GATEWAY_VERSION', '2.6.6' );
		define( 'WPF_WC_GATEWAY_TEMPLATE_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) . '/templates/' );
		define( 'WPF_WC_GATEWAY_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		define( 'WPF_WC_GATEWAY_MAIN_FILE', __FILE__ );

		// Actions
		add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );

	}

	/**
	 * Init localisations and files
	 */
	public function init() {
		if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
			return;
		}

		// Includes
		include_once( 'includes/class-wpf-woocommerce.php' );
		include_once( 'includes/class-wpf-card-actions.php' );
		include_once( 'includes/class-wpf-helper-actions.php' );

		// Localisation
		load_plugin_textdomain( 'wpf-woocommerce', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

	}

	/**
	 * Register the gateway for use
	 */
	public function register_gateway( $methods ) {
		$methods[] = 'WPF_WC';
		return $methods;
	}
}
new WPF_WC_Gateway();