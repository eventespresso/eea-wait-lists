<?php

use EventEspresso\core\exceptions\ExceptionStackTraceDisplay;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidEntityException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\exceptions\InvalidStatusException;
use EventEspresso\WaitList\domain\services\forms\EventEditorWaitListMetaBoxFormHandler;
use EventEspresso\WaitList\domain\Domain;
use EventEspresso\WaitList\domain\services\checkout\WaitListCheckoutMonitor;
use EventEspresso\WaitList\domain\services\event\WaitListMonitor;


defined('EVENT_ESPRESSO_VERSION') || exit;



/**
 * Class EED_Wait_Lists
 * module class for controlling event registration wait lists
 *
 * @package       Event Espresso
 * @author        Brent Christensen
 * @since         1.0.0
 */
class EED_Wait_Lists extends EED_Module
{


    /**
     * @var Events_Admin_Page $admin_page
     */
    protected static $admin_page;


    public static function reset() {
        EED_Wait_Lists::$admin_page = null;
    }



    /**
     * set_hooks - for hooking into EE Core, other modules, etc
     *
     * @return void
     * @throws DomainException
     * @throws InvalidInterfaceException
     * @throws InvalidEntityException
     * @throws EE_Error
     */
    public static function set_hooks()
    {
        EED_Wait_Lists::register_dependencies();
        EED_Wait_Lists::set_shared_hooks();
        EE_Config::register_route(
            'join',
            'EED_Wait_Lists',
            'process_wait_list_form_for_event',
            'event_wait_list[route]'
        );
        add_filter(
            'FHEE__EventEspresso_modules_ticket_selector_DisplayTicketSelector__displaySubmitButton__html',
            array('EED_Wait_Lists', 'add_wait_list_form_for_event'),
            10, 2
        );
        add_action('wp_enqueue_scripts', array('EED_Wait_Lists', 'enqueue_styles_and_scripts'));
    }



    /**
     * set_hooks_admin - for hooking into EE Admin Core, other modules, etc
     *
     * @return void
     * @throws DomainException
     * @throws InvalidInterfaceException
     * @throws InvalidEntityException
     * @throws EE_Error
     */
    public static function set_hooks_admin()
    {
        EED_Wait_Lists::register_dependencies();
        EED_Wait_Lists::set_shared_hooks();
        // hooks into filter found in \EE_Admin_Page::_page_setup
        add_filter(
            'FHEE__Extend_Events_Admin_Page__page_setup__page_config',
            array('EED_Wait_Lists', 'setup_page_config'),
            1, 2
        );
        add_filter(
            'FHEE__Events_Admin_Page___insert_update_cpt_item__event_update_callbacks',
            array('EED_Wait_Lists', 'event_update_callbacks')
        );
        add_filter(
            'FHEE__Extend_Registrations_Admin_Page__page_setup__page_routes',
            array('EED_Wait_Lists', 'reg_admin_page_routes'),
            10
        );
        add_filter(
            'FHEE__Registrations_Admin_Page___set_list_table_views_default__def_reg_status_actions_array',
            array('EED_Wait_Lists', 'reg_status_actions'),
            10, 3
        );
        add_action(
            'AHEE__Events_Admin_Page___generate_publish_box_extra_content__event_editor_overview_add',
            array('EED_Wait_Lists', 'event_editor_overview_add')
        );
        add_action(
            'wp_ajax_process_wait_list_form_for_event',
            array('EED_Wait_Lists', 'process_wait_list_form_for_event')
        );
        add_action(
            'wp_ajax_nopriv_process_wait_list_form_for_event',
            array('EED_Wait_Lists', 'process_wait_list_form_for_event')
        );
    }



