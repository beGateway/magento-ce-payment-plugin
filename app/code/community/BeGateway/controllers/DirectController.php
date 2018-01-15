<?php
/*
 * Copyright (C) 2017 BeGateway
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
 * @copyright   2017 BeGateway
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

/**
 * Class BeGateway_DirectController
 *
 * Front-end method for Direct method
 */
class BeGateway_DirectController extends Mage_Core_Controller_Front_Action
{
    /** @var BeGateway_Helper_Data $helper */
    protected $_helper;

    /** @var BeGateway_Model_Direct $direct */
    protected $_direct;

    protected function _construct()
    {
        $this->_helper = Mage::helper('begateway');

        $this->_direct = Mage::getModel('begateway/direct');
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
            $this->_helper->initClient($this->_direct->getCode());

            $notification = new \BeGateway\Webhook;

            if ($notification->isAuthorized()) {

                // @codingStandardsIgnoreStart
                if ($notification->getUid() != null)) {
                    // @codingStandardsIgnoreEnd

                    $this->_direct->processNotification($notification);

                    $this->getResponse()->clearHeaders();
                    $this->getResponse()->clearBody();

                    $this->getResponse()->setHeader('Content-type', 'application/xml');

                    $this->getResponse()->setBody(
                        $notification->generateResponse()
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
     * @return void
     */
    public function redirectAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('begateway/redirect_direct')->toHtml()
        );
    }

    /**
     * Customer landing page for successful payment
     *
     * @see API_Documentation \ return_url
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
     * @see API_Documentation \ return_url
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
}
