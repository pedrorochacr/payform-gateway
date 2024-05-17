<?php

/**
 * Gateway class
 * 
 */

if(!defined('ABSPATH')) {
	exit;
}


/**
 * Gateway.
 */
class WC_Multipay_Gateway extends WC_Payment_Gateway
{
	/**
	 * Constructor for the gateway.
	*/
	public function __construct() {
		$this->id                 = 'multipay';
		$this->icon               = apply_filters('woo_multipay_icon', plugins_url('assets/images/multipay.png', plugin_dir_path(__FILE__)));
		$this->method_title       = __('MultiPay', 'woo-multipay');
		$this->method_description = __('Aceite pagamentos usando o MultiPay.', 'woo-multipay');
		$this->order_button_text  = __('Prossiga para o pagamento', 'woo-multipay');

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();
		
		// Define user set variables.
		$this->title             = $this->get_option('title');
		$this->description       = $this->get_option('description');
		$this->merchantKey       = $this->get_option('merchantKey');
		$this->establishmentCode = $this->get_option('establishmentCode');
		$this->link_expiration   = $this->get_option('qrcode_expiration');
		$this->invoice_prefix    = $this->get_option('invoice_prefix');
		$this->debug             = $this->get_option('debug');

		// Active logs.
		if($this->debug == 'yes') {
			if(function_exists('wc_get_logger')) {
				$this->log = wc_get_logger();
			} else {
				$this->log = new WC_Logger();
			}
		}

		// Set the API.
		$this->api = new WC_MultiPay_API($this);

		// Main actions.
		add_action('woocommerce_api_wc_multipay_gateway', array($this, 'process_callback'));
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page_multi'));
		add_action('woocommerce_order_status_cancelled', array($this, 'cancel_payment_multi'));
		add_action('woocommerce_order_status_refunded', array($this, 'cancel_payment_multi'));
		add_action('woocommerce_thankyou', array($this, 'thankyou_page_multi'));

		if(defined('REST_REQUEST') && (REST_REQUEST === true)) {
			add_action('woocommerce_update_order', array($this, 'api_process_payment'));
		}
	}

	/**
	 * Returns a bool that indicates if currency is amongst the supported ones.
	 *
	 * @return bool
	 */
	public function using_supported_currency() {
		return get_woocommerce_currency()  === 'BRL';
	}

	/**
	 * Returns a value indicating the the Gateway is available or not. It's called
	 * automatically by WooCommerce before allowing customers to use the gateway
	 * for payment.
	 *
	 * @return bool
	 */
	public function is_available() {
		// Test if is valid for use.
		$available = $this->get_option('enabled') === 'yes' && $this->merchantKey !== '' && $this->establishmentCode !== '' && $this->using_supported_currency();

		if(!class_exists('Extra_Checkout_Fields_For_Brazil')) {
			$available = false;
		}

		return $available;
	}

	/**
	 * Get log.
	 *
	 * @return string
	 */
	protected function get_log_view() {
		if(defined('WC_VERSION') && version_compare(WC_VERSION, '2.2', '>=')) {
			return '<a href="' . esc_url(admin_url('admin.php?page=wc-status&tab=logs&log_file=' . esc_attr($this->id) . '-' . sanitize_file_name(wp_hash($this->id)) . '.log')) . '">' . __('System Status &gt; Logs', 'woo-multipay') . '</a>';
		}

		return '<code>woocommerce/logs/' . esc_attr($this->id) . '-' . sanitize_file_name(wp_hash($this->id)) . '.txt</code>';
	}

