<?php
// use EventEspresso\WaitList\WaitListEventsCollection;
use EventEspresso\WaitList\EventEditorWaitListMetaBoxForm;

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
		add_filter(
			'FHEE__Events_Admin_Page___insert_update_cpt_item__event_update_callbacks',
			array( 'EED_Wait_Lists', 'event_update_callbacks' )
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
		try {
			$wait_list_settings_form = new EventEditorWaitListMetaBoxForm(
				EED_Wait_Lists::$admin_page->get_event_object(),
				EE_Registry::instance()
			);
			echo $wait_list_settings_form->display();
		} catch ( Exception $e ) {
			EE_Error::add_error( $e->getMessage(), __FILE__, __FUNCTION__, __LINE__ );
		}
	}



	/**
	 * @param array $event_update_callbacks
	 * @return array
	 */
	public static function event_update_callbacks( array $event_update_callbacks) {
		$event_update_callbacks = array_merge(
			$event_update_callbacks,
			array( array( 'EED_Wait_Lists', 'update_event_wait_list_settings' ) )
		);
		return $event_update_callbacks;
	}



	/**
	 * @param \EE_Event $event
	 * @param array     $form_data
	 */
	public static function update_event_wait_list_settings( \EE_Event $event, array $form_data) {
		try {
			$wait_list_settings_form = new EventEspresso\WaitList\EventEditorWaitListMetaBoxForm(
				$event,
				EE_Registry::instance()
			);
			$wait_list_settings_form->process($form_data);
		} catch ( Exception $e ) {
			EE_Error::add_error( $e->getMessage(), __FILE__, __FUNCTION__, __LINE__ );
		}
	}

}
// End of file EED_Wait_Lists.module.php
// Location: wp-content/plugins/eea-wait-lists/EED_Wait_Lists.module.php