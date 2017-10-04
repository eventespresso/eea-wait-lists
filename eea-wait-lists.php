<?php
/*
  Plugin Name: Event Espresso - Wait Lists (EE4.9+)
  Plugin URI: http://www.eventespresso.com
  Description: The Event Espresso Wait Lists Addon maximizes event sales by allowing attendees to partially register for a datetime or ticket that has sold out, but then complete the registration process later after spaces have become available due to venue change, additional tickets, non-payment, cancellation, etc.
  Version: 1.0.0.dev.063
  Author: Event Espresso
  Author URI: http://www.eventespresso.com
  Copyright 2014 Event Espresso (email : support@eventespresso.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA02110-1301USA
 *
 * ------------------------------------------------------------------------
 *
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Event Espresso
 * @ copyright	    (c) 2008-2014 Event Espresso  All Rights Reserved.
 * @ license		http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link			http://www.eventespresso.com
 * @ version	 	EE4
 *
 * ------------------------------------------------------------------------
 */
// define versions and this file
define('EE_WAIT_LISTS_VERSION', '1.0.0.rc.000');
define('EE_WAIT_LISTS_PLUGIN_FILE', __FILE__);
/**
 *    captures plugin activation errors for debugging
 */
function espresso_wait_lists_plugin_activation_errors()
{
    if (WP_DEBUG && ob_get_length() > 0) {
        $activation_errors = ob_get_contents();
        file_put_contents(
            EVENT_ESPRESSO_UPLOAD_DIR . 'logs' . DS . 'espresso_wait_lists_plugin_activation_errors.html',
            $activation_errors
        );
    }
}

add_action('activated_plugin', 'espresso_wait_lists_plugin_activation_errors');



/**
 *    registers addon with EE core
 */
function load_espresso_wait_lists()
{
    if (class_exists('EE_Addon') && class_exists('EventEspresso\core\domain\DomainBase')) {
        espresso_load_required(
            'EventEspresso\WaitList\domain\Domain',
            plugin_dir_path(EE_WAIT_LISTS_PLUGIN_FILE) . 'domain/Domain.php'
        );
        EventEspresso\WaitList\domain\Domain::init(
            EE_WAIT_LISTS_PLUGIN_FILE,
            EE_WAIT_LISTS_VERSION
        );
        espresso_load_required(
            'EE_Wait_Lists',
            EventEspresso\WaitList\domain\Domain::pluginPath() . 'EE_Wait_Lists.class.php'
        );
        EE_Wait_Lists::register_addon();
    } else {
        add_action('admin_notices', 'espresso_wait_lists_activation_error');
    }
}

add_action('AHEE__EE_System__load_espresso_addons', 'load_espresso_wait_lists');



/**
 *    verifies that addon was activated
 */
function espresso_wait_lists_activation_check()
{
    if (! did_action('AHEE__EE_System__load_espresso_addons')) {
        add_action('admin_notices', 'espresso_wait_lists_activation_error');
    }
}

add_action('init', 'espresso_wait_lists_activation_check', 1);



/**
 *    displays activation error admin notice
 */
function espresso_wait_lists_activation_error()
{
    unset($_GET['activate'], $_REQUEST['activate']);
    if (! function_exists('deactivate_plugins')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }
    deactivate_plugins(plugin_basename(EE_WAIT_LISTS_PLUGIN_FILE));
    ?>
    <div class="error">
        <p><?php printf(
                esc_html__(
                    'Event Espresso Wait Lists could not be activated. Please ensure that Event Espresso version %1$s or higher is running',
                    'event_espresso'
                ),
                '4.9.39.p'
            );
            ?></p>
    </div>
    <?php
}



// End of file espresso_wait_lists.php
// Location: wp-content/plugins/eea-wait-lists/espresso_wait_lists.php
