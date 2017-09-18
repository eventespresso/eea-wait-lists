<?php

use EventEspresso\core\domain\Domain as CoreDomain;
use EventEspresso\core\domain\entities\Context;
use EventEspresso\WaitList\domain\Domain;

defined('EVENT_ESPRESSO_VERSION') || exit('No direct access allowed.');



/**
 * Test cases for the EED_Wait_List_Messages module
 *
 * @package    EventEspresso\Waitlists
 * @subpackage tests
 * @author     Darren Ethier
 * @since      1.0.0
 * @group      messages
 * @group      integration
 * @group      WaitList
 */
class EED_Wait_List_Messages_Test extends EE_UnitTestCase
{


    public function setUp()
    {
        parent::setUp();
        require_once EE_WAITLISTS_PLUGIN_DIR . 'domain/services/modules/EED_Wait_Lists_Messages.module.php';
        require_once EE_WAITLISTS_TEST_DIR . 'mocks/EED_Wait_Lists_Messages_Mock.php';
    }



    public function tearDown()
    {
        parent::tearDown();
        EED_Wait_Lists_Messages_Mock::reset();
    }


    protected function getRegistrationsForTest()
    {
        //let's setup some regs and register them for some tickets for testing
        $transaction = $this->new_typical_transaction(
            array(
                'ticket_types' => 3,
            )
        );
        //get the registrations for creating the messages!
        $registrations = $transaction->registrations();
        //for the purpose of our test, we're going to make sure the registrations are linked to a specific attendee
        $attendee = EE_Attendee::new_instance(array(
            'ATT_full_name' => 'John Smith',
            'ATT_bio'       => 'Ranger John',
            'ATT_slug'      => 'john-smith',
            'ATT_fname'     => 'John',
            'ATT_lname'     => 'Smith',
            'ATT_address'   => '100 Some Street',
            'ATT_city'      => 'Anytown',
            'ATT_email'     => 'john.smith@gmail.com',
        ));
        $attendee->save();

        array_walk(
            $registrations,
            function (EE_Registration $registration) use ($attendee) {
                $registration->_add_relation_to($attendee, 'Attendee');
                $registration->save();
            }
        );
        return $registrations;
    }



    public function testTriggerWaitListPromotionNotifications()
    {
        $registrations = $this->getRegistrationsForTest();
        //this message type is triggered for each registration. Even though we have three registrations, we only need
        // one of them for our tests.
        /** @var EE_Registration $registration */
        $registration = reset($registrations);
        $event = $registration->event_obj();
        //The callback we're testing has various conditions on it so we'll first test conditions where the registration
        //should NOT get processed.
        //null $context should not get processed
        EED_Wait_Lists_Messages::trigger_wait_list_promotion_notifications($registration, $event);
        $messages_processor = EED_Wait_Lists_Messages_Mock::get_processor();
        //there shouldn't even be a messages processor yet.
        $this->assertNull($messages_processor);

        //$context with none of the matching slugs should get processed
        $context = new Context(
            'test_slug',
            'testing'
        );
        EED_Wait_Lists_Messages::trigger_wait_list_promotion_notifications($registration, $event, $context);
        $messages_processor = EED_Wait_Lists_Messages_Mock::get_processor();
        //there shouldn't even be a messages processor yet.
        $this->assertNull($messages_processor);

        //$context with correct admin context slug, but the user doesn't have access.
        $context = new Context(
            CoreDomain::CONTEXT_REGISTRATION_STATUS_CHANGE_REGISTRATION_ADMIN_NOTIFY,
            'testing'
        );
        EED_Wait_Lists_Messages::trigger_wait_list_promotion_notifications($registration, $event, $context);
        $messages_processor = EED_Wait_Lists_Messages_Mock::get_processor();
        //there shouldn't even be a messages processor yet.
        $this->assertNull($messages_processor);

        //okay let's test a valid context that should trigger the message
        $context = new Context(
            Domain::CONTEXT_REGISTRATION_STATUS_CHANGE_FROM_WAIT_LIST_AUTO_PROMOTE,
            'testing'
        );
        EED_Wait_Lists_Messages::trigger_wait_list_promotion_notifications($registration, $event, $context);
        $messages_processor = EED_Wait_Lists_Messages_Mock::get_processor();
        //trigger generation.
        $queue = $messages_processor->batch_generate_from_queue();
        //verify there's a queue
        $this->assertInstanceOf('EE_Messages_Queue', $queue);
        //there should only be one message.
        $this->assertEquals(1, $queue->get_message_repository()->count());
        //get the message from the queue for verification of generation.
        $queue->get_message_repository()->rewind();
        $message = $queue->get_message_repository()->current();

        //verify the subject is correct
        $this->assertEquals('Registration is Available!', $message->subject());
        //verify the content only has ONE ticket name mentioned in it.
        $this->assertEquals(1, substr_count($message->content(), 'TKT_name'));
        //verify the to field is correct
        $this->assertEquals('john.smith@gmail.com', $message->to());
    }
    


    public function testWaitlistDemotionNotifications()
    {
        $registrations = $this->getRegistrationsForTest();

        //this message type is triggered for each registration. Even though we have three registrations, we only need
        // one of them for our tests.
        /** @var EE_Registration $registration */
        $registration = reset($registrations);
        $event = $registration->event_obj();

        //The callback we're testing has various conditions on it so we'll first test conditions where the registration
        //should NOT get processed.

        //$context with none of the matching slugs should get processed
        $context = new Context(
            'test_slug',
            'testing'
        );
        EED_Wait_Lists_Messages::trigger_wait_list_demotion_notifications($registration, $event, $context);
        $messages_processor = EED_Wait_Lists_Messages_Mock::get_processor();
        //there shouldn't even be a messages processor yet.
        $this->assertNull($messages_processor);

        //$context with correct admin context slug, but the user doesn't have access.
        $context = new Context(
            CoreDomain::CONTEXT_REGISTRATION_STATUS_CHANGE_REGISTRATION_ADMIN_NOTIFY,
            'testing'
        );
        EED_Wait_Lists_Messages::trigger_wait_list_demotion_notifications($registration, $event, $context);
        $messages_processor = EED_Wait_Lists_Messages_Mock::get_processor();
        //there shouldn't even be a messages processor yet.
        $this->assertNull($messages_processor);

        //okay let's test a valid context that should trigger the message
        // (in this case no context provided should trigger)
        EED_Wait_Lists_Messages::trigger_wait_list_demotion_notifications($registration, $event);
        $messages_processor = EED_Wait_Lists_Messages_Mock::get_processor();
        //trigger generation.
        $queue = $messages_processor->batch_generate_from_queue();
        //verify there's a queue
        $this->assertInstanceOf('EE_Messages_Queue', $queue);
        //there should only be one message.
        $this->assertEquals(1, $queue->get_message_repository()->count());
        //get the message from the queue for verification of generation.
        $queue->get_message_repository()->rewind();
        $message = $queue->get_message_repository()->current();

        //verify the subject is correct
        $this->assertEquals('Response Required: Wait List Confirmation', $message->subject());
        //verify the content only has ONE ticket name mentioned in it.
        $this->assertEquals(1, substr_count($message->content(), 'TKT_name'));
        //verify the to field is correct
        $this->assertEquals('john.smith@gmail.com', $message->to());
    }

}
