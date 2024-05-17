<?php

if(!defined('ABSPATH')) {
	exit;
}

/*
* API.
*/
/**
 * API.
 */
class WC_MultiPay_API {

	/**
	 * Gateway class.
	 *
	 * @var WC_MultiPay_Gateway
	 */
	protected $gateway;

	/**
	 * Constructor.
	 *
	 * @param WC_MultiPay_Gateway $gateway Payment Gateway instance.
	 */
	public function __construct($gateway = null) {
		$this->gateway = $gateway;
	}

	/**
	 * Get the payment Token MultiPay.
	 *
	 * @return string.
	 */
	protected function get_payment_token() {
		return 'https://api.multifidelidade.app/multipay/pagamentos/auth/token';
	}
	
	
	/**
	 * Get the payment URL.
	 *
	 * @return string.
	 */
	protected function get_payment_url() {
		return 'https://api.multifidelidade.app/multipay/pagamentos/pagar';
	}
	
	/**
	 * Get the status URL.
	 *
	 * @param  string $order_id Order ID.
	 *
	 * @return string.
	 */
	protected function get_status_url($order_id) {
		return 'https://api.multifidelidade.app/multipay/pagamentos/consultar/' . $order_id;
	}
	
	/**
	 * Get the cancellation URL.
	 *
	 * @param  string $order_id Order ID.
	 *
	 * @return string.
	 */
	protected function get_cancellation_url($order_id) {
		return 'https://api.multifidelidade.app/multipay/pagamentos/cancelar/' . $order_id;
	}
	
	/**
	 * Do requests in the MultiPay API.
	 *
	 * @param  string $url      URL.
	 * @param  string $method   Request method.
	 * @param  array  $data     Request data.
	 * @param  array  $headers  Request headers.
	 *
	 * @return array            Request response.
	 */
	protected function do_request($url, $method = 'POST', $data = array(), $headers = array()) {
		$params = array(
			'method'  => $method,
			'timeout' => 60,
		);

		if($method == 'POST' && !empty($data)) {
			$params['body'] = $data;
		}

		if(!empty($headers)) {
			$params['headers'] = $headers;
		}

		return wp_safe_remote_post($url, $params);
	}
	
	
	/**
	 * Get the headers.
	 *
	 * @return array.
	 */
	protected function get_request_headers($token) {
		return array(
						'Authorization' => 'Bearer ' . $token,						
						'Content-Type' => 'application/json',
						'Accept' => 'application/json'
					);
	}

	/**
	 * Get the headers token.
	 *
	 * @return array.
	 */
	protected function get_request_token() {
		$header = array(						
						'Content-Type' => 'application/json',
						'Accept' => 'application/json'
					);

	    $json = json_encode(array(
			'establishmentCode' => $this->gateway->establishmentCode,
			'merchantKey' => $this->gateway->merchantKey
		), true);

	    $response = $this->do_request($this->get_payment_token(), 'POST', $json, $header);

		if($response['response']['code'] === 200) {
			if($this->gateway->debug == 'yes') {
				$this->gateway->log->add($this->gateway->id, 'MultiPay Payment URL created with success! The return is: '
					. print_r($body, true));
			}

			$body = json_decode($response['body'], true);
			return $body['token'];
		}
		else return "";
	}
	
	/**
	 * Get the checkout json.
	 *
	 * @param WC_Order $order Order data.
	 * @param array    $posted Posted data.
	 *
	 * @return string
	 */
	protected function get_checkout_json($order) {
		$cellphone = $order->get_meta('_billing_cellphone');
		$document = $order->get_meta('_billing_cpf');
		
		if(empty($cellphone)) {
			$cellphone = $order->get_billing_phone();
		}
		
		if($order->get_meta('_billing_persontype') == '2') {
			$document = $order->get_meta('_billing_cnpj');
		}
		
		$buyer = array(
					'firstName' => $order->get_billing_first_name(),
					'lastName' => $order->get_billing_last_name(),
					'document' => $document,
					'email' => $order->get_billing_email(),
					'phone' => $cellphone
				);			
				
				/*
				"referenceId": "0000001",
				"callbackUrl": "http://www.sualoja.com.br/callback",
				"returnUrl": "http://www.sualoja.com.br/cliente/pedido/0000001",
				"value": "centavo 0,01 = 1  | 1,00 = 100 | 1,50 = 150",
				"cpf": "99999999999",
				"message": "Você vai ganhar x Cristais!",
				"softDescriptor": "string"		
				*/

		if($this->gateway->link_expiration === 'yes') {
			$manage_stock = WC_Admin_Settings::get_option('woocommerce_manage_stock', 'no');
			$hs_minutes = WC_Admin_Settings::get_option('woocommerce_hold_stock_minutes', '');
			if(($manage_stock === 'yes') && is_numeric($hs_minutes)) {
				$ct_datetime = current_datetime();
				$dt_interval = new DateInterval('PT' . $hs_minutes . 'M');
				$expires_at = $ct_datetime->add($dt_interval); // Add minutes
				$payment['expiresAt'] = $expires_at->format('c'); // Date format ISO 8601
			}
		}

		$payment = array(
			'referenceId' => $this->gateway->invoice_prefix . $order->get_id(),
			'callbackUrl' => WC()->api_request_url('WC_MultiPay_Gateway'),
			'returnUrl' => $this->gateway->get_return_url($order),
			'value' => $order->get_total(),
			'cpf' => $document,
			'nome' => $order->get_billing_first_name()." ".$order->get_billing_last_name(),
			'email' => $order->get_billing_email(),
			'telefone' => $cellphone,
			'buyer' => $buyer,
			'expiresDt' => $payment['expiresAt']
		);

		return json_encode($payment);
	}
	
