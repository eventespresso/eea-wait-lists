<?php
namespace EventEspresso\WaitList;

use EE_Event;
use EE_Registration;
use EED_Messages;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class WaitListNotificationsManager
 * triggers Wait List related notifications
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class WaitListNotificationsManager
{

    /**
     * @var EE_Registration[] $registrations
     */
    private $registrations;

    /**
     * @var EE_Event $event
     */
    private $event;



    /**
     * WaitListNotificationsManager constructor
     *
     * @param EE_Registration[] $registrations
     * @param EE_Event          $event
     */
    public function __construct(array $registrations, EE_Event $event)
    {
        $this->registrations = $registrations;
        $this->event = $event;
    }



    public function triggerNotifications()
    {
        // \EEH_Debug_Tools::printr($this->registrations, '$this->registrations', __FILE__, __LINE__);
        // \EEH_Debug_Tools::printr($this->event, '$this->event', __FILE__, __LINE__);
        $REG_IDs = array();
        foreach ($this->registrations as $registration) {
            $REG_IDs[] = $registration->ID();
        }
        // just temporary to show that notices are getting triggered
        EED_Messages::process_resend(
            array(
                '_REG_ID'       => $REG_IDs,
                'registrations' => $this->registrations,
                'event'         => $this->event,
            )
        );
    }


}
// End of file WaitListNotificationsManager.php
// Location: EventEspresso\WaitList/WaitListNotificationsManager.php