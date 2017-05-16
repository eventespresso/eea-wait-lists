<?php

namespace EventEspresso\WaitList\domain\services\commands;

use EE_Error;
use EE_Event;
use EE_Registration;
use EEM_Registration;
use EventEspresso\core\exceptions\InvalidEntityException;
use EventEspresso\core\services\commands\CommandInterface;
use EventEspresso\WaitList\domain\Constants;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class UpdateRegistrationWaitListMetaDataCommandHandler
 * Updates Wait List Registrations and their corresponding Event when a status change occurs
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class UpdateRegistrationWaitListMetaDataCommandHandler extends WaitListCommandHandler
{



    /**
     * @param CommandInterface $command
     * @return boolean
     * @throws EE_Error
     * @throws InvalidEntityException
     */
    public function handle(CommandInterface $command)
    {
        if (! $command instanceof UpdateRegistrationWaitListMetaDataCommand) {
            throw new InvalidEntityException(
                $command,
                'EventEspresso\WaitList\domain\services\commands\UpdateRegistrationWaitListMetaDataCommand'
            );
        }
        $event = $command->getEvent();
        $registration = $command->getRegistration();
        $old_STS_ID = $command->getOldStatusId();
        $new_STS_ID = $command->getNewStatusId();
        $wait_list_reg_count = null;
        if ($old_STS_ID === EEM_Registration::status_id_wait_list) {
            $wait_list_reg_count = $this->eventMeta()->getRegCount($event);
            $wait_list_reg_count--;
            $this->eventMeta()->updateRegCount($event, $wait_list_reg_count);
            // if new status is Approved or Pending Payment, then YAY!!!
            if (in_array($new_STS_ID, EEM_Registration::reg_statuses_that_allow_payment(), true)) {
                $this->addMetaDataWhenRegistrationPromoted($registration, $event, $new_STS_ID);
            } else {
                // this guy ain't going to the event EVER !!!
                $this->addMetaDataWhenRegistrationRemoved($registration, $event);
            }
        } elseif ($new_STS_ID === EEM_Registration::status_id_wait_list) {
            $wait_list_reg_count = $this->eventMeta()->getRegCount($event);
            $wait_list_reg_count++;
            $this->eventMeta()->updateRegCount($event, $wait_list_reg_count);
            // if old status was Approved or Pending Payment, but they are being moved to the Wait List
            if (in_array($old_STS_ID, EEM_Registration::reg_statuses_that_allow_payment(), true)) {
                $this->addMetaDataWhenRegistrationDemoted($registration, $event, $old_STS_ID);
            } else {
                $this->addMetaDataWhenRegistrationSignsUp($registration);
            }
        } elseif ($old_STS_ID === EEM_Registration::status_id_pending_payment) {
            // don't need to track this reg anymore,
            // because they were either approved or cancelled altogether
            $this->eventMeta()->removeRegistrationFromPromotedRegIdsArray($registration, $event);
        }
        $perform_sold_out_status_check = $this->eventMeta()->getPerformSoldOutStatusCheck($event);
        if ($wait_list_reg_count !== null && $perform_sold_out_status_check) {
            // updating the reg status will trigger a sold out status check on the event,
            // which in turn will trigger WaitListMonitor::promoteWaitListRegistrants()
            // so let's turn that off while we do this, otherwise this registration
            // could just get set right back to the status it was previously at,
            // which can make it impossible to manually move a registration back to the wait list
            $this->eventMeta()->updatePromoteWaitListRegistrants($event, false);
            $event->perform_sold_out_status_check();
            $this->eventMeta()->updatePromoteWaitListRegistrants($event, true);
            return true;
        }
        return false;
    }




    /**
     * @param EE_Registration $registration
     * @throws EE_Error
     */
    private function addMetaDataWhenRegistrationSignsUp(EE_Registration $registration)
    {
        $registration->add_extra_meta(
            Constants::REG_SIGNED_UP_META_KEY,
            current_time('mysql', true)
        );
    }



    /**
     * @param EE_Registration $registration
     * @param EE_Event        $event
     * @param string          $new_STS_ID
     * @throws EE_Error
     */
    private function addMetaDataWhenRegistrationPromoted(EE_Registration $registration, EE_Event $event, $new_STS_ID)
    {
        $registration->add_extra_meta(
            Constants::REG_PROMOTED_META_KEY,
            current_time('mysql', true)
        );
        // Approved registrations are guaranteed a space right away,
        // but we need a way to track registrations that were promoted from the wait list to pending payment
        if ($new_STS_ID === EEM_Registration::status_id_pending_payment) {
            $promoted_reg_ids = $this->eventMeta()->getPromotedRegIdsArray($event);
            $promoted_reg_ids[$registration->ID()] = current_time('mysql', true);
            $event->update_extra_meta(
                Constants::PROMOTED_REG_IDS_META_KEY,
                $promoted_reg_ids
            );
        }
    }



    /**
     * @param EE_Registration $registration
     * @param EE_Event        $event
     * @param string          $old_STS_ID
     * @throws EE_Error
     */
    private function addMetaDataWhenRegistrationDemoted(EE_Registration $registration, EE_Event $event, $old_STS_ID)
    {
        $registration->add_extra_meta(
            Constants::REG_DEMOTED_META_KEY,
            current_time('mysql', true)
        );
        // don't track this registration anymore since they are back on the wait list
        if ($old_STS_ID === EEM_Registration::status_id_pending_payment) {
            $this->eventMeta()->removeRegistrationFromPromotedRegIdsArray($registration, $event);
        }
    }



    /**
     * @param EE_Registration $registration
     * @param EE_Event        $event
     * @throws EE_Error
     */
    private function addMetaDataWhenRegistrationRemoved(EE_Registration $registration, EE_Event $event)
    {
        $this->eventMeta()->removeRegistrationFromPromotedRegIdsArray($registration, $event);
        $registration->add_extra_meta(
            Constants::REG_REMOVED_META_KEY,
            current_time('mysql', true)
        );
    }


}
