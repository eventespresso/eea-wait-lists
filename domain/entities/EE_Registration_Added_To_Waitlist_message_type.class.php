<?php

use EventEspresso\WaitList\domain\Domain;

class EE_Registration_Added_To_Waitlist_message_type extends EE_Registration_Base_message_type
{
    /**
     * EE_Registration_Demoted_To_Waitlist_message_type constructor.
     */
    public function __construct()
    {
        $this->name              = Domain::MESSAGE_TYPE_REGISTRATION_ADDED_TO_WAIT_LIST;
        $this->description       = esc_html__(
            'Triggered when an attendee signs up for a wait list.',
            'event_espresso'
        );
        $this->label             = array(
            'singular' => esc_html__('registration added to wait list notification', 'event_espresso'),
            'plural'   => esc_html__('registration added to wait list notifications', 'event_espresso'),
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
        $this->_context_label = array(
            'label'       => esc_html__('recipient', 'event_espresso'),
            'plural'      => esc_html__('recipients', 'event_espresso'),
            'description' => esc_html__('A recipient will receive the message.', 'event_espresso'),
        );
        $this->_contexts = array(
            'admin'    => array(
                'label'       => esc_html__('Event Admin', 'event_espresso'),
                'description' => esc_html__(
                    'Messages based on this template sent to the Event Administrator (event author) when users sign up to the waitlist.',
                    'event_espresso'
                ),
            ),
            'attendee' => array(
                'label'       => esc_html__('Registrant', 'event_espresso'),
                'description' => esc_html__(
                    'Messages based on this template are sent to attendees when they sign up for the wait list.',
                    'event_espresso'
                ),
            ),
        );
    }
}
