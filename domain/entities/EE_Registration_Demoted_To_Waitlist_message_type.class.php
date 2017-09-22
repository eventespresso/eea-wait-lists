<?php

use EventEspresso\WaitList\domain\Domain;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

class EE_Registration_Demoted_To_Waitlist_message_type extends EE_Waitlist_Message_Type_Base
{
    /**
     * EE_Registration_Demoted_To_Waitlist_message_type constructor.
     */
    public function __construct()
    {
        $this->name = Domain::MESSAGE_TYPE_WAIT_LIST_DEMOTION;
        $this->description = esc_html__(
            'Triggered when an attendee is automatically or manually demoted to a wait list.',
            'event_espresso'
        );
        $this->label = array(
            'singular' => esc_html__('registration demoted to wait list notification', 'event_espresso'),
            'plural'   => esc_html__('registration demoted to wait list notifications', 'event_espresso'),
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
            'registrant' => array(
                'label'       => esc_html__('Registrant', 'event_espresso'),
                'description' => esc_html__(
                    'This template goes to registrations that were automatically demoted to the wait list.',
                    'event_espresso'
                ),
            ),
        );
    }
}
