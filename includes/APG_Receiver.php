<?php

/**
 * APPOTA RECEIVER
 * Class này có chức năng như sau:
 * - Nhận thông tin giao dịch từ cổng Appota Pay
 * - Xác minh dữ liệu nhận được
 * - Ghi log các dữ liệu và thông báo nhận được
 * - Nếu xác minh thông tin Appota Pay gửi về thành công, cập nhật (hoàn thành) đơn hàng
 *
 * Copy Right by Appota Pay
 * @author tieulonglanh
 */
define( 'APPOTA_PAY_TRANSACTION_STATUS_COMPLETED', 1 );

Class APG_Receiver extends APG_Payment_Gateway {

	public function __construct() {
		parent::__construct();
	}

	public function checkValidRequest( $data ) {

		if ( !$this->verifySignature( $data, $this->appota_api_secret ) ) {
			return array(
				'error_code' => 103,
				'message' => esc_html__( 'Sai signature gửi đến. Không thể thực hiện thanh toán!', 'appota-payment-gateway' )
			);
		}

		return array(
			'error_code' => 0,
			'message' => esc_html__( 'Thông tin request thành công!', 'appota-payment-gateway' )
		);
	}

	public function checkValidOrder( $data ) {
		
		$order_id = (int) $data['order_id'];
		$transaction_status = (int) $data['status'];
		$total_amount = floatval( $data['amount'] );

		$confirm = '';

		//Kiểm tra trạng thái giao dịch
		if ( $transaction_status == APPOTA_PAY_TRANSACTION_STATUS_COMPLETED ) {

			//Lấy thông tin order
			if ( $order_id === 0 ) {
				$confirm .= "\r\n" . sprintf( __( ' Không nhận được mã đơn hàng nào : %s', 'appota-payment-gateway' ), $order_id );
				return array(
					'error_code' => 106,
					'message' => $confirm
				);
			}

			//Kiểm tra sự tồn tại của đơn hàng
			$order_info = new WC_Order( $order_id );
			if ( empty( $order_info ) ) {
				$confirm .= "\r\n" . sprintf( __( 'Đơn hàng với mã đơn : %s không tồn tại trên hệ thống', 'appota-payment-gateway' ), $order_id );
				return array(
					'error_code' => 107,
					'message' => $confirm
				);
			}

			//Kiểm tra số tiền đã thanh toán phải >= giá trị đơn hàng
			//Lấy giá trị đơn hàng
			if ( $total_amount < $order_info->order_total ) {
				$confirm .= "\r\n" . sprintf( __( 'Số tiền thanh toán: %s cho đơn hàng có mã : %s nhỏ hơn giá trị của đơn hàng.' . 'appota-payment-gateway' ), $total_amount, $order_id );
				$order_info->update_status( 'on-hold', sprintf( __( 'Thanh toán tạm giữ: %s', 'appota-payment-gateway' ), $confirm ) );
				return array(
					'error_code' => 108,
					'message' => $confirm
				);
			}
		} else {
			$confirm .= "\r\n" . sprintf( __( 'Trạng thái giao dịch:%s chưa thành công với mã đơn hàng : %s', 'appota-payment-gateway' ), $transaction_status, $order_id );
			return array(
				'error_code' => 109,
				'message' => $confirm
			);
		}

		if ( $confirm == '' ) {
			return array(
				'error_code' => 0,
				'message' => esc_html__( 'Đơn hàng hợp lệ', 'appota-payment-gateway' )
			);
		}

		return array(
			'error_code' => 104,
			'message' => $confirm
		);
	}

	private function verifySignature( $data, $secret_key ) {
		$signature = $data['signature'];
		//	unset($data['signature']);
		//  unset($data['wc-api']);
		$sign_data = array(
			'status' => $data['status'],
			'transaction_id' => $data['transaction_id'],
			'order_id' => $data['order_id'],
			'amount' => $data['amount'],
			'shipping_fee' => $data['shipping_fee'],
			'tax_fee' => $data['tax_fee'],
			'transaction_type' => $data['transaction_type'],
			'currency' => $data['currency'],
			'country_code' => $data['country_code'],
			'message' => $data['message'],
		);
		ksort( $sign_data );
		$data_str = implode( '', $sign_data );
		$secret = pack( 'H*', strtoupper( md5( $secret_key ) ) );
		$new_signature = hash_hmac( 'sha256', $data_str, $secret );
		if ( $signature == $new_signature ) {
			return true;
		} else {
			return false;
		}
	}

}
