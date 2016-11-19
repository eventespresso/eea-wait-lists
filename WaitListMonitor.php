<?php
namespace EventEspresso\WaitList;

use EE_Error;
use EE_Event;
use EE_Registration;
use EE_Registry;
use EEM_Registration;
use EventEspresso\core\exceptions\ExceptionStackTraceDisplay;
use EventEspresso\core\services\collections\Collection;
use Exception;

defined( 'EVENT_ESPRESSO_VERSION' ) || exit;



/**
 * Class WaitListMonitor
 * tracks which event have active wait lists
 * and determines whether wait list forms should be displayed and processed for an event
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class WaitListMonitor {

	/**
	 * @var Collection $wait_list_events
	 */
	private $wait_list_events;



	/**
	 * WaitListMonitor constructor.
	 *
	 * @param Collection $wait_list_events
	 */
	public function __construct( Collection $wait_list_events ) {
		$this->wait_list_events = $wait_list_events;
	}



    /**
     * returns true if an event has an active wait list with available spaces
     *
     * @param \EE_Event $event
     * @return bool
     * @throws \EE_Error
     */
    protected function eventHasOpenWaitList(EE_Event $event)
    {
        if ($this->wait_list_events->hasObject($event)) {
            $wait_list_reg_count = absint($event->get_extra_meta('ee_wait_list_registration_count', true));
            $wait_list_spaces = absint($event->get_extra_meta('ee_wait_list_spaces', true));
            if ($wait_list_reg_count < $wait_list_spaces) {
                return true;
            }
        }
        return false;
    }



    /**
     * @param \EE_Event $event
     * @return string
     * @throws \LogicException
     * @throws \InvalidArgumentException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \DomainException
     * @throws \EE_Error
     */
	public function getWaitListFormForEvent( EE_Event $event ) {
		if ( $event->is_sold_out() && $this->eventHasOpenWaitList($event)){
            $wait_list_form = new WaitListForm($event, EE_Registry::instance());
            return $wait_list_form->display();
		}
		return '';
	}



	/**
	 * @param int $event_id
	 * @return boolean
	 * @throws \EventEspresso\core\exceptions\InvalidEntityException
	 * @throws \EventEspresso\core\exceptions\InvalidFormSubmissionException
	 * @throws \LogicException
	 * @throws \InvalidArgumentException
	 * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
	 * @throws \DomainException
	 * @throws \EE_Error
	 */
	public function processWaitListFormForEvent( $event_id ) {
		if ( $this->wait_list_events->has($event_id)){
            /** @var EE_Event $event */
            $event = $this->wait_list_events->get($event_id);
            try {
                $wait_list_form = new WaitListForm($event, EE_Registry::instance());
                $attendee = $wait_list_form->process($_REQUEST);
                EE_Error::add_success(
                    apply_filters(
                        'FHEE_EventEspresso_WaitList_WaitListMonitor__processWaitListFormForEvent__success_msg',
                        sprintf(
                            esc_html__('Thank You %1$s.%2$sYou have been successfully added to the Wait List for:%2$s%3$s', 'event_espresso'),
                            $attendee->full_name(),
                            '<br />',
                            $event->name()
                        )
                    ),
                    __FILE__, __FUNCTION__, __LINE__
                );
            } catch (Exception $e) {
                EE_Error::add_error(
                    new ExceptionStackTraceDisplay($e),
                    __FILE__, __FUNCTION__, __LINE__
                );
            }
		}
		return false;
	}



    /**
     * increment or decrement the wait list reg count for an event when a registration's status changes to or from RWL
     *
     * @param \EE_Registration $registration
     * @param                  $old_STS_ID
     * @param                  $new_STS_ID
     * @throws \EE_Error
     */
    public function registrationStatusUpdate(EE_Registration $registration, $old_STS_ID, $new_STS_ID)
    {
        $event = $registration->event();
        if ($this->wait_list_events->hasObject($event)) {
            $wait_list_reg_count = null;
            if ($old_STS_ID === EEM_Registration::status_id_wait_list) {
                $wait_list_reg_count = absint(
                    $event->get_extra_meta('ee_wait_list_registration_count', true)
                );
                $wait_list_reg_count--;
                $event->update_extra_meta('ee_wait_list_registration_count', $wait_list_reg_count);
            } elseif ($new_STS_ID === EEM_Registration::status_id_wait_list) {
                $wait_list_reg_count = absint(
                    $event->get_extra_meta('ee_wait_list_registration_count', true)
                );
                $wait_list_reg_count++;
                $event->update_extra_meta('ee_wait_list_registration_count', $wait_list_reg_count);
            }
            if ($wait_list_reg_count !== null) {
                $event->perform_sold_out_status_check();
            }
            // $wait_list_reg_count = $wait_list_reg_count !== null
            //     ? $wait_list_reg_count
            //     : absint(
            //         $event->get_extra_meta('ee_wait_list_registration_count', true)
            //     );
            // $wait_list_spaces = absint($event->get_extra_meta('ee_wait_list_spaces', true));
            // $available_spaces = $wait_list_reg_count < $wait_list_spaces
        }
    }



    // protected function calculateWaitListSpaces(\EE_Event $event)
    // {
    //         $wait_list_reg_count = \EED_Wait_Lists::waitListRegCount($event);
    // }



    /**
     * @param int            $spaces_available
     * @param \EE_Event      $event
     * @return int
     * @throws \EE_Error
     */
    public function adjustEventSpacesAvailable($spaces_available, \EE_Event $event)
    {
        // wait list related event meta:
        // 'ee_wait_list_spaces'
        // 'ee_wait_list_auto_promote'
        // 'ee_wait_list_spaces_before_promote'
        // 'ee_wait_list_registration_count'
        if ($this->wait_list_events->hasObject($event)) {
            $wait_list_reg_count = absint(
                $event->get_extra_meta('ee_wait_list_registration_count', true)
            );
            // consider wait list registrations as taking available spaces
            $spaces_available -= $wait_list_reg_count;
        }
        return $spaces_available;
    }

}
// End of file WaitListMonitor.php
// Location: EventEspresso\WaitList/WaitListMonitor.php