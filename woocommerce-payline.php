<?php
/**
 * Plugin Name: Payline 
 * Plugin URI: https://docs.payline.com/display/DT/Plugin+WooCommerce
 * Description: integrations of Payline payment solution in your WooCommerce store
 * Version: 1.5
 * Author: Monext
 * Text Domain: monext-online-woocommerce
 * Author URI: http://www.monext.fr
 * License: LGPL-3.0+
 * GitHub Plugin URI: https://github.com/PaylineByMonext/payline-woocommerce/
 * Github Branch: master
 * WC tested up to: 4.9.2
 * 
 *  Copyright 2017  Monext  (email : support@payline.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (!defined('ABSPATH')) exit;

define('WCPAYLINE_PLUGIN_URL', plugin_dir_url(__FILE__));

function woocommerce_payline_activation() {
	if (!is_plugin_active('woocommerce/woocommerce.php')) {
		deactivate_plugins(plugin_basename(__FILE__));

		load_plugin_textdomain('payline', false, dirname(plugin_basename(__FILE__)) . '/languages/');
		
		$message = sprintf(__('Sorry! In order to use WooCommerce %s Payment plugin, you need to install and activate the WooCommerce plugin.', 'payline'), 'Payline');
		wp_die($message, 'WooCommerce Payline Gateway Plugin', array('back_link' => true));
	}
}
register_activation_hook(__FILE__, 'woocommerce_payline_activation');


/**
 * inserts class gateway
 */
function woocommerce_payline_init() {
	// Load translation files
	load_plugin_textdomain('payline', false, dirname(plugin_basename(__FILE__)) . '/languages/');

    if ( ! class_exists( 'WC_Abstract_Payline', false ) ) {
        include_once 'class-wc-abstract-payline.php';
    }

    if ( ! class_exists( 'WC_Abstract_Recurring_Payline_NX', false ) ) {
        include_once 'class-wc-abstract-recurring-payline.php';
    }
	
	if (!class_exists('WC_Gateway_Payline')) {
		require_once 'class-wc-gateway-payline.php';
	}

    if (!class_exists('WC_Gateway_Payline_NX')) {
        require_once 'class-wc-gateway-payline-nx.php';
    }

    if (!class_exists('WC_Gateway_Payline_REC')) {
        require_once 'class-wc-gateway-payline-rec.php';
    }

	require_once 'vendor/autoload.php';
}
add_action('woocommerce_init', 'woocommerce_payline_init');



/**
 * adds method to woocommerce methods
 * @param $methods
 * @return mixed
 */
function woocommerce_payline_add_method($methods) {
    $methods[] = 'WC_Gateway_Payline';

    return $methods;
}
add_filter('woocommerce_payment_gateways', 'woocommerce_payline_add_method');


/**
 * add a link from plugin list to parameters
 * @param $links
 * @param $file
 * @return mixed
 */
function woocommerce_payline_add_link($links, $file) {
	$links[] = '<a href="'.admin_url('admin.php?page=wc-settings&tab=checkout&section=payline').'">' . __('Settings CPT') .'</a><br />';
	return $links;
}
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'woocommerce_payline_add_link',  10, 2);

/**
 * @param $statuses
 * @return mixed
 */
function woocommerce_payline_add_custom_status_for_order_again( $statuses ){

    if(isset( $_GET['order_again'], $_GET['_wpnonce'], $_GET['payline_cancel']) && wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'woocommerce-order_again' ) ) {
        $statuses[] = 'cancelled';
        $statuses[] = 'failed';
        $statuses[] = 'pending';

    }

    return $statuses;
}
add_filter( 'woocommerce_valid_order_statuses_for_order_again', 'woocommerce_payline_add_custom_status_for_order_again', 10, 1 );


/**
 * @param $available_gateways
 * @return mixed
 */
function woocommerce_payline_enable_gateway_order_pay( $available_gateways ) {
    if ( is_checkout() && is_wc_endpoint_url( 'order-pay' ) ) {
        unset( $available_gateways['payline'] );

    }

    return $available_gateways;
}

add_filter( 'woocommerce_available_payment_gateways', 'woocommerce_payline_enable_gateway_order_pay' );

/**
 * Payline module is not published on https://wordpress.org/plugins/
 * avoid confusing with bitpay
 *
 * @param $value
 * @return mixed
 */
function payline_site_transient_update_plugins( $value) {

    if(basename(dirname(__FILE__)) != 'payline-woocommerce') {
        return $value;
    }

    if(empty($value) || empty($value->response) || !is_array($value->response)) {
        return $value;
    }

    foreach ($value->response as $pluginFile => $pluginUpdate) {
        if($pluginUpdate->plugin == "payline-woocommerce/woocommerce-payline.php") {
            unset($value->response[$pluginFile]);
        }
    }

    return $value;
}
add_filter( 'site_transient_update_plugins', 'payline_site_transient_update_plugins' );


/**
 * @param $should_update
 * @param $plugin
 * @return false
 */
function payline_auto_update_plugin( $should_update, $plugin ) {
    if ( ! isset( $plugin->plugin, $plugin->new_version ) ) {
        return $should_update;
    }

    if ( basename(dirname(__FILE__)) . DIRECTORY_SEPARATOR . basename(__FILE__) == $plugin->plugin ) {
        return false;
    }
    return $should_update;

}
add_filter( 'auto_update_plugin', 'payline_auto_update_plugin', 100, 2 );


add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );
