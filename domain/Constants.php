<?php
namespace EventEspresso\WaitList\domain;

use EventEspresso\core\domain\ConstantsAbstract;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class Constants
 * domain data regarding the Wait List
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         1.0.0
 */
class Constants extends ConstantsAbstract
{

    /**
     * EE Core Version Required for Add-on
     */
    const CORE_VERSION_REQUIRED = '4.9.39.rc.006';

    /**
     * Event Meta Key used for tracking the number of spaces allocated for Wait List Registrations,
     * ie: the number of registrants that can sign up for the Wait List
     */
    const SPACES_META_KEY = 'ee_wait_list_spaces';

    /**
     * Event Meta Key used for tracking whether or not to automatically promote RWL registrants to RPP
     */
    const AUTO_PROMOTE_META_KEY = 'ee_wait_list_auto_promote';

    /**
     * Event Meta Key used for tracking how many Wait List registrations are under manual control
     */
    const MANUAL_CONTROL_SPACES_META_KEY = 'ee_wait_list_manual_control_spaces';

    /**
     * Event Meta Key used for tracking number of registrants signed up for the wait list
     */
    const REG_COUNT_META_KEY = 'ee_wait_list_reg_count';

    /**
     * Event Meta Key used for tracking whether or not to proceed with promoting wait list registrants
     */
    const PROMOTE_WAIT_LIST_REGISTRANTS_META_KEY = 'ee_wait_list_promote_wait_list_regs';

    /**
     * Event Meta Key used for tracking IDs of registrants promoted from the wait list to pending payment
     */
    const PROMOTED_REG_IDS_META_KEY = 'ee_wait_list_promoted_reg_IDs';

    /**
     * Event Meta Key used for tracking whether or not to perform a sold out status check for the event
     */
    const PERFORM_SOLD_OUT_STATUS_CHECK_META_KEY = 'ee_wait_list_perform_sold_out_status_check';

    /**
     * Registration Meta Key used for tracking when a registrant first signed up for the wait list
     * which is then also used as a flag to identify all registrants that have been on a wait list
     */
    const REG_SIGNED_UP_META_KEY = 'ee_wait_list_reg_signed_up';

    /**
     * Registration Meta Key used for tracking when a registrant was moved from wait list to a reg status that can pay
     */
    const REG_PROMOTED_META_KEY = 'ee_wait_list_reg_promoted';

    /**
     * Registration Meta Key used for tracking when a registrant was moved from another reg status BACK to the  wait list
     */
    const REG_DEMOTED_META_KEY = 'ee_wait_list_reg_demoted';

    /**
     * Registration Meta Key used for tracking when a registrant was removed completely from wait list to a "closed" reg status
     */
    const REG_REMOVED_META_KEY = 'ee_wait_list_reg_removed';

    /**
     * value to be used for the LOG_type field in the esp_log table
     */
    const LOG_TYPE = 'wait_list';


}
// End of file Constants.php
// Location: EventEspresso\Constants/Constants.php