    /**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'              => array(
				'title'   => __('Ativar/Desativar', 'woo-multipay'),
				'type'    => 'checkbox',
				'label'   => __('Ativar MultiPay', 'woo-multipay'),
				'default' => 'yes',
			),
			'title'                => array(
				'title'       => __('Título', 'woo-multipay'),
				'type'        => 'text',
				'description' => __('Isso controla o título que o usuário vê durante o checkout.', 'woo-multipay'),
				'desc_tip'    => true,
				'default'     => __('MultiPay', 'woo-multipay'),
			),
			'description'          => array(
				'title'       => __('Descrição', 'woo-multipay'),
				'type'        => 'textarea',
				'description' => __('Isso controla a descrição que o usuário vê durante o checkout.', 'woo-multipay'),
				'default'     => __('Pagar Via MultiPay', 'woo-multipay'),
			),
			'merchantKey'         => array(
				'title'       => __('MerchantKey', 'woo-multipay'),
				'type'        => 'text',
				/* translators: %s: link to MultiPay settings */
				'description' => sprintf(__('Digite seu MerchantKey. Isso é necessário para processar os pagamentos e as notificações. É possível gerar um novo token %s.', 'woo-multipay'), '<a href="https://forms.gle/ZqhYSWp5gUKzoiBH6" target="_blank">' . __('aqui', 'woo-multipay') . '</a>'),
				'default'     => '',
			),
			'establishmentCode'         => array(
				'title'       => __('EstablishmentCode', 'woo-multipay'),
				'type'        => 'text',
				'description' => __('Digite seu EstablishmentCode.', 'woo-multipay'),
				'default'     => '',
			),
			'qrcode_expiration'    => array(
				'title'       => __('Expiração do Pagamento', 'woo-multipay'),
				'type'        => 'checkbox',
				'label'       => __('Ativar expiração do Pagamento', 'woo-multipay'),
				'default'     => 'no',
				'description' => sprintf(__('A expiração do Pagamento funciona apenas se o <a href="%s" target="_blank">Gerenciar estoque do WooCommerce</a> estiver ativado.<br />O tempo de expiração é controlado em <a href="%s">WooCommerce > Configurações > Produtos > Inventário > Manter estoque (minutos)</a>', 'woo-multipay'), esc_url('https://docs.woocommerce.com/document/configuring-woocommerce-settings/#inventory-options'), esc_url(admin_url('admin.php?page=wc-settings&tab=products&section=inventory'))),
			),
			'invoice_prefix'         => array(
				'title'       => __('Prefixo de pagamento', 'woo-multipay'),
				'type'        => 'text',
				'description' => __('Insira um prefixo para os números de sua fatura. Se você usar sua conta do MultiPay para várias lojas, certifique-se de que esse prefixo não seja válido, pois o MultiPay não permitirá pagamentos com o mesmo número de fatura.', 'woo-multipay'),
				'desc_tip'    => true,
				'default'     => 'WC-',
			),
			'debug'                => array(
				'title'       => __('Registro de depuração', 'woo-multipay'),
				'type'        => 'checkbox',
				'label'       => __('Ativar registro', 'woo-multipay'),
				'default'     => 'no',
				/* translators: %s: log page link */
				'description' => sprintf(__('Grava eventos do MultiPay, como solicitações de API, dentro do arquivo %s', 'woo-multipay'), $this->get_log_view()),
			),
		);
	}
	
