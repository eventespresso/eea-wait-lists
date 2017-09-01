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
        $this->_mt_name = Domain::MESSAGE_TYPE;
        parent::__construct($fields, $context);
    }



    /**
     * Used to modify various properties after construction.  A way of customizing defaults set by parent.
     */
    protected function _modify_validator()
    {
        if ($this->_context === 'attendee') {
            $this->_valid_shortcodes_modifier[$this->_context]['from'] = array(
                'recipient_details',
                'email',
                'organization',
            );
        }
        $this->_specific_shortcode_excludes = array('[DISPLAY_PDF_URL]', '[DISPLAY_PDF_BUTTON]');
    }
}
