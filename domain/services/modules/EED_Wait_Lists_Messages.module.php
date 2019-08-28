<?php

use EventEspresso\core\domain\Domain as CoreDomain;
use EventEspresso\core\domain\entities\contexts\ContextInterface;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\WaitList\domain\Domain;

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

        // notification triggers on promotion from wait list
        add_action(
            'AHEE__UpdateRegistrationWaitListMetaDataCommandHandler__handle__registration_promoted',
            array('EED_Wait_Lists_Messages', 'trigger_wait_list_promotion_notifications'),
            10,
            3
        );

        // notification triggers on demotion to wait list.
        add_action(
            'AHEE__UpdateRegistrationWaitListMetaDataCommandHandler__handle__registration_demoted',
            array('EED_Wait_Lists_Messages', 'trigger_wait_list_demotion_notifications'),
            10,
            3
        );

        // notification triggers adding to wait list (not demoted).
        add_action(
            'FHEE__EEH_MSG_Template__reg_status_to_message_type_array',
            array(
                'EED_Wait_Lists_Messages',
                'register_add_registration_to_waitlist_message_type_with_registration_status_map'
            )
        );
        add_action(
            'AHEE__EventEspresso_WaitList_domain_services_commands_CreateWaitListRegistrationsCommandHandler__createRegistrations',
            array('EED_Wait_Lists_Messages', 'trigger_registration_add_to_waitlist_messages')
        );
    }


    /**
     * Callback on AHEE__UpdateRegistrationWaitListMetaDataCommandHandler__handle__registration_promoted.
     * This gets called when a registration status changes from RWL to a registration status allowing payments.
     *
     * @param EE_Registration $registration
     * @param EE_Event        $event
     * @param ContextInterface|null    $context
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws EE_Error
     */
    public static function trigger_wait_list_promotion_notifications(
        EE_Registration $registration,
        EE_Event $event,
        ContextInterface $context = null
    ) {
        // only trigger if it's a primary registrant
        if (! $registration->is_primary_registrant()) {
            return;
        }
        // check context before triggering.
        if ($context instanceof ContextInterface
            && (
                $context->slug() === Domain::CONTEXT_REGISTRATION_STATUS_CHANGE_FROM_WAIT_LIST_AUTO_PROMOTE
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
        if (is_admin()) {
            // if called from admin, let's exclude this registration from being processed by the admin.
            self::exclude_processing_notification_by_admin($registration);
        }
    }


    /**
     * Callback for AHEE__UpdateRegistrationWaitListMetaDataCommandHandler__handle__registration_demoted
     * This will fire whenever a registration is demoted from a status that allows payments to the RWL registration
     * status.
     *
     * @param EE_Registration $registration
     * @param EE_Event        $event
     * @param ContextInterface|null    $context
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws EE_Error
     */
    public static function trigger_wait_list_demotion_notifications(
        EE_Registration $registration,
        EE_Event $event,
        ContextInterface $context = null
    ) {
        // check context before triggering.
        if ($context === null
            || (
                $context instanceof ContextInterface
                && ($context->slug() === CoreDomain::CONTEXT_REGISTRATION_STATUS_CHANGE_REGISTRATION_ADMIN_NOTIFY
                    && EE_Registry::instance()->CAP->current_user_can(
                        'ee_send_message',
                        'triggering_waitlist_demotion_notification'
                    )
                )
            )
        ) {
            try {
                self::trigger_wait_list_notifications(
                    array($registration),
                    Domain::MESSAGE_TYPE_WAIT_LIST_DEMOTION
                );
                if ($context instanceof ContextInterface
                    && $context->slug() === CoreDomain::CONTEXT_REGISTRATION_STATUS_CHANGE_REGISTRATION_ADMIN_NOTIFY
                ) {
                    EE_Error::add_success(
                        esc_html__(
                            'Messages have been successfully queued for generation and sending.',
                            'event_espresso'
                        )
                    );
                }
            } catch (Exception $e) {
                if ($context instanceof ContextInterface
                    && $context->slug() === CoreDomain::CONTEXT_REGISTRATION_STATUS_CHANGE_REGISTRATION_ADMIN_NOTIFY
                ) {
                    EE_Error::add_error($e->getMessage(), __FILE__, __FUNCTION__, __LINE__);
                }
            }
        }

        if (is_admin()) {
            self::exclude_processing_notification_by_admin($registration);
        }
    }



    public static function register_add_registration_to_waitlist_message_type_with_registration_status_map(
        $registration_status_to_message_type_map
    ) {
        $registration_status_to_message_type_map[ EEM_Registration::status_id_wait_list ] =
            Domain::MESSAGE_TYPE_REGISTRATION_ADDED_TO_WAIT_LIST;
        return $registration_status_to_message_type_map;
    }



    /**
     * Callback on AHEE__EventEspresso_WaitList_domain_services_commands_CreateWaitListRegistrationsCommandHandler__createRegistrations.
     *
     * @param EE_Registration[] $registrations_added_to_waitlist
     */
    public static function trigger_registration_add_to_waitlist_messages(array $registrations_added_to_waitlist)
    {
        try {
            self::_load_controller();
            // grab one of the registrations to get the transaction
            $registration = reset($registrations_added_to_waitlist);
            $transaction = $registration instanceof EE_Registration ? $registration->transaction() : null;
            $messages_to_generate = self::$_MSG_PROCESSOR->setup_mtgs_for_all_active_messengers(
                Domain::MESSAGE_TYPE_REGISTRATION_ADDED_TO_WAIT_LIST,
                array($transaction)
            );
            self::$_MSG_PROCESSOR->batch_queue_for_generation_and_persist($messages_to_generate);
            self::$_MSG_PROCESSOR->get_queue()->initiate_request_by_priority();
        } catch (Exception $e) {
            if (WP_DEBUG) {
                EE_Error::add_error($e->getMessage(), __FILE__, __FUNCTION__, __LINE__);
            }
        }
    }



    /**
     * Method used to trigger wait list notifications for the given registrations and message type.
     *
     * @param EE_Registration[] $registrations
     * @param string            $message_type_slug
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    protected static function trigger_wait_list_notifications(
        $registrations,
        $message_type_slug = Domain::MESSAGE_TYPE_WAIT_LIST_PROMOTION
    ) {
        self::_load_controller();
        if ($registrations) {
            self::$_MSG_PROCESSOR->generate_for_all_active_messengers(
                $message_type_slug,
                (array) $registrations
            );
        }
    }


    /**
     * Used to set a filter for excluding the given registration from being processed by the admin when its included
     * in a registration changing status.
     *
     * @param EE_Registration $registration
     * @throws EE_Error
     */
    protected static function exclude_processing_notification_by_admin(EE_Registration $registration)
    {
        add_filter(
            'FHEE__Registrations_Admin_Page___set_registration_status_from_request__REG_IDs',
            // exclude these registrations from normal admin notifications when status manually changed.
            // notifications will be handled for these registrations by EED_Waitlist_Messages.
            function ($registrations_ids) use ($registration) {
                if (false !== (
                    $key = array_search($registration->ID(), (array) $registrations_ids, true)
                    )
                ) {
                    unset($registrations_ids[ $key ]);
                }
                return $registrations_ids;
            }
        );
    }
}
