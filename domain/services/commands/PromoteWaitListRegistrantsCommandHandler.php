<?php

namespace EventEspresso\WaitList\domain\services\commands;

use EE_Attendee;
use EE_Capabilities;
use EE_Error;
use EE_Event;
use EE_Registration;
use EED_Wait_Lists;
use EEM_Change_Log;
use EEM_Registration;
use EventEspresso\core\domain\entities\contexts\Context;
use EventEspresso\core\exceptions\EntityNotFoundException;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidEntityException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\notices\NoticesContainerInterface;
use EventEspresso\core\services\commands\CommandInterface;
use EventEspresso\WaitList\domain\Domain;
use EventEspresso\WaitList\domain\services\event\WaitListEventMeta;
use InvalidArgumentException;
use ReflectionException;
use RuntimeException;

/**
 * Class PromoteWaitListRegistrantsCommandHandler
 * Manages "moving" registrations from the Wait List
 * to one of the other registration statuses that allows payment (if applicable)
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 */
class PromoteWaitListRegistrantsCommandHandler extends WaitListCommandHandler
{
    /**
     * @var EEM_Registration $registration_model
     */
    protected $registration_model;

    /**
     * @var EE_Capabilities $capabilities
     */
    protected $capabilities;

    /**
     * @var EEM_Change_Log $change_log
     */
    protected $change_log;

    /**
     * @var NoticesContainerInterface $notices
     */
    protected $notices;


    /**
     * PromoteWaitListRegistrantsCommandHandler constructor.
     *
     * @param EEM_Registration          $registration_model
     * @param EE_Capabilities           $capabilities
     * @param EEM_Change_Log            $change_log
     * @param NoticesContainerInterface $notices
     * @param WaitListEventMeta         $wait_list_event_meta
     */
    public function __construct(
        EEM_Registration $registration_model,
        EE_Capabilities $capabilities,
        EEM_Change_Log $change_log,
        NoticesContainerInterface $notices,
        WaitListEventMeta $wait_list_event_meta
    ) {
        $this->registration_model = $registration_model;
        $this->capabilities       = $capabilities;
        $this->change_log         = $change_log;
        $this->notices            = $notices;
        parent::__construct($wait_list_event_meta);
    }


