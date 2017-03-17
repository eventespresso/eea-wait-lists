<?php
namespace EventEspresso\WaitList;

use EE_Event;
use EEM_Registration;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class WaitList
 * domain data regarding the Wait List
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         1.0.0
 */
class WaitList
{

    /**
     * Meta Key used for tracking the number of spaces allocated for Wait List Registrations,
     * ie: the number of registrants that can sign up for the Wait List
     */
    const SPACES_META_KEY = 'ee_wait_list_spaces';

    /**
     * Meta Key used for tracking whether or not to automatically promote RWL registrants to RPP
     */
    const AUTO_PROMOTE_META_KEY = 'ee_wait_list_auto_promote';

    /**
     * Meta Key used for tracking how many Wait List registrations are under manual control
     */
    const MANUAL_CONTROL_SPACES_META_KEY = 'ee_wait_list_manual_control_spaces';

    /**
     * Meta Key used for tracking number of registrants signed up for the wait list
     */
    const REG_COUNT_META_KEY = 'ee_wait_list_reg_count';

    /**
     * Meta Key used for tracking when a registrant first signed up for the wait list
     * which is then also used as a flag to identify all registrants that have been on a wait list
     */
    const REG_SIGNED_UP_META_KEY = 'ee_wait_list_reg_signed_up';

    /**
     * Meta Key used for tracking when a registrant was moved from wait list to another reg status
     */
    const REG_PROMOTED_META_KEY = 'ee_wait_list_reg_promoted';

    /**
     * Meta Key used for tracking when a registrant was moved from another reg status BACK to the  wait list
     */
    const REG_DEMOTED_META_KEY = 'ee_wait_list_reg_demoted';



    /**
     * @param EE_Event $event
     * @return int
     * @throws \EE_Error
     */
    public static function waitListRegCount(EE_Event $event)
    {
        return EEM_Registration::instance()->count(
            array(
                array(
                    'STS_ID' => EEM_Registration::status_id_wait_list,
                    'EVT_ID' => $event->ID(),
                ),
            )
        );
    }

}
// End of file WaitList.php
// Location: EventEspresso\WaitList/WaitList.php