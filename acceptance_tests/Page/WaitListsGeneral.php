<?php
namespace Page;

/**
 * WaitListsGeneral
 * General add-on wide page elements/references
 *
 * @package Page
 * @author  Darren Ethier
 * @since   1.0.0
 */
class WaitListsGeneral
{
    /**
     * Slug for the add-on as implemented on the WordPress plugins page
     */
    const ADDDON_SLUG_FOR_WP_PLUGIN_PAGE = 'event-espresso-wait-lists';

    /**
     * Message type slug for added to waitlist message type.
     */
    const MESSAGE_TYPE_SLUG_REGISTRATION_ADDED_TO_WAIT_LIST = 'registration_added_to_waitlist';


    /**
     * Message type slug for waitlist promotion message type.
     */
    const MESSAGE_TYPE_SLUG_PROMOTION = 'waitlist_can_register';


    /**
     * Message type slug for waitlist demotion message type.
     */
    const MESSAGE_TYPE_SLUG_DEMOTION = 'registration_demoted_to_waitlist';

    /**
     * Selector for the spaces field in the event editor.
     */
    const SELECTOR_EVENT_EDITOR_FIELD_WAITLIST_SPACES = '#event_wait_list_settings-wait-list-spaces';


    /**
     * Selector for the autopromote select field in the event editor
     */
    const SELECTOR_EVENT_EDITOR_FIELD_WAITLIST_AUTOPROMOTE_OPTION
        = '#event_wait_list_settings-auto-promote-registrants';


    /**
     * Selector for the manually controlled spaces field in the event editor.
     */
    const SELECTOR_EVENT_EDITOR_FIELD_WAITLIST_SPACES_MANUALLY_CONTROLLED
        = '#event_wait_list_settings-manual-control-spaces';


    /**
     * Returns the selector for the finalize registration link found in the messages modal for
     * Wait List Promotion message type
     */
    const SELECTOR_LINK_FINALIZE_PAYMENT_WAITLIST_PROMOTION_MESSAGE = "//h3/a";


    /**
     * This will return the selector for the wait list Submit button on an event with the given id.
     * Assumes being called from the context of an event archive view or single event view in the frontend.
     *
     * @param int $event_id
     * @return string
     */
    public static function selectorForWaitlistSubmitButtonOnEventWithId($event_id)
    {
        return "//input[@id='event-wait-list-{$event_id}-join-wait-list-btn-submit']";
    }


    /**
     * Returns the selector for the wait list name field on the frontend
     * Assumes this is called from the context of an event archive or single event view on the frontend.
     * @param int $event_id
     * @return string
     */
    public static function selectorForWaitListNameFieldForEvent($event_id)
    {
        return "//input[@id='event-wait-list-{$event_id}-hidden-inputs-registrant-name']";
    }


    /**
     * Returns the selector for the wait list email field on the frontend.
     * Assumes this is called from the context of an event archive or single event view on the frontend
     * @param int $event_id
     * @return string
     */
    public static function selectorForEmailFieldForEvent($event_id)
    {
        return "//input[@id='event-wait-list-{$event_id}-hidden-inputs-registrant-email']";
    }


    /**
     * Returns the selector for the ticket option select field on the frontend wait list modal.
     * Assumes this is called from the context of an event archive or single event view on the frontend.
     * @param int $event_id
     * @return string
     */
    public static function selectorForTicketOptionSelectForEvent($event_id)
    {
        return "//select[@id='event-wait-list-{$event_id}-hidden-inputs-ticket']";
    }


    /**
     * Returns the selector for the ticket option quantity field on the frontend in the wait list modal.
     * Assumes this is called from the context of an event archive or single event view on the frontend.
     * @param int $event_id
     * @return string
     */
    public static function selectorForTicketOptionQuantityFieldForEvent($event_id)
    {
        return "//input[@id='event-wait-list-{$event_id}-hidden-inputs-quantity']";
    }


    /**
     * Returns the selector for the wait list form submit button in the wait list modal.
     * Assumes this is called from the context of an event archive or single event view on the frontend.
     * @param int $event_id
     * @return string
     */
    public static function selectorForWaitListFormSubmitButton($event_id)
    {
        return "//input[@id='event-wait-list-{$event_id}-hidden-inputs-submit-submit']";
    }
}