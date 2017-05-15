<?php

namespace EventEspresso\WaitList\domain\services\event;

use EE_Error;
use EE_Event;
use EventEspresso\WaitList\domain\Constants;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class WaitListEventMeta
 * class for interacting with wait list related event meta
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class WaitListEventMeta
{

    public function getWaitListSpaces(EE_Event $event)
    {
        return absint($event->get_extra_meta(Constants::SPACES_META_KEY, true));
    }


    /**
     * @param EE_Event $event
     * @return int
     * @throws EE_Error
     */
    public function getRegCount(EE_Event $event)
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
    public function getPromotedRegIdsArray(EE_Event $event)
    {
        $promoted_reg_ids = $event->get_extra_meta(
            Constants::PROMOTED_REG_IDS_META_KEY, false, array(array())
        );
        return reset($promoted_reg_ids);
    }



    /**
     * @param EE_Event $event
     * @return int
     * @throws EE_Error
     */
    public function getPromotedRegIdsArrayCount(EE_Event $event)
    {
        $promoted_reg_ids = $this->getPromotedRegIdsArray($event);
        return count($promoted_reg_ids);
    }



    /**
     * @param EE_Event $event
     * @return mixed
     * @throws EE_Error
     */
    public function getPromoteWaitListRegistrants(EE_Event $event)
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
    public function getAutoPromote(EE_Event $event)
    {
        return filter_var(
            $event->get_extra_meta(Constants::AUTO_PROMOTE_META_KEY, true, false),
            FILTER_VALIDATE_BOOLEAN
        );
    }

}
