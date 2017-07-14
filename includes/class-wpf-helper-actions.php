<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 */
class WPF_Helper_Actions {

	/**
	 * Constructor
	 */
	public function __construct() {

		// Filters
		add_filter( 'plugin_action_links_' . plugin_basename( WPF_WC_GATEWAY_MAIN_FILE ), array( $this, 'plugin_action_links' ) );

		// Actions
		add_action( 'init', array( $this, 'wpf_callback' ) );

	}

	/**
	 * Add relevant links to plugins page
	 */
	public function plugin_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=wpf_wc' ) . '">' . __( 'Settings', 'wpf-woocommerce' ) . '</a>',
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Callback
	 */
	public function wpf_callback() {
		if ( class_exists( 'WPF_WC' ) ) {
			$wpfortify = new WPF_WC();
			return $wpfortify->wpf_listen();
		}
	}
}
new WPF_Helper_Actions();