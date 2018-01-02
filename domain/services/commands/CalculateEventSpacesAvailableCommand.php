<?php

namespace EventEspresso\WaitList\domain\services\commands;

use EE_Event;
use EventEspresso\core\services\commands\Command;

defined('EVENT_ESPRESSO_VERSION') || exit;


/**
 * Class CalculateEventSpacesAvailableCommand
 * DTO for passing data to CalculateEventSpacesAvailableCommandHandler
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * 
 */
class CalculateEventSpacesAvailableCommand extends Command
{


    /**
     * @var EE_Event $event
     */
    private $event;

    /**
     * @var int|float $spaces_available
     */
    private $spaces_available;


    /**
     * CalculateEventSpacesAvailableCommand constructor.
     *
     * @param EE_Event $event
     * @param int|float $spaces_available
     */
    public function __construct(EE_Event $event, $spaces_available)
    {
        $this->event = $event;
        $this->spaces_available = $spaces_available === EE_INF ? $spaces_available : (int)$spaces_available;
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
    public function getSpacesAvailable()
    {
        return $this->spaces_available;
    }


}
