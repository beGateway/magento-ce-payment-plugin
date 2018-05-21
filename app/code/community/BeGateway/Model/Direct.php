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
 * BeGateway Direct Payment Method
 *
 * This class requires the user to input
 * their CC data and as such requires PCI
 * compliance.
 *
 * @see http://magento.com/resources/pci
 * @extends Mage_Payment_Model_Method_Cc
 *
 * @category
 */
class BeGateway_Model_Direct extends Mage_Payment_Model_Method_Cc
{
    // Variables
    protected $_code = 'begateway_direct';

    protected $_formBlockType = 'begateway/form_direct';
    protected $_infoBlockType = 'begateway/info_direct';

    // Configurations
    protected $_isGateway         = true;
    protected $_canAuthorize      = true;
    protected $_canCapture        = true;
    protected $_canCapturePartial = true;
    protected $_canRefund         = true;
    protected $_canVoid           = true;
    protected $_canUseInternal    = false;
    protected $_canUseCheckout    = true;

    protected $_isInitializeNeeded      = false;

    protected $_canFetchTransactionInfo = false;
    protected $_canUseForMultishipping  = false;
    protected $_canSaveCc               = false;

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
     * Check if we're on a secure page and run
     * the parent verification
     *
     * @param Mage_Sales_Model_Quote|null $quote
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        return
            parent::isAvailable($quote) &&
            $this->getHelper()->getIsMethodAvailable(
                $this->getCode(),
                $quote,
                true
            );
    }

    /**
     * Assign the incoming $data to internal variables
     *
     * @param mixed $data
     * @return $this
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }

        $info = $this->getInfoInstance();

        $info->setCcOwner($data->getCcOwner())
            ->setCcNumber($data->getCcNumber())
            ->setCcCid($data->getCcCid())
            ->setCcExpMonth($data->getCcExpMonth())
            ->setCcExpYear($data->getCcExpYear())
            ->setCcType($data->getCcType());

        return $this;
    }

    /**
     * Retrieves the Module Transaction Type Setting
     *
     * @return string
     */
    protected function getConfigTransactionType()
    {
        return $this->getConfigData('payment_action');
    }

    /**
     * Payment action getter compatible with payment model
     *
     * @see Mage_Sales_Model_Order_Payment::place()
     *
     * @return string
     */
    public function getConfigPaymentAction()
    {
        $helper = $this->getHelper();
        $helper->initLibrary();

        switch ($this->getConfigTransactionType()) {
            default:
            case $helper::AUTHORIZE:
                return Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE;

            case $helper::PAYMENT:
                return Mage_Payment_Model_Method_Abstract::ACTION_AUTHORIZE_CAPTURE;
        }
    }

    /**
     * Authorize transaction type
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return BeGateway_Model_Direct
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        Mage::log('Authorize transaction for order #' . $payment->getOrder()->getIncrementId());

        return $this->processTransaction($payment, $amount);
    }

    /**
     * Capture transaction type
     *
     * @param Varien_Object|Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     *
     * @return BeGateway_Model_Direct
     */
    public function capture(Varien_Object $payment, $amount)
    {
        $authorize = $payment->lookupTransaction(null, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);

        if ($authorize) {
            return $this->doCapture($payment, $amount);
        } else {
            Mage::log('Sale transaction for order #' . $payment->getOrder()->getIncrementId());

            return $this->processTransaction($payment, $amount);
        }
    }

    /**
     * Sends a transaction to the Gateway
     *    - Authorize
     *    - Payment
     *
     * @param Varien_Object|Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     *
     * @return BeGateway_Model_Direct
     */
    protected function processTransaction(Varien_Object $payment, $amount)
    {
        try {
            $this->getHelper()->initClient($this->getCode());

            $transactionType = $this->getConfigTransactionType();

            /** @var Mage_Sales_Model_Order $order */
            $order = $payment->getOrder();

            $billing = $order->getBillingAddress();
            $shipping = $order->getShippingAddress();

            $transactionClass = '\BeGateway\\'. ucfirst($transactionType);
            die($transactionClass);
            $begateway = new $transactionClass;

            $begateway->setTrackingId(
              $this->getHelper()->genTransactionId(
                  $order->getIncrementId()
              )
            );
            $begateway->customer->setIp(
              $this->getHelper()->getRemoteAddress()
            );
            $begateway->money->setCurrency($order->getOrderCurrencyCode());
            $begateway->money->setAmount($amount);
            $begateway->card->setCardHolder($payment->getCcOwner());
            $begateway->card->setCardNumber($payment->getCcNumber());
            $begateway->card->setCardExpYear($payment->getCcExpYear());
            $begateway->card->setCardExpMonth($payment->getCcExpMonth());
            $begateway->card->setCvv($payment->getCcCid());

            $begateway->customer->setEmail($order->getCustomerEmail());
            $begateway->customer->setPhone($billing->getTelephone());
            $begateway->customer->setFirstName($billing->getFirstname());
            $begateway->customer->setLastName($billing->getLastname());
            $begateway->customer->setAddress($billing->getStreet(1));
            $begateway->customer->setCountry($billing->getCountry());
            $begateway->customer->setZip($billing->getPostcode());
            $begateway->customer->setState($billing->getRegionCode());
            $begateway->setReturnUrl($this->getHelper()->getNotifyURL('direct'));
            $begateway->setNotificationUrl($this->getHelper()->getNotifyURL('direct'));

            if ($helper->getConfigData($this->getCode(), 'test_mode')) {
              $begateway->setTestMode(true);
            }

            $response = $begateway->submit();

            if ($response->getUid()) {
              $payment
                  ->setTransactionId(
                      $response->getUid()
                  )
                  ->setIsTransactionClosed(
                      false
                  )
                  ->setIsTransactionPending(
                      $response->isIncomplete()
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

              if ($response->isIncomplete()) {
                  $payment->setPreparedMessage(
                      $this->getHelper()->__('3-D Secure: Redirecting customer to a verification page.')
                  );
              }

              $payment->save();
            }

            if ($response->isIncomplete()) {
                // Save the redirect url with our
                $this->getHelper()->getCheckoutSession()->setBeGatewayDirectRedirectUrl(
                    $response->getResponse()->transaction->redirect_url
                );
            } elseif (!$response->isSuccess()) {
                Mage::throwException(
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
     * Capture a successful auth transaction
     *
     * @param Varien_Object|Mage_Sales_Model_Order_Payment $payment
     * @param float $amount
     *
     * @return BeGateway_Model_Direct
     *
     * @throws Mage_Core_Exception
     */
    protected function doCapture($payment, $amount)
    {
        Mage::log('Capture transaction for order #' . $payment->getOrder()->getIncrementId());

        try {
            $this->getHelper()->initClient($this->getCode());

            $authorize = $payment->lookupTransaction(null, Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);

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
     * @return BeGateway_Model_Direct
     */
    public function refund(Varien_Object $payment, $amount)
    {
        Mage::log('Refund transaction for order #' . $payment->getOrder()->getIncrementId());

        try {
            $this->getHelper()->initClient($this->getCode());

            $capture = $payment->lookupTransaction(null, Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE);

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
     * @return BeGateway_Model_Direct
     */
    public function void(Varien_Object $payment)
    {
        Mage::log('Void transaction for order #' . $payment->getOrder()->getIncrementId());

        try {
            $this->getHelper()->initClient($this->getCode());

            $transactions = $this->getHelper()->getTransactionFromPaymentObject(
                $payment,
                array(
                    Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH,
                    Mage_Sales_Model_Order_Payment_Transaction::TYPE_CAPTURE
                )
            );

            $referenceId = $transactions ? reset($transactions)->getTxnId() : null;

            $void = new \BeGateway\Void;
            $void->setParentUid($referenceId);
            $void->money->setCurrency($payment->getOrder()->getOrderCurrencyCode());
            $void->money->setAmount($payment->getOrder()->getBaseGrandTotal());

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
     * Cancel order
     *
     * @param Varien_Object $payment
     *
     * @return BeGateway_Model_Direct
     */
    public function cancel(Varien_Object $payment)
    {
        return $this->void($payment);
    }

    /**
     * Handle an incoming notification
     *
     * @param stdClass $notification
     * @return $this
     */
    // @codingStandardsIgnoreStart
    public function processNotification($webhook)
    {
        // @codingStandardsIgnoreEnd
        try {
            $helper = $this->getHelper();
            $this->getHelper()->initClient($this->getCode());

            /** @var Mage_Sales_Model_Order_Payment_Transaction $transaction */
            $transaction = Mage::getModel('sales/order_payment_transaction')->load(
            // @codingStandardsIgnoreStart
                $webhook->getTrackingId(),
                // @codingStandardsIgnoreEnd
                'txn_id'
            );

            $order = $transaction->getOrder();

            if ($order) {
                $payment = $order->getPayment();

                $transaction->setOrderPaymentObject($payment);

                $transaction->unsAdditionalInformation(
                    Mage_Sales_Model_Order_Payment_transaction::RAW_DETAILS
                );

                $transaction->setAdditionalInformation(
                    Mage_Sales_Model_Order_Payment_transaction::RAW_DETAILS,
                    $this->getHelper()->getArrayFromGatewayResponse(
                        $webhook
                    )
                );

                $isTransactionApproved = $webhook->isSuccess();

                $transaction->setIsClosed(!$isTransactionApproved);

                $transaction->save();
                $money = new \BeGateway\Money;
                $money->setCents($webhook->getResponse()->transaction->amount);
                $money->setCurrency($webhook->getResponse()->transaction->currency);

                // @codingStandardsIgnoreStart
                switch ($webhook->getResponse()->transaction->type) {
                    // @codingStandardsIgnoreEnd
                    case $helper::AUTHORIZE:
                        $payment->registerAuthorizationNotification($money->getAmount());
                        break;
                    case $helper::PAYMENT:
                        $payment->setShouldCloseParentTransaction(true);
                        $payment->setTransactionId(
                        // @codingStandardsIgnoreStart
                            $webhook->getUid()
                        // @codingStandardsIgnoreEnd
                        );

                        $payment->registerCaptureNotification($money->getAmount());
                        break;
                    default:
                        break;
                }

                // @codingStandardsIgnoreStart

                $payment->save();

                $this->getHelper()->setOrderState(
                    $order,
                    $webhook->getResponse()->transaction->status,
                    $webhook->getMessage()
                );
            }
        } catch (Exception $exception) {
            Mage::logException($exception);
        }

        return $this;
    }

    /**
     * Get URL to "Redirect" block
     *
     * @see BeGateway_DirectController
     *
     * @note In order for redirect to work, you must
     * set the session variable "BeGatewayDirectRedirectUrl"
     *
     * @return mixed
     */
    public function getOrderPlaceRedirectUrl()
    {
        return $this->getHelper()->getRedirectUrl('direct');
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
}
