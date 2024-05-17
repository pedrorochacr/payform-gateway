<?php
/**
 * Receipt page error template
 *
 * @package Woo_MultiPay/Templates
 */
if(!defined('ABSPATH')) {
	exit;
}
?>

<ul class="woocommerce-error">
	<li><?php echo __('MultiPay Payment URL not found.', 'woo-multipay'); ?></li>
</ul>

<a class="button cancel" href="<?php echo esc_url($order->get_cancel_order_url()); ?>"><?php esc_html_e('Click to try again', 'woo-multipay'); ?></a>
