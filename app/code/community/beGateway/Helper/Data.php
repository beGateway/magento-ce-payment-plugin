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
 * Class beGateway_Helper_Data
 *
 * Helper functions for beGateway
 */
class beGateway_Helper_Data extends Mage_Core_Helper_Abstract
{
    const RAW_DETAILS_TRANSACTION_TYPE = 'transaction_type';
    const RAW_DETAILS_TERMINAL_TOKEN = 'terminal_token';

    const SECURE_TRANSACTION_TYPE_SUFFIX = '3-D';

    const ADDITIONAL_INFO_KEY_STATUS           = 'status';
    const ADDITIONAL_INFO_KEY_TRANSACTION_TYPE = 'type';
    const ADDITIONAL_INFO_KEY_REDIRECT_URL     = 'redirect_url';
    const ADDITIONAL_INFO_KEY_PAYMENT_METHOD   = 'payment_method_type';
    const ADDITIONAL_INFO_KEY_TEST             = 'test';

    const AUTHORIZE                            = 'authorization';
    const PAYMENT                              = 'payment';
    const CAPTURE                              = 'capture';
    const VOID                                 = 'void';
    const REFUND                               = 'refund';

    const CREDIT_CARD                          = 'credit_card';
    const CREDIT_CARD_HALVA                    = 'credit_card_halva';
    const ERIP                                 = 'erip';

    const PENDING                              = 'pending';
    const INCOMPLETE                           = 'incomplete';
    const SUCCESSFUL                           = 'successful';
    const FAILED                               = 'failed';
    const ERROR                                = 'error';

    public function initLibrary()
    {
        if (!class_exists('\beGateway\Settings', false)) {
            // @codingStandardsIgnoreStart
            include Mage::getBaseDir('lib') . DS . 'beGateway' . DS . 'lib' . DS . 'beGateway.php';
            // @codingStandardsIgnoreEnd
        }
    }

    /**
     * Check whether beGateway is initialized and init if not
     *
     * @param string $model Name of the model, for which we query settings
     *
     * @return void
     */
    public function initClient($model)
    {
        $this->initLibrary();

        \beGateway\Settings::$shopId = $this->getConfigData(
          $model,
          'shop_id'
        );

        \beGateway\Settings::$shopKey = $this->getConfigData(
          $model,
          'shop_pass'
        );

        \beGateway\Settings::$gatewayBase =
          'https://' . $this->getConfigData(
            $model,
            'domain_gateway'
          );

        \beGateway\Settings::$checkoutBase =
          'https://' . $this->getConfigData(
            $model,
            'domain_checkout'
          );
    }

    /**
     * Get Module Configuration Key
     *
     * @param string $model Name of the Model
     * @param string $key Configuration Key
     *
     * @return mixed The content of the requested key
     */
    public function getConfigData($model, $key)
    {
        return Mage::getStoreConfig(
            sprintf(
                'payment/%s/%s',
                $model,
                $key
            )
        );
    }

    /**
     * Get A Success URL
     *
     * @see  API Documentation
     *
     * @param string $model Name of the Model (Checkout/Direct)
     *
     * @return string
     */
    public function getSuccessURL($model)
    {
        return Mage::getUrl(
            sprintf(
                'begateway/%s/success',
                $model
            ),
            array(
                '_secure' => true
            )
        );
    }

    /**
     * Get A Failure URL
     *
     * @param string $model Name of the Model (Checkout/Direct)
     *
     * @return string
     */
    public function getFailureURL($model)
    {
        return Mage::getUrl(
            sprintf(
                'begateway/%s/failure',
                $model
            ),
            array(
                '_secure' => true
            )
        );
    }

    /**
     * Get A Cancel URL
     *
     * @param string $model Name of the Model (Checkout/Direct)
     *
     * @return string
     */
    public function getCancelURL($model)
    {
        return Mage::getUrl(
            sprintf(
                'begateway/%s/cancel',
                $model
            ),
            array(
                '_secure' => true
            )
        );
    }

    /**
     * Get A Notification URL
     *
     * @param string $model Name of the Model (Checkout/Direct)
     *
     * @return string
     */
    public function getNotifyURL($model)
    {
        return Mage::getUrl(
            sprintf(
                'begateway/%s/notify',
                $model
            ),
            array(
                '_secure' => true
            )
        );
    }

    /**
     * Get a Redirect URL for the module
     *
     * @param string $model Name of the Model (Checkout/Direct)
     *
     * @return string
     */
    public function getRedirectUrl($model)
    {
        return Mage::getUrl(
            sprintf(
                'begateway/%s/redirect',
                $model
            ),
            array(
                '_secure' => true
            )
        );
    }

