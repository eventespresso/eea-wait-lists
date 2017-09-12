<?php

use EventEspresso\WaitList\domain\Domain;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed');

class EE_Messages_Email_Registration_Added_To_Waitlist_Validator extends EE_Messages_Validator
{
    public function __construct($fields, $context)
    {
        $this->_m_name = 'email';
        $this->_mt_name = Domain::MESSAGE_TYPE_REGISTRATION_ADDED_TO_WAIT_LIST;
        parent::__construct($fields, $context);
    }



    /**
     *
     */
    protected function _modify_validator()
    {
        $new_config = $this->_messenger->get_validator_config();
        //modify just event_list
        $new_config['event_list'] = array(
            'shortcodes' => array(
                'event',
                'attendee_list',
                'ticket_list',
                'datetime_list',
                'venue',
                'organization',
                'event_author',
                'primary_registration_details',
                'primary_registration_list',
                'recipient_details',
                'recipient_list'
            ),
            'required' => array('[EVENT_LIST]')
        );
        $this->_messenger->set_validator_config($new_config);

        $this->_valid_shortcodes_modifier[$this->_context]['event_list'] = array(
            'event',
            'attendee_list',
            'ticket_list',
            'datetime_list',
            'venue',
            'organization',
            'event_author',
            'primary_registration_details',
            'primary_registration_list',
            'recipient_details',
            'recipient_list'
        );

        $this->_specific_shortcode_excludes['content'] = array('[DISPLAY_PDF_URL]', '[DISPLAY_PDF_BUTTON]');
    }
}
