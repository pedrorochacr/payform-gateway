<?php
/**
 * Missing Brazilian Market on WooCommerce notice
 *
 * @package Woo_MultiPay/Admin/Notices
 */

if(!defined('ABSPATH')) {
	exit;
}

$is_installed = false;

if(function_exists('get_plugins')) {
	$all_plugins  = get_plugins();
	$is_installed = !empty($all_plugins['woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php']);
}
?>
<div class="error">
	<p><strong><?php esc_html_e('Pagamento MultiPay para WooCommerce', 'woo-multipay'); ?></strong> <?php esc_html_e('depende da última versão do Brazilian Market no WooCommerce para funcionar!', 'woo-multipay'); ?></p>

<?php if($is_installed && current_user_can('install_plugins')) : ?>
		<p><a href="<?php echo esc_url(wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php&plugin_status=active'), 'activate-plugin_woocommerce-extra-checkout-fields-for-brazil/woocommerce-extra-checkout-fields-for-brazil.php')); ?>" class="button button-primary"><?php esc_html_e('Ative o Brazilian Market on WooCommerce', 'woo-multipay'); ?></a></p>
<?php else :
		if(current_user_can('install_plugins')) {
			$url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=woocommerce-extra-checkout-fields-for-brazil'), 'install-plugin_woocommerce-extra-checkout-fields-for-brazil');
		} else {
			$url = 'http://wordpress.org/plugins/woocommerce-extra-checkout-fields-for-brazil/';
		}
?>
		<p><a href="<?php echo esc_url($url); ?>" class="button button-primary"><?php esc_html_e('Instale o Brazilian Market on WooCommerce', 'woo-multipay'); ?></a></p>
	<?php endif; ?>
</div>
