<?php
/*
 * Plugin MultiPay para WordPress
 * 
 * Plugin Name:     	 Multifidelidade - MultiPay Pagamentos - for WooCommerce
 * Plugin URI:      	 https://github.com/dinhoguitars/woo-multipay/
 * Description:     	 Plugin for WooCommerce platform to make online payments (PicPay, Pix, Debit and Credit) as a payment gateway to WooCommerce.
 * Author:          	 Multifidelidade
 * Author URI:        	 https://multifidelidade.app/
 * Text Domain:     	 woo-multipay
 * Domain Path:     	 /languages
 * License:              GPLv3 or later 
 * WC requires at least: 5.0.0
 * WC tested up to:      6.9.4
 * Version:        		 0.1.1
 * 
 * This is a Plugin to make online payments (PicPay, Pix, ApplePay, GooglePay, Link, Debit and Credit).
 * 
 * MultiPay for WooCommerce is free software: you can
 * redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation,
 * either version 3 of the License, or any later version.
 *
 * MultiPay for WooCommerce is distributed in the hope that
 * it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.

 * 
 * You should have received a copy of the GNU General Public License
 * along with Multifidelidade - MultiPay for WooCommerce. If not, see
 * <https://www.gnu.org/licenses/gpl-3.0.txt>.
 *
 * @package         	 Api_Multipay
 */

//use LPT\Includes\WC_MultiPay;

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define('WC_MULTIPAY_VERSION', '0.1.1');
define('WC_MULTIPAY_PLUGIN_FILE', __FILE__);

/*define('WC_MULTIPAY_PLUGIN_PATH', untrailingslashit( plugin_dir_path( WC_MULTIPAY_PLUGIN_FILE) ));
define('WC_MULTIPAY_PLUGIN_URL', untrailingslashit( plugins_url( '/', WC_MULTIPAY_PLUGIN_FILE) ));


if (file_exists(WC_MULTIPAY_PLUGIN_PATH . '/vendor/autoload.php')) {
	require_once WC_MULTIPAY_PLUGIN_PATH . '/vendor/autoload.php';
}

require_once WC_MULTIPAY_PLUGIN_PATH . '/includes/Plugin.php';*/


/*
if (class_exists('WC_MultiPay')){


	function PAY() { 
		return WC_MultiPay::getInstance();
	}

	add_action('plugins_loaded',array(PAY(),'init'));


	/*
	// activation
	register_activation_hook(WC_MULTIPAY_PLUGIN_FILE, array(PAY(), 'activate'));

	// deactivation
	register_deactivation_hook(WC_MULTIPAY_PLUGIN_FILE, array(PAY(), 'deactivate'));

	// unistall
	register_uninstall_hook(WC_MULTIPAY_PLUGIN_FILE, array(PAY(), 'unistall'));

}*/


if(!class_exists('WC_MultiPay')) {
	include_once dirname(__FILE__) . '/includes/wcClassMultiPay.php';
	add_action('plugins_loaded', array('WC_MultiPay', 'init'));
}


