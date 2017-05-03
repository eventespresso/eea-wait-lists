<?php

namespace EventEspresso\WaitList\domain\services\forms;

use DomainException;
use EE_Attendee;
use EE_Error;
use EE_Event;
use EE_Registry;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidEntityException;
use EventEspresso\core\exceptions\InvalidFormSubmissionException;
use EventEspresso\core\libraries\form_sections\form_handlers\FormHandler;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class WaitListFormHandler
 * displays and processes the wait list form
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         $VID:$
 */
class WaitListFormHandler extends FormHandler
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
     * @throws EE_Error
     */
    public function __construct(EE_Event $event, EE_Registry $registry)
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
     * @throws EE_Error
     */
    public function generate()
    {
        $tickets = $this->event->tickets();
        foreach ($tickets as $TKT_ID => $ticket) {
            $tickets[$TKT_ID] = $ticket->name_and_info();
        }
        $this->setForm(
            new WaitListForm($this->event, $tickets)
        );
    }



    /**
     * handles processing the form submission
     * returns true or false depending on whether the form was processed successfully or not
     *
     * @param array $form_data
     * @return EE_Attendee
     * @throws RuntimeException
     * @throws InvalidEntityException
     * @throws LogicException
     * @throws InvalidFormSubmissionException
     * @throws EE_Error
     */
    public function process($form_data = array())
    {
        // process form
        $valid_data = (array)parent::process($form_data);
        if (empty($valid_data)) {
            throw new InvalidFormSubmissionException($this->formName());
        }
        $wait_list_form_inputs = (array)$valid_data["hidden_inputs-{$this->event->ID()}"];
        if (empty($wait_list_form_inputs)) {
            throw new InvalidFormSubmissionException($this->formName());
        }
        return $this->registry->BUS->execute(
            $this->registry->create(
                'EventEspresso\WaitList\domain\services\commands\CreateWaitListRegistrationsCommand',
                array(
                    isset($wait_list_form_inputs['registrant_name'])
                        ? $wait_list_form_inputs['registrant_name']
                        : '',
                    isset($wait_list_form_inputs['registrant_email'])
                        ? $wait_list_form_inputs['registrant_email']
                        : '',
                    isset($wait_list_form_inputs['ticket'])
                        ? $wait_list_form_inputs['ticket']
                        : 0,
                    isset($wait_list_form_inputs['quantity']) && $wait_list_form_inputs['quantity'] > 0
                        ? $wait_list_form_inputs['quantity']
                        : 1
                )
            )
        );
    }




}
// End of file WaitListFormHandler.php
// Location: EventEspresso\Constants/WaitListFormHandler.php