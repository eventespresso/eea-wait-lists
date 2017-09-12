<?php

use EventEspresso\WaitList\domain\Domain;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');



/**
 * Message type for handling notifications to those on a waitlist when there are registrations available.
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
        $this->name = Domain::MESSAGE_TYPE_WAITLIST_PROMOTION;
        $this->description = esc_html__(
            'Triggered for registrations are promoted from a wait-list and are able to finalize their registration for the event.',
            'event_espresso'
        );
        $this->label = array(
            'singular' => esc_html__('registration promoted to wait-list notification', 'event_espresso'),
            'plural'   => esc_html__('registration promoted to wait-list notifications', 'event_espresso'),
        );
        $this->_master_templates = array(
            'email' => 'registration',
        );
        parent::__construct();
    }
}
