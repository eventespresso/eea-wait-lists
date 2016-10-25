<?php
// use EventEspresso\WaitList\WaitListEventsCollection;

defined( 'EVENT_ESPRESSO_VERSION' ) || exit;



/**
 * Class EED_Wait_Lists
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         1.0.0
 */
class EED_Wait_Lists extends EED_Module {


	/**
	 * @var \Events_Admin_Page $admin_page
	 */
	protected static $admin_page;



	/**
	 * set_hooks - for hooking into EE Core, other modules, etc
	 *
	 * @access    public
	 * @return    void
	 */
	public static function set_hooks() {
		// $wait_list_events = new WaitListEventsCollection();
		// \EEH_Debug_Tools::printr( $wait_list_events, '$wait_list_events', __FILE__, __LINE__ );
	}



	/**
	 * set_hooks_admin - for hooking into EE Admin Core, other modules, etc
	 *
	 * @access    public
	 * @return    void
	 */
	public static function set_hooks_admin() {
		// add_filter(
		// 	'FHEE__Extend_Events_Admin_Page__page_setup__page_routes',
		// 	array( 'EED_Wait_Lists', 'setup_page_routes' ),
		// 	10,
		// 	2
		// );
		add_filter(
			'FHEE__Extend_Events_Admin_Page__page_setup__page_config',
			array( 'EED_Wait_Lists', 'setup_page_config' ),
			1, 2
		);
	}

	/**
	 *    run - initial module setup
	 *    this method is primarily used for activating resources in the EE_Front_Controller thru the use of filters
	 *
	 * @access    public
	 * @var            WP $WP
	 * @return    void
	 */
	public function run( $WP ) {
		// TODO: Implement run() method.
	}



	/**
	 * callback for FHEE__Extend_Events_Admin_Page__page_setup__page_config
	 *
	 * @param array              $page_config current page config.
	 * @param \Events_Admin_Page $admin_page
	 * @return array
	 * @since  1.0.0
	 */
	public static function setup_page_config( array $page_config, \Events_Admin_Page $admin_page ) {
		EED_Wait_Lists::$admin_page = $admin_page;
		$page_config['edit']['metaboxes'] = array_merge(
			$page_config['edit']['metaboxes'],
			array( array( 'EED_Wait_Lists', 'add_event_wait_list_meta_box' ) )
		);
		return $page_config;
	}



	/**
	 * @return void
	 */
	public static function add_event_wait_list_meta_box() {
		add_meta_box(
			'event-wait-list-mbox',
			esc_html__( 'Event Wait List', 'event_espresso' ),
			array( 'EED_Wait_Lists', 'event_wait_list_meta_box' ),
			EVENTS_PG_SLUG,
			'side',
			'high'
		);
	}



	/**
	 * callback that adds the main "event_wait_list_meta_box" meta_box
	 * calls non static method below
	 *
	 * @throws \EE_Error
	 */
	public static function event_wait_list_meta_box() {

	}

}
// End of file EED_Wait_Lists.module.php
// Location: wp-content/plugins/eea-wait-lists/EED_Wait_Lists.module.php