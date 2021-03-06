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
 * Class BeGateway_Observer_CheckoutSubmitAllAfter
 */
class BeGateway_Observer_CheckoutSubmitAllAfter
{
    /**
     * Observer Event Handler
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function handleAction($observer)
    {
        $event = $observer->getEvent();

        return $this;
    }

}
