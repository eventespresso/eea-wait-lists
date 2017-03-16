<?php if (! defined('EVENT_ESPRESSO_VERSION')) {
    exit();
}
// define the plugin directory path and URL
define('EE_WAIT_LISTS_BASENAME', plugin_basename(EE_WAIT_LISTS_PLUGIN_FILE));
define('EE_WAIT_LISTS_PATH', plugin_dir_path(__FILE__));
define('EE_WAIT_LISTS_URL', plugin_dir_url(__FILE__));
define('EE_WAIT_LISTS_ADMIN', EE_WAIT_LISTS_PATH . 'admin' . DS . 'wait_lists' . DS);



/**
 * Class  EE_Wait_Lists
 *
 * @package               Event Espresso
 * @subpackage            eea-wait-lists
 * @author                Brent Christensen
 */
Class  EE_Wait_Lists extends EE_Addon
{

    /**
     * this is not the place to perform any logic or add any other filter or action callbacks
     * this is just to bootstrap your addon; and keep in mind the addon might be DE-registered
     * in which case your callbacks should probably not be executed.
     * EED_Wait_Lists is the place for most filter and action callbacks (relating
     * the the primary business logic of your addon) to be placed
     *
     * @throws \EE_Error
     */
    public static function register_addon()
    {
        // register addon via Plugin API
        EE_Register_Addon::register(
            'Wait_Lists',
            array(
                'version'          => EE_WAIT_LISTS_VERSION,
                'plugin_slug'      => 'eea_wait_lists',
                'min_core_version' => EE_WAIT_LISTS_CORE_VERSION_REQUIRED,
                'main_file_path'   => EE_WAIT_LISTS_PLUGIN_FILE,
                'namespace'        => array(
                    'FQNS' => 'EventEspresso\WaitList',
                    'DIR'  => __DIR__,
                ),
                'module_paths'     => array(EE_WAIT_LISTS_PATH . 'EED_Wait_Lists.module.php'),
                // if plugin update engine is being used for auto-updates. not needed if PUE is not being used.
                'pue_options'      => array(
                    'pue_plugin_slug' => 'eea-wait-lists',
                    'plugin_basename' => EE_WAIT_LISTS_BASENAME,
                    'checkPeriod'     => '24',
                    'use_wp_update'   => false,
                ),
            )
        );
    }



}
// End of file EE_Wait_Lists.class.php
// Location: wp-content/plugins/eea-wait-lists/EE_Wait_Lists.class.php