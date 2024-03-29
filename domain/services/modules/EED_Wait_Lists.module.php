<?php

use EventEspresso\core\domain\entities\contexts\ContextInterface;
use EventEspresso\core\exceptions\EntityNotFoundException;
use EventEspresso\core\exceptions\ExceptionStackTraceDisplay;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\exceptions\InvalidStatusException;
use EventEspresso\core\services\loaders\LoaderFactory;
use EventEspresso\modules\ticket_selector\DisplayTicketSelector;
use EventEspresso\WaitList\domain\services\forms\EventEditorWaitListMetaBoxFormHandler;
use EventEspresso\WaitList\domain\Domain;
use EventEspresso\WaitList\domain\services\checkout\WaitListCheckoutMonitor;
use EventEspresso\WaitList\domain\services\event\WaitListMonitor;

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
     * @var Events_Admin_Page|null
     */
    protected static $admin_page;


    public static function reset()
    {
        EED_Wait_Lists::$admin_page = null;
    }


    /**
     * set_hooks - for hooking into EE Core, other modules, etc
     *
     * @return void
     */
    public static function set_hooks()
    {
        EED_Wait_Lists::set_shared_hooks();
        EE_Config::register_route(
            'join',
            'EED_Wait_Lists',
            'process_wait_list_form_for_event',
            'event-wait-list-*[route]'
        );
        EE_Config::register_route(
            'wait_list',
            'EED_Wait_Lists',
            'wait_list_confirmation_url',
            'ee-confirmation'
        );
        add_filter(
            'FHEE__EventEspresso_modules_ticket_selector_DisplayTicketSelector__displaySubmitButton__html',
            ['EED_Wait_Lists', 'add_wait_list_form_for_event'],
            10,
            3
        );
        add_action('wp_enqueue_scripts', ['EED_Wait_Lists', 'enqueue_styles_and_scripts']);
    }


    /**
     * set_hooks_admin - for hooking into EE Admin Core, other modules, etc
     *
     * @return void
     */
    public static function set_hooks_admin()
    {
        EED_Wait_Lists::set_shared_hooks();
        // hooks into filter found in \EE_Admin_Page::_page_setup
        add_filter(
            'FHEE__Events_Admin_Page__page_setup__page_config',
            ['EED_Wait_Lists', 'set_admin_page'],
            0,
            2
        );
        add_filter(
            'FHEE__Extend_Events_Admin_Page__page_setup__page_config',
            ['EED_Wait_Lists', 'setup_page_config'],
            1,
            2
        );
        add_filter(
            'FHEE__Events_Admin_Page___insert_update_cpt_item__event_update_callbacks',
            ['EED_Wait_Lists', 'event_update_callbacks']
        );
        add_filter(
            'FHEE__Extend_Registrations_Admin_Page__page_setup__page_routes',
            ['EED_Wait_Lists', 'reg_admin_page_routes']
        );
        add_filter(
            'FHEE__Registrations_Admin_Page___set_list_table_views_default__def_reg_status_actions_array',
            ['EED_Wait_Lists', 'reg_status_actions'],
            10,
            3
        );
        add_action(
            'AHEE__Events_Admin_Page___generate_publish_box_extra_content__event_editor_overview_add',
            ['EED_Wait_Lists', 'event_editor_overview_add']
        );
        add_action(
            'wp_ajax_process_wait_list_form_for_event',
            ['EED_Wait_Lists', 'process_wait_list_form_for_event']
        );
        add_action(
            'wp_ajax_nopriv_process_wait_list_form_for_event',
            ['EED_Wait_Lists', 'process_wait_list_form_for_event']
        );
        add_action('admin_enqueue_scripts', ['EED_Wait_Lists', 'enqueueAdminStylesAndScripts']);
    }


    /**
     * hooks set by both set_hooks() and set_hooks_admin()
     *
     * @return void
     */
    protected static function set_shared_hooks()
    {
        add_action(
            'AHEE__EE_Registration__set_status__after_update',
            ['EED_Wait_Lists', 'registration_status_update'],
            10,
            4
        );
        add_filter(
            'FHEE_EE_Event__spaces_remaining',
            ['EED_Wait_Lists', 'event_spaces_available'],
            10,
            2
        );
        add_filter(
            'FHEE_EE_Event__total_available_spaces__spaces_available',
            ['EED_Wait_Lists', 'event_spaces_available'],
            10,
            2
        );
        add_action(
            'AHEE__EE_Event__perform_sold_out_status_check__end',
            ['EED_Wait_Lists', 'promote_wait_list_registrants'],
            10,
            3
        );
        add_action(
            'AHEE__Single_Page_Checkout___load_and_instantiate_reg_steps__start',
            ['EED_Wait_Lists', 'load_and_instantiate_reg_steps']
        );
        add_filter(
            'FHEE__EE_SPCO_Reg_Step_Payment_Options__find_registrations_that_lost_their_space__allow_reg_payment',
            ['EED_Wait_Lists', 'allow_reg_payment'],
            10,
            3
        );
        add_filter(
            'FHEE__EEM_Change_Log__get_pretty_label_map_for_registered_types',
            ['EED_Wait_Lists', 'register_wait_list_log_type']
        );
        add_filter(
            'FHEE__EE_Registration__edit_attendee_information_url__query_args',
            ['EED_Wait_Lists', 'wait_list_checkout_url_query_args'],
            10,
            3
        );
        add_filter(
            'FHEE__EE_Registration__payment_overview_url__query_args',
            ['EED_Wait_Lists', 'wait_list_checkout_url_query_args'],
            10,
            3
        );
    }


    /**
     * @return void
     * @var WP $WP
     */
    public function run($WP)
    {
        // TODO: Implement run() method.
    }


    /**
     * @return WaitListMonitor
     */
    public static function getWaitListMonitor(): WaitListMonitor
    {
        return LoaderFactory::getShared(
            'EventEspresso\WaitList\domain\services\event\WaitListMonitor'
        );
    }


    /**
     * @return WaitListCheckoutMonitor
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     */
    public static function getWaitListCheckoutMonitor(): WaitListCheckoutMonitor
    {
        return LoaderFactory::getShared(
            'EventEspresso\WaitList\domain\services\checkout\WaitListCheckoutMonitor'
        );
    }


    /**
     * @param EE_Event|null $event
     * @return EventEditorWaitListMetaBoxFormHandler
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     */
    public static function getEventEditorWaitListMetaBoxForm(?EE_Event $event = null): EventEditorWaitListMetaBoxFormHandler
    {
        $event = $event instanceof EE_Event ? $event : EED_Wait_Lists::$admin_page->get_event_object();
        return LoaderFactory::getShared(
            'EventEspresso\WaitList\domain\services\forms\EventEditorWaitListMetaBoxFormHandler',
            [$event]
        );
    }


    public static function enqueueAdminStylesAndScripts()
    {
        $domain = LoaderFactory::getShared('EventEspresso\WaitList\domain\Domain');
        wp_register_style(
            'wait_list_admin',
            $domain->pluginUrl() . 'assets/wait_list_admin.css',
            [],
            EE_WAIT_LISTS_VERSION
        );
        wp_enqueue_style('wait_list_admin');
    }



    /**************************** FRONTEND FUNCTIONALITY ***************************/
    /**
     * enqueue_styles_and_scripts
     *
     * @return void
     * @throws InvalidInterfaceException
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws DomainException
     */
    public static function enqueue_styles_and_scripts()
    {
        $domain = LoaderFactory::getShared('EventEspresso\WaitList\domain\Domain');
        // load css
        wp_register_style(
            'wait_list',
            $domain->pluginUrl() . 'assets/wait_list.css',
            [],
            EE_WAIT_LISTS_VERSION
        );
        wp_enqueue_style('wait_list');
        // load JS
        add_filter('FHEE_load_jquery_validate', '__return_true');
        wp_register_script(
            'wait_list',
            $domain->pluginUrl() . 'assets/wait_list.js',
            ['espresso_core', 'jquery-validate'],
            EE_WAIT_LISTS_VERSION,
            true
        );
        wp_enqueue_script('wait_list');
    }


    /**
     * @param string                $html
     * @param EE_Event              $event
     * @param DisplayTicketSelector $ticket_selector
     * @return string
     * @throws Exception
     * @throws Throwable
     */
    public static function add_wait_list_form_for_event(
        string $html,
        EE_Event $event,
        DisplayTicketSelector $ticket_selector
    ): string {
        try {
            return $html . EED_Wait_Lists::getWaitListMonitor()->getWaitListFormForEvent(
                $event,
                $ticket_selector
            );
        } catch (Exception $e) {
            EED_Wait_Lists::handleException($e, __FILE__, __FUNCTION__, __LINE__);
        }
        return $html;
    }


    /**
     * process_wait_list_form_for_event
     *
     * @throws Exception
     * @throws Throwable
     */
    public static function process_wait_list_form_for_event()
    {
        /** @var EventEspresso\core\services\request\RequestInterface $request */
        $request         = LoaderFactory::getShared('EventEspresso\core\services\request\RequestInterface');
        $event_id        = absint($request->getMatch('event-wait-list-*[event_id]', 0));
        $redirect_params = [];
        try {
            $redirect_params = EED_Wait_Lists::getWaitListMonitor()->processWaitListFormForEvent($event_id);
        } catch (Exception $e) {
            EED_Wait_Lists::handleException($e, __FILE__, __FUNCTION__, __LINE__);
        }
        // todo submit form via AJAX and process return here
        if (defined('DOING_AJAX') && DOING_AJAX) {
            exit();
        }
        // Pull the HTTP_REFERER if we can
        $referer = filter_input(INPUT_SERVER, 'HTTP_REFERER');
        if (empty($referer)) {
            // filter_input() can return null on some setups, so fall back to filter_var() in that case.
            $referer = filter_var($_SERVER['HTTP_REFERER'], FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE);
        }
        // Filter the redirect_url.
        $redirect_url = apply_filters(
            'FHEE__EED_Wait_Lists__process_wait_list_form_for_event__redirect_url',
            $referer,
            $redirect_params
        );
        EEH_URL::safeRedirectAndExit(
            add_query_arg(
                $redirect_params,
                trailingslashit($redirect_url)
            )
        );
    }


    /**
     * @param EE_Registration $registration
     * @return string
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     * @throws EE_Error
     */
    public static function wait_list_confirmation_url(EE_Registration $registration): string
    {
        /** @var EventEspresso\WaitList\domain\services\registration\WaitListRegistrationConfirmation $confirmation */
        $confirmation = LoaderFactory::getShared(
            'EventEspresso\WaitList\domain\services\registration\WaitListRegistrationConfirmation'
        );
        return $confirmation->url($registration);
    }


    /**
     * Displays a notice to the visitor that their spot on t he wait list has been confirmed
     *
     * @return void
     * @throws Exception
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     */
    public static function wait_list_confirmation()
    {
        /** @var EventEspresso\WaitList\domain\services\registration\WaitListRegistrationConfirmation $confirmation */
        $confirmation = LoaderFactory::getShared(
            'EventEspresso\WaitList\domain\services\registration\WaitListRegistrationConfirmation'
        );
        $confirmation->routeHandler();
    }


    /**************************** SPLIT FUNCTIONALITY ***************************/
    /**
     * increment or decrement the wait list reg count for an event when a registration's status changes to or from RWL
     *
     * @param EE_Registration       $registration
     * @param                       $old_STS_ID
     * @param                       $new_STS_ID
     * @param ContextInterface|null $context
     * @throws Exception
     * @throws Throwable
     */
    public static function registration_status_update(
        EE_Registration $registration,
        $old_STS_ID,
        $new_STS_ID,
        ContextInterface $context = null
    ) {
        try {
            EED_Wait_Lists::getWaitListMonitor()->registrationStatusUpdate(
                $registration,
                $old_STS_ID,
                $new_STS_ID,
                $context
            );
        } catch (Exception $e) {
            EED_Wait_Lists::handleException($e, __FILE__, __FUNCTION__, __LINE__);
        }
    }


    /**
     * @param int|float $spaces_available
     * @param EE_Event  $event
     * @return int|float
     * @throws Exception
     * @throws Throwable
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
     * @param array             $page_config
     * @param Events_Admin_Page $admin_page
     * @return array
     */
    public static function set_admin_page(array $page_config, Events_Admin_Page $admin_page): array
    {
        EED_Wait_Lists::$admin_page = $admin_page;
        return $page_config;
    }


    /**
     * callback for FHEE__Extend_Events_Admin_Page__page_setup__page_config &&
     * FHEE__Events_Admin_Page__page_setup__page_config
     *
     * @param array             $page_config current page config.
     * @param Events_Admin_Page $admin_page
     * @return array
     * @since  1.0.0
     */
    public static function setup_page_config(array $page_config, Events_Admin_Page $admin_page): array
    {
        EED_Wait_Lists::$admin_page = $admin_page;

        $page_config['edit']['metaboxes'][]     = ['EED_Wait_Lists', 'add_event_wait_list_meta_box'];
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
            ['EED_Wait_Lists', 'event_wait_list_meta_box'],
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
     * @throws Throwable
     */
    public static function event_editor_overview_add()
    {
        try {
            echo EEH_HTML::div(
                EED_Wait_Lists::getEventEditorWaitListMetaBoxForm()->waitListRegCountDisplay(),
                '',
                'misc-pub-section'
            );
        } catch (Exception $e) {
            EED_Wait_Lists::handleException($e, __FILE__, __FUNCTION__, __LINE__);
        }
    }


    /**
     * callback that adds the main "event_wait_list_meta_box" meta_box
     * calls non-static method below
     *
     * @throws Exception
     * @throws Throwable
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
    public static function reg_admin_page_routes(array $page_routes = []): array
    {
        $page_routes['wait_list_registrations']            = [
            'func'       => 'bulk_action_on_registrations',
            'noheader'   => true,
            'capability' => 'ee_edit_registrations',
            'args'       => ['wait_list'],
        ];
        $page_routes['wait_list_and_notify_registrations'] = [
            'func'       => 'bulk_action_on_registrations',
            'noheader'   => true,
            'capability' => 'ee_edit_registrations',
            'args'       => ['wait_list', true],
        ];
        return $page_routes;
    }


    /**
     * Callback for FHEE__Registrations_Admin_Page___set_list_table_views_default__def_reg_status_actions_array
     *
     * @param array $reg_status_actions
     * @param array $active_message_types Array of slugs for message types that are active.
     * @param bool  $can_send             Whether the user has the capability to send messages or not.
     * @return array
     */
    public static function reg_status_actions(
        array $reg_status_actions,
        array $active_message_types,
        bool $can_send
    ): array {
        $reg_status_actions['wait_list_registrations'] = esc_html__(
            'Move Registrations to Wait List',
            'event_espresso'
        );
        if (
            $can_send
            && in_array(
                Domain::MESSAGE_TYPE_WAIT_LIST_DEMOTION,
                $active_message_types,
                true
            )
        ) {
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
    public static function event_update_callbacks(array $event_update_callbacks): array
    {
        return array_merge(
            $event_update_callbacks,
            [['EED_Wait_Lists', 'update_event_wait_list_settings']]
        );
    }


    /**
     * @param EE_Event $event
     * @param array    $form_data
     * @throws Exception
     * @throws InvalidStatusException
     * @throws Throwable
     */
    public static function update_event_wait_list_settings(EE_Event $event, array $form_data)
    {
        if (
            ! EED_Wait_Lists::$admin_page instanceof Events_Admin_Page
            || ! isset($form_data['event_wait_list_settings'])
        ) {
            return;
        }
        try {
            EED_Wait_Lists::getEventEditorWaitListMetaBoxForm($event)->process($form_data);
        } catch (Exception $e) {
            EED_Wait_Lists::handleException($e, __FILE__, __FUNCTION__, __LINE__);
        }
    }


    /**
     * @param EE_Event  $event
     * @param bool      $sold_out
     * @param int|float $spaces_remaining
     * @throws Exception
     * @throws Throwable
     */
    public static function promote_wait_list_registrants(
        EE_Event $event,
        bool $sold_out = false,
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
     * @param array           $query_args
     * @param EE_Registration $registration
     * @return array
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     * @throws EE_Error
     * @throws EntityNotFoundException
     * @throws ReflectionException
     */
    public static function wait_list_checkout_url_query_args(array $query_args, EE_Registration $registration): array
    {
        try {
            $transaction = $registration->transaction();
        } catch (EntityNotFoundException $exception) {
            /** @var EventEspresso\core\services\request\RequestInterface $request */
            $request = LoaderFactory::getShared('EventEspresso\core\services\request\RequestInterface');
            $action  = $request->getRequestParam('action');
            if ($action !== 'preview_message' && $action !== 'update_message_template') {
                throw $exception;
            }
            $transaction = null;
        }
        // if the attendee info step has not been completed, then always go to that step
        if (
            $transaction instanceof EE_Transaction
            && $transaction->reg_step_completed('attendee_information') !== true
        ) {
            $query_args['step'] = 'attendee_information';
            // and also remove the 'revisit' and 'clear_session' parameters
            unset($query_args['revisit'], $query_args['clear_session']);
        }
        return $query_args;
    }


    /**
     * @param EE_Registration $registration
     * @return string
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws ReflectionException
     */
    public static function wait_list_checkout_url(EE_Registration $registration): string
    {
        return apply_filters(
            'FHEE__EED_Wait_Lists__wait_list_checkout_url',
            add_query_arg(
                [
                    'e_reg_url_link' => $registration->get_primary_registration()->reg_url_link(),
                    'step'           => 'attendee_information',
                ],
                EE_Registry::instance()->CFG->core->reg_page_url()
            ),
            $registration
        );
    }


    /**
     * @param EE_Event    $event
     * @param string|null $link_text
     * @return string
     * @throws EE_Error
     * @throws ReflectionException
     */
    public static function wait_list_registrations_list_table_link(EE_Event $event, string $link_text = null): string
    {
        return EEH_HTML::link(
            add_query_arg(
                [
                    'route'       => 'default',
                    '_reg_status' => EEM_Registration::status_id_wait_list,
                    'event_id'    => $event->ID(),
                ],
                defined('REG_ADMIN_URL') ? REG_ADMIN_URL : admin_url('admin.php?page=espresso_registrations')
            ),
            $link_text ?: esc_html__('Wait List Registrations', 'event_espresso'),
            esc_html__('View registrations on the wait list for this event', 'event_espresso'),
            '',
            'ee-reg-list-link ee-status-color--RWL',
            '',
            'target="_blank"'
        );
    }


    /**
     * @param EE_Checkout $checkout
     * @throws Exception
     * @throws Throwable
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
     * @throws Throwable
     */
    public static function allow_reg_payment(
        bool $allow_payment,
        EE_Registration $registration,
        bool $revisit = false
    ): bool {
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
     */
    public static function register_wait_list_log_type(array $log_type_labels = []): array
    {
        $log_type_labels[ Domain::LOG_TYPE_WAIT_LIST ] = esc_html__('Wait List', 'event_espresso');
        return $log_type_labels;
    }


    /**
     * @param Exception $exception
     * @param string    $file
     * @param string    $func
     * @param string    $line
     * @throws Exception|Throwable
     */
    protected static function handleException(
        Exception $exception,
        string $file = '',
        string $func = '',
        string $line = ''
    ) {
        if (WP_DEBUG) {
            new ExceptionStackTraceDisplay($exception);
        } else {
            EE_Error::add_error($exception->getMessage(), $file, $func, $line);
        }
    }
}
