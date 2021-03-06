<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 *
 */
class WPF_Card_Actions {

	/**
	 * Constructor
	 */
	public function __construct() {

		// Actions
		add_action( 'wp', array( $this, 'delete_card_handler' ) );
		add_action( 'woocommerce_after_my_account', array( $this, 'saved_cards' ) );
		add_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'capture_payment' ) );
		add_action( 'woocommerce_order_status_on-hold_to_completed', array( $this, 'capture_payment' ) );
		add_action( 'woocommerce_order_status_on-hold_to_cancelled', array( $this, 'cancel_payment' ) );
		add_action( 'woocommerce_order_status_on-hold_to_refunded', array( $this, 'cancel_payment' ) );

	}

	/**
	 * Delete a card
	 */
	public function delete_card_handler() {
		if ( ! isset( $_POST['wpf_woocommerce_delete_card'] ) || ! is_account_page() ) {
			return;
		}

		if ( $_POST['testmode'] ) {
			$credit_cards = get_user_meta( get_current_user_id(), '_wpf_woocommerce_card_details_test', false );
		}else{
			$credit_cards = get_user_meta( get_current_user_id(), '_wpf_woocommerce_card_details_live', false );
		}

		if ( ! $credit_cards ) {
			return;
		}

		if ( ! is_user_logged_in() || ! wp_verify_nonce( $_POST['_wpnonce'], "_wpf_woocommerce_del_card" ) ) {
			wp_die( __( 'Unable to verify deletion, please try again', 'wpf-woocommerce' ) );
		}

		if ( $_POST['testmode'] ) {
			delete_user_meta( get_current_user_id(), '_wpf_woocommerce_card_details_test', $credit_cards[ absint( $_POST['wpf_woocommerce_delete_card'] ) ] );
		}else{
			delete_user_meta( get_current_user_id(), '_wpf_woocommerce_card_details_live', $credit_cards[ absint( $_POST['wpf_woocommerce_delete_card'] ) ] );
		}

		wc_add_notice( __( 'Card deleted.', 'wpf-woocommerce' ), 'success' );
		wp_safe_redirect( get_permalink( wc_get_page_id( 'myaccount' ) ) );
		exit;
	}

	/**
	 * Display saved cards
	 */
	public function saved_cards() {
		$wpfortify = new WPF_WC();
		if ( $wpfortify->testmode ) {
			$credit_cards = get_user_meta( get_current_user_id(), '_wpf_woocommerce_card_details_test', false );
		}else{
			$credit_cards = get_user_meta( get_current_user_id(), '_wpf_woocommerce_card_details_live', false );
		}

		if ( ! $credit_cards ) {
			return;
		}

		wc_get_template( 'saved-cards.php', array( 'credit_cards' => $credit_cards, 'testmode' => $wpfortify->testmode ), 'wpf-woocommerce/', WPF_WC_GATEWAY_TEMPLATE_PATH );
	}

	/**
	 * Capture payment when the order is changed from on-hold to complete or processing
	 */
	public function capture_payment( $order_id ) {
		$order = new WC_Order( $order_id );

		if ( $order->payment_method == 'wpfortify' ) {
			$charge   = get_post_meta( $order_id, '_wpf_woocommerce_charge_id', true );
			$captured = get_post_meta( $order_id, '_wpf_woocommerce_charge_captured', true );

			if ( $charge && $captured == 'no' ) {
				$wpfortify = new WPF_WC();
				// Data for wpFortify
				$wpf_charge = array (
					'wpf_charge' => array(
						'plugin'    => 'wpf-woocommerce',
						'action'    => 'capture',
						'testmode'  => $wpfortify->testmode,
						'charge'    => $charge
					)
				);
				$result = $wpfortify->wpf_api( 'repeater', $wpf_charge );

				if ( is_wp_error( $result ) ) {
					$order->add_order_note( __( 'Unable to capture charge!', 'wpf-woocommerce' ) . ' ' . $result->get_error_message() );
				} else {
					$order->add_order_note( sprintf( __( 'wpFortify (Stripe) charge captured. Charge ID: %s', 'wpf-woocommerce' ), $result->id ) );
					update_post_meta( $order->id, '_wpf_woocommerce_charge_captured', 'yes' );
				}
			}
		}
	}

	/**
	 * Cancel pre-auth on refund/cancellation
	 */
	public function cancel_payment( $order_id ) {
		$order = new WC_Order( $order_id );

		if ( $order->payment_method == 'wpfortify' ) {
			$charge   = get_post_meta( $order_id, '_wpf_woocommerce_charge_id', true );

			if ( $charge ) {
				$wpfortify = new WPF_WC();
				// Data for wpFortify
				$wpf_charge = array (
					'wpf_charge' => array(
						'plugin'    => 'wpf-woocommerce',
						'action'    => 'refund',
						'testmode'  => $wpfortify->testmode,
						'charge'    => $charge
					)
				);
				$result = $wpfortify->wpf_api( 'repeater', $wpf_charge );

				if ( is_wp_error( $result ) ) {
					$order->add_order_note( __( 'Unable to refund charge!', 'wpf-woocommerce' ) . ' ' . $result->get_error_message() );
				} else {
					$order->add_order_note( sprintf( __( 'wpFortify (Stripe) charge refunded. Charge ID: %s', 'wpf-woocommerce' ), $result->id ) );
					delete_post_meta( $order->id, '_wpf_woocommerce_charge_captured' );
					delete_post_meta( $order->id, '_wpf_woocommerce_charge_id' );
					delete_post_meta( $order->id, '_wpf_woocommerce_customer_id' );
					delete_post_meta( $order->id, '_wpf_woocommerce_card_id' );
				}
			}
		}
	}
}
new WPF_Card_Actions();