<?php

namespace EventEspresso\WaitList\domain\services\forms;

use DomainException;
use EE_Error;
use EE_Event;
use EE_Form_Section_HTML;
use EE_Form_Section_Proper;
use EE_Registry;
use EED_Wait_Lists;
use EEH_HTML;
use EEM_Registration;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidFormSubmissionException;
use EventEspresso\core\exceptions\InvalidStatusException;
use EventEspresso\core\exceptions\UnexpectedEntityException;
use EventEspresso\core\libraries\form_sections\form_handlers\FormHandler;
use EventEspresso\WaitList\domain\services\event\WaitListEventMeta;
use InvalidArgumentException;
use LogicException;
use ReflectionException;

/**
 * Class EventEditorWaitListMetaBoxFormHandler
 * an admin form for controlling an event's wait list settings.
 * appears in an event editor sidebar metabox
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 */
class EventEditorWaitListMetaBoxFormHandler extends FormHandler
{
    /**
     * @var EE_Event $event
     */
    protected $event;

    /**
     * @var EEM_Registration $registration_model
     */
    private $registration_model;

    /**
     * @param WaitListEventMeta $event_meta
     */
    private $event_meta;


    /**
     * Form constructor.
     *
     * @param EE_Event          $event
     * @param WaitListEventMeta $event_meta
     * @param EEM_Registration  $registration_model
     * @param EE_Registry       $registry
     * @throws DomainException
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     */
    public function __construct(
        EE_Event $event,
        WaitListEventMeta $event_meta,
        EEM_Registration $registration_model,
        EE_Registry $registry
    ) {
        $this->event              = $event;
        $this->event_meta         = $event_meta;
        $this->registration_model = $registration_model;
        parent::__construct(
            esc_html__('Event Wait List Settings', 'event_espresso'),
            esc_html__('Event Wait List Settings', 'event_espresso'),
            'event_wait_list_settings',
            '',
            FormHandler::DO_NOT_SETUP_FORM,
            $registry
        );
    }


    /**
     * creates and returns the actual form
     *
     * @return EE_Form_Section_Proper
     * @throws EE_Error
     * @throws LogicException
     * @throws ReflectionException
     */
    public function generate(): EE_Form_Section_Proper
    {
        $this->setForm(
            new EventEditorWaitListMetaBoxForm($this->event, $this->event_meta)
        );
        return $this->form();
    }


    /**
     * takes the generated form and displays it along with ony other non-form HTML that may be required
     * returns a string of HTML that can be directly echoed in a template
     *
     * @return string
     * @throws UnexpectedEntityException
     * @throws DomainException
     * @throws LogicException
     * @throws EE_Error
     * @throws ReflectionException
     * @throws ReflectionException
     */
    public function display(): string
    {
        $spaces_remaining = $this->event->spaces_remaining([], false);
        $spaces_remaining = $spaces_remaining === EE_INF
            ? esc_html__('unlimited', 'event_espresso')
            : $spaces_remaining;
        // inject some additional subsections with HTML that's for display only
        $this->form(true)->add_subsections(
            [
                'view_wait_list_link' => new EE_Form_Section_HTML(
                    EEH_HTML::div(
                        EEH_HTML::div(
                            EEH_HTML::label(
                                esc_html__('Registration List', 'event_espresso')
                            )
                            . EEH_HTML::span($this->waitListRegCountDisplay()),
                            '',
                            'ee-waitlist-header__container ee-admin-container'
                        )
                        . EEH_HTML::div(
                            EEH_HTML::label(
                                esc_html__('Total Available Event Spaces', 'event_espresso')
                            )
                            . EEH_HTML::span($spaces_remaining),
                            '',
                            'ee-waitlist-header__container ee-admin-container'
                        )
                        . EEH_HTML::div(
                            EEH_HTML::label(
                                esc_html__('Wait List Registrants Previously Promoted', 'event_espresso')
                            )
                            . EEH_HTML::span($this->event_meta->getPromotedRegIdsArrayCount($this->event)),
                            '',
                            'ee-waitlist-header__container ee-admin-container'
                        ),
                        '',
                        'ee-waitlist-header__grid'
                    )
                ),
            ],
            'wait_list_spaces'
        );
        return parent::display();
    }


    /**
     * returns HTML for displaying Wait List Reg Count
     * that links to the reg admin list table filtered for that reg status and event
     *
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     * @throws ReflectionException
     */
    public function waitListRegCountDisplay(): string
    {
        $html = EEH_HTML::span(
            '',
            '',
            'dashicons dashicons-groups ee-status-color--RWL',
            'margin-inline-end: 0.5rem;'
        );
        $text = EEH_HTML::span($this->event_meta->getRegCount($this->event), '', 'ee-reg-list-link__reg-count');
        $text .= ' ' . esc_html__('Wait List Registrations', 'event_espresso');
        $text .= EEH_HTML::span('', '', 'dashicons dashicons-external');
        $html .= EED_Wait_Lists::wait_list_registrations_list_table_link($this->event, $text);
        return $html;
    }


    /**
     * handles processing the form submission
     * returns true or false depending on whether the form was processed successfully or not
     *
     * @param array $submitted_form_data
     * @return bool
     * @throws InvalidArgumentException
     * @throws InvalidStatusException
     * @throws LogicException
     * @throws InvalidFormSubmissionException
     * @throws EE_Error
     * @throws ReflectionException
     */
    public function process($submitted_form_data = []): bool
    {
        // process form
        $valid_data = parent::process($submitted_form_data);
        if (empty($valid_data)) {
            return false;
        }
        $wait_list_spaces = absint($valid_data['wait_list_spaces']);
        $this->event_meta->updateWaitListSpaces($this->event, $wait_list_spaces);
        $this->event_meta->updateAutoPromote($this->event, $valid_data['auto_promote_registrants']);
        $manual_control_spaces = absint($valid_data['manual_control_spaces']);
        // manual control spaces can't be more than the total number of spaces in the wait list
        $manual_control_spaces = $wait_list_spaces > 0
            ? min($wait_list_spaces, $manual_control_spaces)
            : $manual_control_spaces;
        $this->event_meta->updateManualControlSpaces($this->event, $manual_control_spaces);
        $this->event_meta->updateRegCount(
            $this->event,
            $this->registration_model->event_reg_count_for_statuses(
                $this->event->ID(),
                EEM_Registration::status_id_wait_list
            )
        );
        // mark event as having a wait list if number of spaces available is positive
        $this->event->set('EVT_allow_overflow', $wait_list_spaces > 0);
        // make sure event is saved
        $this->event->save();
        return false;
    }
}
