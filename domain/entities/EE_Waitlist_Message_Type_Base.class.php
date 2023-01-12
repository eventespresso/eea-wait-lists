<?php

/**
 * Base Message Type for wait list message types to inherit from.
 *
 * @package    EventEspresso\Waitlists
 * @subpackage messages
 * @author     Darren Ethier
 * @since      1.0.0
 */
class EE_Waitlist_Message_Type_Base extends EE_message_type
{
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
     * @see parent::get_priority() for documentation
     * @return int
     */
    public function get_priority()
    {
        return EEM_Message::priority_medium;
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
        $this->_data_handler = 'REG';
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
     * @return EE_Registration
     */
    protected function _get_data_for_context($context, EE_Registration $registration, $id)
    {
        return $registration;
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
            'description' => esc_html__("Recipients are who will receive the message.", 'event_espresso'),
        );
        $this->_contexts = array(
            'admin'    => array(
                'label'       => esc_html__('Event Admin', 'event_espresso'),
                'description' => esc_html__(
                    'This template will be used to generate the message from the context of Event Administrator (event author).',
                    'event_espresso'
                ),
            ),
            'registrant' => array(
                'label'       => esc_html__('Registrant', 'event_espresso'),
                'description' => esc_html__(
                    'This template goes to individual registrations promoted from the wait list.',
                    'event_espresso'
                ),
            ),
        );
    }


    protected function _set_valid_shortcodes()
    {
        parent::_set_valid_shortcodes();
        $included_shortcodes = array(
            'recipient_waitlist',
            'recipient_details',
            'organization',
            'event',
            'ticket',
            'venue',
            'primary_registration_details',
            'event_author',
            'email',
            'event_meta',
            'recipient_list',
            'transaction',
        );
        $this->_valid_shortcodes['registrant'] = $included_shortcodes;
    }


    /**
     * Override default _attendee_addressees in EE_message_type because we want to loop through the registrations
     * for EE_message_type.
     *
     * @return EE_Messages_Addressee[]
     * @throws EE_Error
     */
    protected function _registrant_addressees()
    {
        $addressee          = array();
        $data_for_addressee = array();

        // just looping through the attendees to make sure that the attendees listed are JUST for this registration.
        foreach ($this->_data->attendees[ $this->_data->reg_obj->attendee_ID() ] as $item => $value) {
            $data_for_addressee[ $item ] = $value;
        }

        $related_event = reset($this->_data->events);
        $data_for_addressee['user_id']   = $related_event['event'] instanceof EE_Event ? $related_event['event']->get('EVT_wp_user') : 0;
        $data_for_addressee['events']    = $this->_data->events;
        $data_for_addressee['reg_obj']   = $this->_data->reg_obj;
        $data_for_addressee['attendees'] = $this->_data->attendees;
        $data_for_addressee              = array_merge($this->_default_addressee_data, $data_for_addressee);
        $addressee[]                     = new EE_Messages_Addressee($data_for_addressee);
        return $addressee;
    }
}
