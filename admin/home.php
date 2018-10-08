<?php
defined('PWG_CLI_EXT_PATH') or die('Hacking attempt!');

// +-----------------------------------------------------------------------+
// | Home tab                                                              |
// +-----------------------------------------------------------------------+

// send variables to template
$template->assign(array(
  'INTRO_CONTENT' => load_language('intro.html', PWG_CLI_EXT_PATH, array('return'=>true)),
  ));

// define template file
$template->set_filename('PiwigoClientServerExt_content', realpath(PWG_CLI_EXT_PATH . 'admin/template/home.tpl'));
