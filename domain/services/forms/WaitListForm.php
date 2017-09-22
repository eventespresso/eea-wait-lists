<?php

namespace EventEspresso\WaitList\domain\services\forms;

use EE_Div_Per_Section_Layout;
use EE_Email_Input;
use EE_Error;
use EE_Event;
use EE_Fixed_Hidden_Input;
use EE_Form_Section_HTML;
use EE_Form_Section_Proper;
use EE_Integer_Input;
use EE_Select_Input;
use EE_Submit_Input;
use EE_Text_Input;
use EE_Ticket;
use EEH_HTML;
use EventEspresso\WaitList\domain\services\event\WaitListEventMeta;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class WaitListForm
 * Top Level EE_Form_Section_Proper for the Wait List Sign Up form
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class WaitListForm extends EE_Form_Section_Proper
{


    /**
     * @param WaitListEventMeta $event_meta
     */
    private $event_meta;



    /**
     * WaitListForm constructor.
     *
     * @param EE_Event          $event
     * @param EE_Ticket[]       $tickets
     * @param WaitListEventMeta $event_meta
     * @throws EE_Error
     */
    public function __construct(EE_Event $event, array $tickets, WaitListEventMeta $event_meta)
    {
        $this->event_meta = $event_meta;
        $wait_list_spaces_left = $this->calculateWaitListSpacesRemaining($event);
        $form_options = $wait_list_spaces_left === 0
            ? $this->emptyFormOptions($event)
            : $this->waitListFormOptions($event, $tickets, $wait_list_spaces_left);
        $form_options['subsections'] += $this->hiddenInputs($event);
        parent::__construct($form_options);
    }



    /**
     * @param EE_Event $event
     * @return mixed
     * @throws EE_Error
     */
    private function calculateWaitListSpacesRemaining(EE_Event $event)
    {
        $wait_list_reg_count = $this->event_meta->getRegCount($event);
        $wait_list_spaces = $this->event_meta->getWaitListSpaces($event);
        $promoted_reg_ids = $this->event_meta->getPromotedRegIdsArrayCount($event);
        $wait_list_spaces_left = $wait_list_spaces - ($wait_list_reg_count + $promoted_reg_ids);
        $wait_list_spaces_left = max($wait_list_spaces_left, 0);
        return min($wait_list_spaces_left, $event->additional_limit());
    }



    /**
     * @param EE_Event $event
     * @return array
     * @throws EE_Error
     */
    private function hiddenInputs(EE_Event $event)
    {
        return array(
            'route' => new EE_Fixed_Hidden_Input(
                array(
                    'default' => 'join',
                )
            ),
            'event_id' => new EE_Fixed_Hidden_Input(
                array(
                    'default' => $event->ID(),
                )
            ),
        );
    }



    /**
     * @param EE_Event $event
     * @return array
     * @throws EE_Error
     */
    private function emptyFormOptions(EE_Event $event)
    {
        return array(
            'name'            => 'event_wait_list',
            'html_id'         => "event-wait-list-{$event->ID()}",
            'html_class'      => 'event-wait-list-form',
            'layout_strategy' => new EE_Div_Per_Section_Layout(),
            'subsections'     => array()
        );
    }



    /**
     * @param EE_Event $event
     * @param array    $tickets
     * @param int      $wait_list_spaces_left
     * @return array
     * @throws \EE_Error
     */
    private function waitListFormOptions(EE_Event $event, array $tickets, $wait_list_spaces_left = 10)
    {
        return array(
            'name'            => 'event_wait_list',
            'html_id'         => "event-wait-list-{$event->ID()}",
            'html_class'      => 'event-wait-list-form',
            'layout_strategy' => new EE_Div_Per_Section_Layout(),
            'subsections'     => array(
                'join_wait_list_btn'            => new EE_Submit_Input(
                    array(
                        'html_class'            => 'ee-join-wait-list-btn float-right',
                        'other_html_attributes' => ' data-inputs="event-wait-list-'
                                                   . $event->ID()
                                                   . '-hidden-inputs"',
                        'default'               => esc_html__(
                            'Sign Up For The Wait List',
                            'event_espresso'
                        ),
                    )
                ),
                'hidden_inputs' => new EE_Form_Section_Proper(
                    array(
                        'layout_strategy' => new EE_Div_Per_Section_Layout(),
                        'html_class'      => 'event_wait_list-hidden-inputs',
                        'html_style'      => 'display:none;',
                        'subsections'     => array(
                            'wait_list_form_notice' => new EE_Form_Section_HTML(
                                EEH_HTML::h2(
                                    esc_html__('Join Now', 'event_espresso'),
                                    '', 'ee-wait-list-notice-hdr'
                                )
                                . EEH_HTML::p(
                                    esc_html__(
                                        'If you would like to be added to the waiting list for this event, then please enter your name and email address, and we will contact you when spaces become available.',
                                        'event_espresso'
                                    ),
                                    '', 'small-text ee-wait-list-notice-pg'
                                )
                            ),
                            'registrant_name'       => new EE_Text_Input(
                                array(
                                    'html_label_text'       => esc_html__('Name', 'event_espresso'),
                                    'html_label_class'      => 'small-text grey-text',
                                    'other_html_attributes' => ' placeholder="'
                                                               . esc_html__(
                                                                   'please enter your name',
                                                                   'event_espresso'
                                                               )
                                                               . '"',
                                    'html_class'            => '',
                                    'default'               => '',
                                    'required'              => true,
                                )
                            ),
                            'registrant_email'      => new EE_Email_Input(
                                array(
                                    'html_label_text'       => esc_html__(
                                        'Email Address',
                                        'event_espresso'
                                    ),
                                    'html_label_class'      => 'small-text grey-text',
                                    'other_html_attributes' => ' placeholder="'
                                                               . esc_html__(
                                                                   'please enter a valid email address',
                                                                   'event_espresso'
                                                               )
                                                               . '"',
                                    'html_class'            => '',
                                    'default'               => '',
                                    'required'              => true,
                                )
                            ),
                            'ticket'                => new EE_Select_Input(
                                $tickets,
                                array(
                                    'html_label_text'  => esc_html__(
                                        'Preferred Option',
                                        'event_espresso'
                                    ),
                                    'html_label_class' => 'small-text grey-text',
                                    'html_class'       => '',
                                    'default'          => '',
                                    'required'         => true,
                                )
                            ),
                            'quantity'              => new EE_Integer_Input(
                                array(
                                    'html_label_text'  => esc_html__(
                                        'Qty',
                                        'event_espresso'
                                    ),
                                    'html_label_class' => 'small-text grey-text',
                                    'html_style'       => 'max-width:120px;',
                                    'default'          => 1,
                                    'required'         => true,
                                    'min_value'        => 1,
                                    'max_value'        => $wait_list_spaces_left,
                                )
                            ),
                            'lb1'                   => new EE_Form_Section_HTML(EEH_HTML::br()),
                            'submit'                => new EE_Submit_Input(
                                array(
                                    'html_class' => 'ee-submit-wait-list-btn float-right',
                                    'default'    => esc_html__('Join The Wait List', 'event_espresso'),
                                )
                            ),
                            'clear_submit'          => new EE_Form_Section_HTML(
                                EEH_HTML::div('&nbsp;', '', 'clear')
                            ),
                            'close_form'            => new EE_Form_Section_HTML(
                                EEH_HTML::div(
                                    EEH_HTML::link(
                                        '',
                                        esc_html__('cancel', 'event_espresso'),
                                        '', '',
                                        'ee-wait-list-cancel-lnk small-text lt-grey-text', '',
                                        ' data-inputs="event-wait-list-'
                                        . $event->ID()
                                        . '-hidden-inputs"'
                                    ),
                                    '', 'ee-wait-list-cancel-dv'
                                )
                            ),
                            'referrer' => new EE_Fixed_Hidden_Input(
                                array(
                                    'default' => home_url(add_query_arg(null, null)),
                                )
                            ),
                        ),
                    )
                ),
                'clear_form'                    => new EE_Form_Section_HTML(
                    EEH_HTML::div(EEH_HTML::br(), '', 'clear')
                ),
            )
        );
    }


}
