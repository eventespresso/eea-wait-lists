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
        $this->label       = esc_html__('Recipient Waitlist Shortcodes', 'event_espresso');
        $this->description = esc_html__(
            'These are shortcodes that are specific to the Waitlist message type.',
            'event_espresso'
        );
        $this->_shortcodes = array(
            '[RECIPIENT_WAITLIST_REGISTRATION_URL]' => esc_html__(
                'This returns the generated url for the wait-list registrant to complete the signup process.',
                'event_espresso'
            ),
        );
    }


    /**
     * Parser for shortcode
     *
     * @param string $shortcode Shortcode being parsed
     * @return string            Results of parser.
     */
    protected function _parser($shortcode)
    {
        //make sure we end up with the EE_Messages_Addressee object
        $this->_recipient = $this->_data instanceof EE_Messages_Addressee ? $this->_data : null;
        $this->_recipient = ! $this->_recipient instanceof EE_Messages_Addressee
                            && is_array($this->_data)
                            && isset($this->_data['data'])
                            && $this->_data['data'] instanceof EE_Messages_Addressee
            ? $this->_data['data'] :
            $this->_recipient;
        $this->_recipient = ! $this->_recipient instanceof EE_Messages_Addressee
                            && ! empty($this->_extra_data['data'])
                            && $this->_extra_data['data'] instanceof EE_Messages_Addressee
            ? $this->_extra_data['data']
            : $this->_recipient;

        if (! $this->_recipient instanceof EE_Messages_Addressee) {
            return '';
        }

        switch ($shortcode) {
            case '[RECIPIENT_WAITLIST_REGISTRATION_URL]':
                if (! $this->_recipient->reg_obj instanceof EE_Registration) {
                    return '';
                }
                return EED_Wait_Lists::wait_list_checkout_url($this->_recipient->reg_obj->get_primary_registration());
        }
        return '';
    }
}
