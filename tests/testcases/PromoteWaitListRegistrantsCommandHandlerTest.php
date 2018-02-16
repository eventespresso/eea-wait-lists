<?php

use EventEspresso\core\exceptions\EntityNotFoundException;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\WaitList\tests\mocks\PromoteWaitListRegistrantsCommandHandlerMock;
use EventEspresso\WaitList\tests\testcases\WaitListUnitTestCase;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class PromoteWaitListRegistrantsCommandHandlerTest
 * Description
 *
 * @author  Brent Christensen
 * @group   WaitList
 */
class PromoteWaitListRegistrantsCommandHandlerTest extends WaitListUnitTestCase
{

    /**
     * @var EE_Event $event
     */
    private $event;

    /**
     * @var PromoteWaitListRegistrantsCommandHandlerMock $handler
     */
    private $handler;


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
        $this->handler = new EventEspresso\WaitList\tests\mocks\PromoteWaitListRegistrantsCommandHandlerMock(
            EEM_Registration::instance(),
            EE_Capabilities::instance(),
            EEM_Change_Log::instance(),
            new EventEspresso\core\services\notices\NoticesContainer(),
            new EventEspresso\WaitList\domain\services\event\WaitListEventMeta()
        );
    }


    /**
     * returns
     *  array(
     *      array(
     *          $ticket_price,
     *          $default_registration_status,
     *          $expected_registration_status,
     *      ),
     *  )
     *
     * @return array[]
     */
    public function RegistrationStatusProvider()
    {
        return array(
            array(
                10,
                EEM_Registration::status_id_pending_payment,
                EEM_Registration::status_id_pending_payment,
            ),
            array(
                10,
                EEM_Registration::status_id_approved,
                EEM_Registration::status_id_approved,
            ),
            array(
                0,
                EEM_Registration::status_id_pending_payment,
                EEM_Registration::status_id_approved,
            ),
            array(
                0,
                EEM_Registration::status_id_not_approved,
                EEM_Registration::status_id_not_approved,
            ),
        );
    }


    /**
     * @dataProvider RegistrationStatusProvider
     * @param float  $ticket_price
     * @param string $default_registration_status
     * @param string $expected_registration_status
     * @throws EE_Error
     * @throws EntityNotFoundException
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws PHPUnit_Framework_Exception
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function test_autoPromoteRegistrations(
        $ticket_price = 10.00,
        $default_registration_status = EEM_Registration::status_id_pending_payment,
        $expected_registration_status = EEM_Registration::status_id_pending_payment
    ) {
        $this->event = $this->setupEvent(true,2, $ticket_price);
        $this->event->set_default_registration_status($default_registration_status);
        $registrations = $this->registerForWaitListEvent($this->event);
        $registration = reset($registrations);
        $this->assertEquals(EEM_Registration::status_id_wait_list, $registration->status_ID());
        $promoted = $this->handler->autoPromoteRegistrations($this->event, 1, true);
        $this->assertEquals(1, $promoted);
        $this->assertEquals($expected_registration_status, $registration->status_ID());
    }
}
// location: /tests/testcases/PromoteWaitListRegistrantsCommandHandlerTest.php
