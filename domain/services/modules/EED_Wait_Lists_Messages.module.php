<?php

use EventEspresso\core\domain\Domain as CoreDomain;
use EventEspresso\core\domain\entities\Context;
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
        //notification triggers on promotion from waitlist
        add_action(
            'AHEE__UpdateRegistrationWaitListMetaDataCommandHandler__handle__registration_promoted',
            array('EED_Wait_Lists_Messages', 'trigger_wait_list_promotion_notifications'),
            10,
            3
        );
    }


    public static function trigger_wait_list_promotion_notifications(
        EE_Registration $registration,
        EE_Event $event,
        Context $context = null
    ) {
        //check context before triggering.
        if ($context instanceof Context
            && (
                $context->slug() === Domain::CONTEXT_REGISTRATION_STATUS_CHANGE_FROM_WAITLIST_AUTO_PROMOTE
                || (
                    $context->slug() === CoreDomain::CONTEXT_REGISTRATION_STATUS_CHANGE_REGISTRATION_ADMIN_NOTIFY
                    && EE_Registry::instance()->CAP->current_user_can(
                        'ee_send_message',
                        'triggering_waitlist_promotion_notification'
                    )
                )
            )
        ) {
            try {
                self::trigger_wait_list_notifications(array($registration));
                if ($context->slug() ===
                    CoreDomain::CONTEXT_REGISTRATION_STATUS_CHANGE_REGISTRATION_ADMIN_NOTIFY
                ) {
                    EE_Error::add_success(
                        esc_html__(
                            'Messages have been successfully queued for generation and sending.',
                            'event_espresso'
                        )
                    );
                }
            } catch (Exception $e) {
                if ($context->slug() ===
                    CoreDomain::CONTEXT_REGISTRATION_STATUS_CHANGE_REGISTRATION_ADMIN_NOTIFY
                ) {
                    EE_Error::add_error($e->getMessage(), __FILE__, __FUNCTION__, __LINE__);
                }
            }
        }
    }



    /**
     * Method used to trigger waitlist notifications for the given registrations and message type.
     *
     * @param EE_Registration[] $registrations
     * @param string            $message_type_slug
     */
    protected static function trigger_wait_list_notifications($registrations, $message_type_slug = Domain::MESSAGE_TYPE_WAITLIST_CAN_REGISTER)
    {
        self::_load_controller();
        if ($registrations) {
            self::$_MSG_PROCESSOR->generate_for_all_active_messengers(
                $message_type_slug,
                (array) $registrations
            );
        }
    }
}
