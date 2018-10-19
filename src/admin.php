<?php
/**
 * This is the main administration page, if you have only one admin page you can put
 * directly its code here or using the tabsheet system like below
 */

defined('PWG_CLI_EXT_PATH') or die('Hacking attempt!');

global $template, $page, $conf;


// get current tab
$page['tab'] = isset($_GET['tab']) ? $_GET['tab'] : $page['tab'] = 'home';

// plugin tabsheet is not present on photo page
if ($page['tab'] != 'photo')
{
  // tabsheet
  include_once(PHPWG_ROOT_PATH.'admin/include/tabsheet.class.php');
  $tabsheet = new tabsheet();
  $tabsheet->set_id('PiwigoClientWsExts');

  $tabsheet->add('home', l10n('Welcome'), PWG_CLI_EXT_ADMIN . '-home');
  //$tabsheet->add('config', l10n('Configuration'), PWG_CLI_EXT_ADMIN . '-config');
  $tabsheet->select($page['tab']);
  $tabsheet->assign();
}

// include page
include(PWG_CLI_EXT_PATH . 'admin/' . $page['tab'] . '.php');

// template vars
$template->assign(array(
  'PWG_CLI_EXT_PATH'=> PWG_CLI_EXT_PATH, // used for images, scripts, ... access
  'PWG_CLI_EXT_ABS_PATH'=> realpath(PWG_CLI_EXT_PATH), // used for template inclusion (Smarty needs a real path)
  'PWG_CLI_EXT_ADMIN' => PWG_CLI_EXT_ADMIN,
  ));

// send page content
$template->assign_var_from_handle('ADMIN_CONTENT', 'PiwigoClientWsExts_content');

?>