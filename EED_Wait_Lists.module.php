<?php

use EventEspresso\WaitList\EventEditorWaitListMetaBoxForm;
use EventEspresso\WaitList\WaitListEventsCollection;
use EventEspresso\WaitList\WaitListMonitor;


defined( 'EVENT_ESPRESSO_VERSION' ) || exit;



/**
 * Class EED_Wait_Lists
 * module class for controlling event registration wait lists
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
	 * @var WaitListMonitor $wait_list_monitor
	 */
	protected static $wait_list_monitor;



	/**
	 * set_hooks - for hooking into EE Core, other modules, etc
	 *
	 * @return void
	 */
	public static function set_hooks() {
        EE_Config::register_route('join', 'EED_Wait_Lists', 'process_wait_list_form_for_event', 'wait_list');
        add_filter(
			'FHEE__EventEspresso_modules_ticket_selector_DisplayTicketSelector__displaySubmitButton__html',
			array( 'EED_Wait_Lists', 'add_wait_list_form_for_event' ),
			10, 2
		);
        add_action('wp_enqueue_scripts', array('EED_Wait_Lists', 'enqueue_styles_and_scripts'));
        \EED_Wait_Lists::shared_hooks();
    }



	/**
	 * set_hooks_admin - for hooking into EE Admin Core, other modules, etc
	 *
	 * @return void
	 */
	public static function set_hooks_admin() {
		add_filter(
			'FHEE__Extend_Events_Admin_Page__page_setup__page_config',
			array( 'EED_Wait_Lists', 'setup_page_config' ),
			1, 2
		);
		add_filter(
			'FHEE__Events_Admin_Page___insert_update_cpt_item__event_update_callbacks',
			array( 'EED_Wait_Lists', 'event_update_callbacks' )
		);
        add_action(
            'wp_ajax_process_wait_list_form_for_event',
            array('EED_Wait_Lists', 'process_wait_list_form_for_event')
        );
        add_action(
            'wp_ajax_nopriv_process_wait_list_form_for_event',
            array('EED_Wait_Lists', 'process_wait_list_form_for_event')
        );
        \EED_Wait_Lists::shared_hooks();
	}



    /**
     * hooks set by both set_hooks() and set_hooks_admin()
     *
     * @return void
     */
    protected static function shared_hooks()
    {
        add_action(
            'AHEE__EE_Registration__set_status__after_update',
            array('EED_Wait_Lists', 'registration_status_update'),
            10, 3
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
	 * @return \EventEspresso\WaitList\WaitListMonitor
	 * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
	 * @throws \EventEspresso\core\exceptions\InvalidEntityException
	 * @throws \EE_Error
	 */
	public static function getWaitListMonitor() {
        // if not already generated, create a wait list monitor object
		if ( ! self::$wait_list_monitor instanceof WaitListMonitor) {
			self::$wait_list_monitor = new WaitListMonitor( new WaitListEventsCollection() );
		}
		return self::$wait_list_monitor;
	}



	/**************************** FRONTEND FUNCTIONALITY ***************************/



    /**
     * enqueue_styles_and_scripts
     *
     * @return void
     */
    public static function enqueue_styles_and_scripts()
    {
        // load css
        wp_register_style(
            'wait_list',
            EE_WAIT_LISTS_URL . 'assets/wait_list.css',
            array(),
            EE_WAIT_LISTS_VERSION
        );
        wp_enqueue_style('wait_list');
        // load JS
        wp_register_script(
            'wait_list',
            EE_WAIT_LISTS_URL . 'assets/wait_list.js',
            array('espresso_core'),
            EE_WAIT_LISTS_VERSION,
            true
        );
        wp_enqueue_script('wait_list');
    }



    /**
     * @param string    $html
     * @param \EE_Event $event
     * @return string
     * @throws \LogicException
     * @throws \InvalidArgumentException
     * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
     * @throws \DomainException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     * @throws \EventEspresso\core\exceptions\InvalidEntityException
     * @throws \EE_Error
     */
	public static function add_wait_list_form_for_event( $html = '', \EE_Event $event ) {
        return $html . \EED_Wait_Lists::getWaitListMonitor()->getWaitListFormForEvent( $event );
	}



	/**
	 * process_wait_list_form_for_event
	 *
	 * @throws \DomainException
	 * @throws \EE_Error
	 * @throws \EventEspresso\core\exceptions\InvalidDataTypeException
	 * @throws \EventEspresso\core\exceptions\InvalidEntityException
	 * @throws \EventEspresso\core\exceptions\InvalidFormSubmissionException
	 * @throws \InvalidArgumentException
	 * @throws \LogicException
	 * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
	 */
	public function process_wait_list_form_for_event() {
        $event_id = isset($_REQUEST['event_id']) ? absint($_REQUEST['event_id']) : 0;
        \EED_Wait_Lists::getWaitListMonitor()->processWaitListFormForEvent($event_id);
        if (defined('DOING_AJAX') && DOING_AJAX) {
            echo 'AJAX';
            exit();
        }
        \EE_Error::get_notices(false, true);
        wp_safe_redirect(filter_input(INPUT_SERVER, 'HTTP_REFERER'));
        exit();
	}



    /**************************** ADMIN FUNCTIONALITY ****************************/



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



    /**
     * increment or decrement the wait list reg count for an event when a registration's status changes to or from RWL
     *
     * @param \EE_Registration $registration
     * @param                  $old_STS_ID
     * @param                  $new_STS_ID
     * @throws \EE_Error
     * @throws \EventEspresso\core\exceptions\InvalidEntityException
     * @throws \EventEspresso\core\exceptions\InvalidInterfaceException
     */
    public static function registration_status_update(EE_Registration $registration, $old_STS_ID, $new_STS_ID)
    {
        \EED_Wait_Lists::getWaitListMonitor()->registrationStatusUpdate($registration, $old_STS_ID, $new_STS_ID);
    }



    /**
     * @param \EE_Event $event
     * @return int
     * @throws \EE_Error
     */
    public static function waitListRegCount(\EE_Event $event)
    {
        return absint($event->get_extra_meta('ee_wait_list_registration_count', true));
    }



}
// End of file EED_Wait_Lists.module.php
// Location: wp-content/plugins/eea-wait-lists/EED_Wait_Lists.module.php