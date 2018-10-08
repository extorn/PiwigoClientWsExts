<?php
/*
Plugin Name: PiwigoClientWsExts
Version: 1.0.1
Description: This plugin exposes more of the standard Piwigo website functionality for the PiwigoClient Android app (or others) to  make use of.
Plugin URI: http://piwigo.org/ext/extension_view.php
Author: Gareth Deli
Author URI: https://github.com/extorn/PiwigoClient-ServerExt
*/

/**
 * This is the main file of the plugin, called by Piwigo in "include/common.inc.php" line 137.
 * At this point of the code, Piwigo is not completely initialized, so nothing should be done directly
 * except define constants and event handlers (see http://piwigo.org/doc/doku.php?id=dev:plugins)
 */

defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');


// +-----------------------------------------------------------------------+
// | Define plugin constants                                               |
// +-----------------------------------------------------------------------+
global $prefixeTable;

define('PWG_CLI_EXT_ID',      basename(dirname(__FILE__)));
define('PWG_CLI_EXT_PATH' ,   PHPWG_PLUGINS_PATH . PWG_CLI_EXT_ID . '/');
define('PWG_CLI_EXT_TABLE',   $prefixeTable . 'PiwigoClientWsExts');
define('PWG_CLI_EXT_ADMIN',   get_root_url() . 'admin.php?page=plugin-' . PWG_CLI_EXT_ID);
define('PWG_CLI_EXT_PUBLIC',  get_absolute_root_url() . make_index_url(array('section' => 'PiwigoClientWsExts')) . '/');
define('PWG_CLI_EXT_DIR',     PHPWG_ROOT_PATH . PWG_LOCAL_DIR . 'PiwigoClientWsExts/');



// +-----------------------------------------------------------------------+
// | Add event handlers                                                    |
// +-----------------------------------------------------------------------+
// init the plugin
add_event_handler('init', 'PiwigoClientWsExts_init');

// file containing API function
$ws_file = PWG_CLI_EXT_PATH . 'include/ws_functions.inc.php';

// add API function
add_event_handler('ws_add_methods', 'PiwigoClientWsExts_ws_add_methods',
    EVENT_HANDLER_PRIORITY_NEUTRAL, $ws_file);

if (defined('IN_ADMIN')) {

  // file containing all admin handlers functions
  $admin_file = PWG_CLI_EXT_PATH . 'include/admin_events.inc.php';
  // admin plugins menu link
    add_event_handler('get_admin_plugin_menu_links', 'PiwigoClientWsExts_admin_plugin_menu_links',
      EVENT_HANDLER_PRIORITY_NEUTRAL, $admin_file);
}
else
{
   // file containing all public handlers functions
   $public_file = PWG_CLI_EXT_PATH . 'include/public_events.inc.php';
}

/**
 * plugin initialization
 *   - check for upgrades
 *   - unserialize configuration
 *   - load language
 */
function PiwigoClientWsExts_init()
{
  global $conf;

  // load plugin language file
  load_language('plugin.lang', PWG_CLI_EXT_PATH);

}
