<?php

namespace EventEspresso\WaitList\domain\services\commands;

use EventEspresso\core\services\commands\CommandHandler;
use EventEspresso\WaitList\domain\services\event\WaitListEventMeta;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class WaitListCommandHandler
 * Shared logic for wait list command handlers
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
abstract class WaitListCommandHandler extends CommandHandler
{



    /**
     * @param WaitListEventMeta $event_meta
     */
    private $event_meta;



    /**
     * WaitListCommandHandler constructor.
     *
     * @param WaitListEventMeta $event_meta
     */
    public function __construct(WaitListEventMeta $event_meta)
    {
        $this->event_meta = $event_meta;
    }



    /**
     * @return mixed
     */
    public function eventMeta()
    {
        return $this->event_meta;
    }


}
