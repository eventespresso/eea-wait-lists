<?php

namespace EventEspresso\WaitList\domain\services\commands;

use EE_Error;
use EventEspresso\core\exceptions\InvalidEntityException;
use EventEspresso\core\services\commands\CommandInterface;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class CalculateEventSpacesAvailableCommandHandler
 * Factors Wait List registrations into the spaces available for an event
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class CalculateEventSpacesAvailableCommandHandler extends WaitListCommandHandler
{



    /**
     * @param CommandInterface $command
     * @return mixed
     * @throws InvalidEntityException
     * @throws EE_Error
     */
    public function handle(CommandInterface $command)
    {
        if (! $command instanceof CalculateEventSpacesAvailableCommand) {
            throw new InvalidEntityException(
                $command,
                'EventEspresso\WaitList\domain\services\commands\CalculateEventSpacesAvailableCommand'
            );
        }
        $event = $command->getEvent();
        $spaces_available = $command->getSpacesAvailable();
        // registrations previously on wait list but now waiting to pay
        $promoted_reg_ids = $this->getPromotedRegIdsArray($event);
        $spaces_available -= count($promoted_reg_ids);
        // plus consider wait list registrations as taking available spaces
        $wait_list_reg_count = $this->getRegCount($event);
        $spaces_available -= $wait_list_reg_count;
        return $spaces_available;
    }
}
