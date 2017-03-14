<?php
namespace EventEspresso\WaitList;

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
    const REG_COUNT_META_KEY = 'ee_wait_list_registration_count';

}
// End of file WaitList.php
// Location: EventEspresso\WaitList/WaitList.php