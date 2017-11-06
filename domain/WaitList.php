<?php

namespace EventEspresso\WaitList\domain;

use DomainException;
use EE_Addon;
use EE_Dependency_Map;
use EE_Error;
use EE_Register_Addon;
use EE_Register_Messages_Shortcode_Library;
use EventEspresso\core\domain\DomainInterface;
use EventEspresso\core\domain\RequiresDependencyMapInterface;
use EventEspresso\core\exceptions\InvalidDataTypeException;
use EventEspresso\core\exceptions\InvalidEntityException;
use EventEspresso\core\exceptions\InvalidInterfaceException;
use EventEspresso\WaitList\domain\services\collections\WaitListEventsCollection;
use InvalidArgumentException;
use ReflectionException;

defined('EVENT_ESPRESSO_VERSION') || exit();


/**
 * Class  WaitListAddon
 *
 * @package               Event Espresso
 * @subpackage            eea-wait-lists
 * @author                Brent Christensen
 */
Class  WaitList extends EE_Addon implements RequiresDependencyMapInterface
{


    /**
     * @var EE_Dependency_Map $dependency_map
 */
    private $dependency_map;


    /**
     * @var Domain $domain
     */
    private $domain;



    /**
     * @param DomainInterface   $domain
     * @param EE_Dependency_Map $dependency_map
     */
    public function __construct(DomainInterface $domain, EE_Dependency_Map $dependency_map)
    {
        $this->domain         = $domain;
        $this->setDependencyMap($dependency_map);
        parent::__construct();
    }


    /**
     * @param EE_Dependency_Map $dependency_map
     */
    public function setDependencyMap($dependency_map)
    {
        $this->dependency_map = $dependency_map;
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
     * @throws InvalidDataTypeException
     * @throws InvalidInterfaceException
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function register()
    {
        // register addon via Plugin API
        EE_Register_Addon::register(
            'EventEspresso\WaitList\domain\WaitList',
            array(
                'version'          => EE_WAIT_LISTS_VERSION,
                'plugin_slug'      => 'eea_wait_lists',
                'min_core_version' => Domain::CORE_VERSION_REQUIRED,
                'main_file_path'   => EE_WAIT_LISTS_PLUGIN_FILE,
                'module_paths'     => array(
                    $this->domain->pluginPath() . 'domain/services/modules/EED_Wait_Lists.module.php',
                    $this->domain->pluginPath() . 'domain/services/modules/EED_Wait_Lists_Messages.module.php',
                ),
                'message_types' => array_merge(
                    $this->messageTypeRegistrationOptions(
                        Domain::MESSAGE_TYPE_WAIT_LIST_PROMOTION,
                        'EE_Waitlist_Can_Register_message_type.class.php'
                    ),
                    $this->messageTypeRegistrationOptions(
                        Domain::MESSAGE_TYPE_WAIT_LIST_DEMOTION,
                        'EE_Registration_Demoted_To_Waitlist_message_type.class.php'
                    ),
                    $this->messageTypeRegistrationOptions(
                        Domain::MESSAGE_TYPE_REGISTRATION_ADDED_TO_WAIT_LIST,
                        'EE_Registration_Added_To_Waitlist_message_type.class.php'
                    )
                ),
                // if plugin update engine is being used for auto-updates. not needed if PUE is not being used.
                'pue_options'      => array(
                    'pue_plugin_slug' => 'eea-wait-lists',
                    'plugin_basename' => $this->domain->pluginBasename(),
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
    public function messageTypeRegistrationOptions($message_type, $message_type_filename) {
        return array(
            $message_type => array(
                'mtfilename'                                       => $message_type_filename,
                'autoloadpaths'                                    => array(
                    $this->domain->pluginPath() . 'domain/services/messages/',
                    $this->domain->pluginPath() . 'domain/entities'
                ),
                'messengers_to_activate_with'                      => array('email'),
                'messengers_to_validate_with'                      => array('email'),
                'force_activation'                                 => true,
                'messengers_supporting_default_template_pack_with' => array('email'),
                'base_path_for_default_templates'                  => $this->domain->pluginPath()
                                                                      . 'views/messages/templates/',
            )
        );
    }



    /**
     * Register things that have to happen early in loading.
     *
     * @throws DomainException
     * @throws InvalidInterfaceException
     * @throws InvalidEntityException
     * @throws EE_Error
     * @throws InvalidDataTypeException
     * @throws InvalidArgumentException
     */
    public function after_registration()
    {
        $this->registerDependencies();
        $this->registerCustomShortcodeLibrary();
    }



    /**
     * @return void
     * @throws InvalidArgumentException
     * @throws InvalidDataTypeException
     * @throws DomainException
     * @throws InvalidInterfaceException
     * @throws InvalidEntityException
     * @throws EE_Error
     */
    protected function registerDependencies()
    {
        $this->dependency_map->add_alias(
            'EventEspresso\WaitList\domain\services\collections\WaitListEventsCollection',
            'EventEspresso\core\services\collections\Collection',
            'EventEspresso\WaitList\domain\services\event\WaitListMonitor'
        );
        EE_Dependency_Map::register_class_loader(
            'EventEspresso\WaitList\domain\services\collections\WaitListEventsCollection',
            function ()
            {
                return new WaitListEventsCollection();
            }
        );
        $this->dependency_map->registerDependencies(
            'EventEspresso\WaitList\domain\services\event\WaitListMonitor',
            array(
                'EventEspresso\WaitList\domain\services\collections\WaitListEventsCollection'      => EE_Dependency_Map::load_from_cache,
                'EventEspresso\WaitList\domain\services\event\WaitListEventMeta'                   => EE_Dependency_Map::load_from_cache,
                'EventEspresso\WaitList\domain\services\collections\WaitListFormHandlerCollection' => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\services\commands\CommandBusInterface'                         => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\services\loaders\LoaderInterface'                              => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\services\notices\NoticeConverterInterface'                     => EE_Dependency_Map::load_from_cache,
            )
        );
        $this->dependency_map->registerDependencies(
            'EventEspresso\WaitList\domain\services\checkout\WaitListCheckoutMonitor',
            array(
                'EventEspresso\WaitList\domain\services\registration\WaitListRegistrationMeta' =>
                    EE_Dependency_Map::load_from_cache,
            )
        );
        $this->dependency_map->registerDependencies(
            'EventEspresso\WaitList\domain\services\commands\PromoteWaitListRegistrantsCommandHandler',
            array(
                'EEM_Registration'                                               => EE_Dependency_Map::load_from_cache,
                'EE_Capabilities'                                                => EE_Dependency_Map::load_from_cache,
                'EEM_Change_Log'                                                 => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\services\notices\NoticesContainerInterface'  => EE_Dependency_Map::load_from_cache,
                'EventEspresso\WaitList\domain\services\event\WaitListEventMeta' => EE_Dependency_Map::load_from_cache,
            )
        );
        $this->dependency_map->registerDependencies(
            'EventEspresso\WaitList\domain\services\commands\CreateWaitListRegistrationsCommandHandler',
            array(
                'EventEspresso\WaitList\domain\services\event\WaitListEventMeta'               => EE_Dependency_Map::load_from_cache,
                'EventEspresso\WaitList\domain\services\registration\WaitListRegistrationMeta' => EE_Dependency_Map::load_from_cache,
                'EEM_Registration'                                                             => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\services\commands\CommandBusInterface'                     => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\services\commands\CommandFactoryInterface'                 => EE_Dependency_Map::load_from_cache,
                'EventEspresso\core\services\notices\NoticesContainerInterface'                => EE_Dependency_Map::load_from_cache,
            )
        );
        $this->dependency_map->registerDependencies(
            'EventEspresso\WaitList\domain\services\commands\UpdateRegistrationWaitListMetaDataCommandHandler',
            array(
                'EventEspresso\WaitList\domain\services\event\WaitListEventMeta'               => EE_Dependency_Map::load_from_cache,
                'EventEspresso\WaitList\domain\services\registration\WaitListRegistrationMeta' => EE_Dependency_Map::load_from_cache,
            )
        );
        $this->dependency_map->registerDependencies(
            'EventEspresso\WaitList\domain\services\commands\CalculateEventSpacesAvailableCommandHandler',
            array(
                'EventEspresso\WaitList\domain\services\event\WaitListEventMeta' => EE_Dependency_Map::load_from_cache,
            )
        );
        $this->dependency_map->registerDependencies(
            'EventEspresso\WaitList\domain\services\forms\EventEditorWaitListMetaBoxFormHandler',
            array(
                null,
                'EventEspresso\WaitList\domain\services\event\WaitListEventMeta' => EE_Dependency_Map::load_from_cache,
                'EEM_Registration'                                               => EE_Dependency_Map::load_from_cache,
                'EE_Registry'                                                    => EE_Dependency_Map::load_from_cache,
            )
        );
        $this->dependency_map->registerDependencies(
            'EventEspresso\WaitList\domain\services\forms\WaitListForm',
            array(
                null,
                null,
                'EventEspresso\WaitList\domain\services\event\WaitListEventMeta' => EE_Dependency_Map::load_from_cache,
            )
        );
        $this->dependency_map->registerDependencies(
            'EventEspresso\WaitList\domain\services\forms\WaitListFormHandler',
            array(null, 'EE_Registry' => EE_Dependency_Map::load_from_cache)
        );
        $this->dependency_map->registerDependencies(
            'EventEspresso\WaitList\domain\services\commands\CreateWaitListRegistrationsCommand',
            array(null, null, null, null, 'EEM_Ticket' => EE_Dependency_Map::load_from_cache)
        );
        $this->dependency_map->registerDependencies(
            'EventEspresso\WaitList\domain\services\registration\WaitListRegistrationConfirmation',
            array(
                'EventEspresso\WaitList\domain\services\registration\WaitListRegistrationMeta' => EE_Dependency_Map::load_from_cache,
                'EE_Request' => EE_Dependency_Map::load_from_cache,
            )
        );
    }



    /**
     * Takes care of registering the custom shortcode library for this add-on
     *
     * @throws DomainException
     */
    protected function registerCustomShortcodeLibrary()
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
                        'autoloadpaths'           => $this->domain->pluginPath() . 'domain/services/messages/',
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
