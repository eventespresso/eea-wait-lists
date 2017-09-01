<?php

namespace EventEspresso\WaitList\domain\services\event;

use DomainException;
use EE_Error;
use EE_Event;
use EE_Registration;
use EE_Wait_Lists;
use EEM_Registration;
use EventEspresso\core\exceptions\EntityNotFoundException;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidEntityException;
use EventEspresso\core\exceptions\InvalidFormSubmissionException;
use EventEspresso\core\services\commands\CommandBusInterface;
use EventEspresso\core\services\notices\NoticeConverterInterface;
use EventEspresso\core\services\notices\NoticesContainerInterface;
use EventEspresso\core\services\loaders\LoaderInterface;
use EventEspresso\WaitList\domain\Domain;
use EventEspresso\WaitList\domain\services\collections\WaitListEventsCollection;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class WaitListMonitor
 * tracks which event have active wait lists
 * and determines whether wait list forms should be displayed and processed for an event
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class WaitListMonitor
{

    /**
     * @var WaitListEventsCollection $wait_list_events
     */
    private $wait_list_events;

    /**
     * @param WaitListEventMeta $event_meta
     */
    private $wait_list_event_meta;

    /**
     * @var CommandBusInterface $command_bus
     */
    private $command_bus;

    /**
     * @var LoaderInterface $loader
     */
    private $loader;

    /**
     * @var NoticeConverterInterface
     */
    private $notice_converter;



    /**
     * WaitListMonitor constructor.
     *
     * @param WaitListEventsCollection $wait_list_events
     * @param WaitListEventMeta        $wait_list_event_meta
     * @param CommandBusInterface      $command_bus
     * @param LoaderInterface          $loader
     * @param NoticeConverterInterface $notice_converter
     */
    public function __construct(
        WaitListEventsCollection $wait_list_events,
        WaitListEventMeta $wait_list_event_meta,
        CommandBusInterface $command_bus,
        LoaderInterface $loader,
        NoticeConverterInterface $notice_converter
    ) {
        $this->wait_list_events = $wait_list_events;
        $this->wait_list_event_meta = $wait_list_event_meta;
        $this->command_bus = $command_bus;
        $this->loader = $loader;
        $this->notice_converter = $notice_converter;
    }



    /**
     * returns true if an event has an active wait list with available spaces
     *
     * @param EE_Event $event
     * @return bool
     * @throws EE_Error
     */
    protected function eventHasOpenWaitList(EE_Event $event)
    {
        if ($this->wait_list_events->hasObject($event)) {
            $wait_list_reg_count = $this->wait_list_event_meta->getRegCount($event);
            $wait_list_spaces = $this->wait_list_event_meta->getWaitListSpaces($event);
            $promoted_reg_ids = $this->wait_list_event_meta->getPromotedRegIdsArrayCount($event);
            if ($wait_list_reg_count + $promoted_reg_ids < $wait_list_spaces) {
                return true;
            }
        }
        return false;
    }



    /**
     * @param EE_Event $event
     * @return \EventEspresso\WaitList\domain\services\forms\WaitListFormHandler
     */
    public function waitListFormForEvent(EE_Event $event)
    {
        return EE_Wait_Lists::loader()->getShared(
            'EventEspresso\WaitList\domain\services\forms\WaitListFormHandler',
            array($event)
        );
    }



    /**
     * @param EE_Event $event
     * @return string
     * @throws LogicException
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws DomainException
     * @throws EE_Error
     */
    public function getWaitListFormForEvent(EE_Event $event)
    {
        if ($event->is_sold_out() && $this->eventHasOpenWaitList($event)) {
            return $this->waitListFormForEvent($event)->display();
        }
        return '';
    }



    /**
     * @param int $event_id
     * @return string
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidEntityException
     * @throws InvalidFormSubmissionException
     * @throws LogicException
     * @throws RuntimeException
     */
    public function processWaitListFormForEvent($event_id)
    {
        $referrer = filter_input(INPUT_SERVER, 'HTTP_REFERER');
        if ($this->wait_list_events->has($event_id)) {
            /** @var EE_Event $event */
            $event = $this->wait_list_events->get($event_id);
            $notices = $this->waitListFormForEvent($event)->process($_REQUEST);
            $this->processNotices($notices);
            if(isset($_REQUEST['event_wait_list']) && is_array($_REQUEST['event_wait_list'])) {
                $inputs = reset($_REQUEST['event_wait_list']);
                $referrer = ! empty($inputs['referrer']) ? $inputs['referrer'] : $referrer;
            }
        }
        return $referrer;
    }


    /**
     * increment or decrement the wait list reg count for an event
     * when a registration's status changes to or from RWL
     *
     * @param EE_Registration  $registration
     * @param                  $old_STS_ID
     * @param                  $new_STS_ID
     * @throws EE_Error
     * @throws EntityNotFoundException
     */
    public function registrationStatusUpdate(EE_Registration $registration, $old_STS_ID, $new_STS_ID)
    {
        $event = $registration->event();
        if ($this->wait_list_events->hasObject($event)) {
            $this->command_bus->execute(
                $this->loader->getNew(
                    'EventEspresso\WaitList\domain\services\commands\UpdateRegistrationWaitListMetaDataCommand',
                    array($event, $registration, $old_STS_ID, $new_STS_ID)
                )
            );
        }
    }



    /**
     * factors wait list registrations into calculations involving spaces available for events
     *
     * @param int      $spaces_available
     * @param EE_Event $event
     * @return int
     * @throws EE_Error
     */
    public function adjustEventSpacesAvailable($spaces_available, EE_Event $event)
    {
        if ($this->wait_list_events->hasObject($event)) {
            $spaces_available = $this->command_bus->execute(
                $this->loader->getNew(
                    'EventEspresso\WaitList\domain\services\commands\CalculateEventSpacesAvailableCommand',
                    array($event, $spaces_available)
                )
            );
        }
        return $spaces_available;
    }



    /**
     * If "auto promote" is turned on for an event with a wait list,
     * then registrations will automatically have their statuses changed from RWL
     * to whatever the event's default reg status is as spaces become available
     *
     * @param EE_Event $event
     * @param int      $spaces_remaining
     * @throws EE_Error
     */
    public function promoteWaitListRegistrants(
        EE_Event $event,
        $spaces_remaining = 0
    ) {
        if ($this->wait_list_events->hasObject($event)) {
            $notices = $this->command_bus->execute(
                $this->loader->getNew(
                    'EventEspresso\WaitList\domain\services\commands\PromoteWaitListRegistrantsCommand',
                    array($event, $spaces_remaining)
                )
            );
            $this->processNotices($notices);
        }
    }



    /**
     * @param NoticesContainerInterface $notices
     * @throws EE_Error
     */
    protected function processNotices(NoticesContainerInterface $notices = null)
    {
        if ($notices instanceof NoticesContainerInterface) {
            $this->notice_converter->process($notices);
        }
    }


}
// End of file WaitListMonitor.php
// Location: EventEspresso/Constants/WaitListMonitor.php
