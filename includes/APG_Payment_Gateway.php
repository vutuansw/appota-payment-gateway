<?php

class APG_Payment_Gateway extends WC_Payment_Gateway {

	public function __construct() {

		// Đặt ID cho phương thức thanh toán (cần Unique)
		$this->id = 'appota_payment';
		// Đặt language cho phương thức thanh toán
		$this->lang = 'vi';
		// Đặt Icon trong cấu hình cho phương thức
		$this->icon = APG_URL . 'assets/images/appota-plugin-icon.png';
		// Không hiện trường ngoài thanh toán người dùng
		$this->has_fields = false;
		// Tên phương thức thanh toán
		$this->method_title = esc_html__( 'Appota Payment', 'appota-payment-gateway' );
		// Mô tả phương thức thanh toán
		$this->method_description = esc_html__( 'Phương thức thanh toán an toàn với chi phí thấp qua cổng thanh toán Appotapay.com', 'appota-payment-gateway' );
		// Có dùng SSL verify khi gọi API Appota hay không. True: có, False: không
		$this->ssl_verify = False;

		// Gọi init_form_fields theo chuẩn Woocommerce
		$this->init_form_fields();
		// Thực hiện chuyển cấu hình init_form_fields thành form cấu hình trong admin
		$this->init_settings();

		// Lấy thông tin tiêu đề phương thức thanh toán
		$this->title = $this->get_option( 'title' );
		// Mô tả phương thức thanh toán
		$this->description = $this->get_option( 'description' );
		// Lấy tên cửa hàng bán
		$this->appota_merchant_name = $this->get_option( 'appota_merchant_name' );
		// Lấy api key được lưu trong cấu hình
		$this->appota_api_key = $this->get_option( 'appota_api_key' );
		// Lấy api secret được lưu trong cấu hình
		$this->appota_api_secret = $this->get_option( 'appota_api_secret' );
		// Lấy tên log file.
		$this->appota_log_file = $this->get_option( 'appota_log_file' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_api_wc_gateway_appota_payment', array( $this, 'payment_complete' ) );
		if ( !$this->is_valid_for_use() ) {
			$this->enabled = false;
		}
	}

	/**
	 * Cấu hình các trường dữ liệu cần lưu trong quản trị
	 */
	public function init_form_fields() {
		parent::init_form_fields();
		$this->form_fields = array(
			'enabled' => array(
				'title' => __( 'Sử dụng phương thức', 'appota-payment-gateway' ),
				'type' => 'checkbox',
				'label' => __( 'Đồng ý', 'appota-payment-gateway' ),
				'default' => 'yes'
			),
			'title' => array(
				'title' => __( 'Tiêu đề', 'appota-payment-gateway' ),
				'type' => 'text',
				'description' => __( 'Tiêu đề của phương thức thanh toán bạn muốn hiển thị cho người dùng.', 'appota-payment-gateway' ),
				'default' => __( 'Appota Payment', 'appota-payment-gateway' ),
				'desc_tip' => true,
			),
			'description' => array(
				'title' => __( 'Mô tả phương thức thanh toán', 'appota-payment-gateway' ),
				'type' => 'textarea',
				'description' => __( 'Mô tả của phương thức thanh toán bạn muốn hiển thị cho người dùng.', 'appota-payment-gateway' ),
				'default' => __( 'Thanh toán an toàn với Appota Payment. Thực hiện thanh toán với thẻ cào hoặc tài khoản ngân hàng trực tuyến', 'appota-payment-gateway' )
			),
			'account_config' => array(
				'title' => __( 'Cấu hình tài khoản', 'appota-payment-gateway' ),
				'type' => 'title',
				'description' => '',
			),
			'appota_merchant_name' => array(
				'title' => __( 'Tên cửa hàng', 'appota-payment-gateway' ),
				'type' => 'text',
				'description' => __( 'Tên cửa hàng của người bán hàng sử dụng cổng thanh toán Appota Pay.', 'appota-payment-gateway' ),
				'default' => '',
				'desc_tip' => true,
			),
			'appota_api_key' => array(
				'title' => __( 'Appota API Key', 'appota-payment-gateway' ),
				'type' => 'text',
				'description' => __( 'API Key của tài khoản.', 'appota-payment-gateway' ),
				'default' => '',
				'desc_tip' => true,
			),
			'appota_api_secret' => array(
				'title' => __( 'Appota API Secret', 'appota-payment-gateway' ),
				'type' => 'text',
				'description' => __( 'API Secret của tài khoản.', 'appota-payment-gateway' ),
				'default' => '',
				'desc_tip' => true,
			),
			'appota_log_file' => array(
				'title' => __( 'Tên file lưu log', 'appota-payment-gateway' ),
				'type' => 'text',
				'description' => sprintf( __( 'Tên file lưu trữ log trong quá trình thực hiện thanh toán bằng cổng Appota Payment, truy cập file log <code>uploads/appota-payment-gateway/appota-payment-%s.log</code>', 'appota-payment-gateway' ), date( "d-m-Y" ) ),
				'default' => 'appota-payment',
				'desc_tip' => true,
			),
		);
	}

	/**
	 * Kiểm tra xem loại tiền tệ hệ thống dùng thanh toán có phù hợp với cổng thanh toán không
	 *
	 * @access public
	 * @return bool
	 */
	public function is_valid_for_use() {
		if ( !in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_appota_supported_currencies', array( 'VND' ) ) ) )
			return false;
		return true;
	}

	/**
	 * Admin Panel Options
	 * - Hiển thị quản trị cấu hình cho plugins
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
		?>
		<h3>
			<?php echo esc_html__( 'Thanh toán Appota Pay', 'appota-payment-gateway' ); ?>
			
			<p class="pull-right">
				<?php printf( __( 'Hướng dẫn cấu hình hệ thống chi tiết %s', 'appota-payment-gateway' ), '<a href="https://github.com/appotapay/appotapay-wordpress">' . __( 'tại đây', 'appota-payment-gateway' ) . '</a>' ) ?></a>
			</p>
		</h3>

		<strong><?php echo esc_html__( 'Đảm bảo an toàn tuyệt đối cho mọi giao dịch.', 'appota-payment-gateway' ); ?></strong>
		
		<?php if ( $this->is_valid_for_use() ) : ?>
			<table class="form-table">
				<?php
				// Generate the HTML For the settings form.
				$this->generate_settings_html();
				?>
			</table><!--/.form-table-->

		<?php else : ?>
			<div class="inline error">
				<p>
					<strong><?php echo esc_html__( 'Gateway Disabled', 'appota-payment-gateway' ); ?></strong>: <?php _e( 'Phương thức thanh toán Appota Pay chỉ hỗ trợ tiền Việt Nam Đồng trên gian hàng của bạn. Xin hãy đổi loại tiền thanh toán thành Việt Nam Đồng', 'appota-payment-gateway' ); ?>
				</p>
			</div>
		<?php
		endif;
	}

	/**
	 * Check if is error
	 * 
	 * @since 1.1
	 */
	public function is_error( $thing ) {
		if ( is_array( $thing ) && isset( $thing['error'] ) && $thing['error']['code'] != 0 ) {
			return true;
		}

		return false;
	}

	public function process_payment( $order_id ) {

		$logger = new APG_Logger();

		$order = new WC_Order( $order_id );

		// Request sang Appota Payment để lấy đường dẫn trang thanh toán
		$result = $this->receive_payment_url( $order );

		if ( empty( $result ) ) {
			$message = __( 'Không nhận được thông tin trả về!', 'appota-payment-gateway' );
			$logger->writeLog( "Failure: " . $message );
			wc_add_notice( __( 'Payment error:', 'appota-payment-gateway' ) . " " . $message, 'error' );
			return;
		}

		// Nếu có lỗi, hiện thông báo lỗi thanh toán
		if ( $this->is_error( $result ) ) {

			$logger->writeLog( 'Failure: ' . $result['error']['message'] );
			wc_add_notice( __( 'Payment error:', 'appota-payment-gateway' ) . " " . $result['error']['message'], 'error' );
			return;
		}


		// Nếu không có lỗi, chuyển hướng url sang trang thanh toán của Appota
		$appota_payment_url = $result['data']['payment_url'];
		
		$logger->writeLog( "Success: Redirect Payment Url -> " . $appota_payment_url );
		
		return array(
			'result' => 'success',
			'redirect' => $appota_payment_url
		);
	}

	/**
	 * Lấy thông tin đơn hàng, gửi sang cổng thanh toán để nhận đường dẫn redirect
	 * @param mixed $order
	 * @internal param order_id             Mã đơn hàng
	 * @internal param total_amount         Giá trị đơn hàng
	 * @internal param shipping_fee         Phí vận chuyển
	 * @internal param tax_fee              Thuế
	 * @internal param currency_code        Mã tiền tệ
	 * @internal param order_description    Mô tả đơn hàng
	 * @internal param url_success          Url trả về khi thanh toán thành công
	 * @internal param url_cancel           Url trả về khi hủy thanh toán
	 * @internal param url_detail           Url chi tiết đơn hàng
	 * @internal param payer_name           Thông tin thanh toán
	 * @internal param payer_email
	 * @internal param payer_phone_no
	 * @internal param shipping_address
	 * @access public
	 * @return array
	 */
	public function receive_payment_url( WC_Order $order ) {

		// Tạo đường dẫn nhận kết quả trả về sau khi thanh toán thành công
		$url_success = get_bloginfo( 'wpurl' ) . "/wc-api/WC_Gateway_Appota_Payment";
		// Tạo đường dẫn nhận kết quả trả về sau khi thanh toán bị dừng
		$url_cancel = $order->get_cancel_order_url();

		// Tạo mảng thông tin sẽ chuyển sang Appota pay để nhận đường link thanh toán
		$params['order_id'] = $order->get_id();
		$params['total_amount'] = strval( $order->get_total() );
		$params['shipping_fee'] = strval( $order->get_shipping_total() ); //isset($method->no_shipping) ? $method->no_shipping : 0,
		$params['tax_fee'] = '';
		$params['currency_code'] = strval( get_woocommerce_currency() );
		$params['url_success'] = $url_success;
		$params['url_cancel'] = $url_cancel;
		$params['order_description'] = preg_replace( '/[^a-zA-Z0-9\_-]/', '', $order->get_customer_note() );
		$params['payer_name'] = strval( $order->get_billing_first_name() . " " . $order->get_billing_last_name() );
		$params['payer_email'] = strval( $order->get_billing_email() );
		$params['payer_phone_no'] = strval( $order->get_billing_phone() );
		$params['payer_address'] = strval( $order->get_shipping_address_1() );
		$params['ip'] = $this->auto_reverse_proxy_pre_comment_user_ip();
		$params['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

		global $woocommerce;
		$items = $woocommerce->cart->get_cart();
		$items_data = array();

		foreach ( $items as $values ) {
			$product = $values['data']->post;
			$items_data[$values['product_id']]['id'] = $values['product_id'];
			$items_data[$values['product_id']]['name'] = $product->post_title;
			$items_data[$values['product_id']]['quantity'] = $values['quantity'];
			$items_data[$values['product_id']]['price'] = get_post_meta( $values['product_id'], '_price', true );
		}

		$params['product_info'] = json_encode( $items_data );
		$config = array();
		$config['api_key'] = $this->appota_api_key;
		$config['lang'] = $this->lang;
		$config['secret_key'] = $this->appota_api_secret;
		$config['ssl_verify'] = $this->ssl_verify;

		// Gọi resful API của Appota Pay
		$call_api = new APG_Api( $config );
		$result = $call_api->getPaymentUrl( $params );

		return $result;
	}

	public function payment_complete() {

		global $woocommerce;

		$receiver = new APG_Receiver();
		$logger = new APG_Logger();

		$check_valid_request = $receiver->checkValidRequest( $_GET );
		if ( $check_valid_request['error_code'] == 0 ) {
			$check_valid_order = $receiver->checkValidOrder( $_GET );
			if ( $check_valid_order['error_code'] == 0 ) {
				$order_id = (int) $_GET['order_id'];
				$transaction_id = $_GET['transaction_id'];
				$total_amount = floatval( $_GET['amount'] );
				$order = new WC_Order( $order_id );
				$comment_status = sprintf( __( 'Thực hiện thanh toán thành công với đơn hàng %s. Giao dịch hoàn thành. Cập nhật trạng thái cho đơn hàng thành công', 'appota-payment-gateway' ), $order_id );
				$order->add_order_note( $comment_status );
				$order->payment_complete();
				$order->update_status( 'completed' );

				$order->update_meta_data( 'transaction_id', $transaction_id );
				update_post_meta( $order_id, 'appotapay_transaction_id', $transaction_id );
				$woocommerce->cart->empty_cart();
				$order_status = 'complete';
				$message = sprintf( __( 'Appota Pay xác nhận đơn hàng: [Order ID: %s] - [Transaction ID: %s] - [Total: %s] - [%s]', 'appota-payment-gateway' ), $order_id, $transaction_id, $total_amount, $order_status );

				$logger->writeLog( $message );

				wp_redirect( add_query_arg( 'utm_nooverride', '1', $this->get_return_url( $order ) ) );
			} else {
				$message = "Mã Lỗi: {$check_valid_order['error_code']} - Message: {$check_valid_order['message']}";
				$logger->writeLog( $message );

				$redirect_url = add_query_arg( 'wc_error', urlencode( $message . " Hãy thanh toán lại!" ), '/thanh-toan/' );
				wp_redirect( $redirect_url );
			}
		} else {
			$message = "Mã Lỗi: {$check_valid_request['error_code']} - Message: {$check_valid_request['message']}";
			$logger->writeLog( $message );
			$redirect_url = add_query_arg( 'wc_error', urlencode( $message . __( 'Hãy thanh toán lại!', 'appota-payment-gateway' ) ), '/thanh-toan/' );
			wp_redirect( $redirect_url );
		}
	}
	
	public function auto_reverse_proxy_pre_comment_user_ip() {

		$REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];

		if ( !empty( $_SERVER['X_FORWARDED_FOR'] ) ) {
			$X_FORWARDED_FOR = explode( ',', $_SERVER['X_FORWARDED_FOR'] );
			if ( !empty( $X_FORWARDED_FOR ) ) {
				$REMOTE_ADDR = trim( $X_FORWARDED_FOR[0] );
			}
		}
		
		/*
		 * Some php environments will use the $_SERVER['HTTP_X_FORWARDED_FOR'] 
		 * variable to capture visitor address information.
		 */ elseif ( !empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$HTTP_X_FORWARDED_FOR = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
			if ( !empty( $HTTP_X_FORWARDED_FOR ) ) {
				$REMOTE_ADDR = trim( $HTTP_X_FORWARDED_FOR[0] );
			}
		}

		return preg_replace( '/[^0-9a-f:\., ]/si', '', $REMOTE_ADDR );
	}

}
