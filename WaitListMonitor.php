<?php
namespace EventEspresso\WaitList;

use EventEspresso\core\services\collections\Collection;

defined( 'ABSPATH' ) || exit;



/**
 * Class WaitListMonitor
 * tracks which event have active wait lists
 * and determines whether wait list forms should be displayed and processed for an event
 *
*@package       Event Espresso
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
     * @param \EE_Event $event
     * @return string
     * @throws \LogicException
     * @throws \InvalidArgumentException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \DomainException
     * @throws \EE_Error
     */
	public function getWaitListFormForEvent( \EE_Event $event ) {
		if ( $event->is_sold_out() && $this->wait_list_events->hasObject($event)){
            $wait_list_form = new WaitListForm($event, \EE_Registry::instance());
            return $wait_list_form->display();
            // $html = '<h1 class="important-notice">HAS WAIT LIST</h1>';
		}
		return '';
	}



    /**
     * @param int $event_id
     * @return boolean
     * @throws \EventEspresso\core\exceptions\InvalidFormSubmissionException
     * @throws \LogicException
     * @throws \InvalidArgumentException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \DomainException
     * @throws \EE_Error
     */
	public function processWaitListFormForEvent( $event_id ) {
		if ( $this->wait_list_events->has($event_id)){
            $wait_list_form = new WaitListForm(
                $this->wait_list_events->get($event_id),
                \EE_Registry::instance()
            );
            \EEH_Debug_Tools::printr($_REQUEST, '$_REQUEST', __FILE__, __LINE__);
            return $wait_list_form->process($_REQUEST);
		}
		return false;
	}


}
// End of file WaitListMonitor.php
// Location: EventEspresso\WaitList/WaitListMonitor.php