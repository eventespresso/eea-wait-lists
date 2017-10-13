<?php

use Page\EventsAdmin;
use Page\MessagesAdmin;
use Page\WaitListsGeneral;

/**
 * This test builds on the artifacts created as a part of b-TestAddedtoWaitListNotificationCept.php
 */
$I = new EventEspressoAddonAcceptanceTester(
    $scenario,
    WaitListsGeneral::ADDDON_SLUG_FOR_WP_PLUGIN_PAGE,
    false
);
$I->wantTo('Test Auto Promotion from WaitList and outgoing Messages.');

$I->amGoingTo('Edit "Event Wait List Test" from previous test and open up 5 more available spots.');
$I->loginAsAdmin();
$I->amOnDefaultEventsListTablePage();
$I->amEditingTheEventWithTitle('Event Wait List Test');
$I->fillField(
    EventsAdmin::eventEditorTicketQuantityFieldSelector(),
    '15'
);
$I->publishEvent();
//even though the event is sold out the publish button/activity still works (it just gets switched back to sold out).
$I->waitForText('Event published');

$I->amGoingTo('Verify that 5 Wait List Promotion messages were sent out.');
$I->amOnMessagesActivityListTablePage(
    '&ee_message_type_filter_by=' . WaitListsGeneral::MESSAGE_TYPE_SLUG_PROMOTION
);
$I->verifyMatchingCountofTextInMessageActivityListTableFor(
    5,
    'dude+groupb@example.org',
    'to',
    'Registration Promoted From Wait List Notification',
    MessagesAdmin::MESSAGE_STATUS_SENT,
    'Email',
    'Registrant'
);
