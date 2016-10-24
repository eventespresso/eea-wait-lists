<?php if ( ! defined( 'EVENT_ESPRESSO_VERSION' )) { exit(); }

// define the plugin directory path and URL
define( 'EE_WAIT_LISTS_BASENAME', plugin_basename( EE_WAIT_LISTS_PLUGIN_FILE ) );
define( 'EE_WAIT_LISTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'EE_WAIT_LISTS_URL', plugin_dir_url( __FILE__ ) );
define( 'EE_WAIT_LISTS_ADMIN', EE_WAIT_LISTS_PATH . 'admin' . DS . 'wait_lists' . DS );



/**
 *
 * Class  EE_Wait_Lists
 *
 * @package			Event Espresso
 * @subpackage		eea-wait-lists
 * @author			    Brent Christensen
 *
 */
Class  EE_Wait_Lists extends EE_Addon {

	/**
	 * this is not the place to perform any logic or add any other filter or action callbacks
	 * this is just to bootstrap your addon; and keep in mind the addon might be DE-registered
	 * in which case your callbacks should probably not be executed.
	 * EED_Wait_Lists is the place for most filter and action callbacks (relating
	 * the the primary business logic of your addon) to be placed
	 *
	 * @throws \EE_Error
	 */
	public static function register_addon() {
		// register addon via Plugin API
		EE_Register_Addon::register(
			'Wait_Lists',
			array(
				'version'               => EE_WAIT_LISTS_VERSION,
				'plugin_slug'           => 'espresso_wait_lists',
				'min_core_version'      => EE_WAIT_LISTS_CORE_VERSION_REQUIRED,
				'main_file_path'        => EE_WAIT_LISTS_PLUGIN_FILE,
				// 'admin_path'            => EE_WAIT_LISTS_ADMIN,
				// 'admin_callback'        => '',
				// 'config_class'          => 'EE_Wait_Lists_Config',
				// 'config_name'           => 'EE_Wait_Lists',
				// 'autoloader_paths'      => array(
					// 'EE_Wait_Lists_Config'       => EE_WAIT_LISTS_PATH . 'EE_Wait_Lists_Config.php',
					// 'Wait_Lists_Admin_Page'      => EE_WAIT_LISTS_ADMIN . 'Wait_Lists_Admin_Page.core.php',
					// 'Wait_Lists_Admin_Page_Init' => EE_WAIT_LISTS_ADMIN . 'Wait_Lists_Admin_Page_Init.core.php',
				// ),
				// 'dms_paths'             => array( EE_WAIT_LISTS_PATH . 'core' . DS . 'data_migration_scripts' . DS ),
				'module_paths'          => array( EE_WAIT_LISTS_PATH . 'EED_Wait_Lists.module.php' ),
				// 'shortcode_paths'       => array( EE_WAIT_LISTS_PATH . 'EES_Wait_Lists.shortcode.php' ),
				// 'widget_paths'          => array( EE_WAIT_LISTS_PATH . 'EEW_Wait_Lists.widget.php' ),
				// if plugin update engine is being used for auto-updates. not needed if PUE is not being used.
				'pue_options'           => array(
					'pue_plugin_slug' => 'eea-wait-lists',
					'plugin_basename' => EE_WAIT_LISTS_BASENAME,
					'checkPeriod'     => '24',
					'use_wp_update'   => false,
				),
				'capabilities'          => array(
					'administrator' => array(
						'edit_wait_list',
						'edit_wait_lists',
						'edit_others_wait_lists',
						'edit_private_wait_lists',
					),
				),
				'capability_maps'       => array(
					'EE_Meta_Capability_Map_Edit' => array(
						'edit_wait_list',
						array( 'Wait_Lists', 'edit_wait_lists', 'edit_others_wait_lists', 'edit_private_wait_lists' ),
					),
				),
				// 'class_paths'           => EE_WAIT_LISTS_PATH . 'core' . DS . 'db_classes',
				// 'model_paths'           => EE_WAIT_LISTS_PATH . 'core' . DS . 'db_models',
				// 'class_extension_paths' => EE_WAIT_LISTS_PATH . 'core' . DS . 'db_class_extensions',
				// 'model_extension_paths' => EE_WAIT_LISTS_PATH . 'core' . DS . 'db_model_extensions',
				//note for the mock we're not actually adding any custom cpt stuff yet.
				// 'custom_post_types'     => array(),
				// 'custom_taxonomies'     => array(),
				// 'default_terms'         => array(),
			)
		);
	}






}
// End of file EE_Wait_Lists.class.php
// Location: wp-content/plugins/eea-wait-lists/EE_Wait_Lists.class.php
