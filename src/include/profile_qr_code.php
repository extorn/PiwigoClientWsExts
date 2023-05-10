<?php
/*
Plugin Name: @plugin_name@
Version: @plugin_version@
Description: This plugin exposes more of the standard Piwigo website functionality for the PiwigoClient Android app (or others) to  make use of.
Plugin URI: https://piwigo.org/ext/extension_view.php?eid=880
Author: Gareth Deli
Author URI: https://github.com/extorn/@plugin_name@
Has Settings: true
*/

defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

global $conf;
/*
 * Called when the profile.tpl template is loaded into the $template var
 * we add a pre-filter for the template, called prior to render.
 * we then assign our template variable we created
 */
function add_user_qr_code($userdata) {
    global $template;

    // get the user details for the qr code (password is a hash of the password so can't use it).
    $current_password = '';//get_current_password($userdata['id']);
    $current_username = $userdata['username'];
    $server_uri = get_absolute_root_url();

	// Build a QR Code and assign it to a template var
	$template->assign('qr_img_src',gen_qr_code($server_uri, $current_username, $current_password));
	
	// lets add an extension tag to the standard profile data
	$template->set_prefilter('profile_content','extend_profile_content_template',50);
	// now lets load our template into that extension tag
	$template->set_filename('PWGEXT_qr_code',PWG_CLI_EXT_PATH.'templates/qr_code.tpl');
	$template->assign_var_from_handle('PROFILE_EXT','PWGEXT_qr_code');

	// all done - it's now visible to the user!
}

function extend_profile_content_template($content, $smarty) {
	return '{$PROFILE_EXT}'.$content;
}

function get_current_password($user_id) {

    global $conf;
	
    $query = '
              SELECT '.$conf['user_fields']['password'].' AS password
                FROM '.USERS_TABLE.'
                WHERE '.$conf['user_fields']['id'].' = \''.$user_id.'\'
              ;';
    list($current_password) = pwg_db_fetch_row(pwg_query($query));
    return $current_password;
}

/*
 * Generate a QR code of the site address, username and password for
 * the current user.
 */
function gen_qr_code($server_uri,$username, $password) {
        //include_once(PWG_CLI_EXT_PATH . 'include/libs/phpqrcode/qrlib.php');
        include_once(PWG_CLI_EXT_PATH . 'include/libs/phpqrcode/phpqrcode.php');
        //include_once(PWG_CLI_EXT_PATH . 'include/libs/phpqrcode/qrconfig.php');
        $dataText = 'https://api-8938561204297001672-604498.firebaseapp.com/config';
        $dataText .= '?';
        $dataText .= 's='.urlencode($server_uri);
        $dataText .= '&';
        $dataText .= 'u='.urlencode($username);
        // $dataText .= '&';
        // $dataText .= 'p='.urlencode($password);
        $svgTagId  = 'id-of-svg';
        $saveToFile = false;
        $imageWidth = 250; // px

        // create an output redirect to capture the image data when rendered by QRcode lib
        ob_start ();
        QRcode::png($dataText);
        // capture the output in a variable
        $image_data = ob_get_contents();
        ob_end_clean();
        // end the output redirect

        // base64 encode the image so it can be rendered into the html page.
        return base64_encode($image_data);
}

add_event_handler('load_profile_in_template', 'add_user_qr_code');

?>
