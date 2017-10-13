<?php

use Page\CoreAdmin;
use Page\MessagesAdmin;
use Page\WaitListsGeneral;

/**
 * This test covers the add-on activation for the Wait List Add-on.
 */
$I = new EventEspressoAddonAcceptanceTester($scenario, WaitListsGeneral::ADDDON_SLUG_FOR_WP_PLUGIN_PAGE);
$I->wantTo("Activate Wait-List add-on and verify it activates correctly.");

//
$I->loginAsAdmin();

$I->amGoingTo('Confirm expected message types for the add-on are active.');
$I->amOnDefaultMessageTemplateListTablePage();
$I->click(CoreAdmin::ADMIN_LIST_TABLE_NEXT_PAGE_CLASS);
$I->see('Registration Promoted From Wait List Notification');
$I->see('Registration Demoted To Wait List Notification');
$I->see('Registration Added To Wait List Notification');

//for the purpose of all tests we need send on same request to be set for messages
$I->amOnMessageSettingsPage();
$I->selectOption(MessagesAdmin::GLOBAL_MESSAGES_SETTINGS_ON_REQUEST_SELECTION_SELECTOR, '1');
$I->click(MessagesAdmin::GLOBAL_MESSAGES_SETTINGS_SUBMIT_SELECTOR);
