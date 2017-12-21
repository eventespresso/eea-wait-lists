<?php

namespace EventEspresso\WaitList\domain\services\commands;

use EE_Event;
use EE_Registration;
use EventEspresso\core\domain\entities\contexts\ContextInterface;
use EventEspresso\core\services\commands\Command;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class UpdateRegistrationWaitListMetaDataCommand
 * DTO for passing data to UpdateRegistrationWaitListMetaDataCommandHandler
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class UpdateRegistrationWaitListMetaDataCommand extends Command
{

    /**
     * @var EE_Event $event
     */
    private $event;
    /**
     * @var EE_Registration $registration
     */
    private $registration;

    /**
     * @var string $old_Status_Id
     */
    private $old_Status_Id;

    /**
     * @var string $new_Status_Id
     */
    private $new_Status_Id;


    /**
     * @var ContextInterface|null
     */
    private $context;


    /**
     * UpdateRegistrationWaitListMetaDataCommand constructor.
     *
     * @param EE_Event        $event
     * @param EE_Registration $registration
     * @param string          $old_Status_Id
     * @param string          $new_Status_Id
     * @param ContextInterface|null    $context
     */
    public function __construct(
        EE_Event $event,
        EE_Registration $registration,
        $old_Status_Id,
        $new_Status_Id,
        ContextInterface $context = null
    ) {
        $this->event = $event;
        $this->registration = $registration;
        $this->old_Status_Id = $old_Status_Id;
        $this->new_Status_Id = $new_Status_Id;
        $this->context = $context;
    }



    /**
     * @return EE_Event
     */
    public function getEvent()
    {
        return $this->event;
    }



    /**
     * @return EE_Registration
     */
    public function getRegistration()
    {
        return $this->registration;
    }



    /**
     * @return string
     */
    public function getOldStatusId()
    {
        return $this->old_Status_Id;
    }



    /**
     * @return string
     */
    public function getNewStatusId()
    {
        return $this->new_Status_Id;
    }


    /**
     * @return ContextInterface|null
     */
    public function getContext()
    {
        return $this->context;
    }


}
