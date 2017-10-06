<?php

use EventEspresso\modules\ticket_selector\DisplayTicketSelector;
use EventEspresso\WaitList\domain\Domain;
use EventEspresso\WaitList\domain\services\collections\WaitListEventsCollection;
use EventEspresso\WaitList\domain\services\event\WaitListMonitor;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class WaitListMonitorTest
 * Unit tests for the WaitListMonitor class that directly or indirectly test all public methods
 *
 * @package Event Espresso
 * @author  Brent Christensen
 * @group   WaitList
 * @group WaitListMonitor
 */
class WaitListMonitorTest extends EE_UnitTestCase
{

    /**
     * @var EE_Event[] $events
     */
    private $events;

    /**
     * @var \EventEspresso\WaitList\domain\services\collections\WaitListEventsCollection $wait_list_events
     */
    private $wait_list_events;

    /**
     * @var WaitListMonitor $wait_list_monitor
     */
    private $wait_list_monitor;



    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        $this->events = $this->setupEvents();
        $this->wait_list_events = new WaitListEventsCollection();
        $this->wait_list_monitor = EE_Registry::instance()->create(
            'EventEspresso\WaitList\domain\services\event\WaitListMonitor',
            array($this->wait_list_events)
        );
    }



    /**
     * because tests are failing when they are run as a group
     */
    public function tearDown()
    {
        $this->events = array();
        $this->wait_list_events = null;
        $this->wait_list_monitor = null;
        EED_Wait_Lists::reset();
        parent::tearDown();
    }



    /**
     * creates 18 events that each have one related datetime,
     * which in turn has one related ticket.
     * datetime reg limits are twice the "event key"
     * which is used to access the event within $this->events.
     * events with an "event key" that is a factor of 2 (ie: 2, 4, 6),
     * have wait lists with spaces set to their key (ie: 2, 4, 6),
     * auto promote is turned OFF, and there are no manually controlled spaces
     *
     * @throws EE_Error
     */
    private function setupEvents()
    {
        $events = array();
        for ($x = 1; $x <= 18; $x++) {
            $args = array(
                'status' => 'publish',
            );
            // for factors of 3
            if ($x % 2 === 0) {
                $args['EVT_allow_overflow'] = true;
            }
            /** @var EE_Event $event */
            $events[$x] = $this->new_model_obj_with_dependencies('Event', $args);
            $datetime = $this->new_model_obj_with_dependencies(
                'Datetime',
                array(
                    'EVT_ID'        => $events[$x]->ID(),
                    'DTT_EVT_start' => time() + DAY_IN_SECONDS,
                    'DTT_EVT_end'   => time() + DAY_IN_SECONDS + HOUR_IN_SECONDS,
                    'DTT_reg_limit' => $x * 2,
                    'DTT_sold'      => 0,
                    'DTT_reserved'  => 0,
                )
            );
            $this->new_ticket(
                array(
                    'TKT_min'          => 0,
                    'TKT_max'          => EE_INF,
                    'TKT_sold'         => 0,
                    'TKT_reserved'     => 0,
                    'datetime_objects' => array($datetime),
                )
            );
            // for factors of 2
            if ($x % 2 === 0) {
                // add wait list details to event
                $events[$x]->add_extra_meta(Domain::META_KEY_WAIT_LIST_SPACES, $x);
                $events[$x]->add_extra_meta(Domain::META_KEY_WAIT_LIST_AUTO_PROMOTE, false);
                $events[$x]->add_extra_meta(Domain::META_KEY_WAIT_LIST_MANUALLY_CONTROLLED_SPACES, 0);
                $events[$x]->add_extra_meta(Domain::META_KEY_WAIT_LIST_REG_COUNT, 0);
            }
        }
        return $events;
    }



    /**
     * @param int  $number
     * @param bool $auto_promote
     * @return EE_Event
     * @throws EE_Error
     * @throws PHPUnit_Framework_Exception
     */
    private function getSoldOutEventWithEmptyWaitList($number = 1, $auto_promote = false)
    {
        $number *= 2;
        // events whose key is a factor of 3 have wait lists, let's get one
        $event_with_wait_list = $this->events[$number];
        $this->assertInstanceOf('EE_Event', $event_with_wait_list);
        $event_with_wait_list->set_status(EEM_Event::sold_out);
        $datetimes = $event_with_wait_list->datetimes();
        $this->assertCount(1, $datetimes);
        /** @var EE_Datetime $datetime */
        $datetime = reset($datetimes);
        $this->assertInstanceOf('EE_Datetime', $datetime);
        $this->assertEquals(0, $datetime->sold());
        if ($auto_promote) {
            // now turn on auto promote for this event
            $event_with_wait_list->update_extra_meta(Domain::META_KEY_WAIT_LIST_AUTO_PROMOTE, true);
        }
        $reg_count = $event_with_wait_list->get_extra_meta(Domain::META_KEY_WAIT_LIST_REG_COUNT, true);
        $this->assertEquals(0, $reg_count);
        return $event_with_wait_list;
    }



    /**
     * @param EE_Event $event
     * @param int      $qty
     * @param string   $reg_status
     * @return EE_Base_Class[]|EE_Registration[]
     * @throws EE_Error
     * @throws PHPUnit_Framework_Exception
     */
    private function registerForWaitListEvent(
        EE_Event $event,
        $qty = 1,
        $reg_status = EEM_Registration::status_id_wait_list
    ) {
        $registrations = array();
        $tickets = $event->tickets();
        $ticket = reset($tickets);
        $transaction = $this->new_typical_transaction(
            array(
                'tickets'   => array(1 => $ticket),
                'tkt_qty'   => $qty,
                'setup_reg' => false,
            )
        );
        for($x = 0; $x < $qty; $x++) {
            $registrations[$x] = $this->new_model_obj_with_dependencies(
                'Registration',
                array(
                    'EVT_ID'          => $event->ID(),
                    'TKT_ID'          => $ticket->ID(),
                    'TXN_ID'          => $transaction->ID(),
                    'STS_ID'          => $reg_status,
                    'REG_count'       => 1,
                    'REG_group_size'  => 1,
                    'REG_final_price' => $ticket->price(),
                )
            );
            $registrations[$x]->save();
            // $ticket->increase_sold();
            if ($reg_status === EEM_Registration::status_id_wait_list) {
                $registrations[$x]->add_extra_meta(
                    Domain::META_KEY_WAIT_LIST_REG_SIGNED_UP,
                    current_time('mysql', true),
                    true
                );
            }
        }
        $this->assertEquals($x, $qty);
        if ($reg_status === EEM_Registration::status_id_wait_list) {
            $reg_count = $event->get_extra_meta(Domain::META_KEY_WAIT_LIST_REG_COUNT, true);
            $event->update_extra_meta(
                Domain::META_KEY_WAIT_LIST_REG_COUNT,
                $reg_count + $qty
            );
        }
        $ticket->save();
        return $registrations;
    }



    /**
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws LogicException
     * @throws PHPUnit_Framework_AssertionFailedError
     * @throws PHPUnit_Framework_Exception
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     */
    public function test_getWaitListFormForEvent()
    {
        $event_with_wait_list = $this->events[2];
        $this->assertInstanceOf('EE_Event', $event_with_wait_list);
        $this->assertTrue($event_with_wait_list->allow_overflow());
        $this->assertFalse($event_with_wait_list->is_sold_out());
        // event is not sold out so there should not be a wait list form
        $wait_list_form_for_event = $this->wait_list_monitor->getWaitListFormForEvent(
            $event_with_wait_list,
            new DisplayTicketSelector()
        );
        $this->assertEmpty($wait_list_form_for_event);
        // now mark the event as being sold out
        $event_with_wait_list->set_status(EEM_Event::sold_out);
        $this->assertTrue($event_with_wait_list->is_sold_out());
        $wait_list_form_for_event = $this->wait_list_monitor->getWaitListFormForEvent(
            $event_with_wait_list,
            new DisplayTicketSelector()
        );
        $this->assertNotEmpty($wait_list_form_for_event);
    }



    /**
     * @throws EE_Error
     * @throws PHPUnit_Framework_Exception
     * @throws \EventEspresso\core\exceptions\EntityNotFoundException
     */
    public function test_registrationStatusUpdate()
    {
        for($reg_count = 1; $reg_count < 4; $reg_count++) {
            $event_with_wait_list = $this->getSoldOutEventWithEmptyWaitList(1);
            // add a wait list registrations for that event
            $registrations = $this->registerForWaitListEvent($event_with_wait_list, $reg_count);
            $this->assertCount($reg_count, $registrations);
            $event_reg_count = $event_with_wait_list->get_extra_meta(Domain::META_KEY_WAIT_LIST_REG_COUNT, true);
            // now pretend that registrations were promoted to Pending Payment
            foreach ($registrations as $registration) {
                $this->wait_list_monitor->registrationStatusUpdate(
                    $registration,
                    EEM_Registration::status_id_wait_list,
                    EEM_Registration::status_id_pending_payment
                );
            }
            $new_reg_count = $event_with_wait_list->get_extra_meta(Domain::META_KEY_WAIT_LIST_REG_COUNT, true);
            $this->assertEquals(
                $event_reg_count - $reg_count,
                $new_reg_count,
                "{$event_reg_count} - {$reg_count} !== {$new_reg_count}"
            );
        }
    }



    /**
     * @throws EE_Error
     * @throws PHPUnit_Framework_Exception
     * @throws \EventEspresso\core\exceptions\EntityNotFoundException
     */
    public function test_registrationStatusUpdateWithAutoPromote()
    {
        for ($reg_count = 4; $reg_count < 7; $reg_count++) {
            $event_with_wait_list = $this->getSoldOutEventWithEmptyWaitList($reg_count, true);
            // add a wait list registrations for that event
            $registrations = $this->registerForWaitListEvent($event_with_wait_list, $reg_count);
            $this->assertCount($reg_count, $registrations);
            $event_reg_count = $event_with_wait_list->get_extra_meta(Domain::META_KEY_WAIT_LIST_REG_COUNT, true);
            // now perform sold out status check which will trigger auto promotion
            // because WaitListMonitor::promoteWaitListRegistrants() is hooked into
            // AHEE__EE_Event__perform_sold_out_status_check__end
            $event_with_wait_list->perform_sold_out_status_check();
            $new_reg_count = $event_with_wait_list->get_extra_meta(Domain::META_KEY_WAIT_LIST_REG_COUNT, true);
            $this->assertEquals(
                $event_reg_count - $reg_count,
                $new_reg_count,
                "{$event_reg_count} - {$reg_count} !== {$new_reg_count}"
            );
        }
    }



    /**
     * @throws EE_Error
     * @throws PHPUnit_Framework_Exception
     */
    public function test_adjustEventSpacesAvailable()
    {
        for ($reg_count = 7; $reg_count < 10; $reg_count++) {
            $event_with_wait_list = $this->getSoldOutEventWithEmptyWaitList($reg_count);
            // get existing number of spaces left
            $orig_spaces_remaining = $event_with_wait_list->spaces_remaining_for_sale();
            // now add a couple of regs to the wait list
            $registrations = $this->registerForWaitListEvent($event_with_wait_list, $reg_count);
            $this->assertCount($reg_count, $registrations);
            // $event_with_wait_list->update_extra_meta(Domain::REG_COUNT_META_KEY, $reg_count);
            $new_spaces_remaining = $event_with_wait_list->spaces_remaining_for_sale();
            $this->assertEquals(
                $orig_spaces_remaining - $reg_count,
                $new_spaces_remaining,
                "{$orig_spaces_remaining} - {$reg_count} !== {$new_spaces_remaining}"
            );
        }
    }

}
// End of file WaitListMonitorTest.php
// Location: /tests/testcases/WaitListMonitorTest.php
