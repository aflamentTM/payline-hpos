<?php


/**
 * Payline module for WooCommerce
 *
 * @class 		WC_Payline
 * @package		WooCommerce
 * @category	Payment Gateways
 *
 * WC tested up to: 4.0.1
 */


class WC_Gateway_Payline extends WC_Abstract_Payline {

    protected $paymentMode = 'CPT';

    public $id = 'payline';

    public $method_title = 'Payline CPT';


    /**
     * @param WC_Refund|bool|WC_Order $order
     * @return mixed|void
     */
    protected function getWebPaymentRequest(WC_Order $order) {

        $requestParams = parent::getWebPaymentRequest($order);

        do_action('payline_before_do_web_payment', $requestParams, $this);

        return $requestParams;
    }

    /**
     * @param WC_Order $order
     * @param array $res
     * @return false
     */
    protected function paylineCancelWebPaymentDetails(WC_Order $order, array $res) {
        return false;
    }

    /**
     * @param WC_Order $order
     * @param array $res
     * @return bool
     */
    protected function paylineSuccessWebPaymentDetails(WC_Order $order, array $res) {

        if($res['result']['code'] == '00000') {
            $orderId = $order->get_id();

            // Store transaction details
            $order->update_meta_data( 'Transaction ID', $res['transaction']['id']);
            $order->update_meta_data( 'Card number', $res['card']['number']);
            $order->update_meta_data( 'Payment mean', $res['card']['type']);
            $order->update_meta_data( 'Card expiry', $res['card']['expirationDate']);
            $order->update_meta_data( '_contract_number', $res['payment']['contractNumber']);
            $order->save();
            $order->payment_complete($res['transaction']['id']);
            return true;
        }
        return false;
    }






}
