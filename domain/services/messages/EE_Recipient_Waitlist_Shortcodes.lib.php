<?php
defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');



/**
 * Shortcode library for registration waitlist message types
 *
 * @package    EventEspresso\Waitlists
 * @subpackage messages
 * @author     Darren Ethier
 * @since      1.0.0
 */
class EE_Recipient_Waitlist_Shortcodes extends EE_Shortcodes
{


    /**
     * @var EE_Messages_Addressee
     */
    protected $_recipient;



    /**
     * Initialize main properties for library.
     */
    protected function _init_props()
    {
        $this->label = esc_html__('Recipient Waitlist Shortcodes', 'event_espresso');
        $this->description = esc_html__(
            'These are shortcodes that are specific to the Waitlist message type.',
            'event_espresso'
        );
        $this->_shortcodes = array(
            '[RECIPIENT_WAITLIST_REGISTRATION_URL]' => esc_html__(
                'This returns the generated url for the wait-list registrant to complete the sign-up process.',
                'event_espresso'
            ),
            '[RECIPIENT_WAITLIST_CONFIRMATION_URL]' => esc_html(
                'This returns the generated url for a registrant to confirm being added to a waitlist.',
                'event_espresso'
            )
        );
    }


    /**
     * Parser for shortcode
     *
     * @param string $shortcode Shortcode being parsed
     * @return string Results of parser.
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     */
    protected function _parser($shortcode)
    {
        //make sure we end up with a registration object.
        $messages_addressee = $this->_data instanceof EE_Messages_Addressee ? $this->_data : null;
        $messages_addressee = $this->_extra_data instanceof EE_Messages_Addressee
            ? $this->_extra_data
            : $messages_addressee;
        $registration = $messages_addressee instanceof EE_Messages_Addressee
            && $messages_addressee->reg_obj instanceof EE_Registration
            ? $messages_addressee->reg_obj
            : null;
        if (! $registration instanceof EE_Registration) {
            return '';
        }
        switch ($shortcode) {
            case '[RECIPIENT_WAITLIST_REGISTRATION_URL]':
                return EED_Wait_Lists::wait_list_checkout_url($registration);
            case '[RECIPIENT_WAITLIST_CONFIRMATION_URL]':
                //@todo add logic here for obtaining the confirmation url once that's done.
                return '';
        }
        return '';
    }
}