	/**
	 * Do checkout request.
	 *
	 * @param  WC_Order $order  Order data.
	 *
	 * @return array
	 */
	public function do_checkout_request($order) {
		// Sets the json.
		$json = $this->get_checkout_json($order);
		$body = '';
		
		if($this->gateway->debug == 'yes') {
			$this->gateway->log->add($this->gateway->id, 'Get payment request for order ' . $order->get_order_number() . ' with the following data: ' . $json);
		}

		//Busca Token
		$token = $this->get_request_token();
			
		$response = $this->do_request($this->get_payment_url(), 'POST', $json, $this->get_request_headers($token));

		if(is_wp_error($response)) {
			if($this->gateway->debug == 'yes') {
				$this->gateway->log->add($this->gateway->id, 'WP_Error in generate payment request: ' . $response->get_error_message());
			}
		}
		else {
			$body = json_decode($response['body'], true);
			
			if(json_last_error() != JSON_ERROR_NONE) {
				if($this->gateway->debug == 'yes') {
					$this->gateway->log->add($this->gateway->id, 'Error while parsing the MultiPay response: ' . print_r($response, true));
				}
			}
		}
		
		if($response['response']['code'] === 200) {
			if($this->gateway->debug == 'yes') {
				$this->gateway->log->add($this->gateway->id, 'MultiPay Payment URL created with success! The return is: '
					. print_r($body, true));
			}

			/*"referenceId": "100235",
  			  "pagamentoSr": "https://multifidelidade.app/mobile/pay/pagar?idP=bHrPU1p6SVk=",
  			  "expiresDt": "2023-03-01T17:00:00-02:00"
  			*/

			return array(
				'referenceId'   => $body['referenceId'],
				'pagamentoSr'  =>  $body['pagamentoSr'],
				'expiresDt'  =>  $body['expiresDt'],
				'error' => ''
			);

			/*
			return array(
				'url'   => $body['paymentUrl'],
				'data'  => array(
					'qrcode_base64' => $body['qrcode']['base64'],
					'expires_at'    => $body['expiresAt']
				),
				'error' => ''
			);
			*/
		}
		else if($response['response']['code'] === 401) {
			if($this->gateway->debug == 'yes') {
				$this->gateway->log->add($this->gateway->id, 'Invalid token settings!');
			}

			return array(
				'referenceId'   => '',
				'pagamentoSr'  =>  '',
				'expiresDt'  =>  '',
				'error' => array(__('Muito ruim! Os tokens do MultiPay são inválidos meu amiguinho!', 'woo-multipay'))
			);
		}
		else if(($response['response']['code'] === 422) || ($response['response']['code'] === 500)) {
			if(isset($body['message'])) {
				$errors = array();

				if($this->gateway->debug == 'yes') {
					$this->gateway->log->add($this->gateway->id, 'Falha ao gerar a URL de pagamento do MultiPay: ' . print_r( $response, true ) );
				}

				return array(
					'referenceId'   => '',
					'pagamentoSr'  =>  '',
					'expiresDt'  =>  '',
					'error' => array($body['message']),
				);
			}
		}
		
		if($this->gateway->debug == 'yes') {
			$this->gateway->log->add($this->gateway->id, 'Erro ao gerar a URL de pagamento do MultiPay:' . print_r($response, true));
		}

		// Return error message.
		return array(
			'referenceId'   => '',
			'pagamentoSr'  =>  '',
			'expiresDt'  =>  '',
			'error' => array('<strong>' . __('MultiPay', 'woo-multipay') . '</strong>: ' . __('Ocorreu um erro ao processar seu pagamento, tente novamente. Ou entre em contato conosco para obter assistência.', 'woo-multipay'))
		);
	}
	
