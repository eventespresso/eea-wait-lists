<?php

use EventEspresso\WaitList\domain\Domain;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');



/**
 * This is used when validating shortcodes that can be used for the registered fields and contexts for the
 * `waitlist_can_register` message type and the `email` messenger.
 *
 * @package    EventEspresso\Waitlists
 * @subpackage messages
 * @author     Darren Ethier
 * @since      1.0.0
 */
class EE_Messages_Email_Waitlist_Can_Register_Validator extends EE_Messages_Validator
{

    /**
     * EE_Messages_Email_Waitlist_Can_Register_Validator constructor.
     *
     * @param array $fields
     * @param       $context
     * @throws EE_Error
     */
    public function __construct($fields, $context)
    {
        $this->_m_name = 'email';
        $this->_mt_name = Domain::MESSAGE_TYPE_WAITLIST_PROMOTION;
        parent::__construct($fields, $context);
    }



    /**
     * Used to modify various properties after construction.  A way of customizing defaults set by parent.
     */
    protected function _modify_validator()
    {
        $new_config = $this->_MSGR->get_validator_config();
        $new_config['datetime_list']['shortcodes'] =  array('datetime');
        $new_config['content']['shortcodes'] = array(
            'recipient_waitlist',
            'organization',
            'primary_registration_list',
            'primary_registration_details',
            'email',
            'transaction',
            'payment_list',
            'venue',
            'event',
            'messenger',
            'ticket',
            'recipient_details',
        );
        $new_config['subject']['shortcodes'] = array(
            'organization',
            'primary_registration_details',
            'email',
            'event',
            'transaction'
        );
        $this->_MSGR->set_validator_config($new_config);
        $this->_specific_shortcode_excludes = array('[DISPLAY_PDF_URL]', '[DISPLAY_PDF_BUTTON]');
    }
}