    /**
     * @param CommandInterface $command
     * @return NoticesContainerInterface|null
     * @throws EE_Error
     * @throws InvalidEntityException
     * @throws RuntimeException
     * @throws EntityNotFoundException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function handle(CommandInterface $command)
    {
        if (! $command instanceof PromoteWaitListRegistrantsCommand) {
            throw new InvalidEntityException(
                $command,
                'EventEspresso\WaitList\domain\services\commands\PromoteWaitListRegistrantsCommand'
            );
        }
        $event            = $command->getEvent();
        $spaces_remaining = $command->getSpacesRemaining();
        // registrations currently on wait list
        $wait_list_reg_count = $this->eventMeta()->getRegCount($event);
        $spaces_remaining    += $wait_list_reg_count;
        if ($spaces_remaining < 1) {
            return null;
        }
        $auto_promote                  = $this->eventMeta()->getAutoPromote($event);
        $manual_control_spaces         = $this->eventMeta()->getManualControlSpaces($event);
        $promote_wait_list_registrants = $this->eventMeta()->getPromoteWaitListRegistrants($event);
        if ($promote_wait_list_registrants) {
            $wait_list_reg_count -= $this->autoPromoteRegistrations(
                $event,
                $spaces_remaining - $manual_control_spaces,
                $auto_promote
            );
        }
        $this->manuallyPromoteRegistrationsNotification(
            $event,
            $spaces_remaining,
            $wait_list_reg_count,
            $manual_control_spaces,
            $auto_promote
        );
        return $this->notices;
    }


    /**
     * @param EE_Event  $event
     * @param int|float $spaces_remaining
     * @param int       $wait_list_reg_count
     * @param int       $manual_control_spaces
     * @param bool      $auto_promote
     * @return void
     * @throws EE_Error
     */
    protected function manuallyPromoteRegistrationsNotification(
        EE_Event $event,
        $spaces_remaining,
        int $wait_list_reg_count,
        int $manual_control_spaces,
        bool $auto_promote = false
    ) {
        if (
            $spaces_remaining > 0
            && $wait_list_reg_count > 0
            && ($manual_control_spaces > 0 || $auto_promote === false)
            && is_admin()
            && $this->capabilities->current_user_can(
                'ee_edit_registrations',
                'espresso_promote_wait_list_registrants'
            )
        ) {
            $this->notices->addAttention(
                sprintf(
                    esc_html__(
                        'There is %1$d space(s) available for "%2$s", with %3$d space(s) under manual control, and %4$d registrant(s) on the Wait List for that event. %6$s View a list of %5$s and select those you wish to offer a space to by updating their registration status accordingly.',
                        'event_espresso'
                    ),
                    $spaces_remaining,
                    $event->name(),
                    $manual_control_spaces,
                    $wait_list_reg_count,
                    EED_Wait_Lists::wait_list_registrations_list_table_link($event),
                    '<br />'
                ),
                __FILE__,
                __FUNCTION__,
                __LINE__
            );
        }
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
    protected function autoPromoteRegistrations(
        EE_Event $event,
        int $regs_to_promote = 0,
        bool $auto_promote = false
    ): int {
        if (! $auto_promote || $regs_to_promote < 1) {
            return 0;
        }
        // because we use $regs_to_promote as a query limit, make sure it's not INF
        $regs_to_promote = $regs_to_promote === EE_INF
            ? $this->eventMeta()->getWaitListSpaces($event)
            : $regs_to_promote;
        /** @var EE_Registration[] $registrations */
        $registrations = $this->registration_model->get_all(
            [
                [
                    'EVT_ID' => $event->ID(),
                    'STS_ID' => EEM_Registration::status_id_wait_list,
                ],
                'limit'    => $regs_to_promote,
                'order_by' => ['REG_ID' => 'ASC'],
            ]
        );
        if (empty($registrations)) {
            return 0;
        }
        // updating the reg status will trigger a sold out status check on the event,
        // so let's turn that off while we promote these registrations by switching their status,
        // because that won't affect the event status, as these registrations
        // were already being counted against the event's sold tickets count
        $this->eventMeta()->updatePerformSoldOutStatusCheck($event, false);
        $promoted = 0;
        foreach ($registrations as $registration) {
            if (! $registration instanceof EE_Registration) {
                continue;
            }
            // check to make sure that ticket of current registration has saleable spots
            $has_spots = false;
            $ticket    = $registration->ticket();
            $datetimes = $ticket->datetimes();
            foreach ($datetimes as $datetime) {
                $has_spots = $ticket->remaining($datetime->ID());
                if ($has_spots) {
                    break;
                }
            }
            if (! $has_spots) {
                continue;
            }
            // set the registration status to the event's default registration status but filtered to allow for changes
            $new_reg_status = apply_filters(
                'FHEE__EventEspresso_WaitList_domain_services_commands_PromoteWaitListRegistrantsCommandHandler__autoPromoteRegistrations__new_reg_status',
                $event->default_registration_status(),
                $registration
            );
            $registration->set_status(
                $new_reg_status,
                false,
                new Context(
                    Domain::CONTEXT_REGISTRATION_STATUS_CHANGE_FROM_WAIT_LIST_AUTO_PROMOTE,
                    esc_html__(
                        'Executed when a registration on the wait-list was auto-promoted.',
                        'event_espresso'
                    )
                )
            );
            $message = sprintf(
                esc_html__(
                    'The registration status for "%1$s" %2$s(ID:%3$d)%4$s has been successfully updated to "%5$s". They were previously on the Wait List for "%6$s".',
                    'event_espresso'
                ),
                $registration->attendee() instanceof EE_Attendee ? $registration->attendee()->full_name() : '',
                '<span class="lt-grey-text">',
                $registration->ID(),
                '</span>',
                $registration->pretty_status(),
                $event->name()
            );
            $this->change_log->log(Domain::LOG_TYPE_WAIT_LIST, $message, $event);
            if (
                $this->capabilities->current_user_can(
                    'ee_edit_registrations',
                    'espresso_view_wait_list_update_notice'
                )
            ) {
                $this->notices->addSuccess($message);
            }
            $promoted++;
        }
        do_action_ref_array(
            'AHEE__EventEspresso_WaitList_domain_services_commands_PromoteWaitListRegistrantsCommandHandler__autoPromoteRegistrations',
            [$registrations, $event, $this]
        );
        $this->eventMeta()->updatePerformSoldOutStatusCheck($event, true);
        return $promoted;
    }
}
