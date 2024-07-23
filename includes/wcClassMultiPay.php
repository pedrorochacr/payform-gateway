<?php

/*namespace LPT\Includes;

use LPT\Includes\Activate;
use LPT\Includes\Deactivate;*/

defined( 'ABSPATH' ) || exit;


class WC_MultiPay
{

	/*
	* Initialize the plugin public actions.
	*/
	public static function init() {
		// Load plugin text domain.
		add_action('init', array(__CLASS__, 'load_plugin_textdomain'));

		$teste = array(__CLASS__, 'load_plugin_textdomain');

		// Checks with WooCommerce is installed.
		if(class_exists('WC_Payment_Gateway')) {
			self::includes();
			
			add_filter('woocommerce_payment_gateways', array(__CLASS__, 'add_gateway'));
			add_filter('woocommerce_available_payment_gateways', array(__CLASS__, 'hides_when_is_outside_brazil'));
			add_filter('plugin_action_links_' . plugin_basename(WC_MULTIPAY_PLUGIN_FILE), array(__CLASS__, 'plugin_action_links'));
			
			if(is_admin()) {
				add_action('admin_notices', array(__CLASS__, 'ecfb_missing_notice'));
			}
		} else {
			add_action('admin_notices', array(__CLASS__, 'woocommerce_missing_notice'));
		}
	}

	/*
	public function activate()
	{
		Activate::activate();
	}

	public function deactivate()
	{
		Deactivate::deactivate();
	}*/

	 /**
	 * Load the plugin text domain for translation.
	 */
	public static function load_plugin_textdomain() {
		load_plugin_textdomain('woo-multipay', false, dirname(plugin_basename(WC_MULTIPAY_PLUGIN_FILE)) . '/languages/');
	}
	
	/**
	 * Action links.
	 *
	 * @param array $links Action links.
	 *
	 * @return array
	 */
	public static function plugin_action_links($links) {
		$plugin_links   = array();
		$plugin_links[] = '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=multipay')) . '">' . __('Configurações', 'woo-multipay') . '</a>';

		return array_merge($plugin_links, $links);
	}
	
	/**
	 * Includes.
	 */
	private static function includes() {
		include_once dirname(__FILE__) . '/wcClassMultiPayApi.php';
		include_once dirname(__FILE__) . '/wcClassMultiPayGateway.php';
	}
	
	/**
	 * Add the gateway to WooCommerce.
	 *
	 * @param  array $methods WooCommerce payment methods.
	 *
	 * @return array Payment methods with MultiPay.
	 */
	public static function add_gateway($methods) {
		$methods[] = 'WC_Multipay_Gateway';

		return $methods;
	}
	
	/**
	 * Hides the MultiPay with payment method with the customer lives outside Brazil.
	 *
	 * @param   array $available_gateways Default Available Gateways.
	 *
	 * @return  array                     New Available Gateways.
	 */
	public static function hides_when_is_outside_brazil($available_gateways) {
		// Remove MultiPay gateway.
		if(isset($_REQUEST['country']) && $_REQUEST['country'] !== 'BR') {
			unset($available_gateways['multipay']);
		}

		return $available_gateways;
	}
	
	/**
	 * Brazilian Market on WooCommerce notice.
	 */
	public static function ecfb_missing_notice() {
		if(!class_exists('Extra_Checkout_Fields_For_Brazil')) {
			include dirname(__FILE__) . '/admin/views/html-notice-missing-ecfb.php';
		}
	}
	
	/**
	 * WooCommerce missing notice.
	 */
	public static function woocommerce_missing_notice() {
		include dirname(__FILE__) . '/admin/views/html-notice-missing-woocommerce.php';
	}

}