/**
	 * Admin page.
	 */
	public function admin_options() {
		include dirname(__FILE__) . '/admin/views/html-admin-page.php';
	}
	
	/**
	 * Send email notification.
	 *
	 * @param string $subject Email subject.
	 * @param string $title   Email title.
	 * @param string $message Email message.
	 */
	protected function send_email($subject, $title, $message) {
		$mailer = WC()->mailer();

		$mailer->send(get_option('admin_email'), $subject, $mailer->wrap_message($title, $message));
	}
	
	/**
	 * Process the payment and return the result.
	 *
	 * @param  int $order_id Order ID.
	 * @param  boolean $is_rest_api Order created from the REST API.
	 * @return mixed
	 */
	public function process_payment($order_id, $is_rest_api = false) {
		$response = array();
		$order = wc_get_order($order_id);
		
		// Check if MultiPay PaymentURL already exists. 
		$response['pagamentoSr'] = $order->get_meta('MultiPay_PaymentURL');
		if(!$response['pagamentoSr']) {
			do_action('woo_multipay_checkout_request_before', $order);

			$response = $this->api->do_checkout_request($order);
			
			if($response['pagamentoSr']) {
				$order->add_meta_data('MultiPay_PaymentURL', $response['pagamentoSr'], true);
				if($is_rest_api) {
					//$order->add_meta_data('Multi_QRCode', $response['data']['qrcode_base64'], true);
					$order->add_meta_data('referenceId', $response['referenceId'], true);
					$order->add_meta_data('expiresDt', $response['expiresDt'], true);
					$order->add_meta_data('pagamentoSr', $response['pagamentoSr'], true);
				}

				$order->save();

				do_action('woo_multipay_checkout_request_after', $response, $order);
			}
		}

		if($response['pagamentoSr']) {
			if($is_rest_api) {
				$order->add_order_note(__('MultiPay: A transação iniciou a partir da API REST, mas até o momento o MultiPay não recebeu nenhuma informação de pagamento.', 'woo-multipay'));

				return;
			}

			// Remove cart.
			WC()->cart->empty_cart();
			
			$order->add_order_note(__('MultiPay: O comprador iniciou a transação, mas até o momento o MultiPay não recebeu nenhuma informação de pagamento.', 'woo-multipay'));
			
			$url_redirect = $response['pagamentoSr'];
			if(wp_is_mobile()) {
				$url_redirect = $this->get_return_url($order);
			}

			return array(
				'result'   => 'success',
				'redirect' => $url_redirect,
			);
		}
		else {
			foreach($response['error'] as $error) {
				wc_add_notice($error, 'error');
			}
        
			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}
	}
	
	/**
	 * Output for the order received page.
	 *
	 * @param int $order_id Order ID.
	 */
	public function receipt_page_multi($order_id) {
		$order = wc_get_order($order_id);
		$payment_url = $order->get_meta('MultiPay_PaymentURL');
		
		if($order->get_status() == 'pending') {
			if(!empty($payment_url)) {
				wp_redirect($payment_url, 302);
			}
			else {
				include dirname(__FILE__) . '/views/html-receipt-page-error.php';
			}
		}
	}

	/**
	 * Output for the thank you page.
	 *
	 * @param int $order_id Order ID.
	 */
	public function thankyou_page_multi($order_id) {
		$order = wc_get_order($order_id);

		if($order->get_status() == 'pending') {
			$payment_url = $order->get_meta('MultiPay_PaymentURL');

			if(!empty($payment_url)) {
				@ob_clean();
				include dirname(__FILE__) . '/views/html-open-multipay.php';
			}
		}
	}

	/**
	 * Process callback.
	 */
	public function process_callback() {
		@ob_clean();
		$payment = $this->api->process_callback();
		if(is_array($payment)) {
			$order_id = intval(str_replace($this->invoice_prefix, '', $payment['referenceId']));
			$order = wc_get_order($order_id);
			$cancellation_id = $order->get_meta('MultiPay_cancellationId');
			
			if(($payment['status'] == 'refunded') && empty($cancellation_id)) {
				$payment['cancellationId'] = __('Pagamento reembolsado diretamente pelo MultiPay.', 'woo-multipay');
			}
			
			$this->update_order_status($payment);

			do_action('woo_multipay_callback', $payment, $order);
		}
		exit;
	}
	
	/**
	 * Save payment meta data.
	 *
	 * @param WC_Order $order Order instance.
	 * @param array $payment Payment Status.
	 */
	protected function save_payment_meta_data($order, $payment) {
		foreach($payment as $key => $value) {
			if(($key != 'referenceId') && ($key != 'status')) {
				$order->add_meta_data('MultiPay_' . $key, $value, true);
			}
		}
		$order->save();
	}
	
	/**
	 * Update order status.
	 *
	 * @param array $payment Payment Status.
	 */
	public function update_order_status($payment) {
		$id = intval(str_replace($this->invoice_prefix, '', $payment['referenceId']));
		$order = wc_get_order($id);

		// Check if order exists.
		if(!$order) {
			return;
		}
		
		$order_id = method_exists($order, 'get_id') ? $order->get_id() : $order->id;

		if($this->debug == 'yes') {
			$this->log->add($this->id, 'Status de pagamento MultiPay para pedido ' . $order->get_order_number() . ' is: ' . $payment['status']);
		}
		
		// Save meta data.
		$this->save_payment_meta_data($order, $payment);
		
		switch($payment['status']) {
			case 'expired':
				if(($order->get_status() == 'pending') || ($order->get_status() == 'on-hold')) {
					$order->update_status('cancelled', __('MultiPay: Pagamento expirado.', 'woo-multipay'));
				}

				break;
			case 'analysis':
				$order->update_status('on-hold', __('MultiPay: Pagamento em análise.', 'woo-multipay'));
				wc_reduce_stock_levels($order_id);
				
				break;
			case 'paid':
				if($order->get_status() == 'pending') {
					wc_reduce_stock_levels($order_id);
				}
				$order->update_status('processing', __('MultiPay: Pagamento aprovado.', 'woo-multipay'));
			
				break;
			case 'completed':
				$order->add_order_note(__('MultiPay: Pagamento concluído e creditado em sua conta.', 'woo-multipay'));
				
				break;
			case 'refunded':
				if($order->get_status() != 'refunded') { // Prevents repeat refunded.
					$order->update_status('refunded', __('MultiPay: Pagamento reembolsado.', 'woo-multipay'));
					wc_increase_stock_levels($order_id);
				}
				else {
					$order->add_order_note(__('MultiPay: Pagamento reembolsado.', 'woo-multipay'));
				}
				
				$this->send_email(
					/* translators: %s: order number */
					sprintf(__('Pagamento do pedido %s reembolsado', 'woo-multipay'), $order->get_order_number()),
					__('Pagamento reembolsado', 'woo-multipay'),
					/* translators: %s: order number */
					sprintf(__('Pedido %s foi marcado como reembolsado pelo MultiPay.', 'woo-multipay' ), $order->get_order_number())
				);
			
				break;
			case 'chargeback':
				$order->update_status('refunded', __('MultiPay: Estorno de pagamento.', 'woo-multipay'));
				
				break;
			default:
				break;
		}
	}
	
	/**
	 * Cancel payment.
	 *
	 * @param  int $order_id Order ID.
	 */
	public function cancel_payment_multi($order_id) {
		$order = wc_get_order($order_id);

		if($order->get_payment_method() !== 'multipay') {
			return;
		}

		$cancellation_id = $order->get_meta('MultiPay_cancellationId');
		
		if(empty($cancellation_id)) { // Prevents repeat refunded.
			do_action('woo_multipay_payment_cancel_before', $order);

			$payment = $this->api->do_payment_cancel($order);
				
			if(is_array($payment)) {
				$this->save_payment_meta_data($order, $payment);

				do_action('woo_multipay_payment_cancel_after', $payment, $order);
			}
		}
	}

	/**
	 * Process the payment order created from the REST API.
	 *
	 * @param  int $order_id Order ID.
	 */
	public function api_process_payment($order_id) {
		$order = wc_get_order($order_id);

		if(($order->get_payment_method() === 'multipay') && (!$order->get_meta('MultiPay_PaymentURL'))) {
			$this->process_payment($order_id, true);
		}
	}
}
