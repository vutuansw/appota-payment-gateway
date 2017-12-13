<?php

class Appota_CallApi
{
    private $API_URL = 'https://api.appotapay.com/';
    private $API_KEY;
    private $SECRET_KEY;
    private $LANG;
    private $API_PRIVATE_KEY;
    private $SSL_VERIFY;
    private $VERSION = 'v1';
    private $METHOD = 'POST';

    public function __construct($config)
    {
        // set params
        $this->API_KEY = $config['api_key'];
        $this->LANG = $config['lang'];
        $this->SECRET_KEY = $config['secret_key'];
        $this->SSL_VERIFY = $config['ssl_verify'];
    }

    /*
    * function get payment bank url
    */
    public function getPaymentUrl($params)
    {
        // build api url
        $api_url = $this->API_URL.$this->VERSION.'/payment/ecommerce?api_key='.$this->API_KEY.'&lang='.$this->LANG;
        
        if(!$this->SECRET_KEY) {
            return array(
                'error' => 111,
                'message' => 'Website chưa nhập api secret key. Không thể thực hiện thanh toán!'
            );
        }

        // request get payment url
        $result = $this->makeRequest($api_url, $params, $this->METHOD);
        return json_decode($result, true);

    }

    /*
     * function make request
     * url : string | url request
     * params : array | params request
     * method : string(POST,GET) | method request
     */
    private function makeRequest($url, $params, $method = 'POST')
    {
        $result = wp_remote_post($url, array(
            'method' => $method,
            'timeout' => 60,
            'body' => $params
        ));
        return $result['body'];

    }
	
}