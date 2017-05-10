<?php

namespace EventEspresso\WaitList\domain\services\commands;

use EE_Error;
use EE_Event;
use EventEspresso\core\services\commands\CommandHandler;
use EventEspresso\WaitList\domain\Constants;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class WaitListCommandHandler
 * Shared logic for wait list command handlers
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
abstract class WaitListCommandHandler extends CommandHandler
{



    /**
     * @param EE_Event $event
     * @return int
     * @throws EE_Error
     */
    protected function getRegCount(EE_Event $event)
    {
        return absint(
            $event->get_extra_meta(Constants::REG_COUNT_META_KEY, true, 0)
        );
    }



    /**
     * @param EE_Event $event
     * @return array
     * @throws EE_Error
     */
    protected function getPromotedRegIdsArray(EE_Event $event)
    {
        $promoted_reg_ids = $event->get_extra_meta(
            Constants::PROMOTED_REG_IDS_META_KEY, false, array(array())
        );
        return reset($promoted_reg_ids);
    }



    /**
     * @param EE_Event $event
     * @return mixed
     * @throws EE_Error
     */
    protected function getPromoteWaitListRegistrants(EE_Event $event)
    {
        return filter_var(
            $event->get_extra_meta(
                Constants::PROMOTE_WAIT_LIST_REGISTRANTS_META_KEY,
                true,
                true
            ),
            FILTER_VALIDATE_BOOLEAN
        );
    }



    /**
     * @param EE_Event $event
     * @return boolean
     * @throws EE_Error
     */
    protected function getAutoPromote(EE_Event $event)
    {
        return filter_var(
            $event->get_extra_meta(Constants::AUTO_PROMOTE_META_KEY, true, false),
            FILTER_VALIDATE_BOOLEAN
        );
    }

}
