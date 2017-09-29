<?php

use Codeception\Lib\Console\Message;
use Page\EventsAdmin;
use Page\MessagesAdmin;
use Page\WaitListsGeneral;

/**
 * This test builds on the artifacts created from the previous tests.
 */
$I = new EventEspressoAddonAcceptanceTester(
    $scenario,
    WaitListsGeneral::ADDDON_SLUG_FOR_WP_PLUGIN_PAGE,
    false
);
$I->wantTo('Test Auto Demotion to WaitList and outgoing Messages.');

$I->amGoingTo(
    'Edit "Event Wait List Test" from previous test and remove 2 spots which should cause trigger auto-demotion of the pending payment registrations.'
);
$I->loginAsAdmin();
$I->amOnDefaultEventsListTablePage();
$I->amEditingTheEventWithTitle("Event Wait List Test");
$I->fillField(
    EventsAdmin::eventEditorTicketQuantityFieldSelector(),
    '5'
);
$I->publishEvent();
$I->waitForText('Event published');


//auto demotions don't go out until the checkout is visited by one of the regs. So let's get a payment link.
$I->amGoingTo('Get payment link from existing pending payment message and visit checkout.');
$I->amOnMessagesActivityListTablePage(
    '&ee_message_type_filter_by=' . WaitListsGeneral::MESSAGE_TYPE_SLUG_PROMOTION
);
$I->viewMessageInMessagesListTableFor(
    'Registration Promoted From Wait List Notification',
    MessagesAdmin::MESSAGE_STATUS_SENT,
    'Email',
    'Registrant'
);
$finalize_payment_link = $I->observeLinkAtInMessageModal(
    WaitListsGeneral::SELECTOR_LINK_FINALIZE_PAYMENT_WAITLIST_PROMOTION_MESSAGE
);
$I->dismissMessageModal();
$I->logOut();
$I->amOnUrl($finalize_payment_link);
$I->see('Registration Checkout');
$I->goToNextRegistrationStep();


