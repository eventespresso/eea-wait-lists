<?php

namespace EventEspresso\WaitList\domain\services\collections;

use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\collections\Collection;

/**
 * Class WaitListFormHandlerCollection
 * a Collection of WaitListFormHandler objects for events with active Wait Lists
 *
 * @package EventEspresso\WaitList\domain\services\collections
 * @author  Brent Christensen
 *
 */
class WaitListFormHandlerCollection extends Collection
{

    /**
     * WaitListFormHandlerCollection constructor.
     *
     * @throws InvalidInterfaceException
     */
    public function __construct()
    {
        parent::__construct('EventEspresso\WaitList\domain\services\forms\WaitListFormHandler');
    }
}
