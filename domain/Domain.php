<?php

namespace EventEspresso\WaitList\domain;

use EventEspresso\core\domain\DomainBase;

/**
 * Class Domain
 * domain data regarding the Wait List
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         1.0.0
 */
class Domain extends DomainBase
{
    /**
     * EE Core Version Required for Add-on
     */
    const CORE_VERSION_REQUIRED = '4.9.59.rc.055';

    /**
     * Event Meta Key used for tracking the number of spaces allocated for Wait List Registrations,
     * ie: the number of registrants that can sign up for the Wait List
     */
    const META_KEY_WAIT_LIST_SPACES = 'ee_wait_list_spaces';

    /**
     * Event Meta Key used for tracking whether or not to automatically promote RWL registrants to RPP
     */
    const META_KEY_WAIT_LIST_AUTO_PROMOTE = 'ee_wait_list_auto_promote';

    /**
     * Event Meta Key used for tracking how many Wait List registrations are under manual control
     */
    const META_KEY_WAIT_LIST_MANUALLY_CONTROLLED_SPACES = 'ee_wait_list_manual_control_spaces';

    /**
     * Event Meta Key used for tracking number of registrants signed up for the wait list
     */
    const META_KEY_WAIT_LIST_REG_COUNT = 'ee_wait_list_reg_count';

    /**
     * Event Meta Key used for tracking whether or not to proceed with promoting wait list registrants
     */
    const META_KEY_WAIT_LIST_PROMOTE_WAIT_LIST_REGISTRANTS = 'ee_wait_list_promote_wait_list_regs';

    /**
     * Event Meta Key used for tracking IDs of registrants promoted from the wait list to pending payment
     */
    const META_KEY_WAIT_LIST_PROMOTED_REG_IDS = 'ee_wait_list_promoted_reg_IDs';

    /**
     * Event Meta Key used for tracking whether or not to perform a sold out status check for the event
     */
    const META_KEY_WAIT_LIST_PERFORM_SOLD_OUT_STATUS_CHECK = 'ee_wait_list_perform_sold_out_status_check';

    /**
     * Registration Meta Key used for tracking when a registrant first signed up for the wait list
     * which is then also used as a flag to identify all registrants that have been on a wait list
     */
    const META_KEY_WAIT_LIST_REG_SIGNED_UP = 'ee_wait_list_reg_signed_up';

    /**
     * Registration Meta Key used for tracking when a registrant was moved from wait list to a reg status that can pay
     */
    const META_KEY_WAIT_LIST_REG_PROMOTED = 'ee_wait_list_reg_promoted';

    /**
     * Registration Meta Key used for tracking when a registrant was moved from another reg status BACK to the wait list
     */
    const META_KEY_WAIT_LIST_REG_DEMOTED = 'ee_wait_list_reg_demoted';

    /**
     * Registration Meta Key used for tracking when a registrant was removed completely from wait list to a "closed"
     * reg status
     */
    const META_KEY_WAIT_LIST_REG_REMOVED = 'ee_wait_list_reg_removed';

    /**
     * value to be used for the LOG_type field in the esp_log table
     */
    const LOG_TYPE_WAIT_LIST = 'wait_list';

    /**
     * Slug representing the Wait List Promotion Notification message type.
     */
    const MESSAGE_TYPE_WAIT_LIST_PROMOTION = 'waitlist_can_register';


    /**
     * Slug representing the Wait List Demotion Notification message type.
     */
    const MESSAGE_TYPE_WAIT_LIST_DEMOTION = 'registration_demoted_to_waitlist';


    /**
     * Slug representing the message type for when registrations are added to the wait-ist (not demoted).
     */
    const MESSAGE_TYPE_REGISTRATION_ADDED_TO_WAIT_LIST = 'registration_added_to_waitlist';


    /**
     * Slug representing the context where a registration is auto promoted from the wait list.
     */
    const CONTEXT_REGISTRATION_STATUS_CHANGE_FROM_WAIT_LIST_AUTO_PROMOTE = 'auto_promoted_from_waitlist';

    /**
     * Registration Meta Key used for tracking when a registrant has confirmed their desire to be on the wait list
     */
    const META_KEY_WAIT_LIST_SPACE_CONFIRMED = 'ee_wait_list_space_confirmed';
}
