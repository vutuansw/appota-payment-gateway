<?php

/**
 * APPOTA LOG
 * Class có chức năng như sau:
 * Tạo file log thông tin thanh toán
 * Ghi thông tin vào file log
 */

Class APG_Logger extends APG_Payment_Gateway {

	protected $my_log_file;
	protected $dir_log;

	public function __construct() {
		parent::__construct();

		$upload = wp_upload_dir();
		$this->dir_log = $upload['basedir'] . '/appota-payment-gateway/';

		$apr_file_log = $this->getAPRFileLog();
		$this->my_log_file = $this->dir_log . $apr_file_log . "-" . date( "d-m" ) . ".log";
		$this->isFileORDirExist( $this->dir_log, $this->my_log_file );
	}

	/**
	 *
	 * @param string $message
	 */
	public function writeLog( $message ) {
		$file_log = $this->my_log_file;
		$fh = fopen( $file_log, 'a' ) or die( "can't open file" );
		fwrite( $fh, "\r\n" . "-----------------------------------------------------" );
		fwrite( $fh, "\r\n" . date( "Y-m-d H:i:s" ) . " --- | --- " . $message );
	}

	/**
	 * Hàm lấy lấy và kiểm tra tên file log do người dùng cấu hình trong trang quản trị.
	 * Loại bỏ ký tự đặc biệt, nếu rỗng hoặc có dấu cách tên file mặc định là bpn
	 * @return mixed
	 */
	private function getAPRFileLog() {
		$apr_file_log = preg_replace( '/[^a-zA-Z0-9\_-]/', '', $this->appota_log_file );
		if ( !empty( $apr_file_log ) ) {
			$this->file_log_name = $apr_file_log;
		}
		return $this->file_log_name;
	}

	/**
	 * Hàm kiểm tra sự tồn tại của file log. Thực hiện tạo mới nếu file không tồn tại
	 * @param $dir      string Tên thư mục
	 * @param $fileName string Tên file
	 */
	private function isFileORDirExist( $dir, $fileName ) {
		if ( $dir != '' ) {
			if ( !is_dir( $dir ) ) {
				wp_mkdir_p( $dir );
			}
		}
		if ( $fileName != '' ) {
			if ( !file_exists( $fileName ) ) {
				$ourFileHandle = fopen( $fileName, 'w' ) or die( "can't open file" );
				fclose( $ourFileHandle );
			}
		} else {
			die;
		}
	}

}
