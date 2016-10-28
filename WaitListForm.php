<?php
namespace EventEspresso\WaitList;

use EE_Form_Section_Proper;
use EventEspresso\core\libraries\form_sections\form_handlers\FormHandler;
use EE_Text_Input;
use EE_Email_Input;
use LogicException;

defined('ABSPATH') || exit;



/**
 * Class WaitListForm
 * displays and processes the wait list form
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class WaitListForm extends FormHandler
{


    /**
     * @var \EE_Event $event
     */
    protected $event;



    /**
     * Form constructor.
     *
     * @param \EE_Event    $event
     * @param \EE_Registry $registry
     * @throws \DomainException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \InvalidArgumentException
     * @throws \EE_Error
     */
    public function __construct(\EE_Event $event, \EE_Registry $registry)
    {
        $this->event = $event;
        parent::__construct(
            esc_html__('Event Wait List', 'event_espresso'),
            esc_html__('Event Wait List', 'event_espresso'),
            'event_wait_list',
            site_url() . '?wait_list=join&event_id=' . $event->ID(),
            FormHandler::ADD_FORM_TAGS_ONLY,
            $registry
        );
    }



    /**
     * creates and returns the actual form
     *
     * @return void
     * @throws \EE_Error
     */
    public function generate()
    {
        $this->setForm(
            new EE_Form_Section_Proper(
                array(
                    'name'            => 'event_wait_list',
                    'html_id'         => 'event_wait_list',
                    'layout_strategy' => new \EE_Div_Per_Section_Layout(),
                    'subsections'     => array(
                        'join_wait_list_btn'    => new \EE_Submit_Input(
                            array(
                                'html_class' => 'ee-join-wait-list-btn float-right',
                                'other_html_attributes' => ' data-inputs="event_wait_list-hidden-inputs-'
                                                           . $this->event->ID() . '"',
                                'default'    => esc_html__('Sign Up For The Wait List', 'event_espresso'),
                            )
                        ),
                        'hidden_inputs-' . $this->event->ID() => new \EE_Form_Section_Proper(
                            array(
                                'layout_strategy' => new \EE_Div_Per_Section_Layout(),
                                'html_class' => 'event_wait_list-hidden-inputs',
                                'html_style' => 'display:none;',
                                'subsections'     => array(
                                    'wait_list_form_notice' => new \EE_Form_Section_HTML(
                                        \EEH_HTML::p(
                                            \EEH_HTML::br() .
                                            esc_html__('If you would like to be added to the waiting list for this event, then please enter your name and email address, and we will contact you when spaces become available.',
                                                'event_espresso'),
                                            '', 'small-text ee-wait-list-notice-pg'
                                        )
                                    ),
                                    'registrant_name'       => new EE_Text_Input(
                                        array(
                                            'html_label_text'       => esc_html__('Name', 'event_espresso'),
                                            'html_label_class'      => 'small-text grey-text',
                                            'other_html_attributes' => ' placeholder="'
                                                                       . esc_html__('please enter your name', 'event_espresso')
                                                                       . '"',
                                            'html_class'            => '',
                                            'default'               => '',
                                            'required'              => true
                                        )
                                    ),
                                    'registrant_email'      => new EE_Email_Input(
                                        array(
                                            'html_label_text'       => esc_html__('Email Address', 'event_espresso'),
                                            'html_label_class'      => 'small-text grey-text',
                                            'other_html_attributes' => ' placeholder="'
                                                                       . esc_html__('please enter a valid email address', 'event_espresso')
                                                                       . '"',
                                            'html_class'            => '',
                                            'default'               => '',
                                            'required'              => true
                                        )
                                    ),
                                    'lb1' => new \EE_Form_Section_HTML(\EEH_HTML::br()),
                                    'submit' => new \EE_Submit_Input(
                                        array(
                                            'html_class' => 'ee-submit-wait-list-btn float-right',
                                            'default'    => esc_html__('Join The Wait List', 'event_espresso'),
                                        )
                                    ),
                                    'clear_submit' => new \EE_Form_Section_HTML(
                                        \EEH_HTML::div('&nbsp;', '', 'clear')
                                    ),
                                )
                            )
                        ),
                        'clear_form' => new \EE_Form_Section_HTML(\EEH_HTML::div(\EEH_HTML::br(), '', 'clear')),
                    )
                )
            )
        );
    }



    /**
     * handles processing the form submission
     * returns true or false depending on whether the form was processed successfully or not
     *
     * @param array $form_data
     * @return bool
     * @throws \LogicException
     * @throws \EventEspresso\core\exceptions\InvalidFormSubmissionException
     * @throws \EE_Error
     */
    public function process($form_data = array())
    {
        // \EEH_Debug_Tools::printr($this, '$this', __FILE__, __LINE__);
        // process form
        $valid_data = (array)parent::process($form_data);
        if (empty($valid_data)) {
            return false;
        }
        $wait_list_form_inputs = (array)$valid_data["hidden_inputs-{$this->event->ID()}"];
        if (! empty($wait_list_form_inputs)) {
            // yay
            \EEH_Debug_Tools::printr($valid_data, '$valid_data', __FILE__, __LINE__);
            die();
            return true;
        } else {
            // error
        }
        // $meta = $this->event->get_extra_meta('ee_wait_list_spaces', true );
        // \EEH_Debug_Tools::printr( $meta, '$meta', __FILE__, __LINE__ );
        // die();
        return false;
    }



}
// End of file WaitListForm.php
// Location: EventEspresso\WaitList/WaitListForm.php