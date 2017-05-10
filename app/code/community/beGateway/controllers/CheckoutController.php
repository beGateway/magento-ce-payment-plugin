<?php
/*
 * Copyright (C) 2017 beGateway
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author      eComCharge
 * @copyright   2017 beGateway
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

/**
 * Class beGateway_CheckoutController
 *
 * Front-end controller for Checkout method
 */
class beGateway_CheckoutController extends Mage_Core_Controller_Front_Action
{
    /** @var beGateway_Helper_Data $helper */
    protected $_helper;

    /** @var beGateway_Model_Checkout $checkout */
    protected $_checkout;

    protected function _construct()
    {
        $this->_helper = Mage::helper('begateway');

        $this->_checkout = Mage::getModel('begateway/checkout');
    }

    /**
     * Process an incoming Notification
     * If it appears valid, do a reconcile and
     * use the reconcile data to save details
     * about the transaction
     *
     * @see API_Documentation \ notification_url
     *
     * @return void
     */
    public function notifyAction()
    {
        // Notifications are only POST, deny everything else
        if (!$this->getRequest()->isPost()) {
            return;
        }

        try {
            $this->_helper->initClient($this->_checkout->getCode());

            $notification = new \beGateway\Webhook;

            if ($notification->isAuthorized()) {

                // @codingStandardsIgnoreStart
                if ($notification->getTrackingId() != null) {
                    // @codingStandardsIgnoreStart
                    $this->_checkout->processNotification($notification);

                    $this->getResponse()->clearHeaders();
                    $this->getResponse()->clearBody();

                    $this->getResponse()->setHeader('Content-type', 'text/html');

                    $this->getResponse()->setBody(
                        'OK'
                    );

                    $this->getResponse()->setHttpResponseCode(200);
                }
            }
        } catch (Exception $exception) {
            Mage::logException($exception);
        }
    }

    /**
     * When a customer has to be redirected, show
     * a "transition" page where you notify them,
     * that they will be redirected to a new website.
     *
     * @see API_Documentation \ notification_url
     *
     * @return void
     */
    public function redirectAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('begateway/redirect_checkout')->toHtml()
        );
    }

    /**
     * Customer landing page for successful payment
     *
     * @see API_Documentation \ success_url
     *
     * @return void
     */
    public function successAction()
    {
        $this->_redirect('checkout/onepage/success', array('_secure' => true));
    }

    /**
     * Customer landing page for unsuccessful payment
     *
     * @see API_Documentation \ fail_url
     *
     * @return void
     */
    public function failureAction()
    {
        $this->_helper->restoreQuote();

        $this->_helper->getCheckoutSession()->addError(
            $this->_helper->__('We were unable to process your payment! Please check your input or try again later.')
        );

        $this->_redirect('checkout/cart', array('_secure' => true));
    }

    /**
     * Customer landing page for cancelled payment
     *
     * @return void
     */
    public function cancelAction()
    {
        $this->_helper->restoreQuote($shouldCancel = true);

        $this->_helper->getCheckoutSession()->addSuccess(
            $this->_helper->__('Your payment session has been cancelled successfully!')
        );

        $this->_redirect('checkout/cart', array('_secure' => true));
    }
}
