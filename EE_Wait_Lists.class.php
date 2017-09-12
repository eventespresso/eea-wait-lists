<?php
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\core\services\loaders\LoaderFactory;
use EventEspresso\core\services\loaders\LoaderInterface;
use EventEspresso\WaitList\domain\Domain;

if (! defined('EVENT_ESPRESSO_VERSION')) {
    exit();
}


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
     * @var LoaderInterface $loader
     */
    private static $loader;



    /**
     * @param LoaderInterface $loader
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws InvalidArgumentException
     */
    public function __construct(LoaderInterface $loader = null)
    {
        EE_Wait_Lists::$loader = $loader;
        parent::__construct();
    }



    /**
     * @return LoaderInterface
     * @throws InvalidArgumentException
     * @throws InvalidInterfaceException
     * @throws InvalidDataTypeException
     */
    public static function loader()
    {
        if (! EE_Wait_Lists::$loader instanceof LoaderInterface) {
            EE_Wait_Lists::$loader = LoaderFactory::getLoader();
        }
        return EE_Wait_Lists::$loader;
    }



    /**
     * this is not the place to perform any logic or add any other filter or action callbacks
     * this is just to bootstrap your addon; and keep in mind the addon might be DE-registered
     * in which case your callbacks should probably not be executed.
     * EED_Wait_Lists is the place for most filter and action callbacks (relating
     * the the primary business logic of your addon) to be placed
     *
     * @throws EE_Error
     * @throws DomainException
     */
    public static function register_addon()
    {
        // register addon via Plugin API
        EE_Register_Addon::register(
            'Wait_Lists',
            array(
                'version'          => EE_WAIT_LISTS_VERSION,
                'plugin_slug'      => 'eea_wait_lists',
                'min_core_version' => Domain::CORE_VERSION_REQUIRED,
                'main_file_path'   => EE_WAIT_LISTS_PLUGIN_FILE,
                'namespace'        => array(
                    'FQNS' => 'EventEspresso\WaitList',
                    'DIR'  => __DIR__,
                ),
                'module_paths'     => array(
                    Domain::pluginPath() . 'domain/services/modules/EED_Wait_Lists.module.php',
                    Domain::pluginPath() . 'domain/services/modules/EED_Wait_Lists_Messages.module.php',
                ),
                'message_types' => array_merge(
                    self::get_message_type_registration_options_for(),
                    self::get_message_type_registration_options_for(
                        Domain::MESSAGE_TYPE_WAIT_LIST_DEMOTION,
                        'EE_Registration_Demoted_To_Waitlist_message_type.class.php'
                    ),
                    self::get_message_type_registration_options_for(
                        Domain::MESSAGE_TYPE_REGISTRATION_ADDED_TO_WAIT_LIST,
                        'EE_Registration_Added_To_Waitlist_message_type.class.php'
                    )
                ),
                // if plugin update engine is being used for auto-updates. not needed if PUE is not being used.
                'pue_options'      => array(
                    'pue_plugin_slug' => 'eea-wait-lists',
                    'plugin_basename' => Domain::pluginBasename(),
                    'checkPeriod'     => '24',
                    'use_wp_update'   => false,
                ),
            )
        );
    }


    /**
     * Returns the message type options array for registering the message type.
     *
     * @param string $message_type
     * @param string $message_type_filename
     * @return array
     * @throws DomainException
     */
    public static function get_message_type_registration_options_for(
        $message_type = Domain::MESSAGE_TYPE_WAIT_LIST_PROMOTION,
        $message_type_filename = 'EE_Waitlist_Can_Register_message_type.class.php'
    ) {
        return array(
            $message_type => array(
                'mtfilename'                                       =>
                    $message_type_filename,
                'autoloadpaths'                                    => array(
                    Domain::pluginPath() . 'domain/services/messages/',
                    Domain::pluginPath() . 'domain/entities'
                ),
                'messengers_to_activate_with'                      => array('email'),
                'messengers_to_validate_with'                      => array('email'),
                'force_activation'                                 => true,
                'messengers_supporting_default_template_pack_with' => array('email'),
                'base_path_for_default_templates'                  => Domain::pluginPath()
                                                                      . 'views/messages/templates/',
            )
        );
    }



    /**
     * Register things that have to happen early in loading.
     *
     * @throws DomainException
     */
    public function after_registration()
    {
        $this->_register_custom_shortcode_library();
    }



    /**
     * Takes care of registering the custom shortcode library for this add-on
     *
     * @throws DomainException
     */
    protected function _register_custom_shortcode_library()
    {
        //ya intentionally using closures here.  If client code want's this library to not be registered there's
        //facility for deregistering via the provided api.  This forces client code to use that api.
        add_action(
            'EE_Brewing_Regular___messages_caf',
            function () {
                EE_Register_Messages_Shortcode_Library::register(
                    'recipient_waitlist_shortcode_library',
                    array(
                        'name'                    => 'recipient_waitlist',
                        'autoloadpaths'           => Domain::pluginPath() . 'domain/services/messages/',
                    )
                );
            },
            20
        );
        //make sure the shortcode library is deregistered if this add-on is deregistered.
        add_action(
            'AHEE__EE_Register_Addon__deregister__after',
            function ($addon_name) {
                if ($addon_name === 'Wait_Lists') {
                    EE_Register_Messages_Shortcode_Library::deregister('recipient_waitlist_shortcode_library');
                }
            }
        );
    }
}
// End of file EE_Wait_Lists.class.php
// Location: wp-content/plugins/eea-wait-lists/EE_Wait_Lists.class.php