    /**
     * Generate Transaction Id based on the order id
     * and salted to avoid duplication
     *
     * @param string $orderId
     *
     * @return string
     */
    public function genTransactionId($orderId = null)
    {
        $hash = Mage::helper('core')->uniqHash();
        if (empty($orderId)) {
          return $hash;
        }

        return sprintf(
                    "%s_%s",
                    strval($orderId),
                    $hash
                );
    }

    /**
     * Get the current locale in 2-digit i18n format
     *
     * @return string
     */
    public function getLocale()
    {
        $languageCode = substr(
            strtolower(
                Mage::app()->getLocale()->getLocaleCode()
            ),
            0,
            2
        );

        return $languageCode;
    }

    /**
     * Return checkout session instance
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    public function getCustomerSession()
    {
        return Mage::getSingleton('customer/session');
    }

    /**
     * Return sales quote instance for specified ID
     *
     * @param int $quoteId Quote identifier
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote($quoteId)
    {
        return Mage::getModel('sales/quote')->load(
            abs(
                (int) $quoteId
            )
        );
    }

    /**
     * Generates an array from Payment Gateway Response Object
     * @param \stdClass $response
     * @return array
     */
    public function getArrayFromGatewayResponse($response)
    {
        try {
          $arResponse = $response->getResponseArray();

          $money = new \beGateway\Money;

          if (isset($arResponse['transaction'])) {

            $arResponse = $arResponse['transaction'];

            if (isset($arResponse['amount'])) {
              $money->setCents($arResponse['amount']);
              $money->setCurrency($arResponse['currency']);
              $arResponse['amount'] = $money->getAmount();
            }

            if (isset($arResponse['credit_card'])) {
              $arResponse['credit_card'] =
                $arResponse['credit_card']['first_1'] . ' xxxx ' .
                $arResponse['credit_card']['last_4'];

              if (isset($arResponse['credit_card']['sub_brand']))
                $arResponse['credit_card_sub_brand'] =
                  $arResponse['credit_card']['sub_brand'];

              if (isset($arResponse['credit_card']['product']))
                $arResponse['credit_card_product'] =
                  $arResponse['credit_card']['product'];
            }

            if (isset($arResponse['type'])) {
              $arResponse = array_merge($arResponse, $arResponse[$arResponse['type']]);
            }
          }

          if (isset($arResponse['checkout'])) {
            $arResponse = $arResponse['checkout'];
          }

          foreach ($arResponse as $p => $v) {
            if (!is_array($v))
              $transaction_details[$p] = (string)$v;
          }

        } catch (Exception $e) {
          $transaction_details = array();
        }
        return $transaction_details;
    }

    /**
     * Get DESC list of specific transactions from payment object
     *
     * @param Mage_Sales_Model_Order_Payment    $payment
     * @param array|string                      $typeFilter
     * @return array
     */
    public function getTransactionFromPaymentObject($payment, $typeFilter)
    {
        $transactions = array();

        $collection = Mage::getModel('sales/order_payment_transaction')->getCollection()
            ->setOrderFilter($payment->getOrder())
            ->addPaymentIdFilter($payment->getId())
            ->addTxnTypeFilter($typeFilter)
            ->setOrder('created_at', Varien_Data_Collection::SORT_ORDER_DESC);

        /** @var Mage_Sales_Model_Order_Payment_Transaction $txn */
        foreach ($collection as $txn) {
            $transactions[] = $txn->setOrderPaymentObject($payment);
        }

        return $transactions;
    }

    /**
     * Restore customer Quote
     *
     * @param $shouldCancel
     * @return bool
     */
    public function restoreQuote($shouldCancel = false)
    {
        $order = $this->getCheckoutSession()->getLastRealOrder();

        if ($order->getId()) {
            $quote = $this->getQuote($order->getQuoteId());

            if ($shouldCancel && $order->canCancel()) {
                $order->cancel()->save();
            }

            if ($quote->getId()) {
                $quote->setIsActive(1)
                    ->setReservedOrderId(null)
                    ->save();
                $this->getCheckoutSession()
                    ->replaceQuote($quote)
                    ->unsLastRealOrderId();

                return true;
            }
        }

        return false;
    }

