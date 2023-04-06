<?php

namespace EventEspresso\WaitList\domain\services\event;

use DomainException;
use EE_Error;
use EE_Event;
use EE_Registration;
use EEH_HTML;
use EventEspresso\core\domain\entities\contexts\ContextInterface;
use EventEspresso\core\exceptions\EntityNotFoundException;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidEntityException;
use EventEspresso\core\exceptions\InvalidFormSubmissionException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\commands\CommandBusInterface;
use EventEspresso\core\services\notices\NoticeConverterInterface;
use EventEspresso\core\services\notices\NoticesContainerInterface;
use EventEspresso\core\services\loaders\LoaderInterface;
use EventEspresso\modules\ticket_selector\DisplayTicketSelector;
use EventEspresso\WaitList\domain\services\collections\WaitListEventsCollection;
use EventEspresso\WaitList\domain\services\collections\WaitListFormHandlerCollection;
use EventEspresso\WaitList\domain\services\forms\WaitListFormHandler;
use Exception;
use InvalidArgumentException;
use LogicException;
use ReflectionException;
use RuntimeException;

/**
 * Class WaitListMonitor
 * tracks which event have active wait lists
 * and determines whether wait list forms should be displayed and processed for an event
 *
 * @package       Event Espresso
 * @author        Brent Christensen
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
     * @param WaitListFormHandlerCollection $wait_list_forms
     */
    private $wait_list_form_handlers;

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
     * @param WaitListEventsCollection      $wait_list_events
     * @param WaitListEventMeta             $wait_list_event_meta
     * @param WaitListFormHandlerCollection $wait_list_forms
     * @param CommandBusInterface           $command_bus
     * @param LoaderInterface               $loader
     * @param NoticeConverterInterface      $notice_converter
     */
    public function __construct(
        WaitListEventsCollection $wait_list_events,
        WaitListEventMeta $wait_list_event_meta,
        WaitListFormHandlerCollection $wait_list_forms,
        CommandBusInterface $command_bus,
        LoaderInterface $loader,
        NoticeConverterInterface $notice_converter
    ) {
        $this->wait_list_events        = $wait_list_events;
        $this->wait_list_event_meta    = $wait_list_event_meta;
        $this->wait_list_form_handlers = $wait_list_forms;
        $this->command_bus             = $command_bus;
        $this->loader                  = $loader;
        $this->notice_converter        = $notice_converter;
    }


    /**
     * returns true if an event has an active wait list with available spaces
     *
     * @param EE_Event $event
     * @return bool
     * @throws EE_Error
     */
    protected function eventHasOpenWaitList(EE_Event $event): bool
    {
        if ($this->wait_list_events->hasObject($event)) {
            $wait_list_reg_count = $this->wait_list_event_meta->getRegCount($event);
            $wait_list_spaces    = $this->wait_list_event_meta->getWaitListSpaces($event);
            $promoted_reg_ids    = $this->wait_list_event_meta->getPromotedRegIdsArrayCount($event);
            if ($wait_list_reg_count + $promoted_reg_ids < $wait_list_spaces) {
                return true;
            }
        }
        return false;
    }


    /**
     * @param EE_Event $event
     * @return WaitListFormHandler
     * @throws DomainException
     * @throws InvalidEntityException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    public function waitListFormForEvent(EE_Event $event): WaitListFormHandler
    {
        if ($this->wait_list_form_handlers->has($event->ID())) {
            return $this->wait_list_form_handlers->get($event->ID());
        }
        $wait_list_form_handler = $this->loader->getNew(
            'EventEspresso\WaitList\domain\services\forms\WaitListFormHandler',
            [ $event ]
        );
        if (! $this->wait_list_form_handlers->add($wait_list_form_handler, $event->ID())) {
            throw new DomainException(
                sprintf(
                    esc_html__(
                        'The Wait List form handler for event "%1$s" could not be added to the WaitListFormHandlerCollection.',
                        'event_espresso'
                    ),
                    $event->name()
                )
            );
        }
        return $wait_list_form_handler;
    }


    /**
     * @param EE_Event              $event
     * @param DisplayTicketSelector $ticket_selector
     * @return string
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidEntityException
     * @throws InvalidInterfaceException
     * @throws LogicException
     * @throws ReflectionException
     */
    public function getWaitListFormForEvent(EE_Event $event, DisplayTicketSelector $ticket_selector): string
    {
        if ($event->is_sold_out(true) && $this->eventHasOpenWaitList($event)) {
            if ($ticket_selector->isIframe()) {
                return $this->getWaitListLinkForEvent($event);
            }
            return apply_filters(
                'FHEE__EventEspresso_WaitList_domain_services_event_WaitListMonitor__getWaitListFormForEvent__redirect_params',
                $this->waitListFormForEvent($event)->display(),
                $event,
                $this
            );
        }
        return '';
    }


    /**
     * @param int $event_id
     * @return array
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidEntityException
     * @throws InvalidFormSubmissionException
     * @throws InvalidInterfaceException
     * @throws LogicException
     * @throws ReflectionException
     * @throws RuntimeException
     * @throws Exception
     */
    public function processWaitListFormForEvent(int $event_id): array
    {
        if (! $event_id) {
            throw new DomainException(
                esc_html__(
                    'The Wait List form can not be processed because an invalid or missing Event ID was supplied.',
                    'event_espresso'
                )
            );
        }
        $redirect_params = [];
        if (! $this->wait_list_events->has($event_id)) {
            return $redirect_params;
        }
        /** @var EE_Event $event */
        $event = $this->wait_list_events->get($event_id);
        try {
            $notices = $this->waitListFormForEvent($event)->process($_REQUEST);
            $this->processNotices($notices);
        } catch (Exception $exception) {
            // allow other code to catch exceptions and control whether they are thrown
            // by returning a list of query args to get added to the redirect URL
            // ie: either return query args to indicate a redirect should occur,
            // or the exception will get thrown
            // (client code could decide to thrown their own exception of course)
            $redirect_params = apply_filters(
                'FHEE__EventEspresso_WaitList_domain_services_event_WaitListMonitor__processWaitListFormForEvent__redirect_params',
                $redirect_params,
                $exception,
                $event,
                $this
            );
            if (empty($redirect_params)) {
                throw $exception;
            }
        }
        return $redirect_params;
    }


    /**
     * increment or decrement the wait list reg count for an event
     * when a registration's status changes to or from RWL
     *
     * @param EE_Registration       $registration
     * @param                       $old_STS_ID
     * @param                       $new_STS_ID
     * @param ContextInterface|null $context
     * @throws EE_Error
     * @throws EntityNotFoundException
     * @throws ReflectionException
     */
    public function registrationStatusUpdate(
        EE_Registration $registration,
        $old_STS_ID,
        $new_STS_ID,
        ContextInterface $context = null
    ) {
        $event = $registration->event();
        $this->command_bus->execute(
            $this->loader->getNew(
                'EventEspresso\WaitList\domain\services\commands\UpdateRegistrationWaitListMetaDataCommand',
                [$event, $registration, $old_STS_ID, $new_STS_ID, $context]
            )
        );
    }


    /**
     * factors wait list registrations into calculations involving spaces available for events
     *
     * @param int|float $spaces_available
     * @param EE_Event  $event
     * @return int|float
     */
    public function adjustEventSpacesAvailable($spaces_available, EE_Event $event)
    {
        if ($this->wait_list_events->hasObject($event)) {
            $spaces_available = $this->command_bus->execute(
                $this->loader->getNew(
                    'EventEspresso\WaitList\domain\services\commands\CalculateEventSpacesAvailableCommand',
                    [$event, $spaces_available]
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
     * @param EE_Event  $event
     * @param int|float $spaces_remaining
     */
    public function promoteWaitListRegistrants(EE_Event $event, $spaces_remaining = 0)
    {
        if ($this->wait_list_events->hasObject($event)) {
            $notices = $this->command_bus->execute(
                $this->loader->getNew(
                    'EventEspresso\WaitList\domain\services\commands\PromoteWaitListRegistrantsCommand',
                    [$event, $spaces_remaining]
                )
            );
            $this->processNotices($notices);
        }
    }


    /**
     * @param NoticesContainerInterface|null $notices
     */
    protected function processNotices(?NoticesContainerInterface $notices = null)
    {
        if ($notices instanceof NoticesContainerInterface) {
            $this->notice_converter->process($notices);
        }
    }


    /**
     * returns HTML for what appears to be the Wait List sign up form button,
     * but this button only opens the event in a new window/tab,
     * and should only be used if the Ticket Selector is detected within an iFrame
     *
     * @param EE_Event $event
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function getWaitListLinkForEvent(EE_Event $event): string
    {
        $EVT_ID              = $event->ID();
        $wait_list_form_html = EEH_HTML::div(
            '',
            '',
            'event-wait-list-form'
        );
        $wait_list_form_html .= EEH_HTML::div(
            '',
            "event-wait-list-$EVT_ID-join-wait-list-btn-submit-dv",
            'ee-join-wait-list-btn float-right-submit-dv ee-submit-input-dv'
        );
        $wait_list_form_html .= '<input name="event_wait_list[join_wait_list_btn]"';
        $wait_list_form_html .= ' value="' . esc_html__('Join The Wait List', 'event_espresso') . '"';
        $wait_list_form_html .= ' id="event-wait-list-' . $EVT_ID . '-join-wait-list-btn-submit"';
        $wait_list_form_html .= ' class="ee-join-wait-list-btn float-right button button-primary"';
        $wait_list_form_html .= ' type="submit" />';
        $wait_list_form_html .= EEH_HTML::divx(
            "event-wait-list-$EVT_ID-join-wait-list-btn-submit-dv",
            'ee-join-wait-list-btn float-right-submit-dv ee-submit-input-dv'
        );
        $event_link          = add_query_arg(
            ['display-wait-list' => 'true'],
            $event->get_permalink()
        );
        $wait_list_form_html .= '
    <script type="text/javascript">
        document.getElementById("event-wait-list-' . $EVT_ID . '-join-wait-list-btn-submit").onclick = function () {
            window.open("' . $event_link . '");
        };
    </script>';
        $wait_list_form_html .= EEH_HTML::div(
            EEH_HTML::br(),
            '',
            'clear'
        );
        $wait_list_form_html .= EEH_HTML::divx('', 'event-wait-list-form');
        return $wait_list_form_html;
    }
}
