<?php

namespace EventEspresso\WaitList\domain\services\commands;

use EE_Event;
use EventEspresso\core\services\commands\Command;

defined('EVENT_ESPRESSO_VERSION') || exit;


/**
 * Class PromoteWaitListRegistrantsCommand
 * DTO for passing data to PromoteWaitListRegistrantsCommandHandler
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class PromoteWaitListRegistrantsCommand extends Command
{

    /**
     * @var EE_Event $event
     */
    private $event;

    /**
     * @var int|float $spaces_remaining
     */
    private $spaces_remaining;


    /**
     * PromoteWaitListRegistrantsCommand constructor.
     *
     * @param EE_Event $event
     * @param int|float $spaces_remaining
     */
    public function __construct(EE_Event $event, $spaces_remaining)
    {
        $this->event = $event;
        $this->spaces_remaining = $spaces_remaining === EE_INF ? $spaces_remaining : absint($spaces_remaining);
    }


    /**
     * @return EE_Event
     */
    public function getEvent()
    {
        return $this->event;
    }


    /**
     * @return int|float
     */
    public function getSpacesRemaining()
    {
        return $this->spaces_remaining;
    }


}
