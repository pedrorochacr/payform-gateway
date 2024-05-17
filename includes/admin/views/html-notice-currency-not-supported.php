<?php
/**
 * Admin View: Notice - Currency not supported.
 *
 * @package Woo_MultiPay/Admin/Notices
 */

if(!defined('ABSPATH')) {
	exit;
}
?>

<div class="error inline">
	<p><strong><?php _e('MultiPay Desabilitado', 'woo-multipay'); ?></strong>: <?php printf(__('A moeda <code>%s</code> não é suportada. Funciona apenas com o Real Brasileiro.', 'woo-multipay'), get_woocommerce_currency()); ?>
	</p>
</div>
