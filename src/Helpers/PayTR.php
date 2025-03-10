<?php
class PayTR {
    private $merchantId;
    private $merchantKey;
    private $merchantSalt;
    private $debug;
    
    public function __construct($debug = false) {
        $this->merchantId = PAYTR_MERCHANT_ID;
        $this->merchantKey = PAYTR_MERCHANT_KEY;
        $this->merchantSalt = PAYTR_MERCHANT_SALT;
        $this->debug = $debug;
    }
    
    public function createPaymentForm($data) {
        $merchant_oid = $data['transaction_id'];
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $merchant_ok_url = "https://dijitalhediye.com/payment/success";
        $merchant_fail_url = "https://dijitalhediye.com/payment/failed";
        $user_basket = base64_encode(json_encode([
            [$data['package_name'], $data['amount'], 1]
        ]));
        
        $user_name = $data['user_name'];
        $user_email = $data['user_email'];
        $payment_amount = $data['amount'] * 100; // TL to kuruş
        
        $paytr_token = $this->generatePayTRToken([
            'merchant_id' => $this->merchantId,
            'user_ip' => $user_ip,
            'merchant_oid' => $merchant_oid,
            'email' => $user_email,
            'payment_amount' => $payment_amount,
            'user_basket' => $user_basket,
            'debug_on' => $this->debug ? 1 : 0,
            'no_installment' => 1,
            'max_installment' => 0,
            'currency' => 'TL',
            'test_mode' => 0
        ]);
        
        return [
            'action' => 'https://www.paytr.com/odeme',
            'params' => [
                'merchant_id' => $this->merchantId,
                'user_ip' => $user_ip,
                'merchant_oid' => $merchant_oid,
                'email' => $user_email,
                'payment_amount' => $payment_amount,
                'paytr_token' => $paytr_token,
                'user_basket' => $user_basket,
                'debug_on' => $this->debug ? 1 : 0,
                'no_installment' => 1,
                'max_installment' => 0,
                'user_name' => $user_name,
                'merchant_ok_url' => $merchant_ok_url,
                'merchant_fail_url' => $merchant_fail_url,
                'currency' => 'TL',
                'test_mode' => 0
            ]
        ];
    }
    
    private function generatePayTRToken($params) {
        $hash_str = $params['merchant_id'] . $params['user_ip'] . $params['merchant_oid'] . 
                   $params['email'] . $params['payment_amount'] . $params['user_basket'] . 
                   $params['no_installment'] . $params['max_installment'] . 
                   $params['currency'] . $params['test_mode'];
        
        $paytr_token = base64_encode(hash_hmac('sha256', $hash_str . $this->merchantSalt, $this->merchantKey, true));
        return $paytr_token;
    }
    
    public function validateCallback($post_data) {
        $hash = base64_encode(hash_hmac('sha256', $post_data['merchant_oid'] . $this->merchantSalt . 
                                      $post_data['status'] . $post_data['total_amount'], 
                                      $this->merchantKey, true));
        
        if ($hash != $post_data['hash']) {
            return false;
        }
        
        return true;
    }
} 