<?php
/**
 * Bootstrap for eea-wait-lists tests
 */
use EETests\bootstrap\AddonLoader;

$ee_core_dir = 'event-espresso-core';
$core_tests_dir = dirname(dirname(__DIR__)) . "/{$ee_core_dir}/tests/";
//if still don't have $core_tests_dir, then let's check tmp folder.
if (! is_dir($core_tests_dir)) {
    $core_tests_dir = "/tmp/{$ee_core_dir}/tests/";
}
require $core_tests_dir . 'includes/CoreLoader.php';
require $core_tests_dir . 'includes/AddonLoader.php';

define('EE_WAITLISTS_PLUGIN_DIR', dirname(__DIR__) . '/');
define('EE_WAITLISTS_TEST_DIR', EE_WAITLISTS_PLUGIN_DIR . 'tests/');

$addon_loader = new AddonLoader(
    EE_WAITLISTS_TEST_DIR,
    EE_WAITLISTS_PLUGIN_DIR,
    'eea-wait-lists.php'
);
$addon_loader->init();
