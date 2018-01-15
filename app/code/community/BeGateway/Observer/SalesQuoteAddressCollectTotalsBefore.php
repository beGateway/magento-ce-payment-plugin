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
 * Class BeGateway_Observer_SalesQuoteAddressCollectTotalsBefore
 */
class BeGateway_Observer_SalesQuoteAddressCollectTotalsBefore
{
    protected $_methodCodes = array(
        'begateway_checkout',
        'begateway_direct'
    );

    /**
     * Observer Event Handler
     * @param Varien_Event_Observer $observer
     * @return bool|BeGateway_Observer_SalesQuoteAddressCollectTotalsBefore
     */
    public function handleAction($observer)
    {
        $event = $observer->getEvent();
        $quoteAddress = $event->getQuoteAddress();

        if (!is_object($quoteAddress) || !is_object($quoteAddress->getQuote()->getPayment())) {
            return false;
        }

        $paymentMethodCode = $quoteAddress->getQuote()->getPayment()->getMethod();

        if (!isset($paymentMethodCode) || !in_array($paymentMethodCode, $this->getMethodCodes())) {
            return false;
        }

        if (!$this->getHelper()->getIsMethodAvailable($paymentMethodCode, $quoteAddress->getQuote())) {
            return false;
        }

        return $this;
    }

    /**
     * Return the available payment methods
     * @return array
     */
    protected function getMethodCodes()
    {
        return $this->_methodCodes;
    }

    /**
     * @return BeGateway_Helper_Data
     */
    protected function getHelper()
    {
        return Mage::helper('begateway');
    }
}
