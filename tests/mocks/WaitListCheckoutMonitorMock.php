<?php

namespace EventEspresso\WaitList\tests\mocks;

use EventEspresso\WaitList\domain\services\checkout\WaitListCheckoutMonitor;
use EventEspresso\WaitList\domain\services\registration\WaitListRegistrationMeta;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class WaitListCheckoutMonitor
 * Description
 *
 * @author  Brent Christensen
 *
 */
class WaitListCheckoutMonitorMock extends WaitListCheckoutMonitor
{

    /**
     * @return WaitListRegistrationMeta
     */
    public function getRegistrationMeta()
    {
        return $this->registration_meta;
    }


}
// Location: WaitListCheckoutMonitor.php
