<?php

use EventEspresso\WaitList\tests\mocks\WaitListCheckoutMonitorMock;
use EventEspresso\WaitList\domain\Domain;
use EventEspresso\WaitList\domain\services\registration\WaitListRegistrationMeta;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class WaitListCheckoutMonitorTest
 * Unit tests for the WaitListCheckoutMonitor class
 * PLZ NOTE: not yet able to test methods that utilize EE_Checkout
 *
 * @package Event Espresso
 * @author  Brent Christensen
 * @group   WaitList
 * @group   WaitListCheckoutMonitor
 */
class WaitListCheckoutMonitorTest extends EE_UnitTestCase
{

    /**
     * @var WaitListCheckoutMonitorMock $wait_list_checkout_monitor
    */
    protected $wait_list_checkout_monitor;



    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        $this->wait_list_checkout_monitor = new WaitListCheckoutMonitorMock(
            new WaitListRegistrationMeta()
        );
    }



    /**
     * @throws EE_Error
     * @throws PHPUnit_Framework_AssertionFailedError
     */
    public function testAllowRegPaymentWithInvalidEvent()
    {
        $registration = EE_Registration::new_instance(
            array(
              'EVT_ID'          => 1,
              'TKT_ID'          => 2,
              'TXN_ID'          => 3,
              'STS_ID'          => EEM_Registration::status_id_pending_payment,
              'REG_count'       => 1,
              'REG_group_size'  => 1,
              'REG_final_price' => 10.00,
            )
        );
        $registration->save();
        //first confirm that this guy can't pay
        $this->setExceptionExpected('DomainException');
        $this->wait_list_checkout_monitor->allowRegPayment(false, $registration);

    }


    /**
     * @throws EE_Error
     * @throws PHPUnit_Framework_AssertionFailedError
     */
    public function testAllowRegPayment()
    {
        $ticket = $this->new_ticket();
        $registration = EE_Registration::new_instance(
            array(
              'EVT_ID'          => $ticket->get_event_ID(),
              'TKT_ID'          => $ticket->ID(),
              'TXN_ID'          => 3,
              'STS_ID'          => EEM_Registration::status_id_pending_payment,
              'REG_count'       => 1,
              'REG_group_size'  => 1,
              'REG_final_price' => $ticket->price(),
            )
        );
        $registration->save();
        $event = $ticket->get_related_event();
        //event should have infinite spaces because we did not set any datetime reg limit
        $this->assertEquals(
            EE_INF,
            $event->spaces_remaining(array(), false)
        );
        // and registration has not yet signed up
        $this->assertNull(
            $this->wait_list_checkout_monitor->getRegistrationMeta()->getRegistrationSignedUp(
                $registration
            )
        );
        // so first confirm that this guy can't pay
        $this->assertFalse(
            $this->wait_list_checkout_monitor->allowRegPayment(false, $registration)
        );
        // now add meta data to indicate that this guy was on the waitlist
        $registration->add_extra_meta(
            Domain::META_KEY_WAIT_LIST_REG_SIGNED_UP,
            current_time('mysql', true),
            true
        );
        // then try again
        $this->assertTrue(
            $this->wait_list_checkout_monitor->allowRegPayment(false, $registration)
        );
        // now let's set the datetime reg limit to zero to simulate no spaces available
        $datetime = $ticket->first_datetime();
        $datetime->set_reg_limit(0);
        // normally a request would not include setting the reg limit
        // and then performing sold out status check one after the  other
        // so  we need to clear the caching on the EventSpacesCalculator
        $event->getAvailableSpacesCalculator()->clearResults();
        $this->assertEquals(
            0,
            $event->spaces_remaining(array(), false)
        );
        // payment should not be allowed now
        $this->assertFalse(
            $this->wait_list_checkout_monitor->allowRegPayment(false, $registration)
        );
    }

}
// End of file WaitListCheckoutMonitorTest.php
// Location: /tests/testcases/WaitListCheckoutMonitorTest.php
