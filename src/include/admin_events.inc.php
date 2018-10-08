<?php
defined('PWG_CLI_EXT_PATH') or die('Hacking attempt!');

/**
 * admin plugins menu link
 */
function PiwigoClientWsExts_admin_plugin_menu_links($menu)
{
  $menu[] = array(
    'NAME' => l10n('PiwigoClientWsExts'),
    'URL' => PWG_CLI_EXT_ADMIN,
    );

  return $menu;
}

?>