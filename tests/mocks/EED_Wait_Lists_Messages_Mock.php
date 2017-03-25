<?php
defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');

class EED_Wait_Lists_Messages_Mock extends EED_Wait_Lists_Messages
{
    /**
     * Expose processor for tests.
     * @return EE_Messages_Processor
     */
    public static function get_processor()
    {
        return self::$_MSG_PROCESSOR;
    }
}