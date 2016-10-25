<?php
namespace EventEspresso\WaitList;


use EventEspresso\core\services\collections\Collection;

defined( 'ABSPATH' ) || exit;



/**
 * Class WaitListEventsCollection
 * Description
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class WaitListEventsCollection extends Collection  {

	/**
	 * ProgressStepCollection constructor.
	 *
	 * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
	 * @throws \EE_Error
	 * @throws \EventEspresso\core\exceptions\InvalidEntityException
	 */
	public function __construct() {
		parent::__construct( '\EE_Event' );
		$wait_list_events = \EEM_Event::instance()->get_all(
			array(
				array(
					'EVT_allow_overflow' => true
				)
			)
		);
		if ( ! empty($wait_list_events) && is_array($wait_list_events)) {
			foreach ( $wait_list_events as $wait_list_event ) {
				if ( $wait_list_event instanceof \EE_Event ) {
					$this->add( $wait_list_event, $wait_list_event->ID() );
				}
			}
		}
	}

}
// End of file WaitListEventsCollection.php
// Location: wp-content/plugins/eea-wait-lists/WaitListEventsCollection.php