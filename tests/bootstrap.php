<?php
/**
 * Bootstrap for eea-wait-lists tests
 */

use EETests\bootstrap\AddonLoader;

$core_tests_dir = dirname(dirname(dirname(__FILE__))) . '/event-espresso-core/tests/';
require $core_tests_dir . 'includes/CoreLoader.php';
require $core_tests_dir . 'includes/AddonLoader.php';

define('EE_WAITLISTS_PLUGIN_DIR', dirname(dirname(__FILE__)) . '/');
define('EE_WAITLISTS_TEST_DIR', EE_WAITLISTS_PLUGIN_DIR . 'tests');


$addon_loader = new AddonLoader(
    EE_WAITLISTS_TEST_DIR,
    EE_WAITLISTS_PLUGIN_DIR,
    'eea-wait-lists.php'
);
$addon_loader->init();
