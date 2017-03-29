<?php
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
 */
class EED_Wait_List_Messages_Test extends EE_UnitTestCase
{


    public function setUp()
    {
        parent::setUp();
        require_once EE_WAITLISTS_TEST_DIR . 'mocks/EED_Wait_Lists_Messages_Mock.php';
        //setup events etc for tests to use
        $this->scenarios->get_scenarios_by_type('event');
    }



    public function tearDown()
    {
        parent::tearDown();
        EED_Wait_Lists_Messages_Mock::reset();
    }



    public function test_trigger_wait_list_notifications()
    {
        //let's setup some regs and register them for some tickets so we have
        $transaction = $this->new_typical_transaction(
            array(
                'ticket_types' => 3,
            )
        );
        //get the registrations for creating the messages!
        $registrations = $transaction->registrations();
        //for the purpose of our test, we're going to make sure the registration is linked to a specific attendee
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
        array_walk($registrations, function (EE_Registration $registration) use ($attendee) {
            $registration->_add_relation_to($attendee, 'Attendee');
            $registration->save();
        });
        //okay now let's trigger the messages
        EED_Wait_Lists_Messages::trigger_wait_list_notifications($registrations);
        //let's trigger generation and see what we got.
        $messages_processor = EED_Wait_Lists_Messages_Mock::get_processor();
        //trigger generation
        $queue = $messages_processor->batch_generate_from_queue();
        //verify we have a queue
        $this->assertInstanceOf('EE_Messages_Queue', $queue);
        //there should only be 1 message because there is one attendee, this message type only sends out one message per
        //attendee (with tickets related to that attendee in the message).
        $this->assertEquals(1, $queue->get_message_repository()->count());
        //get the message from the queue for verification of generation.
        $queue->get_message_repository()->rewind();
        $message = $queue->get_message_repository()->current();
        //verify the subject is correct
        $this->assertEquals('Registration is Available!', $message->subject());
        //verify the content has three tickets mentioned in it.
        $this->assertEquals(3, substr_count($message->content(), 'TKT_name'));
        //verify the to field is correct
        $this->assertEquals('john.smith@gmail.com', $message->to());
    }

}