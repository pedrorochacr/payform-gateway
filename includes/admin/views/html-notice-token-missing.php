<?php
/**
 * Admin View: Notice - Token missing
 *
 * @package Woo_MultiPay/Admin/Notices
 */

if(!defined('ABSPATH')) {
	exit;
}
?>

<div class="error inline">
	<p><strong><?php _e('MultiPay Desabilitado', 'woo-multipay'); ?></strong>: <?php _e('Você deve informar seu EstablishmentCode e MerchantKey.', 'woo-multipay'); ?>
	</p>
</div>