    /**
     * Set an order status based on transaction status
     *
     * @param Mage_Sales_Model_Order $order
     * @param string $status
     * @param string $message
     */
    public function setOrderState($order, $status, $message = '')
    {
        $this->initLibrary();

        switch ($status) {
            case self::SUCCESSFUL:
                $order
                    ->setState(
                        Mage_Sales_Model_Order::STATE_PROCESSING,
                        Mage_Sales_Model_Order::STATE_PROCESSING,
                        $message,
                        false
                    )
                    ->save();
                break;

                case self::INCOMPLETE:
                case self::PENDING:
                $order
                    ->setState(
                        Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                        Mage_Sales_Model_Order::STATE_PENDING_PAYMENT,
                        $message,
                        false
                    )
                    ->save();
                break;

                case self::FAILED:
                case self::ERROR:
                /** @var Mage_Sales_Model_Order_Invoice $invoice */
                foreach ($order->getInvoiceCollection() as $invoice) {
                    $invoice->cancel();
                }

                $order
                    ->registerCancellation($message)
                    ->setCustomerNoteNotify(true)
                    ->save();

                break;

            default:
                $order->save();
                break;
        }
    }

    /**
     * Redirect the visitor to the login page if
     * they are not logged in
     *
     * @param string $target Alternative target, if you don't want to redirect to login
     *
     * @return void
     */
    public function redirectIfNotLoggedIn($target = null)
    {
        /** @var Mage_Customer_Helper_Data $customer */
        $customer = Mage::helper('customer');

        /** @var Mage_Core_Helper_Url $url */
        $url = Mage::helper('core/url');

        if (!$customer->isLoggedIn()) {
            $target = $target ? $target : Mage::getUrl('customer/account/login', array('_secure' => true));

            $this->getCustomerSession()->setBeforeAuthUrl(
                $url->getCurrentUrl()
            );

            Mage::app()
                ->getFrontController()
                ->getResponse()
                ->setRedirect($target)
                ->sendHeaders();
        }
    }

    /**
     * @param string $model
     * @param string $key
     * @return bool
     */
    public function getConfigBoolValue($model, $key)
    {
        return
            filter_var(
                $this->getConfigData(
                    $model,
                    $key
                ),
                FILTER_VALIDATE_BOOLEAN
            );
    }

    /**
     * @param string $method
     * @return bool
     */
    public function getIsMethodActive($method)
    {
        return $this->getConfigBoolValue($method, 'active');
    }

    /**
     * Returns true if the WebSite is configured over Secured SSL Connection
     * @return bool
     */
    public function getIsSecureConnectionEnabled()
    {
        return (bool) Mage::app()->getStore()->isCurrentlySecure();
    }

    /**
     * @param string $method
     * @param Mage_Sales_Model_Quote $quote
     * @param bool $requiresSecureConnection
     * @return bool
     */
    public function getIsMethodAvailable($method, $quote, $requiresSecureConnection = false)
    {
        return
            $this->getIsMethodActive($method) &&
            (!$requiresSecureConnection || $this->getIsSecureConnectionEnabled());
    }

    /**
     * Get the Remote Address of the machine
     * @return string
     */
    public function getRemoteAddress()
    {
        $remoteAddress = Mage::helper('core/http')->getRemoteAddr(false);

        if (!$remoteAddress && function_exists("gethostbyname")) {
            $requestHostName = Mage::app()->getRequest()->getHttpHost();

            $remoteAddress = gethostbyname($requestHostName);
        }

        return $remoteAddress ?: "127.0.0.1";
    }

    /**
     * Extracts a Transaction Param Value from Transaction Additional Information
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @return string|null
     */
    public function getBeGatewayPaymentTransactionParam($transaction, $paramName)
    {
        if (!is_object($transaction) || !$transaction->getId()) {
            return null;
        }

        $transactionRawDetails = $transaction->getAdditionalInformation(
            Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS
        );

        return
            isset($transactionRawDetails[$paramName])
                ? $transactionRawDetails[$paramName]
                : null;
    }

    /**
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @return string|null
     */
    public function getBeGatewayPaymentTransactionType($transaction)
    {
        return $this->getBeGatewayPaymentTransactionParam(
            $transaction,
            self::RAW_DETAILS_TRANSACTION_TYPE
        );
    }

    /**
     * @param Mage_Sales_Model_Order_Payment_Transaction $transaction
     * @return string|null
     */
    public function getBeGatewayPaymentTransactionToken($transaction)
    {
        return $this->getBeGatewayPaymentTransactionParam(
            $transaction,
            self::RAW_DETAILS_TERMINAL_TOKEN
        );
    }

    /**
     * Get Admin Session (Used to display Success and Error Messages)
     * @return Mage_Core_Model_Session_Abstract
     */
    public function getAdminSession()
    {
        return Mage::getSingleton("adminhtml/session");
    }

    /**
     * Returns the current datetime as a formatted MySQL Datetime Value
     * @return string
     */
    public function formatCurrentDateTimeToMySQLDateTime()
    {
        return Mage::getSingleton('core/date')->gmtDate();
    }
}
