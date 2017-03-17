<?php
namespace EventEspresso\WaitList;

use DomainException;
use EE_Div_Per_Section_Layout;
use EE_Error;
use EE_Event;
use EE_Form_Section_HTML;
use EE_Form_Section_Proper;
use EE_Registry;
use EE_Text_Input;
use EE_Yes_No_Input;
use EED_Wait_Lists;
use EEH_HTML;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidFormSubmissionException;
use EventEspresso\core\libraries\form_sections\form_handlers\FormHandler;
use InvalidArgumentException;
use LogicException;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class EventEditorWaitListMetaBoxForm
 * an admin form for controlling an event's wait list settings.
 * appears in an event editor sidebar metabox
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class EventEditorWaitListMetaBoxForm extends FormHandler
{


    /**
     * @var EE_Event $event
     */
    protected $event;



    /**
     * Form constructor.
     *
     * @param EE_Event    $event
     * @param EE_Registry $registry
     * @throws DomainException
     * @throws InvalidDataTypeException
     * @throws InvalidArgumentException
     */
    public function __construct(EE_Event $event, EE_Registry $registry)
    {
        $this->event = $event;
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
     * @return void
     * @throws EE_Error
     */
    public function generate()
    {
        $this->setForm(
            new EE_Form_Section_Proper(
                array(
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
                                'default'               => absint(
                                    $this->event->get_extra_meta(WaitList::SPACES_META_KEY, true)
                                ),
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
                                'default'         => filter_var(
                                    $this->event->get_extra_meta(WaitList::AUTO_PROMOTE_META_KEY, true),
                                    FILTER_VALIDATE_BOOLEAN
                                ),
                                'required'        => false,
                            )
                        ),
                        'lb2'                      => new EE_Form_Section_HTML(EEH_HTML::br()),
                        'manual_control_spaces'    => new EE_Text_Input(
                            array(
                                'html_label_text'       => esc_html__('Manually Controlled Spaces',
                                    'event_espresso'),
                                'html_help_text'        => esc_html__(
                                    'Controls the number of spaces that are NOT automatically promoted from the wait list to another registration status. This allows you to manually control a portion of the wait list spaces if you so desire. So for example, if the Wait List had a total of 10 spaces, and you set this to 5, then 5 of those spaces would need to be selected and managed by you, and the rest would be under automatic control. Setting this to zero puts the wait list fully under automatic control, and all registrants will be managed completely by the system.',
                                    'event_espresso'
                                ),
                                'other_html_attributes' => ' size="4"',
                                'html_class'            => 'ee-numeric',
                                'default'               => absint(
                                    $this->event->get_extra_meta(WaitList::MANUAL_CONTROL_SPACES_META_KEY, true)
                                ),
                                'required'              => false,
                            )
                        ),
                        'lb3'                      => new EE_Form_Section_HTML(EEH_HTML::br()),
                    ),
                )
            )
        );
    }



    /**
     * takes the generated form and displays it along with ony other non-form HTML that may be required
     * returns a string of HTML that can be directly echoed in a template
     *
     * @return string
     * @throws LogicException
     * @throws EE_Error
     */
    public function display()
    {
        // inject some additional subsections with HTML that's for display only
        $this->form(true)->add_subsections(
            array(
                'view_wait_list_link' => new EE_Form_Section_HTML(
                    EEH_HTML::br() . $this->waitListRegCountDisplay() . EEH_HTML::br(2)
                ),
            ),
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
     */
    public function waitListRegCountDisplay()
    {
        $html = EEH_HTML::span(
            '',
            '',
            'dashicons dashicons-groups ee-icon-color-ee-purple ee-icon-size-20'
        );
        $html .= ' ' . EED_Wait_Lists::wait_list_registrations_list_table_link($this->event);
        $html .= ' : ' . EED_Wait_Lists::waitListRegCount($this->event);
        return $html;
    }



    /**
     * handles processing the form submission
     * returns true or false depending on whether the form was processed successfully or not
     *
     * @param array $form_data
     * @return bool
     * @throws LogicException
     * @throws InvalidFormSubmissionException
     * @throws EE_Error
     */
    public function process($form_data = array())
    {
        // process form
        $valid_data = (array)parent::process($form_data);
        if (empty($valid_data)) {
            return false;
        }
        $wait_list_spaces = absint($valid_data['wait_list_spaces']);
        $this->event->update_extra_meta(WaitList::SPACES_META_KEY, $wait_list_spaces);
        $this->event->update_extra_meta(
            WaitList::AUTO_PROMOTE_META_KEY,
            filter_var(
                $valid_data['auto_promote_registrants'],
                FILTER_VALIDATE_BOOLEAN
            )
        );
        $manual_control_spaces = absint($valid_data['manual_control_spaces']);
        // manual control spaces can't be more than the total number of spaces in the wait list
        $manual_control_spaces = $wait_list_spaces > 0
            ? min($wait_list_spaces, $manual_control_spaces)
            : $manual_control_spaces;
        $this->event->update_extra_meta(WaitList::MANUAL_CONTROL_SPACES_META_KEY, $manual_control_spaces);
        $this->event->update_extra_meta(
            WaitList::REG_COUNT_META_KEY,
            WaitList::waitListRegCount($this->event)
        );
        // mark event as having a waitlist if number of spaces available is positive
        $this->event->set('EVT_allow_overflow', $wait_list_spaces > 0);
        return false;
    }



}
// End of file EventEditorWaitListMetaBoxForm.php
// Location: EventEspresso\WaitList/EventEditorWaitListMetaBoxForm.php