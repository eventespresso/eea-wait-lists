<?php

use EventEspresso\WaitList\domain\Domain;

/**
 * Message type for handling notifications to those on a wait list when there are registrations available.
 *
 * @package    EventEspresso\Waitlists
 * @subpackage messages
 * @author     Darren Ethier
 * @since      1.0.0
 */
class EE_Waitlist_Can_Register_message_type extends EE_Waitlist_Message_Type_Base
{
    /**
     * EE_Waitlist_Can_Register_message_type constructor.
     */
    public function __construct()
    {
        $this->name = Domain::MESSAGE_TYPE_WAIT_LIST_PROMOTION;
        $this->description = esc_html__(
            'Triggered when an attendee is promoted from a wait list and has the option to finalize their registration for an event.',
            'event_espresso'
        );
        $this->label = array(
            'singular' => esc_html__('registration promoted from wait list notification', 'event_espresso'),
            'plural'   => esc_html__('registration promoted from wait list notifications', 'event_espresso'),
        );
        $this->_master_templates = array(
            'email' => 'registration',
        );
        parent::__construct();
    }

    /**
     * _set_contexts
     * This sets up the contexts associated with the message_type
     */
    public function _set_contexts()
    {
        parent::_set_contexts();
        $this->_contexts = array(
            'admin'    => array(
                'label'       => esc_html__('Event Admin', 'event_espresso'),
                'description' => esc_html__(
                    'This template goes to the Event Administrator (event author) when registrations are automatically promoted to the wait list.',
                    'event_espresso'
                ),
            ),
            'registrant' => array(
                'label'       => esc_html__('Registrant', 'event_espresso'),
                'description' => esc_html__(
                    'This template goes to registrations that were automatically promoted to the wait list.',
                    'event_espresso'
                ),
            ),
        );
    }
}
