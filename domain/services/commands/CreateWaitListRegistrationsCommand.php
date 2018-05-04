<?php

namespace EventEspresso\WaitList\domain\services\commands;

use EE_Ticket;
use EEM_Ticket;
use EventEspresso\core\exceptions\InvalidEntityException;
use EventEspresso\core\services\commands\Command;
use InvalidArgumentException;

/**
 * Class CreateWaitListRegistrationsCommand
 * DTO for passing data to CreateWaitListRegistrationsCommandHandler
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 *
 */
class CreateWaitListRegistrationsCommand extends Command
{

    /**
     * @var string $registrant_name
     */
    private $registrant_name;

    /**
     * @var string $registrant_email
     */
    private $registrant_email;

    /**
     * @var EE_Ticket $ticket
     */
    private $ticket;

    /**
     * @var int $quantity
     */
    private $quantity;


    /**
     * CreateWaitListRegistrationsCommand constructor.
     *
     * @param string     $registrant_name
     * @param string     $registrant_email
     * @param int        $TKT_ID
     * @param int        $quantity
     * @param EEM_Ticket $ticket_model
     * @throws InvalidEntityException
     * @throws InvalidArgumentException
     */
    public function __construct(
        $registrant_name = '',
        $registrant_email = '',
        $TKT_ID = 0,
        $quantity = 0,
        EEM_Ticket $ticket_model
    ) {
        $this->setRegistrantName($registrant_name);
        $this->setRegistrantEmail($registrant_email);
        $this->setTicketFromId($TKT_ID, $ticket_model);
        $this->setQuantity($quantity);
    }


    /**
     * @param string $registrant_name
     * @throws InvalidArgumentException
     */
    public function setRegistrantName($registrant_name)
    {
        $this->registrant_name = sanitize_text_field($registrant_name);
        if (empty($this->registrant_name)) {
            throw new InvalidArgumentException(
                sprintf(
                    __(
                        '"%1$s" is not a valid registrant name.',
                        'event_espresso'
                    ),
                    $registrant_name
                )
            );
        }
    }


    /**
     * @param string $registrant_email
     * @throws InvalidArgumentException
     */
    public function setRegistrantEmail($registrant_email)
    {
        $this->registrant_email = sanitize_email($registrant_email);
        if ($this->registrant_email !== $registrant_email) {
            throw new InvalidArgumentException(
                sprintf(
                    __(
                        '"%1$s" is not a valid email address.',
                        'event_espresso'
                    ),
                    $registrant_email
                )
            );
        }
    }


    /**
     * @param            $TKT_ID
     * @param EEM_Ticket $ticket_model
     * @throws InvalidArgumentException
     */
    private function setTicketFromId($TKT_ID, EEM_Ticket $ticket_model)
    {
        $ticket = $ticket_model->get_one_by_ID($TKT_ID);
        if (! $ticket instanceof EE_Ticket) {
            throw new InvalidArgumentException(
                sprintf(
                    __(
                        '"%1$s" is not a valid ticket ID.',
                        'event_espresso'
                    ),
                    $TKT_ID
                )
            );
        }
        $this->ticket = $ticket;
    }


    /**
     * @param int $quantity
     * @throws InvalidArgumentException
     */
    private function setQuantity($quantity)
    {
        $this->quantity = absint($quantity);
        if (empty($this->quantity)) {
            throw new InvalidArgumentException(
                __(
                    'You must select at least one ticket to sign up for the Wait List.',
                    'event_espresso'
                )
            );
        }
    }


    /**
     * @return string
     */
    public function getRegistrantName()
    {
        return $this->registrant_name;
    }


    /**
     * @return string
     */
    public function getRegistrantEmail()
    {
        return $this->registrant_email;
    }


    /**
     * @return EE_Ticket
     */
    public function getTicket()
    {
        return $this->ticket;
    }


    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
}
