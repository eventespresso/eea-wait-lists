<?php

namespace EventEspresso\WaitList\domain\services\commands;

use EE_Attendee;
use EE_Error;
use EE_Line_Item;
use EE_Registration;
use EE_Transaction;
use EE_Wait_Lists;
use EEM_Registration;
use EEM_Transaction;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidEntityException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\exceptions\UnexpectedEntityException;
use EventEspresso\core\services\commands\attendee\CreateAttendeeCommand;
use EventEspresso\core\services\commands\CommandBusInterface;
use EventEspresso\core\services\commands\CommandFactoryInterface;
use EventEspresso\core\services\commands\notices\CommandHandlerNotices;
use EventEspresso\core\services\commands\CommandInterface;
use EventEspresso\core\services\commands\CompositeCommandHandler;
use EventEspresso\WaitList\domain\Constants;
use InvalidArgumentException;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class CreateWaitListRegistrationsCommandHandler
 * Generates Registrations and the corresponding Transaction and Line Items for the Wait List
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class CreateWaitListRegistrationsCommandHandler extends CompositeCommandHandler
{

    /**
     * @var CommandHandlerNotices $notices
     */
    private $notices;



    /**
     * CreateWaitListRegistrationsCommandHandler constructor.
     *
     * @param CommandHandlerNotices   $notices
     * @param CommandBusInterface     $command_bus
     * @param CommandFactoryInterface $command_factory
     */
    public function __construct(
        CommandHandlerNotices $notices,
        CommandBusInterface $command_bus,
        CommandFactoryInterface $command_factory
    ) {
        $this->notices = $notices;
        parent::__construct($command_bus, $command_factory);
    }

    /**
     * @param CommandInterface $command
     * @return mixed
     * @throws UnexpectedEntityException
     * @throws InvalidEntityException
     * @throws EE_Error
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws InvalidArgumentException
     */
    public function handle(CommandInterface $command)
    {
        if (! $command instanceof CreateWaitListRegistrationsCommand) {
            throw new InvalidEntityException(
                $command,
                'EventEspresso\WaitList\domain\services\commands\CreateWaitListRegistrationsCommand'
            );
        }
        $registrant_name = $command->getRegistrantName();
        $registrant_email = $command->getRegistrantEmail();
        $ticket = $command->getTicket();
        $quantity = $command->getQuantity();

        /** @var EE_Transaction $transaction */
        $transaction = $this->commandBus()->execute(
            $this->commandFactory()->getNew(
                'EventEspresso\core\services\commands\transaction\CreateTransactionCommand'
            )
        );
        /** @var EE_Line_Item $ticket_line_item */
        $ticket_line_item = $this->commandBus()->execute(
            $this->commandFactory()->getNew(
                'EventEspresso\core\services\commands\ticket\CreateTicketLineItemCommand',
                array(
                    $transaction,
                    $ticket,
                    $quantity
                )
            )
        );
        $primary_registrant = $this->createRegistrations(
            $transaction,
            $ticket_line_item,
            $registrant_name,
            $registrant_email,
            $quantity
        );
        // update txn
        $transaction->set_status(EEM_Transaction::incomplete_status_code);
        $transaction->save();
        // finally... update the wait list reg count
        $event = $ticket->get_related_event();
        $event->update_extra_meta(
            Constants::REG_COUNT_META_KEY,
            EE_Wait_Lists::waitListRegCount($event)
        );
        $this->notices->addSuccess(
            apply_filters(
                'FHEE_EventEspresso_WaitList_WaitListMonitor__processWaitListFormForEvent__success_msg',
                sprintf(
                    esc_html__('Thank You %1$s.%2$sYou have been successfully added to the Wait List for:%2$s%3$s',
                        'event_espresso'),
                    $primary_registrant->full_name(),
                    '<br />',
                    $event->name()
                )
            ),
            __FILE__, __FUNCTION__, __LINE__
        );
        return $this->notices;
    }



    /**
     * @param EE_Transaction $transaction
     * @param EE_Line_Item   $ticket_line_item
     * @param string         $registrant_name
     * @param string         $registrant_email
     * @param int            $quantity
     * @return EE_Attendee
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     */
    private function createRegistrations(
        EE_Transaction $transaction,
        EE_Line_Item $ticket_line_item,
        $registrant_name,
        $registrant_email,
        $quantity
    ){
        $attendee = null;
        for ($x = 1; $x <= $quantity; $x++) {
            /** @var \EE_Registration $registration */
            $registration = $this->commandBus()->execute(
                $this->commandFactory()->getNew(
                    'EventEspresso\core\services\commands\registration\CreateRegistrationCommand',
                    array(
                        $transaction,
                        $ticket_line_item,
                        $x,
                        $quantity,
                        EEM_Registration::status_id_wait_list
                    )
                )
            );
            // add relation to registration
            $transaction->_add_relation_to($registration, 'Registration');
            $transaction->update_cache_after_object_save('Registration', $registration);
            if ($x === 1) {
                $attendee = $this->createAttendeeForPrimaryRegistrant(
                    $registration,
                    $registrant_name,
                    $registrant_email
                );
            }
            //ok, we have all of the pieces, now let's do some final tweaking
            // add relation to attendee
            $registration->_add_relation_to($attendee, 'Attendee');
            $registration->set_attendee_id($attendee->ID());
            $registration->update_cache_after_object_save('Attendee', $attendee);
            $registration->add_extra_meta(
                Constants::REG_SIGNED_UP_META_KEY,
                current_time('mysql', true),
                true
            );
            $registration->save();
        }
        return $attendee;
    }



    /**
     * @param EE_Registration $registration
     * @param string          $registrant_name
     * @param string          $registrant_email
     * @return EE_Attendee
     */
    private function createAttendeeForPrimaryRegistrant(
        EE_Registration $registration,
        $registrant_name,
        $registrant_email
    )
    {
        $registrant_name = explode(' ', $registrant_name);
        return $this->commandBus()->execute(
            new CreateAttendeeCommand(
                array(
                    // grab first string from registrant name array
                    'ATT_fname' => array_shift($registrant_name),
                    // join rest of array back together for rest of name
                    'ATT_lname' => implode(' ', $registrant_name),
                    'ATT_email' => $registrant_email,
                ),
                $registration
            )
        );
    }



}
