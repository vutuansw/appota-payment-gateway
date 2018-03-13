<?php

/**
 * Plugin Name: Appota Payment Gateway
 * Plugin URI: https://github.com/vutuansw/appota-payment-gateway
 * Version: 1.0.1
 * Author: wedesignwebuild
 * Author URI: https://profiles.wordpress.org/wedesignwebuild
 * License: GPLv3
 * Description: Thanh toán với Appota Payment
 * - Tích hợp thanh toán qua appotapay.com cho các website bán hàng có đăng ký API.
 * - Thực hiện lấy thông tin tài khoản người bán                             *
 *   danh sách các phương thức thanh toán ngân hàng qua email
 * - Gửi thông tin thanh toán tới appotapay.com để xử lý việc thanh toán.
 * - Xác thực tính chính xác của thông tin được gửi về từ appotapay.com
 * 
 * WC requires at least: 2.6.0
 * WC tested up to: 3.3.3
 * 
 * Requires at least: 4.5
 * Tested up to: 4.9
 * Text Domain: appota-payment-gateway
 * Domain Path: /languages/
 *
 * @package    APG
 * 
 */
/**
 * Defines
 * @since 1.0.1
 */
define( 'APG_VER', '1.0.1' );
define( 'APG_URL', plugin_dir_url( __FILE__ ) );
define( 'APG_DIR', plugin_dir_path( __FILE__ ) );
define( 'APG_BASENAME', plugin_basename( __FILE__ ) );
define( 'APG_FILE', __FILE__ );

/**
 * Register Payment gateway
 * @param  array $methods
 * @return array
 */
function apg_register_gateways( $methods ) {
	$methods[] = 'APG_Payment_Gateway';
	return $methods;
}

/**
 * Display transaction ID in order detail
 */
function apg_transaction_id_order_meta( $order ) {
	if ( $id = get_post_meta( $order->get_id(), 'appotapay_transaction_id', true ) ) {
		echo '<p class="form-field form-field-wide"><label>' . esc_html__( 'Appotapay Transaction ID', 'appota-payment-gateway' ) . ': </label>' . $id . '</p>';
	}
}

/**
 * Thông báo cài đặt hoặc kích hoạt WooCommerce nếu plugin chưa được cài đặt hoặc kích hoạt
 */
function apg_notice() {
	$class = 'notice notice-error';
	$message = esc_html__( 'Hệ thống chưa cài đặt hoặc kích hoạt plugin WooCommerce! Bạn cần cài đặt hoặc kích hoạt WooCommerce để sử dụng plugin Appota Payment', 'appota-payment-gateway' );

	printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
}

/**
 * Load Local files.
 * @since 1.0.1
 * @return void
 */
function apg_load_plugin_textdomain() {

	// Set filter for plugin's languages directory
	$dir = APG_DIR . 'languages/';
	$dir = apply_filters( 'apg_languages_directory', $dir );

	// Traditional WordPress plugin locale filter
	$locale = apply_filters( 'plugin_locale', get_locale(), 'appota-payment-gateway' );
	$mofile = sprintf( '%1$s-%2$s.mo', 'appota-payment-gateway', $locale );

	// Setup paths to current locale file
	$mofile_local = $dir . $mofile;

	$mofile_global = WP_LANG_DIR . '/appota-payment-gateway/' . $mofile;

	if ( file_exists( $mofile_global ) ) {
		load_textdomain( 'appota-payment-gateway', $mofile_global );
	} elseif ( file_exists( $mofile_local ) ) {
		load_textdomain( 'appota-payment-gateway', $mofile_local );
	} else {
		// Load the default language files
		load_plugin_textdomain( 'appota-payment-gateway', false, $dir );
	}
}

add_action( 'plugins_loaded', 'apg_load_plugin_textdomain' );

/**
 * Include file classes
 */
function apg_init() {

	if ( class_exists( 'WooCommerce' ) ) {

		include APG_DIR . 'includes/APG_Api.php';
		include APG_DIR . 'includes/APG_Payment_Gateway.php';
		include APG_DIR . 'includes/APG_Receiver.php';
		include APG_DIR . 'includes/APG_Logger.php';


		add_filter( 'woocommerce_payment_gateways', 'apg_register_gateways' );
		add_action( 'woocommerce_admin_order_data_after_order_details', 'apg_transaction_id_order_meta', 10, 1 );
	} else {
		add_action( 'admin_notices', 'apg_notice' );
	}
}

add_action( 'plugins_loaded', 'apg_init' );

