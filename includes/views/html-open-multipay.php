<?php
/**
 * Open MultiPay template
 *
 * @package Woo_MultiPay/Templates
 */
if(!defined('ABSPATH')) {
	exit;
}
?>

<a class="button alt" href="<?php echo esc_url($payment_url); ?>" target="_blank"><?php echo __('Open MultiPay', 'woo-multipay'); ?></a>
