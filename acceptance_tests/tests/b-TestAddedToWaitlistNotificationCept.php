<?php

use Page\EventsAdmin;
use Page\MessagesAdmin;
use Page\RegistrationsAdmin;
use Page\TicketSelector;
use Page\WaitListsGeneral;

/**
 * This test covers testing the promotion waitlist notification.
 */
$I = new EventEspressoAddonAcceptanceTester($scenario, WaitListsGeneral::ADDDON_SLUG_FOR_WP_PLUGIN_PAGE, false);
$I->wantTo('Test functionality of the "Registration Promoted To Wait-list Notification" message type.');

$I->amGoingTo('Setup an event with a waitlist for testing with.');
$I->loginAsAdmin();
$I->amOnDefaultEventsListTablePage();
$I->click(EventsAdmin::ADD_NEW_EVENT_BUTTON_SELECTOR);
$I->fillField(EventsAdmin::EVENT_EDITOR_TITLE_FIELD_SELECTOR, 'Event Wait List Test');
$I->fillField(
    EventsAdmin::eventEditorTicketNameFieldSelector(),
    'Ticket A'
);
$I->fillField(
    EventsAdmin::eventEditorTicketPriceFieldSelector(),
    '100'
);
$I->fillField(
    EventsAdmin::eventEditorTicketQuantityFieldSelector(),
    '5'
);
$I->fillField(
    WaitListsGeneral::SELECTOR_EVENT_EDITOR_FIELD_WAITLIST_SPACES,
    '10'
);
$I->selectOption(
    WaitListsGeneral::SELECTOR_EVENT_EDITOR_FIELD_WAITLIST_AUTOPROMOTE_OPTION,
    'Yes'
);
$I->seeInField(
    WaitListsGeneral::SELECTOR_EVENT_EDITOR_FIELD_WAITLIST_SPACES_MANUALLY_CONTROLLED,
    0
);
$I->click(EventsAdmin::EVENT_EDITOR_PUBLISH_BUTTON_SELECTOR);
$I->waitForText('Event published');

$event_link = $I->observeLinkUrlAt(EventsAdmin::EVENT_EDITOR_VIEW_LINK_AFTER_PUBLISH_SELECTOR);
$event_id = $I->observeValueFromInputAt(EventsAdmin::EVENT_EDITOR_EVT_ID_SELECTOR);

//verify settings stuck
$I->seeInField(WaitListsGeneral::SELECTOR_EVENT_EDITOR_FIELD_WAITLIST_SPACES, 10);
$I->seeOptionIsSelected(WaitListsGeneral::SELECTOR_EVENT_EDITOR_FIELD_WAITLIST_AUTOPROMOTE_OPTION, 'Yes');

$I->amGoingTo('Register for 5 spaces on the event I just created.');
$I->logOut();
$I->amOnUrl($event_link);
$I->see('Event Wait List Test');
$I->see('Ticket A');
$I->selectOption(TicketSelector::ticketOptionByEventIdSelector($event_id), '5');
$I->click(TicketSelector::ticketSelectionSubmitSelectorByEventId($event_id));
$I->waitForText('Personal Information');
$I->fillOutFirstNameFieldForAttendee('Tester');
$I->fillOutLastNameFieldForAttendee('Guy');
$I->fillOutEmailFieldForAttendee('dude+registrationgroup1@example.org');
$I->goToNextRegistrationStep();
//payment options step
$I->selectPaymentOptionFor(); //defaults to invoice
$I->submitPaymentOptionsRegistrationStepForm();
$I->waitForText('Congratulations', 15);

//spots aren't reserved until registrations are approved, so let's just go ahead and do that.
$I->amGoingTo('Approve recent registrations.');
$I->loginAsAdmin();
$I->amOnDefaultRegistrationsListTableAdminPage();
$I->selectBulkActionCheckboxesForRegistrationIds(array(1,2,3,4,5));
$I->submitBulkActionOnListTable('Approve Registrations');
$I->waitForText('Registrations have been set to approved.');

$I->amGoingTo('Do another registration that should add to waitlist');
$I->logOut();
$I->amOnUrl($event_link);
//should see sold out.
$I->see('Sold Out');
$I->fillOutAndSubmitWaitListFormForEvent(
    $event_id,
    'Tester WaitlistGuy',
    'dude+testerwaitlistguy@example.org',
    'Ticket A',
    5
);

$I->amGoingTo('Verify emails got sent for added to wait list message type.');
$I->loginAsAdmin();
$I->amOnMessagesActivityListTablePage(
    '&ee_message_type_filter_by=' . WaitListsGeneral::MESSAGE_TYPE_SLUG_REGISTRATION_ADDED_TO_WAIT_LIST
);
$I->see(
    'dude+testerwaitlistguy@example.org',
    MessagesAdmin::messagesActivityListTableCellSelectorFor(
        'to',
        'Registration Added To Wait List Notification',
        MessagesAdmin::MESSAGE_STATUS_SENT,
        '',
        'Registrant'
    )
);
$I->verifyMatchingCountofTextInMessageActivityListTableFor(
    1,
    'dude+testerwaitlistguy@example.org',
    'to',
    'Registration Added To Wait List Notification',
    MessagesAdmin::MESSAGE_STATUS_SENT,
    'Email',
    'Registrant'
);