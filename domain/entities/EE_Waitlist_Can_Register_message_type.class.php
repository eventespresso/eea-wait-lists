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
class EE_Waitlist_Can_Register_message_type extends EE_message_type
{

    /**
     * EE_Waitlist_Can_Register_message_type constructor.
     */
    public function __construct()
    {
        $this->name = Domain::MESSAGE_TYPE_WAITLIST_CAN_REGISTER;
        $this->description = esc_html__(
            'Triggered when registration is opened up for those on the waitlist and will send out notifications to all '
            . 'wait-list registrants.',
            'event_espresso'
        );
        $this->label = array(
            'singular' => esc_html__('waitlist registrations notification', 'event_espresso'),
            'plural'   => esc_html__('waitlist registration notifications', 'event_espresso'),
        );
        $this->_master_templates = array(
            'email' => 'registration',
        );
        parent::__construct();
    }



    /**
     * sets the _admin_settings_fields property which needs to be defined by child classes.
     * You will want to set the _admin_settings_fields properties as a multi-dimensional array with the following format
     * array(
     *        {field_name - also used for setting index} => array(
     *            'field_type' => {type of field: 'text', 'textarea', 'checkbox'},
     *            'value_type' => {type of value: 'string', 'int', 'array', 'bool'},
     *            'required' => {bool, required or not},
     *            'validation' => {bool, true if we want validation, false if not},
     *            'format' => {%d, or %s},
     *            'label' => {label for the field, make sure it's localized},
     *            'default' => {default value for the setting}
     *        ),
     * );
     */
    protected function _set_admin_settings_fields()
    {
        $this->_admin_settings_fields = array();
    }



    /**
     * sets any properties on whether a message type or messenger interface shows up on a ee administration page.
     * Child classes have to define this method but don't necessarily have to set the flags
     * as they will be set to false by default.
     * Child classes use this method to set the `_admin_registered_page` property.
     * That property is to indicate what EE admin pages we have a corresponding callback for in the child class
     * so Message Type/messenger fields/content is included on that admin page.
     */
    protected function _set_admin_pages()
    {
        $this->admin_registered_pages = array(
            'events_edit' => true,
        );
    }



    /**
     * This sets the data handler for the message type.  It must be used to define the _data_handler property.  It is
     * called when messages are setup.
     */
    protected function _set_data_handler()
    {
        $this->_data_handler = 'Registrations';
        $this->_single_message = $this->_data instanceof EE_Registration;
    }



    /**
     * This method should return a EE_Base_Class object (or array of EE_Base_Class objects) for the given context and
     * ID (which should be the primary key id for the base class).  Client code doesn't have to know what a message
     * type's data handler is.
     *
     * @since 4.5.0
     * @param string          $context      This should be a string matching a valid context for the message type.
     * @param EE_Registration $registration Need a registration to ensure that the data is valid (prevents people
     *                                      guessing a url).
     * @param int             $id           Optional. Integer corresponding to the value for the primary key of a
     *                                      EE_Base_Class_Object
     * @return EE_Registration[]
     */
    protected function _get_data_for_context($context, EE_Registration $registration, $id)
    {
        return array($registration);
    }



    /**
     * _set_contexts
     * This sets up the contexts associated with the message_type
     */
    protected function _set_contexts()
    {
        $this->_context_label = array(
            'label'       => esc_html__('recipient', 'event_espresso'),
            'plural'      => esc_html__('recipients', 'event_espresso'),
            'description' => esc_html__("Recipient's are who will receive the message.", 'event_espresso'),
        );
        $this->_contexts = array(
            'attendee' => array(
                'label'       => __('Registrant', 'event_espresso'),
                'description' => __('This template goes to registrants on the waitlist', 'event_espresso'),
            ),
        );
    }



    /**
     * Override default _attendee_addressees in EE_message_type because we want to loop through the registrations
     * for EE_message_type.
     *
     * @throws EE_Error
     */
    protected function _attendee_addressees()
    {
        $addressee = array();
        //looping through registrations
        foreach ($this->_data->registrations as $reg_id => $details) {
            // reset $attendee array with default data on each loop
            $aee = $this->_default_addressee_data;
            //need to get the attendee from this registration.
            $attendee = isset($details['att_obj']) && $details['att_obj'] instanceof EE_Attendee
                ? $details['att_obj']
                : null;
            // If we don't have an attendee object or the $addressee array
            // already has a object generated for this attendee, then let's just continue.
            if (! $attendee instanceof EE_Attendee
                || isset($addressee[$attendee->ID()])
            ) {
                continue;
            }
            //set $aee from attendee object
            $aee['att_obj'] = $attendee;
            $aee['reg_objs'] = isset($this->_data->attendees[$attendee->ID()]['reg_objs'])
                ? $this->_data->attendees[$attendee->ID()]['reg_objs']
                : array();
            $aee['attendee_email'] = $attendee->email();
            $aee['tkt_objs'] = isset($this->_data->attendees[$attendee->ID()]['tkt_objs'])
                ? $this->_data->attendees[$attendee->ID()]['tkt_objs']
                : array();
            if (isset($this->_data->attendees[$attendee->ID()]['evt_objs'])) {
                $aee['evt_objs'] = $this->_data->attendees[$attendee->ID()]['evt_objs'];
                $aee['events'] = $this->_data->attendees[$attendee->ID()]['evt_objs'];
            } else {
                $aee['evt_objs'] = $aee['events'] = array();
            }
            $aee['reg_obj'] = isset($details['reg_obj'])
                ? $details['reg_obj']
                : null;
            $aee['attendees'] = $this->_data->attendees;
            $addressee[$attendee->ID()] = new EE_Messages_Addressee($aee);
        }
        return $addressee;
    }
}
