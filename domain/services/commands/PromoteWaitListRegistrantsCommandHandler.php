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
use EventEspresso\core\exceptions\InvalidEntityException;
use EventEspresso\core\services\notices\NoticesContainerInterface;
use EventEspresso\core\services\commands\CommandInterface;
use EventEspresso\WaitList\domain\Constants;
use RuntimeException;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class PromoteWaitListRegistrantsCommandHandler
 * Manages "moving" registrations from the Wait List
 * to one of the other registration statuses that allows payment (if applicable)
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class PromoteWaitListRegistrantsCommandHandler extends WaitListCommandHandler
{

    /**
     * @var EEM_Registration $registration_model
     */
    private $registration_model;

    /**
     * @var EE_Capabilities $capabilities
     */
    private $capabilities;

    /**
     * @var EEM_Change_Log $change_log
     */
    private $change_log;

    /**
     * @var NoticesContainerInterface $notices
     */
    private $notices;



    /**
     * PromoteWaitListRegistrantsCommandHandler constructor.
     *
     * @param EEM_Registration          $registration_model
     * @param EE_Capabilities           $capabilities
     * @param EEM_Change_Log            $change_log
     * @param NoticesContainerInterface $notices
     */
    public function __construct(
        EEM_Registration $registration_model,
        EE_Capabilities $capabilities,
        EEM_Change_Log $change_log,
        NoticesContainerInterface $notices
    ) {
        $this->registration_model = $registration_model;
        $this->capabilities = $capabilities;
        $this->change_log = $change_log;
        $this->notices = $notices;
    }



    /**
     * @param CommandInterface $command
     * @return NoticesContainerInterface|null
     * @throws EE_Error
     * @throws InvalidEntityException
     * @throws RuntimeException
     */
    public function handle(CommandInterface $command)
    {
        if (! $command instanceof PromoteWaitListRegistrantsCommand) {
            throw new InvalidEntityException(
                $command,
                'EventEspresso\WaitList\domain\services\commands\PromoteWaitListRegistrantsCommand'
            );
        }
        $event = $command->getEvent();
        $spaces_remaining = $command->getSpacesRemaining();
        $promote_wait_list_registrants = $this->getPromoteWaitListRegistrants($event);
        if ($promote_wait_list_registrants) {
            // registrations currently on wait list
            $wait_list_reg_count = $this->getRegCount($event);
            $spaces_remaining += $wait_list_reg_count;
            if ($spaces_remaining < 1) {
                return null;
            }
            $auto_promote = $this->getAutoPromote($event);
            $spaces_remaining = $this->manuallyPromoteRegistrations(
                $event,
                $spaces_remaining,
                $wait_list_reg_count,
                $auto_promote
            );
            $this->autoPromoteRegistrations($event, $spaces_remaining, $auto_promote);
            return $this->notices;
        }
        return null;
    }



    /**
     * @param EE_Event $event
     * @param int      $spaces_remaining
     * @param int      $wait_list_reg_count
     * @param bool     $auto_promote
     * @return bool
     * @throws EE_Error
     */
    private function manuallyPromoteRegistrations(
        EE_Event $event,
        $spaces_remaining,
        $wait_list_reg_count,
        $auto_promote = false
    ) {
        $manual_control_spaces = absint(
            $event->get_extra_meta(Constants::MANUAL_CONTROL_SPACES_META_KEY, true)
        );
        if (
            $spaces_remaining > 0
            && ($manual_control_spaces > 0 || $auto_promote === false)
            && is_admin()
            && $this->capabilities->current_user_can(
                'ee_edit_registration',
                'espresso_promote_wait_list_registrants'
            )
        ) {
            $this->notices->addAttention(
                sprintf(
                    esc_html__(
                        'There is %1$d space(s) available for "%2$s", with %3$d space(s) under manual control, and %4$d registrant(s) on the Wait List for that event. %6$s Please click here to view a list of %5$s and select those you wish to offer a space to by updating their reg status accordingly.'
                    ),
                    $auto_promote === false ? $spaces_remaining : min($spaces_remaining, $manual_control_spaces),
                    $event->name(),
                    $manual_control_spaces,
                    $wait_list_reg_count,
                    EED_Wait_Lists::wait_list_registrations_list_table_link($event),
                    '<br />'
                ),
                __FILE__, __FUNCTION__, __LINE__
            );
        }
        $spaces_remaining -= $manual_control_spaces;
        return $spaces_remaining;
    }



    /**
     * @param EE_Event $event
     * @param int      $regs_to_promote
     * @param bool     $auto_promote
     * @throws EE_Error
     * @throws RuntimeException
     */
    private function autoPromoteRegistrations(EE_Event $event, $regs_to_promote = 0, $auto_promote = false)
    {
        if (! $auto_promote || $regs_to_promote < 1) {
            return;
        }
        /** @var EE_Registration[] $registrations */
        $registrations = $this->registration_model->get_all(
            array(
                array(
                    'EVT_ID' => $event->ID(),
                    'STS_ID' => EEM_Registration::status_id_wait_list,
                ),
                'limit'    => $regs_to_promote,
                'order_by' => array('REG_ID' => 'ASC'),
            )
        );
        if (empty($registrations)) {
            return;
        }
        // updating the reg status will trigger a sold out status check on the event,
        // so let's turn that off while we promote these registrations by switching their status,
        // because that won't affect the event status, as these registrations
        // were already being counted against the event's sold tickets count
        $event->update_extra_meta(
            Constants::PERFORM_SOLD_OUT_STATUS_CHECK_META_KEY,
            false
        );
        foreach ($registrations as $registration) {
            if (! $registration instanceof EE_Registration) {
                continue;
            }
            $registration->set_status($event->default_registration_status());
            $message = sprintf(
                esc_html__(
                    'The registration status for "%1$s" %2$s(ID:%3$d)%4$s has been successfully updated to "%5$s". They were previously on the Wait List for "%6$s".'
                ),
                $registration->attendee() instanceof EE_Attendee ? $registration->attendee()->full_name() : '',
                '<span class="lt-grey-text">',
                $registration->ID(),
                '</span>',
                $registration->pretty_status(),
                $event->name()
            );
            $this->change_log->log(Constants::LOG_TYPE, $message, $event);
            if (
                $this->capabilities->current_user_can(
                    'ee_edit_registration',
                    'espresso_view_wait_list_update_notice'
                )
            ) {
                $this->notices->addSuccess($message, __FILE__, __FUNCTION__, __LINE__);
            }
        }
        do_action(
            'AHEE__EventEspresso_WaitList_WaitListMonitor__promoteWaitListRegistrants__after_registrations_promoted',
            $registrations,
            $event,
            $this
        );
        $event->update_extra_meta(
            Constants::PERFORM_SOLD_OUT_STATUS_CHECK_META_KEY,
            true
        );
    }
}
