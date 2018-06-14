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

class BeGateway_Model_Checkout extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'begateway_checkout';

    protected $_formBlockType = 'begateway/form_checkout';
    protected $_infoBlockType = 'begateway/info_checkout';

    protected $_isGateway         = true;
    protected $_canOrder          = true;
    protected $_canAuthorize      = true;
    protected $_canCapture        = true;
    protected $_canCapturePartial = true;
    protected $_canRefund         = true;
    protected $_canVoid           = true;
    protected $_canUseInternal    = false;
    protected $_canUseCheckout    = true;

    protected $_canUseForMultishipping     = false;
    protected $_canFetchTransactionInfo    = false;
    protected $_canManageRecurringProfiles = false;

    /**
     * Create payment to token and return redirect url
     *
     * @return array of form fields
     * @throws Mage_Core_Exception
     */
    public function getFormData()
    {
        $helper = $this->getHelper();
        $session = $helper->getCheckoutSession();
        $order_id = $session->getLastRealOrderId();

        Mage::log('Checkout transaction for order #' . $order_id);

        try {
            $helper->initClient($this->getCode());
            /** @var Mage_Sales_Model_Order $order */
            $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);

            if (!$order)
              Mage::throwException($helper->__('Error to load the order'));

            $billing  = $order->getBillingAddress();

            if (!$billing)
              Mage::throwException($helper->__('Error to load the order data'));

            $token = new \BeGateway\GetPaymentToken;

            $tracking_id = $helper->genTransactionId($order->getIncrementId());

            $token->setTrackingId(
              $tracking_id
            );
            $token->money->setCurrency($order->getOrderCurrencyCode());
            $token->money->setAmount($order->getGrandTotal());
            $token->setDescription($helper->__('Order # %s payment', $order_id));

            $token->customer->setFirstName($billing->getFirstname());
            $token->customer->setLastName($billing->getLastname());
            $token->customer->setAddress($billing->getStreet(1));

            $token->customer->setCountry($billing->getCountry());
            $token->customer->setZip($billing->getPostcode());
            $token->customer->setPhone($billing->getTelephone());
            $token->customer->setEmail($order->getCustomerEmail());
            $token->customer->setState($billing->getRegionCode());

            $notification_url = $helper->getNotifyURL('checkout');
            $notification_url = str_replace('carts.local', 'webhook.begateway.com:8443', $notification_url);

            $token->setNotificationUrl($notification_url);
            $token->setSuccessUrl($helper->getSuccessURL('checkout'));
            $token->setDeclineUrl($helper->getFailureURL('checkout'));
            $token->setFailUrl($helper->getFailureURL('checkout'));
            $token->setCancelUrl($helper->getCancelURL('checkout'));
            $token->setLanguage($helper->getLocale());

            if ($helper->getConfigData($this->getCode(),'payment_action') ==
                $helper::AUTHORIZE) {
              $token->setAuthorizationTransactionType();
            }

            if ($helper->getConfigData($this->getCode(), $helper::CREDIT_CARD)) {
              $cc = new \BeGateway\PaymentMethod\CreditCard;
              $token->addPaymentMethod($cc);
            }

            if ($helper->getConfigData($this->getCode(), $helper::CREDIT_CARD_HALVA)) {
              $halva = new \BeGateway\PaymentMethod\CreditCardHalva;
              $token->addPaymentMethod($halva);
            }

            if ($helper->getConfigData($this->getCode(), $helper::ERIP)) {
              $erip = new \BeGateway\PaymentMethod\Erip(array(
                'order_id' => $order_id,
                'account_number' => strval($order_id),
                'service_no' => $helper->getConfigData($this->getCode(), 'erip_service_no'),
                'service_info' => array($helper->__('Order %s payment', $order_id))
              ));
              $token->addPaymentMethod($erip);
            }

            if ($helper->getConfigData($this->getCode(), 'test_mode')) {
              $token->setTestMode(true);
            }

            $response = $token->submit();

            if (!$response->isSuccess()) {
              Mage::log("BeGateway API response: " . $response->getMessage());
              Mage::throwException($helper->__('Error to get a payment token. Contact the store administrator.'));
            }

            $payment = $order->getPayment();

            $payment
                ->setTransactionId(
                    $tracking_id
                )
                ->setIsTransactionPending(true)
                ->addTransaction(
                    Mage_Sales_Model_Order_Payment_Transaction::TYPE_ORDER
                );

            $payment->setSkipTransactionCreation(true);

            // Save the redirect url with our
            return array(
              'token' => $response->getToken(),
              'action' => $response->getRedirectUrlScriptName()
            );
        } catch (Exception $exception) {
            Mage::logException($exception);

            Mage::throwException(
                $helper->__($exception->getMessage())
            );
        }
    }

    /**
     * @param Varien_Object|Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     * @return $this|bool
     * @throws Mage_Core_Exception
     */
    public function capture(Varien_Object $payment, $amount)
    {
        Mage::log('Capture transaction for order #' . $payment->getOrder()->getIncrementId());

        try {
            $this->getHelper()->initClient($this->getCode());

            $authorize = $payment->lookupTransaction(null, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);

            /* Capture should only be possible, when Authorize Transaction Exists */
            if (!isset($authorize) || $authorize === false) {
                Mage::log(
                    'Capture transaction for order #' .
                    $payment->getOrder()->getIncrementId() .
                    ' cannot be finished (No Authorize Transaction exists)'
                );

                return $this;
            }

            $referenceId = $authorize->getTxnId();

            $capture = new \BeGateway\Capture;
            $capture->setParentUid($referenceId);
            $capture->money->setAmount($amount);
            $capture->money->setCurrency($payment->getOrder()->getOrderCurrencyCode());

            $response = $capture->submit();

            if ($response->getUid()) {
              $payment
                  ->setTransactionId(
                  // @codingStandardsIgnoreStart
                      $response->getUid()
                  // @codingStandardsIgnoreEnd
                  )
                  ->setParentTransactionId(
                      $referenceId
                  )
                  ->setShouldCloseParentTransaction(
                      true
                  )
                  ->resetTransactionAdditionalInfo(

                  )
                  ->setTransactionAdditionalInfo(
                      array(
                          Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS =>
                              $this->getHelper()->getArrayFromGatewayResponse(
                                  $response
                              )
                      ),
                      null
                  );

              $payment->save();
            }

            if ($response->isSuccess()) {
                $this->getHelper()->getAdminSession()->addSuccess(
                    $response->getMessage()
                );
            } else {
                $this->getHelper()->getAdminSession()->addError(
                    $response->getMessage()
                );
            }
        } catch (Exception $exception) {
            Mage::logException($exception);

            Mage::throwException(
                $this->getHelper()->__($exception->getMessage())
            );
        }

        return $this;
    }

    /**
     * Refund the last successful transaction
     *
     * @param Varien_Object|Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     *
     * @return BeGateway_Model_Checkout
     */
    public function refund(Varien_Object $payment, $amount)
    {
        Mage::log('Refund transaction for order #' . $payment->getOrder()->getIncrementId());

        try {
            $this->getHelper()->initClient($this->getCode());

            $capture = $payment->lookupTransaction(
                null,
                Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE
            );

            /* Refund Transaction is only possible, when Capture Transaction Exists */
            if (!isset($capture) || $capture === false) {
                Mage::log(
                    'Refund transaction for order #' .
                    $payment->getOrder()->getIncrementId() .
                    ' could not be completed! (No Capture Transaction Exists'
                );
                return $this;
            }

            $referenceId = $capture->getTxnId();

            $refund = new \BeGateway\Refund;
            $refund->setParentUid($referenceId);
            $refund->money->setAmount($amount);
            $refund->money->setCurrency($payment->getOrder()->getOrderCurrencyCode());
            $refund->setReason($this->getHelper()->__('Refunded from Magento'));

            $response = $refund->submit();

            if ($response->getUid()) {
              $payment
                  ->setTransactionId(
                  // @codingStandardsIgnoreStart
                      $response->getUid()
                  // @codingStandardsIgnoreEnd
                  )
                  ->setParentTransactionId(
                      $referenceId
                  )
                  ->setShouldCloseParentTransaction(
                      true
                  )
                  ->resetTransactionAdditionalInfo(

                  )
                  ->setTransactionAdditionalInfo(
                      array(
                          Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS =>
                              $this->getHelper()->getArrayFromGatewayResponse(
                                  $response
                              )
                      ),
                      null
                  );

              $payment->save();
            }

            if ($response->isSuccess()) {
                $this->getHelper()->getAdminSession()->addSuccess(
                    $response->getMessage()
                );

            } else {
                $this->getHelper()->getAdminSession()->addError(
                    $response->getMessage()
                );
            }
        } catch (Exception $exception) {
            Mage::logException($exception);

            Mage::throwException(
                $exception->getMessage()
            );
        }

        return $this;
    }

    /**
     * Void the last successful transaction
     *
     * @param Varien_Object|Mage_Sales_Model_Order_Payment $payment
     *
     * @return BeGateway_Model_Checkout
     */
    public function void(Varien_Object $payment)
    {
        Mage::log('Void transaction for order #' . $payment->getOrder()->getIncrementId());

        try {
            $this->getHelper()->initClient($this->getCode());

            $transactions = $this->getHelper()->getTransactionFromPaymentObject(
                $payment,
                array(
                    Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH
                )
            );

            $referenceId = $transactions ? reset($transactions)->getTxnId() : null;

            $void = new \BeGateway\Void;
            $void->setParentUid($referenceId);
            $void->money->setCurrency($payment->getOrder()->getOrderCurrencyCode());
            $void->money->setAmount($payment->getOrder()->getGrandTotal());

            $response = $void->submit();

            if ($response->getUid()) {
              $payment
                  ->setTransactionId(
                  // @codingStandardsIgnoreStart
                      $response->getUid()
                  // @codingStandardsIgnoreEnd
                  )
                  ->setParentTransactionId(
                      $referenceId
                  )
                  ->setShouldCloseParentTransaction(
                      true
                  )
                  ->resetTransactionAdditionalInfo(

                  )
                  ->setTransactionAdditionalInfo(
                      array(
                          Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS =>
                              $this->getHelper()->getArrayFromGatewayResponse(
                                  $response
                              )
                      ),
                      null
                  );

              $payment->save();
            }

            if ($response->isSuccess()) {
                $this->getHelper()->getAdminSession()->addSuccess(
                    $response->getMessage()
                );
            } else {
                $this->getHelper()->getAdminSession()->addError(
                    $response->getMessage()
                );
            }
        } catch (Exception $exception) {
            Mage::logException($exception);

            Mage::throwException(
                $exception->getMessage()
            );
        }

        return $this;
    }

    /**
     * Cancel payment abstract method
     *
     * @param Varien_Object $payment
     *
     * @return BeGateway_Model_Checkout
     */
    public function cancel(Varien_Object $payment)
    {
        return $this->void($payment);
    }

    /**
     * Handle an incoming notification
     *
     * @param stdClass $checkoutTransaction
     * @return bool
     */
    // @codingStandardsIgnoreStart
    public function processNotification($webhook)
    {
        // @codingStandardsIgnoreEnd
        try {
            $helper = $this->getHelper();
            $helper->initClient($this->getCode());

            /** @var Mage_Sales_Model_Order_Payment_Transaction $transaction */
            $transaction = Mage::getModel('sales/order_payment_transaction')->load(
            // @codingStandardsIgnoreStart
                $webhook->getTrackingId(),
                // @codingStandardsIgnoreEnd
                'txn_id'
            );

            list($order_id, $hash) = explode('_', $webhook->getTrackingId());

            $order = Mage::getModel('sales/order')->loadByIncrementId($order_id);

            if (!$order) {
                return false;
            }

            // verify that paid amount is correct
            $money = new \BeGateway\Money;

            $money->setCurrency($order->getOrderCurrencyCode());
            $money->setAmount($order->getGrandTotal());
            
            if ($money->getCents() != $webhook->getResponse()->transaction->amount ||
                $order->getOrderCurrencyCode() != $webhook->getResponse()->transaction->currency) {
              return false;
            }

            $payment = $order->getPayment();

            $transaction
                ->setOrderPaymentObject(
                    $payment
                )
                ->setAdditionalInformation(
                    Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS,
                    $this->getHelper()->getArrayFromGatewayResponse(
                        $webhook
                    )
                )
                ->save();

            $payment
                ->setTransactionId(
                // @codingStandardsIgnoreStart
                    $webhook->getUid()
                // @codingStandardsIgnoreEnd
                )
                ->setParentTransactionId(
                // @codingStandardsIgnoreStart
                    isset(
                      $webhook->getResponse()->transaction->parent_uid
                    ) ?
                      $webhook->getResponse()->transaction->parent_uid
                      : null
                // @codingStandardsIgnoreEnd
                )
                ->setShouldCloseParentTransaction(true)
                ->setIsTransactionPending(false)
                ->resetTransactionAdditionalInfo()
                ->setTransactionAdditionalInfo(
                    array(
                        Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS =>
                            $helper->getArrayFromGatewayResponse(
                              $webhook
                            )
                    ),
                    null
                );

            if ($webhook->isSuccess()) {
                $payment->setIsTransactionClosed(false);
            } else {
                $payment->setIsTransactionClosed(true);
            }

            // @codingStandardsIgnoreStart
            switch ($webhook->getResponse()->transaction->type) {
                // @codingStandardsIgnoreEnd
                case $helper::AUTHORIZE:
                    $payment->registerAuthorizationNotification(
                        $order->getBaseGrandTotal()
                    );
                    break;
                case $helper::PAYMENT:
                    $payment->registerCaptureNotification(
                        $order->getBaseGrandTotal()
                    );
                    break;
                default:
                    break;
            }

            $payment->save();

            $helper->setOrderState(
                $order,
                $webhook->getResponse()->transaction->status
            );

            return true;
        } catch (Exception $exception) {
            Mage::logException($exception);
        }

        return false;
    }

    /**
     * Get URL to "Redirect" block
     *
     * @see BeGateway_CheckoutController
     *
     * @note In order for redirect to work, you must
     * set the session variable:
     *
     * BeGatewayCheckoutRedirectUrl
     *
     * @return mixed
     */
    public function getOrderPlaceRedirectUrl()
    {
        return $this->getHelper()->getRedirectUrl('checkout');
    }

    /**
     * Get the helper or return its instance
     *
     * @param $helper string - Name of the helper, empty for the default class helper
     *
     * @return BeGateway_Helper_Data|mixed
     */
    protected function getHelper($helper = '')
    {
        if (!$helper) {
            return Mage::helper('begateway');
        } else {
            return Mage::helper($helper);
        }
    }

    /**
     * Determines if the Payment Method should be available on the checkout page
     * @param Mage_Sales_Model_Quote $quote
     * @param int|null $checksBitMask
     * @return bool
     */
    public function isApplicableToQuote($quote, $checksBitMask)
    {
        return
            parent::isApplicableToQuote($quote, $checksBitMask) ||
            (
                ($checksBitMask & self::CHECK_ORDER_TOTAL_MIN_MAX)
            );
    }

    /**
     * Determines if the Payment Method should be visible on the chechout page or not
     * @param Mage_Sales_Model_Quote|null $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        return
            parent::isAvailable($quote) &&
            $this->getHelper()->getIsMethodAvailable(
                $this->getCode(),
                $quote,
                false
            );
    }
}
