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
 * @since         $VID:$
 */
class CalculateEventSpacesAvailableCommand extends Command
{


    /**
     * @var EE_Event $event
     */
    private $event;

    /**
     * @var int $spaces_available
     */
    private $spaces_available;



    /**
     * CalculateEventSpacesAvailableCommand constructor.
     *
     * @param EE_Event $event
     * @param int      $spaces_available
     */
    public function __construct(EE_Event $event, $spaces_available)
    {
        $this->event = $event;
        $this->spaces_available = absint($spaces_available);
    }



    /**
     * @return EE_Event
     */
    public function getEvent()
    {
        return $this->event;
    }



    /**
     * @return int
     */
    public function getSpacesAvailable()
    {
        return $this->spaces_available;
    }


}
