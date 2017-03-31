<?php
defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');



/**
 * Module for handling wait list integration with the messages system.
 *
 * @package    EventEspresso\WaitList
 * @subpackage modules
 * @author     Darren Ethier
 * @since      1.0.0
 */
class EED_Wait_Lists_Messages extends EED_Messages
{

    /**
     * Called for setting any necessary hooks on the frontend
     */
    public static function set_hooks()
    {
        self::_set_shared_hooks();
    }



    /**
     * Called for setting any necessary hooks in the admin (or on ajax requests).
     */
    public static function set_hooks_admin()
    {
        self::_set_shared_hooks();
    }



    /**
     * Helper currently used for setting hooks that are active both in the frontend and in the admin.
     */
    protected static function _set_shared_hooks()
    {
        add_action(
            'AHEE__EventEspresso_WaitList_WaitListMonitor__promoteWaitListRegistrants__after_registrations_promoted',
            array('EED_Wait_Lists_Messages', 'trigger_wait_list_notifications'),
            10,
            2
        );
    }



    /**
     * Callback for
     * AHEE__EventEspresso_WaitList_WaitListMonitor__promoteWaitListRegistrants__after_registrations_promoted
     *
     * @param EE_Registration[] $registrations
     * @param EE_Event|null     $event
     */
    public static function trigger_wait_list_notifications(array $registrations, EE_Event $event = null)
    {
        self::_load_controller();
        self::$_MSG_PROCESSOR->generate_for_all_active_messengers('waitlist_can_register', $registrations);
    }

}
