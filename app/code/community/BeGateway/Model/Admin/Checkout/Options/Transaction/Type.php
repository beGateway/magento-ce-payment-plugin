<?php
/*
 * Copyright (C) 2016 BeGateway
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
 * @copyright   2016 BeGateway
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU General Public License, version 2 (GPL-2.0)
 */

/**
 * Class BeGateway_Model_Admin_Direct_Options_Transaction_Type
 *
 * Admin options Drop-down for Direct Transaction Types
 */
class BeGateway_Model_Admin_Checkout_Options_Transaction_Type
{
    /**
     * Pre-load the required files
     */
    public function __construct()
    {
    }
    /**
     * Return the transaction types for an Options field
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = array();

        foreach ($this->getTransactionTypes() as $code => $name) {
            $options[] = array(
                'value' => $code,
                'label' => $name
            );
        }

        return $options;
    }

    /**
     * Get the transaction types as:
     *
     * key   = Code Name
     * value = Localized Name
     *
     * @return array
     */
    protected function getTransactionTypes()
    {
      $helper = Mage::helper('begateway');
      return array(
          $helper::PAYMENT =>
              $helper->__('Payment'),
          $helper::AUTHORIZE =>
              $helper->__('Authorization'),
      );
    }
}
