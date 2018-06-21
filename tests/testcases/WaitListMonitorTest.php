<?php

use EventEspresso\core\exceptions\EntityNotFoundException;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\exceptions\UnexpectedEntityException;
use EventEspresso\modules\ticket_selector\DisplayTicketSelector;
use EventEspresso\WaitList\domain\Domain;
use EventEspresso\WaitList\domain\services\collections\WaitListEventsCollection;
use EventEspresso\WaitList\domain\services\event\WaitListMonitor;
use EventEspresso\WaitList\tests\testcases\WaitListUnitTestCase;
use PHPUnit\Framework\Exception;

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
class WaitListMonitorTest extends WaitListUnitTestCase
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
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws ReflectionException
     * @throws InvalidInterfaceException
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
            $allow_overflow = $x % 2 === 0;
            $reg_limit = $x * 2;
            $ticket_price = 10;
            $add_extra_meta = $x % 2 === 0;
            $spaces = 100;
            $auto_promote = false;
            $manual_spaces = 0;
            $events[ $x ] = $this->setupEvent(
                $allow_overflow,
                $reg_limit,
                $ticket_price,
                $add_extra_meta,
                $spaces,
                $auto_promote,
                $manual_spaces
            );
        }
        return $events;
    }



    /**
     * @param int  $number
     * @param bool $auto_promote
     * @return EE_Event
     * @throws EE_Error
     * @throws Exception
     */
    private function getSoldOutEventWithEmptyWaitList($number = 1, $auto_promote = false)
    {
        $number *= 2;
        // events whose key is a factor of 2 have wait lists, let's get one
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
     * @throws DomainException
     * @throws EE_Error
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws LogicException
     * @throws \EventEspresso\core\exceptions\InvalidEntityException
     * @throws InvalidInterfaceException
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
     * @throws EntityNotFoundException
     * @throws Exception
     * @throws PHPUnit_Framework_Exception
     */
    public function test_registrationStatusUpdate()
    {
        for($reg_count = 1; $reg_count < 4; $reg_count++) {
            $event_with_wait_list = $this->getSoldOutEventWithEmptyWaitList();
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
     * @throws DomainException
     * @throws EE_Error
     * @throws Exception
     * @throws PHPUnit_Framework_Exception
     * @throws UnexpectedEntityException
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
     * @throws DomainException
     * @throws EE_Error
     * @throws Exception
     * @throws PHPUnit_Framework_Exception
     * @throws UnexpectedEntityException
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