	/**
	 * Process callback.
	 *
	 * @return array | boolean
	 */
	public function process_callback() {
		$payment = array();
		
		if($this->gateway->debug == 'yes') {
			$this->gateway->log->add($this->gateway->id, 'Verificando solicitação de RETORNO DE CHAMADA...');
		}
		
		// Checks the Seller Token.
		if($_SERVER['HTTP_X_SELLER_TOKEN'] != $this->gateway->seller_token) {
			if($this->gateway->debug == 'yes') {
				$this->gateway->log->add($this->gateway->id, 'Solicitação de CALLBACK inválida, token de vendedor inválido.');
			}
			
			return false;
		}
		
		$payment = file_get_contents("php://input");
		$payment = json_decode($payment, true);
		if(json_last_error() != JSON_ERROR_NONE) {
			if($this->gateway->debug == 'yes') {
				$this->gateway->log->add($this->gateway->id, 'Solicitação de CALLBACK inválida: ' . print_r($payment, true));
			}
			
			return false;
		}
		else {
			if($this->gateway->debug == 'yes') {
				$this->gateway->log->add($this->gateway->id, 'A solicitação CALLBACK está OK.');
			}
		}
		
		if($this->gateway->debug == 'yes') {
			$this->gateway->log->add($this->gateway->id, 'Obter status de pagamento do pedido ' . $payment['referenceId']);
		}
		
		//Busca Token
		$token = $this->get_request_token();

		// Get payment Status.		
		$res_status = $this->do_request($this->get_status_url($payment['referenceId']), 'GET', array(), $this->get_request_headers($token));
		$res_status = json_decode($res_status['body'], true);
		
		if(json_last_error() != JSON_ERROR_NONE) {
			if($this->gateway->debug == 'yes') {
				$this->gateway->log->add($this->gateway->id, 'Erro ao analisar a resposta de status de pagamento do MultiPay: ' . print_r($res_status, true));
			}
			
			return false;
		}
		
		if(array_key_exists('status', $res_status)) {
			if($this->gateway->debug == 'yes') {
				$this->gateway->log->add($this->gateway->id, 'A resposta de status do MultiPay é válida! O retorno é: ' . print_r($res_status, true));
			}
			
			$payment['status'] = $res_status['status'];
			
			return $payment;
		}
		
		if($this->gateway->debug == 'yes') {
			$this->gateway->log->add($this->gateway->id, 'Resposta do status de pagamento do MultiPay: ' . print_r($res_status, true));
		}
		
		return false;
	}
	
	/**
	 * Do payment cancel.
	 *
	 * @param  WC_Order $order  Order data.
	 *
	 * @return array | boolean
	*/
	public function do_payment_cancel($order) {
		$json = '';
		$order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;
		$order_id = $this->gateway->invoice_prefix . strval($order_id);
		$authorization_id = $order->get_meta('MultiPay_authorizationId');
		
		if(!empty($authorization_id)) {
			$json = json_encode(array('authorizationId' => $authorization_id));
			
			if($this->gateway->debug == 'yes') {
				$this->gateway->log->add($this->gateway->id, 'Obter cancelamento de pagamento para pedido ' . $order->get_order_number() . ' and refund with the authorizationId: ' . $authorization_id);
			}
		}
		else {
			if($this->gateway->debug == 'yes') {
				$this->gateway->log->add($this->gateway->id, 'Obter cancelamento de pagamento para pedido ' . $order->get_order_number());
			}
		}
		
		//Busca Token
		$token = $this->get_request_token();
		
		$payment = $this->do_request($this->get_cancellation_url($order_id), 'POST', $json, $this->get_request_headers($token));
		
		if($payment['response']['code'] === 200) {
				if($this->gateway->debug == 'yes') {
					$this->gateway->log->add($this->gateway->id, 'Resposta de cancelamento de pagamento MultiPay OK.');
				}
		}
		
		$payment = json_decode($payment['body'], true);
			
		if(json_last_error() != JSON_ERROR_NONE) {
			if($this->gateway->debug == 'yes') {
				$this->gateway->log->add($this->gateway->id, 'Erro ao analisar a resposta de cancelamento de pagamento do MultiPay: ' . print_r($payment, true));
			}
			
			return false;
		}
		
		return $payment;
	}
}
