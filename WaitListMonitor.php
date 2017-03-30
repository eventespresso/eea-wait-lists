<?php
namespace EventEspresso\WaitList;

use DomainException;
use EE_Error;
use EE_Event;
use EE_Registration;
use EE_Registry;
use EED_Wait_Lists;
use EEM_Registration;
use EventEspresso\core\exceptions\EntityNotFoundException;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidEntityException;
use EventEspresso\core\exceptions\InvalidFormSubmissionException;
use EventEspresso\core\services\collections\Collection;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class WaitListMonitor
 * tracks which event have active wait lists
 * and determines whether wait list forms should be displayed and processed for an event
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class WaitListMonitor
{

    /**
     * @var Collection $wait_list_events
     */
    private $wait_list_events;

    /**
     * @var boolean $perform_sold_out_status_check
     */
    private $perform_sold_out_status_check = true;

    /**
     * @var boolean $promote_wait_list_registrants
     */
    private $promote_wait_list_registrants = true;



    /**
     * WaitListMonitor constructor.
     *
     * @param Collection $wait_list_events
     */
    public function __construct(Collection $wait_list_events)
    {
        $this->wait_list_events = $wait_list_events;
    }



    /**
     * returns true if an event has an active wait list with available spaces
     *
     * @param EE_Event $event
     * @return bool
     * @throws EE_Error
     */
    protected function eventHasOpenWaitList(EE_Event $event)
    {
        if ($this->wait_list_events->hasObject($event)) {
            $wait_list_reg_count = absint($event->get_extra_meta(WaitList::REG_COUNT_META_KEY, true));
            $wait_list_spaces = absint($event->get_extra_meta(WaitList::SPACES_META_KEY, true));
            if ($wait_list_reg_count < $wait_list_spaces) {
                return true;
            }
        }
        return false;
    }



    /**
     * @param EE_Event $event
     * @return string
     * @throws LogicException
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws DomainException
     * @throws EE_Error
     */
    public function getWaitListFormForEvent(EE_Event $event)
    {
        if ($event->is_sold_out() && $this->eventHasOpenWaitList($event)) {
            $wait_list_form = new WaitListForm($event, EE_Registry::instance());
            return $wait_list_form->display();
        }
        return '';
    }



    /**
     * @param int $event_id
     * @return bool
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidEntityException
     * @throws InvalidFormSubmissionException
     * @throws LogicException
     * @throws RuntimeException
     */
    public function processWaitListFormForEvent($event_id)
    {
        if ($this->wait_list_events->has($event_id)) {
            /** @var EE_Event $event */
            $event = $this->wait_list_events->get($event_id);
            $wait_list_form = new WaitListForm($event, EE_Registry::instance());
            $attendee = $wait_list_form->process($_REQUEST);
            EE_Error::add_success(
                apply_filters(
                    'FHEE_EventEspresso_WaitList_WaitListMonitor__processWaitListFormForEvent__success_msg',
                    sprintf(
                        esc_html__('Thank You %1$s.%2$sYou have been successfully added to the Wait List for:%2$s%3$s',
                            'event_espresso'),
                        $attendee->full_name(),
                        '<br />',
                        $event->name()
                    )
                ),
                __FILE__, __FUNCTION__, __LINE__
            );
        }
        return false;
    }



    /**
     * increment or decrement the wait list reg count for an event
     * when a registration's status changes to or from RWL
     *
     * @param EE_Registration  $registration
     * @param                  $old_STS_ID
     * @param                  $new_STS_ID
     * @throws EE_Error
     * @throws EntityNotFoundException
     */
    public function registrationStatusUpdate(EE_Registration $registration, $old_STS_ID, $new_STS_ID)
    {
        $event = $registration->event();
        if ($this->wait_list_events->hasObject($event)) {
            $wait_list_reg_count = null;
            if ($old_STS_ID === EEM_Registration::status_id_wait_list) {
                $wait_list_reg_count = absint(
                    $event->get_extra_meta(WaitList::REG_COUNT_META_KEY, true)
                );
                $wait_list_reg_count--;
                $event->update_extra_meta(WaitList::REG_COUNT_META_KEY, $wait_list_reg_count);
                $registration->add_extra_meta(
                    WaitList::REG_DEMOTED_META_KEY,
                    current_time('mysql', true)
                );
            } elseif ($new_STS_ID === EEM_Registration::status_id_wait_list) {
                $wait_list_reg_count = absint(
                    $event->get_extra_meta(WaitList::REG_COUNT_META_KEY, true)
                );
                $wait_list_reg_count++;
                $event->update_extra_meta(WaitList::REG_COUNT_META_KEY, $wait_list_reg_count);
                $registration->add_extra_meta(
                    WaitList::REG_PROMOTED_META_KEY,
                    current_time('mysql', true)
                );
            }
            if ($wait_list_reg_count !== null && $this->perform_sold_out_status_check) {
                // updating the reg status will trigger a sold out status check on the event,
                // which in turn will trigger WaitListMonitor::promoteWaitListRegistrants()
                // so let's turn that off while we do this, otherwise this registration
                // could just get set right back to the status it was previously at,
                // which can make it impossible to manually move a registration back to the wait list
                $this->promote_wait_list_registrants = false;
                $event->perform_sold_out_status_check();
                $this->promote_wait_list_registrants = true;
            }
        }
    }



    /**
     * factors wait list registrations into calculations involving spaces available for events
     *
     * @param int      $spaces_available
     * @param EE_Event $event
     * @return int
     * @throws EE_Error
     */
    public function adjustEventSpacesAvailable($spaces_available, EE_Event $event)
    {
        if ($this->wait_list_events->hasObject($event)) {
            $wait_list_reg_count = absint(
                $event->get_extra_meta(WaitList::REG_COUNT_META_KEY, true)
            );
            // consider wait list registrations as taking available spaces
            $spaces_available -= $wait_list_reg_count;
        }
        return $spaces_available;
    }



    /**
     * If "auto promote" is turned on for an event with a wait list,
     * then registrations will automatically have their statuses changed from RWL
     * to whatever the event's default reg status is as spaces become available
     *
     * @param EE_Event $event
     * @param bool     $sold_out
     * @param int      $spaces_remaining
     * @throws EE_Error
     * @throws RuntimeException
     */
    public function promoteWaitListRegistrants(
        EE_Event $event,
        $sold_out = false,
        $spaces_remaining = 0
    ) {
        if ($this->promote_wait_list_registrants && $this->wait_list_events->hasObject($event)) {
            $wait_list_reg_count = absint(
                $event->get_extra_meta(WaitList::REG_COUNT_META_KEY, true)
            );
            $regs_to_promote = $spaces_remaining + $wait_list_reg_count;
            if ($regs_to_promote < 1) {
                return;
            }
            if ($this->manuallyPromoteRegistrations($event, $regs_to_promote, $wait_list_reg_count)) {
                return;
            }
            $this->autoPromoteRegistrations($event, $regs_to_promote);
        }
    }



    /**
     * @param EE_Event $event
     * @param int      $regs_to_promote
     * @param int      $wait_list_reg_count
     * @return bool
     * @throws EE_Error
     */
    private function manuallyPromoteRegistrations(EE_Event $event, $regs_to_promote, $wait_list_reg_count)
    {
        $auto_promote = absint(
            $event->get_extra_meta(WaitList::AUTO_PROMOTE_META_KEY, true)
        );
        if (! $auto_promote) {
            if (
                is_admin()
                && EE_Registry::instance()->CAP->current_user_can(
                    'ee_edit_registration',
                    'espresso_promote_wait_list_registrants'
                )
            ) {
                EE_Error::add_attention(
                    sprintf(
                        esc_html__(
                            'There is %1$d space(s) available for "%3$s" and %2$d registrant(s) on the Wait List for that event. %5$s Please click here to view a list of %4$s and select those you wish to offer a space to by updating their reg status accordingly.'
                        ),
                        $regs_to_promote,
                        $wait_list_reg_count,
                        $event->name(),
                        EED_Wait_Lists::wait_list_registrations_list_table_link($event),
                        '<br />'
                    )
                );
            }
            return true;
        }
        return false;
    }



    /**
     * @param EE_Event $event
     * @param int      $regs_to_promote
     * @throws EE_Error
     * @throws RuntimeException
     */
    private function autoPromoteRegistrations(EE_Event $event, $regs_to_promote = 0)
    {
        $manual_control_spaces = absint(
            $event->get_extra_meta(WaitList::MANUAL_CONTROL_SPACES_META_KEY, true)
        );
        $regs_to_promote -= $manual_control_spaces;
        if ($regs_to_promote < 1) {
            return;
        }
        /** @var EE_Registration[] $registrations */
        $registrations = EEM_Registration::instance()->get_all(
            array(
                array(
                    'EVT_ID' => $event->ID(),
                    'STS_ID' => EEM_Registration::status_id_wait_list,
                ),
                'limit'    => $regs_to_promote,
                'order_by' => array('REG_ID' => 'ASC'),
            )
        );
        if (empty($registrations)) {
            return;
        }
        // updating the reg status will trigger a sold out status check on the event,
        // so let's turn that off while we promote these registrations by switching their status,
        // because that won't affect the event status, as these registrations
        // were already being counted against the event's sold tickets count
        $this->perform_sold_out_status_check = false;
        foreach ($registrations as $registration) {
            $registration->set_status($event->default_registration_status());
            $message = sprintf(
                esc_html__(
                    'The registration status for "%1$s" %2$s(ID:%3$d)%4$s has been successfully updated to "%5$s". They were previously on the Wait List for "%6$s".'
                ),
                $registration->attendee()->full_name(),
                '<span class="lt-grey-text">',
                $registration->ID(),
                '</span>',
                $registration->pretty_status(),
                $event->name()
            );
            \EEM_Change_Log::instance()->log(WaitList::LOG_TYPE, $message, $event);
            if (
                EE_Registry::instance()->CAP->current_user_can(
                    'ee_edit_registration',
                    'espresso_view_wait_list_update_notice'
                )
            ) {
                EE_Error::add_success($message);
            }
        }
        do_action(
            'AHEE__EventEspresso_WaitList_WaitListMonitor__promoteWaitListRegistrants__after_registrations_promoted',
            $registrations,
            $event,
            $this
        );
        $this->perform_sold_out_status_check = true;
    }

}
// End of file WaitListMonitor.php
// Location: EventEspresso/WaitList/WaitListMonitor.php