    /**
     * @return void
     * @throws DomainException
     * @throws InvalidInterfaceException
     * @throws InvalidEntityException
     * @throws EE_Error
     */
    protected static function register_dependencies()
    {
        EE_Dependency_Map::instance()->add_alias(
            'EventEspresso\WaitList\domain\services\collections\WaitListEventsCollection',
            'EventEspresso\core\services\collections\Collection',
            'EventEspresso\WaitList\domain\services\event\WaitListMonitor'
        );
        EE_Dependency_Map::register_class_loader(
            'EventEspresso\WaitList\domain\services\collections\WaitListEventsCollection',
            function () {
                return new \EventEspresso\WaitList\domain\services\collections\WaitListEventsCollection();
            }
        );
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\WaitList\domain\services\event\WaitListMonitor',
            array(
                'EventEspresso\WaitList\domain\services\collections\WaitListEventsCollection' =>
                    EE_Dependency_Map::load_from_cache,
                'EventEspresso\WaitList\domain\services\event\WaitListEventMeta' => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\services\commands\CommandBusInterface'       => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\services\loaders\LoaderInterface'            => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\services\notices\NoticeConverterInterface'   => EE_Dependency_Map::load_from_cache,
            )
        );
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\WaitList\domain\services\checkout\WaitListCheckoutMonitor',
            array(
                'EventEspresso\WaitList\domain\services\registration\WaitListRegistrationMeta' =>
                    EE_Dependency_Map::load_from_cache,
            )
        );
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\WaitList\domain\services\commands\PromoteWaitListRegistrantsCommandHandler',
            array(
                'EEM_Registration'                                               => EE_Dependency_Map::load_from_cache,
                'EE_Capabilities'                                                => EE_Dependency_Map::load_from_cache,
                'EEM_Change_Log'                                                 => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\services\notices\NoticesContainerInterface'  => EE_Dependency_Map::load_from_cache,
                'EventEspresso\WaitList\domain\services\event\WaitListEventMeta' => EE_Dependency_Map::load_from_cache,
            )
        );
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\WaitList\domain\services\commands\CreateWaitListRegistrationsCommandHandler',
            array(
                'EventEspresso\WaitList\domain\services\event\WaitListEventMeta' => EE_Dependency_Map::load_from_cache,
                'EventEspresso\WaitList\domain\services\registration\WaitListRegistrationMeta' =>
                    EE_Dependency_Map::load_from_cache,
                'EEM_Registration'                                              => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\services\commands\CommandBusInterface'      => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\services\commands\CommandFactoryInterface'  => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\services\notices\NoticesContainerInterface' => EE_Dependency_Map::load_from_cache,
            )
        );
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\WaitList\domain\services\commands\UpdateRegistrationWaitListMetaDataCommandHandler',
            array(
                'EventEspresso\WaitList\domain\services\event\WaitListEventMeta' => EE_Dependency_Map::load_from_cache,
                'EventEspresso\WaitList\domain\services\registration\WaitListRegistrationMeta' =>
                    EE_Dependency_Map::load_from_cache,
            )
        );
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\WaitList\domain\services\commands\CalculateEventSpacesAvailableCommandHandler',
            array(
                'EventEspresso\WaitList\domain\services\event\WaitListEventMeta' => EE_Dependency_Map::load_from_cache,
            )
        );
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\WaitList\domain\services\forms\EventEditorWaitListMetaBoxFormHandler',
            array(
                null,
                'EventEspresso\WaitList\domain\services\event\WaitListEventMeta' => EE_Dependency_Map::load_from_cache,
                'EEM_Registration'                                               => EE_Dependency_Map::load_from_cache,
                'EE_Registry'                                                    => EE_Dependency_Map::load_from_cache,
            )
        );
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\WaitList\domain\services\forms\WaitListForm',
            array(
                null,
                null,
                'EventEspresso\WaitList\domain\services\event\WaitListEventMeta' => EE_Dependency_Map::load_from_cache,
            )
        );
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\WaitList\domain\services\forms\WaitListFormHandler',
            array( null, 'EE_Registry' => EE_Dependency_Map::load_from_cache)
        );
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\WaitList\domain\services\commands\CreateWaitListRegistrationsCommand',
            array( null, null, null, null, 'EEM_Ticket' => EE_Dependency_Map::load_from_cache)
        );
    }



    /**
     * hooks set by both set_hooks() and set_hooks_admin()
     *
     * @return void
     * @throws InvalidInterfaceException
     * @throws InvalidEntityException
     * @throws EE_Error
     */
    protected static function set_shared_hooks()
    {
        add_action(
            'AHEE__EE_Registration__set_status__after_update',
            array('EED_Wait_Lists', 'registration_status_update'),
            10, 3
        );
        add_filter(
            'FHEE_EE_Event__spaces_remaining',
            array('EED_Wait_Lists', 'event_spaces_available'),
            10, 2
        );
        add_filter(
            'FHEE_EE_Event__total_available_spaces__spaces_available',
            array('EED_Wait_Lists', 'event_spaces_available'),
            10, 2
        );
        add_action(
            'AHEE__EE_Event__perform_sold_out_status_check__end',
            array('EED_Wait_Lists', 'promote_wait_list_registrants'),
            10, 3
        );
        add_action(
            'AHEE__Single_Page_Checkout___load_and_instantiate_reg_steps__start',
            array('EED_Wait_Lists', 'load_and_instantiate_reg_steps')
        );
        add_filter(
            'FHEE__EE_SPCO_Reg_Step_Payment_Options__find_registrations_that_lost_their_space__allow_reg_payment',
            array('EED_Wait_Lists', 'allow_reg_payment'),
            10, 3
        );
        add_filter(
            'FHEE__EEM_Change_Log__get_pretty_label_map_for_registered_types',
            array('EED_Wait_Lists', 'register_wait_list_log_type'),
            10
        );
    }



    /**
     * @var WP $WP
     * @return void
     */
    public function run($WP)
    {
        // TODO: Implement run() method.
    }



    /**
     * @return WaitListMonitor
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     */
    public static function getWaitListMonitor()
    {
        return EE_Wait_Lists::loader()->load('\EventEspresso\WaitList\domain\services\event\WaitListMonitor');
    }



    /**
     * @return WaitListCheckoutMonitor
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     */
    public static function getWaitListCheckoutMonitor()
    {
        return EE_Wait_Lists::loader()->load('\EventEspresso\WaitList\domain\services\checkout\WaitListCheckoutMonitor');
    }



    /**
     * @param EE_Event $event
     * @return EventEditorWaitListMetaBoxFormHandler
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     */
    public static function getEventEditorWaitListMetaBoxForm($event = null)
    {
        $event = $event instanceof EE_Event ? $event : EED_Wait_Lists::$admin_page->get_event_object();
        return EE_Wait_Lists::loader()->load(
            '\EventEspresso\WaitList\domain\services\forms\EventEditorWaitListMetaBoxFormHandler',
            array($event)
        );
    }



    /**************************** FRONTEND FUNCTIONALITY ***************************/



    /**
     * enqueue_styles_and_scripts
     *
     * @return void
     * @throws DomainException
     */
    public static function enqueue_styles_and_scripts()
    {
        // load css
        wp_register_style(
            'wait_list',
            EventEspresso\WaitList\domain\Domain::pluginUrl() . 'assets/wait_list.css',
            array(),
            EE_WAIT_LISTS_VERSION
        );
        wp_enqueue_style('wait_list');
        // load JS
        add_filter('FHEE_load_jquery_validate', '__return_true');
        wp_register_script(
            'wait_list',
            EventEspresso\WaitList\domain\Domain::pluginUrl() . 'assets/wait_list.js',
            array('espresso_core', 'jquery-validate'),
            EE_WAIT_LISTS_VERSION,
            true
        );
        wp_enqueue_script('wait_list');
    }



    /**
     * @param string    $html
     * @param EE_Event $event
     * @return string
     * @throws Exception
     */
    public static function add_wait_list_form_for_event($html = '', EE_Event $event)
    {
        try {
            return $html . EED_Wait_Lists::getWaitListMonitor()->getWaitListFormForEvent($event);
        } catch (Exception $e) {
            EED_Wait_Lists::handleException($e, __FILE__, __FUNCTION__, __LINE__);
        }
        return $html;
    }



    /**
     * process_wait_list_form_for_event
     *
     * @throws Exception
     */
    public static function process_wait_list_form_for_event()
    {
        $referrer = filter_input(INPUT_SERVER, 'HTTP_REFERER');
        try {
            $event_id = isset($_REQUEST['event_wait_list'], $_REQUEST['event_wait_list']['event_id'])
                ? absint($_REQUEST['event_wait_list']['event_id'])
                : 0;
            $referrer = EED_Wait_Lists::getWaitListMonitor()->processWaitListFormForEvent($event_id);
        } catch (Exception $e) {
            EED_Wait_Lists::handleException($e, __FILE__, __FUNCTION__, __LINE__);
        }
        // todo submit form via AJAX and process return here
        if (defined('DOING_AJAX') && DOING_AJAX) {
            echo 'AJAX';
            exit();
        }
        EE_Error::get_notices(false, true);
        wp_safe_redirect($referrer);
        exit();
    }



    /**************************** SPLIT FUNCTIONALITY ***************************/



    /**
     * increment or decrement the wait list reg count for an event when a registration's status changes to or from RWL
     *
     * @param EE_Registration  $registration
     * @param                  $old_STS_ID
     * @param                  $new_STS_ID
     * @throws Exception
     */
    public static function registration_status_update(EE_Registration $registration, $old_STS_ID, $new_STS_ID)
    {
        try {
            EED_Wait_Lists::getWaitListMonitor()->registrationStatusUpdate(
                $registration,
                $old_STS_ID,
                $new_STS_ID
            );
        } catch (Exception $e) {
            EED_Wait_Lists::handleException($e, __FILE__, __FUNCTION__, __LINE__);
        }
    }



    /**
     * @param int       $spaces_available
     * @param EE_Event $event
     * @return int
     * @throws Exception
     */
    public static function event_spaces_available($spaces_available, EE_Event $event)
    {
        try {
            return EED_Wait_Lists::getWaitListMonitor()->adjustEventSpacesAvailable(
                $spaces_available,
                $event
            );
        } catch (Exception $e) {
            EED_Wait_Lists::handleException($e, __FILE__, __FUNCTION__, __LINE__);
        }
        return $spaces_available;
    }



    /**************************** ADMIN FUNCTIONALITY ****************************/



    /**
     * callback for FHEE__Extend_Events_Admin_Page__page_setup__page_config
     *
     * @param array              $page_config current page config.
     * @param Events_Admin_Page $admin_page
     * @return array
     * @since  1.0.0
     */
    public static function setup_page_config(array $page_config, Events_Admin_Page $admin_page)
    {
        EED_Wait_Lists::$admin_page = $admin_page;
        $page_config['edit']['metaboxes'][] = array('EED_Wait_Lists', 'add_event_wait_list_meta_box');
        $page_config['create_new']['metaboxes'] = $page_config['edit']['metaboxes'];
        return $page_config;
    }



    /**
     * @return void
     */
    public static function add_event_wait_list_meta_box()
    {
        add_meta_box(
            'event-wait-list-mbox',
            esc_html__('Event Wait List', 'event_espresso'),
            array('EED_Wait_Lists', 'event_wait_list_meta_box'),
            EVENTS_PG_SLUG,
            'normal', // normal    advanced    side
            'core' // high    core    default    low
        );
    }



    /**
     * callback that adds a link to the Event Editor Publish metabox
     * to view registrations on the wait list for the event
     *
     * @throws Exception
     */
    public static function event_editor_overview_add()
    {
        try {
            echo EEH_HTML::div(
                EED_Wait_Lists::getEventEditorWaitListMetaBoxForm()->waitListRegCountDisplay(),
                '', 'misc-pub-section'
            );
        } catch (Exception $e) {
            EED_Wait_Lists::handleException($e, __FILE__, __FUNCTION__, __LINE__);
        }
    }



    /**
     * callback that adds the main "event_wait_list_meta_box" meta_box
     * calls non static method below
     *
     * @throws Exception
     */
    public static function event_wait_list_meta_box()
    {
        try {
            echo EED_Wait_Lists::getEventEditorWaitListMetaBoxForm()->display();
        } catch (Exception $e) {
            EED_Wait_Lists::handleException($e, __FILE__, __FUNCTION__, __LINE__);
        }
    }



    /**
     * @param array $page_routes
     * @return array
     */
    public static function reg_admin_page_routes($page_routes = array())
    {
        $page_routes['wait_list_registrations'] = array(
            'func'       => 'bulk_action_on_registrations',
            'noheader'   => true,
            'capability' => 'ee_edit_registrations',
            'args'       => array('wait_list'),
        );
        $page_routes['wait_list_and_notify_registrations'] = array(
            'func'       => 'bulk_action_on_registrations',
            'noheader'   => true,
            'capability' => 'ee_edit_registrations',
            'args'       => array('wait_list', true),
        );
        return $page_routes;
    }



    /**
     * @param array $reg_status_actions
     * @param array $active_mts
     * @param bool  $can_send
     * @return array
     */
    public static function reg_status_actions($reg_status_actions = array(), $active_mts = array(), $can_send = false)
    {
        $reg_status_actions['wait_list_registrations'] = esc_html__(
            'Move Registrations to Wait List',
            'event_espresso'
        );
        if ($can_send && in_array('waitlist_can_register', $active_mts, true)) {
            $reg_status_actions['wait_list_and_notify_registrations'] = esc_html__(
                'Move Registrations to Wait List and Notify',
                'event_espresso'
            );
        }
        return $reg_status_actions;
    }



    /**
     * @param array $event_update_callbacks
     * @return array
     */
    public static function event_update_callbacks(array $event_update_callbacks)
    {
        $event_update_callbacks = array_merge(
            $event_update_callbacks,
            array(array('EED_Wait_Lists', 'update_event_wait_list_settings'))
        );
        return $event_update_callbacks;
    }



    /**
     * @param EE_Event $event
     * @param array    $form_data
     * @throws Exception
     * @throws InvalidStatusException
     */
    public static function update_event_wait_list_settings(EE_Event $event, array $form_data)
    {
        try {
            EED_Wait_Lists::getEventEditorWaitListMetaBoxForm($event)->process($form_data);
        } catch (Exception $e) {
            EED_Wait_Lists::handleException($e, __FILE__, __FUNCTION__, __LINE__);
        }
    }




    /**
     * @param EE_Event $event
     * @param bool     $sold_out
     * @param int      $spaces_remaining
     * @throws Exception
     */
    public static function promote_wait_list_registrants(
        EE_Event $event,
        $sold_out = false,
        $spaces_remaining = 0
    ) {
        try {
            EED_Wait_Lists::getWaitListMonitor()->promoteWaitListRegistrants(
                $event,
                $spaces_remaining
            );
        } catch (Exception $e) {
            EED_Wait_Lists::handleException($e, __FILE__, __FUNCTION__, __LINE__);
        }
    }



    /**
     * @param EE_Registration $registration
     * @return string
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     * @throws EE_Error
     */
    public static function wait_list_checkout_url(EE_Registration $registration)
    {
        return apply_filters(
            'FHEE__EED_Wait_Lists__wait_list_checkout_url',
            add_query_arg(
                array(
                    'e_reg_url_link' => $registration->reg_url_link(),
                    'revisit'        => 0,
                ),
                EE_Registry::instance()->CFG->core->reg_page_url()
            ),
            $registration
        );
    }



    /**
     * @param EE_Event $event
     * @return string
     * @throws EE_Error
     */
    public static function wait_list_registrations_list_table_link(EE_Event $event)
    {
        return EEH_HTML::link(
            add_query_arg(
                array(
                    'route'       => 'default',
                    '_reg_status' => EEM_Registration::status_id_wait_list,
                    'event_id'    => $event->ID(),
                ),
                REG_ADMIN_URL
            ),
            esc_html__('Wait List Registrations', 'event_espresso'),
            esc_html__('View registrations on the wait list for this event', 'event_espresso')
        );
    }



    /**
     * @param EE_Checkout $checkout
     * @throws Exception
     */
    public static function load_and_instantiate_reg_steps(EE_Checkout $checkout)
    {
        try {
            EED_Wait_Lists::getWaitListCheckoutMonitor()->loadAndInstantiateRegSteps($checkout);
        } catch (Exception $e) {
            EED_Wait_Lists::handleException($e, __FILE__, __FUNCTION__, __LINE__);
        }
    }



    /**
     * @param bool            $allow_payment
     * @param EE_Registration $registration
     * @param bool            $revisit
     * @return bool
     * @throws Exception
     */
    public static function allow_reg_payment($allow_payment, EE_Registration $registration, $revisit = false)
    {
        if ($revisit) {
            return $allow_payment;
        }
        try {
            return EED_Wait_Lists::getWaitListCheckoutMonitor()->allowRegPayment(
                $allow_payment,
                $registration
            );
        } catch (Exception $e) {
            EED_Wait_Lists::handleException($e, __FILE__, __FUNCTION__, __LINE__);
        }
        return $allow_payment;
    }



    /**
     * @param array $log_type_labels
     * @return array
     * @throws EE_Error
     */
    public static function register_wait_list_log_type($log_type_labels = array())
    {
        $log_type_labels[Domain::LOG_TYPE] = esc_html__('Wait List', 'event_espresso');
        return $log_type_labels;
    }



    /**
     * @param Exception $exception
     * @param string    $file
     * @param string    $func
     * @param string    $line
     * @throws Exception
     */
    protected static function handleException(Exception $exception, $file = '', $func = '', $line = '') {
        if (WP_DEBUG) {
            new ExceptionStackTraceDisplay($exception);
        } else {
            EE_Error::add_error($exception->getMessage(), $file, $func, $line);
        }
    }


}
// End of file EED_Wait_Lists.module.php
// Location: wp-content/plugins/eea-wait-lists/EED_Wait_Lists.module.php
