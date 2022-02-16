<?php

namespace EventEspresso\WaitList\domain\services\event;

use EE_Error;
use EE_Event;
use EE_Registration;
use EventEspresso\WaitList\domain\Domain;

/**
 * Class WaitListEventMeta
 * class for interacting with wait list related event meta
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 *
 */
class WaitListEventMeta
{
    /**
     * @param EE_Event $event
     * @return int
     * @throws EE_Error
     */
    public function getWaitListSpaces(EE_Event $event)
    {
        return absint($event->get_extra_meta(Domain::META_KEY_WAIT_LIST_SPACES, true));
    }


    /**
     * @param EE_Event $event
     * @param int      $wait_list_spaces
     * @return bool|int
     * @throws EE_Error
     */
    public function updateWaitListSpaces(EE_Event $event, $wait_list_spaces)
    {
        return $event->update_extra_meta(Domain::META_KEY_WAIT_LIST_SPACES, absint($wait_list_spaces));
    }


    /**
     * @param EE_Event $event
     * @return int
     * @throws EE_Error
     */
    public function getRegCount(EE_Event $event)
    {
        return absint(
            $event->get_extra_meta(Domain::META_KEY_WAIT_LIST_REG_COUNT, true, 0)
        );
    }


    /**
     * @param EE_Event $event
     * @param int      $reg_count
     * @return bool|int
     * @throws EE_Error
     */
    public function updateRegCount(EE_Event $event, $reg_count)
    {
        return $event->update_extra_meta(
            Domain::META_KEY_WAIT_LIST_REG_COUNT,
            absint($reg_count)
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
            Domain::META_KEY_WAIT_LIST_PROMOTED_REG_IDS,
            false,
            array(array())
        );
        return reset($promoted_reg_ids);
    }


    /**
     * @param EE_Event $event
     * @param array    $promoted_reg_ids
     * @return bool|int
     * @throws EE_Error
     */
    public function updatePromotedRegIdsArray(EE_Event $event, array $promoted_reg_ids)
    {
        return $event->update_extra_meta(
            Domain::META_KEY_WAIT_LIST_PROMOTED_REG_IDS,
            $promoted_reg_ids
        );
    }


    /**
     * @param EE_Registration $registration
     * @param EE_Event        $event
     * @return bool|int
     * @throws EE_Error
     * @internal param array $promoted_reg_ids
     */
    public function removeRegistrationFromPromotedRegIdsArray(EE_Registration $registration, EE_Event $event)
    {
        $promoted_reg_ids = $this->getPromotedRegIdsArray($event);
        // remove this registration
        unset($promoted_reg_ids[ $registration->ID() ]);
        // resave the list of Reg IDs
        return $this->updatePromotedRegIdsArray($event, $promoted_reg_ids);
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
     * @return boolean
     * @throws EE_Error
     */
    public function getPromoteWaitListRegistrants(EE_Event $event)
    {
        return filter_var(
            $event->get_extra_meta(
                Domain::META_KEY_WAIT_LIST_PROMOTE_WAIT_LIST_REGISTRANTS,
                true,
                true
            ),
            FILTER_VALIDATE_BOOLEAN
        );
    }


    /**
     * @param EE_Event $event
     * @param bool     $promote_wait_list_registrants
     * @return bool|int
     * @throws EE_Error
     */
    public function updatePromoteWaitListRegistrants(EE_Event $event, $promote_wait_list_registrants)
    {
        return $event->update_extra_meta(
            Domain::META_KEY_WAIT_LIST_PROMOTE_WAIT_LIST_REGISTRANTS,
            filter_var(
                $promote_wait_list_registrants,
                FILTER_VALIDATE_BOOLEAN
            )
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
            $event->get_extra_meta(Domain::META_KEY_WAIT_LIST_AUTO_PROMOTE, true, false),
            FILTER_VALIDATE_BOOLEAN
        );
    }


    /**
     * @param EE_Event $event
     * @param bool     $auto_promote_registrants
     * @return bool|int
     * @throws EE_Error
     */
    public function updateAutoPromote(EE_Event $event, $auto_promote_registrants)
    {
        return $event->update_extra_meta(
            Domain::META_KEY_WAIT_LIST_AUTO_PROMOTE,
            filter_var(
                $auto_promote_registrants,
                FILTER_VALIDATE_BOOLEAN
            )
        );
    }


    /**
     * @param EE_Event $event
     * @return int
     * @throws EE_Error
     */
    public function getManualControlSpaces(EE_Event $event)
    {
        return absint(
            $event->get_extra_meta(
                Domain::META_KEY_WAIT_LIST_MANUALLY_CONTROLLED_SPACES,
                true,
                0
            )
        );
    }


    /**
     * @param EE_Event $event
     * @param int      $manual_control_spaces
     * @return bool|int
     * @throws EE_Error
     */
    public function updateManualControlSpaces(EE_Event $event, $manual_control_spaces)
    {
        return $event->update_extra_meta(
            Domain::META_KEY_WAIT_LIST_MANUALLY_CONTROLLED_SPACES,
            absint($manual_control_spaces)
        );
    }


    /**
     * @param EE_Event $event
     * @return bool|int
     * @throws EE_Error
     */
    public function getPerformSoldOutStatusCheck(EE_Event $event)
    {
        return $event->get_extra_meta(
            Domain::META_KEY_WAIT_LIST_PERFORM_SOLD_OUT_STATUS_CHECK,
            true,
            true
        );
    }


    /**
     * @param EE_Event $event
     * @param bool     $perform_sold_out_status_check
     * @return bool|int
     * @throws EE_Error
     */
    public function updatePerformSoldOutStatusCheck(EE_Event $event, $perform_sold_out_status_check)
    {
        return $event->update_extra_meta(
            Domain::META_KEY_WAIT_LIST_PERFORM_SOLD_OUT_STATUS_CHECK,
            filter_var(
                $perform_sold_out_status_check,
                FILTER_VALIDATE_BOOLEAN
            )
        );
    }
}
