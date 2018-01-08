<?php

use Codeception\Lib\Console\Message;
use Page\EventsAdmin;
use Page\MessagesAdmin;
use Page\TicketSelector;
use Page\WaitListsGeneral;

/**
 * This test builds on the artifacts created from the previous tests.
 * The state we have from previous builds is:
 * - A watilist event (Event Wait List Test)
 * - TKT Qty 10
 * - 5 registrations that were done via invoice and manually approved
 * - 5 registrations that initially got added to waitlist, then were auto-promoted when the number of spots was bumped
 *   up.
 * - So we have 10 registrations in total.  5 approved (groupa) and 5 pending payment (groupb).
 *
 * In this test to simulate auto-demotion we will
 * - Register another group of 5 registrations (groupc) with invoice payment method.
 * - Manually approve the previous 5 that were auto-promoted (groupb), and then we'll try going to pay with the pending
 *   payment notification from the third group (groupc)
 */
$I = new EventEspressoAddonAcceptanceTester(
    $scenario,
    WaitListsGeneral::ADDDON_SLUG_FOR_WP_PLUGIN_PAGE,
    false
);
$I->wantTo('Test Auto Demotion to WaitList and outgoing Messages.');

$event_title = 'Event Wait List Test';

$I->amGoingTo(
    sprintf(
        'Register another group of 5 registrations for the "%s" event.',
        $event_title
    )
);
$I->loginAsAdmin();
$I->amOnDefaultEventsListTablePage();
$event_id = $I->observeEventIdInListTableForEvent($event_title);
$I->amOnEventPageAfterClickingViewLinkInListTableForEvent($event_title);
$I->see('Ticket A');
$I->selectOption(TicketSelector::ticketOptionByEventIdSelector($event_id), '5');
$I->click(TicketSelector::ticketSelectionSubmitSelectorByEventId($event_id));
$I->waitForText('Personal Information');
$I->fillOutFirstNameFieldForAttendee('Group');
$I->fillOutLastNameFieldForAttendee('C');
$I->fillOutEmailFieldForAttendee('dude+groupc@example.org');
$I->goToNextRegistrationStep();
//payment options step
$I->selectPaymentOptionFor();
$I->submitPaymentOptionsRegistrationStepForm();
$I->waitForText('Congratulations', 15);

$I->amGoingTo('Approve Group B registrations.');
$I->loginAsAdmin();
$I->amOnDefaultRegistrationsListTableAdminPage();
$I->selectBulkActionCheckboxesForRegistrationIds(array(6,7,8,9,10));
$I->submitBulkActionOnListTable('Approve Registrations');
$I->waitForText('Registrations have been set to approved.');

$I->amGoingTo(
    sprintf(
        'Register another group of 5 registrations (Group D) for the "%s" event.',
        $event_title
    )
);
$I->amOnDefaultEventsListTablePage();
$I->amOnEventPageAfterClickingViewLinkInListTableForEvent($event_title);
$I->see('Ticket A');
$I->selectOption(TicketSelector::ticketOptionByEventIdSelector($event_id), '5');
$I->click(TicketSelector::ticketSelectionSubmitSelectorByEventId($event_id));
$I->waitForText('Personal Information');
$I->fillOutFirstNameFieldForAttendee('Group');
$I->fillOutLastNameFieldForAttendee('D');
$I->fillOutEmailFieldForAttendee('dude+groupd@example.org');
$I->goToNextRegistrationStep();
//payment options step
$I->selectPaymentOptionFor();
$I->submitPaymentOptionsRegistrationStepForm();
$I->waitForText('Congratulations', 15);

$I->amGoingTo('Approve Group C Registrations');
$I->loginAsAdmin();
$I->amOnDefaultRegistrationsListTableAdminPage();
$I->searchForRegistrationOnRegistrationListTableWithText('Group C');
$I->selectBulkActionCheckboxesForRegistrationIds(array(11,12,13,14,15));
$I->submitBulkActionOnListTable('Approve Registrations');
$I->waitForText('Registrations have been set to approved.');

//auto demotions don't go out until the checkout is visited by one of the regs. So let's get a payment link.
$I->amGoingTo('Get payment link for Group D\'s registrations.');
$I->amOnMessagesActivityListTablePage(
    '&ee_message_type_filter_by=' . MessagesAdmin::MESSAGE_TYPE_PENDING_PAYMENT
);
$I->viewMessageInMessagesListTableFor(
    'Registration Pending Payment',
    MessagesAdmin::MESSAGE_STATUS_SENT,
    'Email',
    'Primary Registrant'
);
$finalize_payment_link = $I->observeLinkAtSelectorInMessageModal(
    MessagesAdmin::SELECTOR_LINK_FINALIZE_PAYMENT_PENDING_PAYMENT_MESSAGE
);
$I->dismissMessageModal();
$I->logOut();
$I->amOnUrl($finalize_payment_link);
$I->see('Registration Checkout');
$I->see('We\'re Sorry');


$I->amGoingTo('Verify that the registrations for Group D received the auto-demotion message.');
$I->loginAsAdmin();
$I->amOnMessagesActivityListTablePage(
    '&ee_message_type_filter_by=' . WaitListsGeneral::MESSAGE_TYPE_SLUG_DEMOTION
);
$I->see(
    'dude+groupd@example.org',
    MessagesAdmin::messagesActivityListTableCellSelectorFor(
        'to',
        'Registration Demoted To Wait List Notification',
        MessagesAdmin::MESSAGE_STATUS_SENT,
        '',
        'Registrant'
    )
);
$I->verifyMatchingCountofTextInMessageActivityListTableFor(
    5,
    'dude+groupd@example.org',
    'to',
    'Registration Demoted To Wait List Notification',
    MessagesAdmin::MESSAGE_STATUS_SENT,
    'Email',
    'Registrant'
);
