<?php

namespace EventEspresso\WaitList\tests\mocks;

use EE_Capabilities;
use EE_Error;
use EE_Event;
use EEM_Change_Log;
use EEM_Registration;
use EventEspresso\core\exceptions\EntityNotFoundException;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\notices\NoticesContainerInterface;
use EventEspresso\WaitList\domain\services\commands\PromoteWaitListRegistrantsCommandHandler;
use InvalidArgumentException;
use ReflectionException;
use RuntimeException;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class PromoteWaitListRegistrantsCommandHandlerMock
 * Description
 *
 * @package EventEspresso\WaitList\tests\mocks
 * @author  Brent Christensen
 * @since   $VID:$
 */
class PromoteWaitListRegistrantsCommandHandlerMock extends PromoteWaitListRegistrantsCommandHandler
{

    /**
     * @return EEM_Registration
     */
    public function getRegistrationModel()
    {
        return $this->registration_model;
    }


    /**
     * @return EE_Capabilities
     */
    public function getCapabilities()
    {
        return $this->capabilities;
    }


    /**
     * @return EEM_Change_Log
     */
    public function getChangeLog()
    {
        return $this->change_log;
    }


    /**
     * @return NoticesContainerInterface
     */
    public function getNotices()
    {
        return $this->notices;
    }



    /**
     * @param EE_Event $event
     * @param int      $spaces_remaining
     * @param int      $wait_list_reg_count
     * @param int      $manual_control_spaces
     * @param bool     $auto_promote
     * @return void
     * @throws EE_Error
     */
    public function manuallyPromoteRegistrationsNotification(
        EE_Event $event,
        $spaces_remaining,
        $wait_list_reg_count,
        $manual_control_spaces,
        $auto_promote = false
    ) {
        parent::manuallyPromoteRegistrationsNotification(
            $event,
            $spaces_remaining,
            $wait_list_reg_count,
            $manual_control_spaces,
            $auto_promote
        );
    }


    /**
     * @param EE_Event $event
     * @param int      $regs_to_promote
     * @param bool     $auto_promote
     * @return int
     * @throws EE_Error
     * @throws RuntimeException
     * @throws EntityNotFoundException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function autoPromoteRegistrations(EE_Event $event, $regs_to_promote = 0, $auto_promote = false)
    {
        return parent::autoPromoteRegistrations(
            $event,
            $regs_to_promote,
            $auto_promote
        );
    }
}
