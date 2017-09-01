<?php

namespace EventEspresso\WaitList\domain\services\commands;

use EE_Error;
use EE_Event;
use EE_Registration;
use EEM_Registration;
use EventEspresso\core\exceptions\InvalidEntityException;
use EventEspresso\core\services\commands\CommandInterface;
use EventEspresso\WaitList\domain\services\event\WaitListEventMeta;
use EventEspresso\WaitList\domain\services\registration\WaitListRegistrationMeta;

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
     * @var WaitListRegistrationMeta $registration_meta
     */
    private $registration_meta;



    /**
     * UpdateRegistrationWaitListMetaDataCommandHandler constructor.
     *
     * @param WaitListEventMeta        $event_meta
     * @param WaitListRegistrationMeta $registration_meta
     */
    public function __construct(WaitListEventMeta $event_meta, WaitListRegistrationMeta $registration_meta)
    {
        parent::__construct($event_meta);
        $this->registration_meta = $registration_meta;
    }



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
                do_action(
                    'AHEE__UpdateRegistrationWaitListMetaDataCommandHandler__handle__registration_promoted',
                    $registration,
                    $event,
                    $this
                );
                add_filter(
                    'FHEE__Registrations_Admin_Page___set_registration_status_from_request__notify',
                    function($notify = false, $REG_IDs = array()) use ($registration) {
                        if(in_array($registration->ID(), $REG_IDs, true)){
                            $notify = false;
                        }
                        return $notify;
                    },
                    10, 2
                );

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
                do_action(
                    'AHEE__UpdateRegistrationWaitListMetaDataCommandHandler__handle__registration_demoted',
                    $registration,
                    $event,
                    $this
                );
            } else {
                $this->registration_meta->addRegistrationSignedUp($registration);
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
     * @param EE_Event        $event
     * @param string          $new_STS_ID
     * @throws EE_Error
     */
    private function addMetaDataWhenRegistrationPromoted(EE_Registration $registration, EE_Event $event, $new_STS_ID)
    {
        $this->registration_meta->addRegistrationPromoted($registration);
        // Approved registrations are guaranteed a space right away,
        // but we need a way to track registrations that were promoted from the wait list to pending payment
        if ($new_STS_ID === EEM_Registration::status_id_pending_payment) {
            $promoted_reg_ids = $this->eventMeta()->getPromotedRegIdsArray($event);
            $promoted_reg_ids[$registration->ID()] = current_time('mysql', true);
            $this->eventMeta()->updatePromotedRegIdsArray($event, $promoted_reg_ids);
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
        $this->registration_meta->addRegistrationDemoted($registration);
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
        $this->registration_meta->addRegistrationRemoved($registration);
    }


}
