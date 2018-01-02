<?php
namespace EventEspresso\WaitList\domain\services\collections;

use EE_Error;
use EE_Event;
use EEM_Event;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidEntityException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\collections\Collection;
use InvalidArgumentException;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class WaitListEventsCollection
 * a Collection of EE_Event objects that have been tagged as having an active wait list
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * 
 */
class WaitListEventsCollection extends Collection
{

    /**
     * WaitListEventsCollection constructor.
     *
     * @throws InvalidInterfaceException
     * @throws EE_Error
     * @throws InvalidEntityException
     * @throws InvalidDataTypeException
     * @throws InvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct('EE_Event');
        $wait_list_events = EEM_Event::instance()->get_active_and_upcoming_events(
            array(
                array(
                    'EVT_allow_overflow' => true,
                ),
            )
        );
        if (! empty($wait_list_events) && is_array($wait_list_events)) {
            foreach ($wait_list_events as $wait_list_event) {
                if ($wait_list_event instanceof EE_Event) {
                    $this->add($wait_list_event, $wait_list_event->ID());
                }
            }
        }
    }

}
// End of file WaitListEventsCollection.php
// Location: wp-content/plugins/eea-wait-lists/WaitListEventsCollection.php
