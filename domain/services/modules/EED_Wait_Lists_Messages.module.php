<?php

use EventEspresso\WaitList\domain\Domain;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');



/**
 * Module for handling wait list integration with the messages system.
 *
 * @package    EventEspresso\Constants
 * @subpackage modules
 * @author     Darren Ethier
 * @since      1.0.0
 */
class EED_Wait_Lists_Messages extends EED_Messages
{


    /**
     * Used to queue up manually promoted registrations (usually via the admin) for triggering one batch message send
     * with.
     *
     * @var array
     */
    protected static $manually_promoted_registrations = array();

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
        //notifications on auto-promotion
        add_action(
            'AHEE__EventEspresso_WaitList_WaitListMonitor__promoteWaitListRegistrants__after_registrations_promoted',
            array('EED_Wait_Lists_Messages', 'trigger_wait_list_notifications')
        );
        //notifications on manual promotion
        //add the registration to our property for tracking registrations manually promoted.
        add_action(
            'AHEE__UpdateRegistrationWaitListMetaDataCommandHandler__handle__registration_promoted',
            array('EED_Wait_Lists_Messages', 'track_registration_manually_promoted')
        );
        add_action(
            'AHEE__Registrations_Admin_Page___set_registration_status_from_request__end',
            array('EED_Wait_Lists_Messages', 'trigger_manually_promoted_wait_list_notifications'),
            10,
            2
        );
    }


    /**
     * Callback for AHEE__UpdateRegistrationWaitListMetaDataCommandHandler__handle__registration_promoted
     * used to track registrations that are manually promoted on a request.
     *
     * @param EE_Registration $registration
     * @throws EE_Error
     */
    public static function track_registration_manually_promoted(EE_Registration $registration)
    {
        self::$manually_promoted_registrations[$registration->ID()] = $registration;
    }


    /**
     * Callback on AHEE__Registrations_Admin_Page___set_registration_status_from_request_end
     * Used to trigger notifications for registrations manually promoted.  This is done using registrations found in the
     * manually_promoted_registrations property.
     *
     * @param array $registration_ids
     * @param bool  $notify
     * @throws InvalidArgumentException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     */
    public static function trigger_manually_promoted_wait_list_notifications($registration_ids, $notify)
    {
        if ($notify
            && self::$manually_promoted_registrations
            && EE_Registry::instance()->CAP->current_user_can(
                'ee_send_message',
                'send_registrations_notification_for_manual_waitlist_promotion'
            )
        ) {
            try {
                self::trigger_wait_list_notifications(self::$manually_promoted_registrations);
                EE_Error::add_success(
                    esc_html__('Messages have been successfully queued for generation and sending.', 'event_espresso')
                );
            } catch (Exception $e) {
                EE_Error::add_error($e->getMessage(), __FILE__, __FUNCTION__, __LINE__);
            }
        }
    }


    /**
     * Callback for
     * AHEE__EventEspresso_WaitList_WaitListMonitor__promoteWaitListRegistrants__after_registrations_promoted
     *
     * @param EE_Registration[] $registrations
     */
    public static function trigger_wait_list_notifications($registrations)
    {
        self::_load_controller();
        if ($registrations) {
            self::$_MSG_PROCESSOR->generate_for_all_active_messengers(
                Domain::MESSAGE_TYPE,
                (array)$registrations
            );
        }
    }


    /**
     * Override parent to just make sure any registrations being tracked on the static property are reset.
     */
    protected static function _load_controller()
    {
        self::$manually_promoted_registrations = array();
        parent::_load_controller();
    }

}
