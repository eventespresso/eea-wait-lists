<?php

namespace EventEspresso\WaitList\domain\services\commands;

use EE_Attendee;
use EE_Error;
use EE_Line_Item;
use EE_Registration;
use EE_Transaction;
use EEM_Registration;
use EEM_Transaction;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidEntityException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\exceptions\InvalidStatusException;
use EventEspresso\core\exceptions\UnexpectedEntityException;
use EventEspresso\core\services\commands\attendee\CreateAttendeeCommand;
use EventEspresso\core\services\commands\CommandBusInterface;
use EventEspresso\core\services\commands\CommandFactoryInterface;
use EventEspresso\core\services\notices\NoticesContainerInterface;
use EventEspresso\core\services\commands\CommandInterface;
use EventEspresso\core\services\commands\CompositeCommandHandler;
use EventEspresso\WaitList\domain\services\event\WaitListEventMeta;
use EventEspresso\WaitList\domain\services\registration\WaitListRegistrationMeta;
use InvalidArgumentException;

/**
 * Class CreateWaitListRegistrationsCommandHandler
 * Generates Registrations and the corresponding Transaction and Line Items for the Wait List
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 *
 */
class CreateWaitListRegistrationsCommandHandler extends CompositeCommandHandler
{
    /**
     * @var WaitListEventMeta $event_meta
     */
    private $event_meta;

    /**
     * @var WaitListRegistrationMeta $registration_meta
     */
    private $registration_meta;

    /**
     * @var EEM_Registration $registration_model
     */
    private $registration_model;

    /**
     * @var NoticesContainerInterface $notices
     */
    private $notices;


    /**
     * CreateWaitListRegistrationsCommandHandler constructor.
     *
     * @param WaitListEventMeta         $event_meta
     * @param WaitListRegistrationMeta  $registration_meta
     * @param EEM_Registration          $registration_model
     * @param CommandBusInterface       $command_bus
     * @param CommandFactoryInterface   $command_factory
     * @param NoticesContainerInterface $notices
     */
    public function __construct(
        WaitListEventMeta $event_meta,
        WaitListRegistrationMeta $registration_meta,
        EEM_Registration $registration_model,
        CommandBusInterface $command_bus,
        CommandFactoryInterface $command_factory,
        NoticesContainerInterface $notices
    ) {
        $this->event_meta = $event_meta;
        $this->registration_meta = $registration_meta;
        $this->registration_model = $registration_model;
        $this->notices = $notices;
        parent::__construct($command_bus, $command_factory);
    }


    /**
     * @param CommandInterface $command
     * @return NoticesContainerInterface
     * @throws \RuntimeException
     * @throws InvalidStatusException
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
                    $quantity,
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
        $this->event_meta->updateRegCount(
            $event,
            $this->registration_model->event_reg_count_for_statuses(
                $event->ID(),
                EEM_Registration::status_id_wait_list
            )
        );
        $this->notices->addSuccess(
            apply_filters(
                'FHEE_EventEspresso_WaitList_WaitListMonitor__processWaitListFormForEvent__success_msg',
                sprintf(
                    esc_html__(
                        'Thank You %1$s.%2$sYou have been successfully added to the Wait List for:%2$s%3$s',
                        'event_espresso'
                    ),
                    $primary_registrant->full_name(),
                    '<br />',
                    $event->name()
                )
            )
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
     * @throws \RuntimeException
     */
    private function createRegistrations(
        EE_Transaction $transaction,
        EE_Line_Item $ticket_line_item,
        $registrant_name,
        $registrant_email,
        $quantity
    ) {
        $attendee = null;
        $registrations_created = array();
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
                        EEM_Registration::status_id_wait_list,
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
            // ok, we have all of the pieces, now let's do some final tweaking
            // add relation to attendee
            $registration->_add_relation_to($attendee, 'Attendee');
            $registration->set_attendee_id($attendee->ID());
            $registration->update_cache_after_object_save('Attendee', $attendee);
            $this->registration_meta->addRegistrationSignedUp($registration);
            $registration->save();
            $registrations_created[] = $registration;
        }
        do_action_ref_array(
            'AHEE__EventEspresso_WaitList_domain_services_commands_CreateWaitListRegistrationsCommandHandler__createRegistrations',
            array($registrations_created, $attendee)
        );
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
    ) {
        $registrant_names = explode(' ', $registrant_name);
        // remove any empty elements
        $registrant_names = array_filter($registrant_names);
        return $this->commandBus()->execute(
            new CreateAttendeeCommand(
                array(
                    // grab first string from registrant name array
                    'ATT_fname' => array_shift($registrant_names),
                    // join rest of array back together for rest of name
                    'ATT_lname' => implode(' ', $registrant_names),
                    'ATT_email' => $registrant_email,
                ),
                $registration
            )
        );
    }
}
