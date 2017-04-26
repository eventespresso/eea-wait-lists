<?php
use EventEspresso\core\exceptions\ExceptionStackTraceDisplay;
use EventEspresso\core\exceptions\InvalidEntityException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\WaitList\EventEditorWaitListMetaBoxForm;
use EventEspresso\WaitList\WaitList;
use EventEspresso\WaitList\WaitListCheckoutMonitor;
use EventEspresso\WaitList\WaitListMonitor;

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
     * @var \Events_Admin_Page $admin_page
     */
    protected static $admin_page;


    public static function reset() {
        self::$admin_page = null;
        self::$_instance = null;
    }



    /**
     * set_hooks - for hooking into EE Core, other modules, etc
     *
     * @return void
     * @throws InvalidInterfaceException
     * @throws InvalidEntityException
     * @throws EE_Error
     */
    public static function set_hooks()
    {
        EED_Wait_Lists::set_shared_hooks();
        EE_Config::register_route('join', 'EED_Wait_Lists', 'process_wait_list_form_for_event', 'wait_list');
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
     * @throws InvalidInterfaceException
     * @throws InvalidEntityException
     * @throws EE_Error
     */
    public static function set_hooks_admin()
    {
        EED_Wait_Lists::set_shared_hooks();
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
            'FHEE__Registrations_Admin_Page___set_list_table_views_default__def_reg_status_actions_array',
            array('EED_Wait_Lists', 'reg_status_actions'),
            10, 2
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
     * hooks set by both set_hooks() and set_hooks_admin()
     *
     * @return void
     * @throws InvalidInterfaceException
     * @throws InvalidEntityException
     * @throws EE_Error
     */
    protected static function set_shared_hooks()
    {
        EE_Dependency_Map::instance()->add_alias(
            'EventEspresso\WaitList\WaitListEventsCollection',
            'EventEspresso\core\services\collections\Collection',
            'EventEspresso\WaitList\WaitListMonitor'
        );
        EE_Dependency_Map::register_class_loader(
            'EventEspresso\WaitList\WaitListEventsCollection',
            function () {
                return new EventEspresso\WaitList\WaitListEventsCollection();
            }
        );
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\WaitList\WaitListMonitor',
            array('EventEspresso\WaitList\WaitListEventsCollection' => EE_Dependency_Map::load_from_cache)
        );
        EE_Dependency_Map::register_dependencies(
            'EventEspresso\WaitList\EventEditorWaitListMetaBoxForm',
            array('EE_Registry' => EE_Dependency_Map::load_from_cache)
        );
        add_action(
            'AHEE__EE_Registration__set_status__after_update',
            array('EED_Wait_Lists', 'registration_status_update'),
            10, 3
        );
        add_filter(
            'FHEE_EE_Event__perform_sold_out_status_check__spaces_remaining',
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
            'FHEE__EE_Enum_Text_Field___allowed_enum_options',
            array('EED_Wait_Lists', 'allowed_enum_values'),
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
    public function run($WP)
    {
        // TODO: Implement run() method.
    }



    /**
     * @return WaitListMonitor
     * @throws EE_Error
     * @throws InvalidEntityException
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     */
    public static function getWaitListMonitor()
    {
        return EE_Wait_Lists::loader()->load('\EventEspresso\WaitList\WaitListMonitor');
    }



    /**
     * @return WaitListCheckoutMonitor
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     */
    public static function getWaitListCheckoutMonitor()
    {
        return EE_Wait_Lists::loader()->load('\EventEspresso\WaitList\WaitListCheckoutMonitor');
    }



    /**
     * @return EventEditorWaitListMetaBoxForm
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     */
    public static function getEventEditorWaitListMetaBoxForm()
    {
        return EE_Wait_Lists::loader()->load(
            '\EventEspresso\WaitList\EventEditorWaitListMetaBoxForm',
            array( EED_Wait_Lists::$admin_page->get_event_object() )
        );
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
        add_filter('FHEE_load_jquery_validate', '__return_true');
        wp_register_script(
            'wait_list',
            EE_WAIT_LISTS_URL . 'assets/wait_list.js',
            array('espresso_core', 'jquery-validate'),
            EE_WAIT_LISTS_VERSION,
            true
        );
        wp_enqueue_script('wait_list');
    }



    /**
     * @param string    $html
     * @param \EE_Event $event
     * @return string
     */
    public static function add_wait_list_form_for_event($html = '', \EE_Event $event)
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
     */
    public static function process_wait_list_form_for_event()
    {
        try {
            $event_id = isset($_REQUEST['event_id']) ? absint($_REQUEST['event_id']) : 0;
            EED_Wait_Lists::getWaitListMonitor()->processWaitListFormForEvent($event_id);
        } catch (Exception $e) {
            EED_Wait_Lists::handleException($e, __FILE__, __FUNCTION__, __LINE__);
        }
        // todo submit form via AJAX and process return here
        if (defined('DOING_AJAX') && DOING_AJAX) {
            echo 'AJAX';
            exit();
        }
        EE_Error::get_notices(false, true);
        wp_safe_redirect(filter_input(INPUT_SERVER, 'HTTP_REFERER'));
        exit();
    }



    /**************************** SPLIT FUNCTIONALITY ***************************/
    /**
     * increment or decrement the wait list reg count for an event when a registration's status changes to or from RWL
     *
     * @param \EE_Registration $registration
     * @param                  $old_STS_ID
     * @param                  $new_STS_ID
     */
    public static function registration_status_update(EE_Registration $registration, $old_STS_ID, $new_STS_ID)
    {
        try {
            EED_Wait_Lists::getWaitListMonitor()->registrationStatusUpdate($registration, $old_STS_ID, $new_STS_ID);
        } catch (Exception $e) {
            EED_Wait_Lists::handleException($e, __FILE__, __FUNCTION__, __LINE__);
        }
    }



    /**
     * @param int       $spaces_available
     * @param \EE_Event $event
     * @return int
     */
    public static function event_spaces_available($spaces_available, \EE_Event $event)
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
     * @param \Events_Admin_Page $admin_page
     * @return array
     * @since  1.0.0
     */
    public static function setup_page_config(array $page_config, \Events_Admin_Page $admin_page)
    {
        EED_Wait_Lists::$admin_page = $admin_page;
        $page_config['edit']['metaboxes'] = array_merge(
            $page_config['edit']['metaboxes'],
            array(array('EED_Wait_Lists', 'add_event_wait_list_meta_box'))
        );
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
            'side', // advanced   normal  side
            'high' // default   high    low
        );
    }



    /**
     * callback that adds a link to the Event Editor Publish metabox
     * to view registrations on the wait list for the event
     */
    public static function event_editor_overview_add()
    {
        try {
            echo \EEH_HTML::div(
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
     * @param array $reg_status_actions
     * @param array $active_mts
     * @return array
     */
    public static function reg_status_actions(array $reg_status_actions, array $active_mts)
    {
        $reg_status_actions['wait_list_registration'] = __('Set Registrations to Wait List', 'event_espresso');
        if (
            in_array('waitlist_can_register', $active_mts, true)
            && EE_Registry::instance()->CAP->current_user_can('ee_send_message', 'batch_send_messages')
        ) {
            $def_reg_status_actions['wait_list_and_notify_registration'] = __(
                'Set Registrations to Wait List and Notify',
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
     * @param \EE_Event $event
     * @param array     $form_data
     */
    public static function update_event_wait_list_settings(EE_Event $event, array $form_data)
    {
        try {
            $wait_list_settings_form = new EventEditorWaitListMetaBoxForm(
                $event,
                EE_Registry::instance()
            );
            $wait_list_settings_form->process($form_data);
        } catch (Exception $e) {
            EED_Wait_Lists::handleException($e, __FILE__, __FUNCTION__, __LINE__);
        }
    }



    /**
     * @param \EE_Event $event
     * @return int
     * @throws EE_Error
     */
    public static function waitListRegCount(\EE_Event $event)
    {
        return absint($event->get_extra_meta(WaitList::REG_COUNT_META_KEY, true));
    }



    /**
     * @param EE_Event $event
     * @param bool     $sold_out
     * @param int      $spaces_remaining
     */
    public static function promote_wait_list_registrants(
        \EE_Event $event,
        $sold_out = false,
        $spaces_remaining = 0
    ) {
        try {
            EED_Wait_Lists::getWaitListMonitor()->promoteWaitListRegistrants(
                $event,
                $sold_out,
                $spaces_remaining
            );
        } catch (Exception $e) {
            EED_Wait_Lists::handleException($e, __FILE__, __FUNCTION__, __LINE__);
        }
    }



    /**
     * @param EE_Registration $registration
     * @return string
     * @throws EE_Error
     */
    public static function wait_list_checkout_url(EE_Registration $registration)
    {
        return apply_filters(
            'FHEE__EED_Wait_Lists__wait_list_checkout_url', '',
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
    public static function wait_list_registrations_list_table_link(\EE_Event $event)
    {
        return \EEH_HTML::link(
            add_query_arg(
                array(
                    'route'       => 'default',
                    '_reg_status' => \EEM_Registration::status_id_wait_list,
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



    public static function allowed_enum_values(array $allowed_enum_values)
    {
        $allowed_enum_values[WaitList::LOG_TYPE] = esc_html__('Wait List', 'event_espresso');
        return $allowed_enum_values;
    }



    /**
     * @param Exception $exception
     * @param string    $file
     * @param string    $func
     * @param string    $line
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