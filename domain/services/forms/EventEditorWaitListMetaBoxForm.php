<?php

namespace EventEspresso\WaitList\domain\services\forms;

use EE_Div_Per_Section_Layout;
use EE_Error;
use EE_Event;
use EE_Form_Section_HTML;
use EE_Form_Section_Proper;
use EE_Text_Input;
use EE_Yes_No_Input;
use EEH_HTML;
use EventEspresso\WaitList\domain\services\event\WaitListEventMeta;

/**
 * Class EventEditorWaitListMetaBoxForm
 * Top Level EE_Form_Section_Proper for the Wait List Event Editor form
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 *
 */
class EventEditorWaitListMetaBoxForm extends EE_Form_Section_Proper
{
    /**
     * @param WaitListEventMeta $event_meta
     */
    private $event_meta;



    /**
     * EventEditorWaitListMetaBoxForm constructor.
     *
     * @param EE_Event          $event
     * @param WaitListEventMeta $event_meta
     * @throws EE_Error
     */
    public function __construct(EE_Event $event, WaitListEventMeta $event_meta)
    {
        $this->event_meta = $event_meta;
        parent::__construct(
            $this->waitListMetaBoxFormOptions($event)
        );
    }



    /**
     * @param EE_Event $event
     * @return array
     * @throws EE_Error
     */
    private function waitListMetaBoxFormOptions(EE_Event $event)
    {
        return array(
            'name'            => 'event_wait_list_settings',
            'html_id'         => 'event_wait_list_settings',
            'layout_strategy' => new EE_Div_Per_Section_Layout(),
            'subsections'     => array(
                'wait_list_spaces'         => new EE_Text_Input(
                    array(
                        'html_label_text'       => esc_html__('Wait List Spaces', 'event_espresso'),
                        'html_help_text'        => esc_html__(
                            'Number of additional registrants the wait list accepts before the event is completely sold out. For example, if your reg limit for an event is 100, and this field is set to 20, then the wait list form will be displayed until there are a total of 120 registrants for the event. IMPORTANT! Setting this field to zero will turn off the Wait List and prevent any further processing for any registrants that may already be signed up.',
                            'event_espresso'
                        ),
                        'other_html_attributes' => ' size="4"',
                        'html_class'            => 'ee-numeric',
                        'default'               => $this->event_meta->getWaitListSpaces($event),
                        'required'              => false,
                    )
                ),
                'lb1'                      => new EE_Form_Section_HTML(EEH_HTML::br()),
                'auto_promote_registrants' => new EE_Yes_No_Input(
                    array(
                        'html_label_text' => esc_html__('Auto Promote Registrants?', 'event_espresso'),
                        'html_help_text'  => esc_html__(
                            'Controls whether or not to automatically promote registrants from the wait list to the "Pending Payment" status (or the default event reg status) based on their position on the wait list. If no, then this will need to be done manually.',
                            'event_espresso'
                        ),
                        'default'         => $this->event_meta->getAutoPromote($event),
                        'required'        => false,
                    )
                ),
                'lb2'                      => new EE_Form_Section_HTML(EEH_HTML::br()),
                'manual_control_spaces'    => new EE_Text_Input(
                    array(
                        'html_label_text'       => esc_html__(
                            'Manually Controlled Spaces',
                            'event_espresso'
                        ),
                        'html_help_text'        => esc_html__(
                            'Controls the number of spaces that are NOT automatically promoted from the wait list to another registration status. This allows you to manually control a portion of the wait list spaces if you so desire. So for example, if the Wait List had a total of 10 spaces, and you set this to 5, then 5 of those spaces would need to be selected and managed by you, and the rest would be under automatic control. Setting this to zero puts the wait list fully under automatic control, and all registrants will be managed completely by the system.',
                            'event_espresso'
                        ),
                        'other_html_attributes' => ' size="4"',
                        'html_class'            => 'ee-numeric',
                        'default'               => $this->event_meta->getManualControlSpaces($event),
                        'required'              => false,
                    )
                ),
                'lb3'                      => new EE_Form_Section_HTML(EEH_HTML::br()),
            ),
        );
    }
}
