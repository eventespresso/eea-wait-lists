<?php

use Page\MessagesAdmin;
use Page\WaitListsGeneral;

/**
 * This test covers manually changing a registration from RWL to RPP and that it receives the Registration Promoted
 * From Wait List Notification
 */
$I = new EventEspressoAddonAcceptanceTester($scenario, WaitListsGeneral::ADDDON_SLUG_FOR_WP_PLUGIN_PAGE, false);
$I->wantTo('Test that manually setting the status of a registration to RPP from RWL sends the appropriate notifications.');

$I->amGoingTo(
    'Go to registration admin page and change the status of a registration from Group  (created in previous testcases)to RWL.'
);
$I->loginAsAdmin();
$I->amOnDefaultRegistrationsListTableAdminPage();
$I->selectBulkActionCheckboxesForRegistrationIds(array(15));
$I->submitBulkActionOnListTable('Move Registrations To Wait List and Notify');
$I->waitForText('Registration status has been set to wait list');
$I->see('Messages have been successfully queued for generation and sending.');

$I->amGoingTo(
    'Go to message activity list table and verify registration received the correct message type.'
);
$I->amOnMessagesActivityListTablePage(
    '&ee_message_type_filter_by=' . WaitListsGeneral::MESSAGE_TYPE_SLUG_DEMOTION
);
$I->see(
    'dude+groupc@example.org',
    MessagesAdmin::messagesActivityListTableCellSelectorFor(
        'to',
        'Registration Demoted To Wait List Notification',
        MessagesAdmin::MESSAGE_STATUS_SENT,
        '',
        'Registrant'
    )
);
$I->verifyMatchingCountofTextInMessageActivityListTableFor(
    1,
    'dude+groupc@example.org',
    'to',
    'Registration Demoted To Wait List Notification',
    MessagesAdmin::MESSAGE_STATUS_SENT,
    'Email',
    'Registrant'
);
