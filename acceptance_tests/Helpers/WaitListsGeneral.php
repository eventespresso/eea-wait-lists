<?php

namespace EventEspresso\Codeception\helpers;

use Page\WaitListsGeneral as WaitListsGeneralSelectors;


/**
 * Trait WaitListsGeneral
 * General actor/module helpers for common tasks in tests for this add-on
 *
 * @package EventEspresso\Codeception\helpers
 * @author  Darren Ethier
 * @since   1.0.0
 */
trait WaitListsGeneral
{

    /**
     * Opens and fills out the wait list form for a given event id.
     * Assumes the actor is in the context of an event archive or single event view on the frontend.
     *
     * @param int $event_id
     * @param string $name
     * @param string $email
     * @param string $ticket_option  The label as it appears in the options.
     * @param int $quantity
     */
    public function fillOutAndSubmitWaitListFormForEvent(
        $event_id,
        $name,
        $email,
        $ticket_option,
        $quantity
    ) {
        $this->actor()->click(WaitListsGeneralSelectors::selectorForWaitlistSubmitButtonOnEventWithId($event_id));
        $this->actor()->waitForText('Join Now');
        $this->actor()->fillField(
            WaitListsGeneralSelectors::selectorForWaitListNameFieldForEvent($event_id),
            $name
        );
        $this->actor()->fillField(
            WaitListsGeneralSelectors::selectorForEmailFieldForEvent($event_id),
            $email
        );
        $this->actor()->selectOption(
            WaitListsGeneralSelectors::selectorForTicketOptionSelectForEvent($event_id),
            $ticket_option
        );
        $this->actor()->fillField(
            WaitListsGeneralSelectors::selectorForTicketOptionQuantityFieldForEvent($event_id),
            $quantity
        );
        $this->actor()->click(WaitListsGeneralSelectors::selectorForWaitListFormSubmitButton($event_id));
        $this->actor()->waitForText('You have been successfully added to the Wait List for:');
    }
}