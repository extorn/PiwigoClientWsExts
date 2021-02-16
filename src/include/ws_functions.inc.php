<?php
defined('PWG_CLI_EXT_PATH') or die('Hacking attempt!');

include_once(PHPWG_ROOT_PATH.'include/common.inc.php');
include_once(PHPWG_ROOT_PATH.'include/ws_core.inc.php');

function PiwigoClientWsExts_ws_add_methods($arr)
{
  global $conf, $user;
  $service = &$arr[0];

  include_once(PHPWG_ROOT_PATH.'include/ws_functions.inc.php');
  include_once(PWG_CLI_EXT_PATH.'include/functions.inc.php');
  $ws_functions_root = PWG_CLI_EXT_PATH.'include/ws_functions/';

  $f_params = array(
      'f_min_rate' => array('default'=>null,
                            'type'=>WS_TYPE_FLOAT),
      'f_max_rate' => array('default'=>null,
                            'type'=>WS_TYPE_FLOAT),
      'f_min_hit' =>  array('default'=>null,
                            'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
      'f_max_hit' =>  array('default'=>null,
                            'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
      'f_min_ratio' => array('default'=>null,
                             'type'=>WS_TYPE_FLOAT|WS_TYPE_POSITIVE),
      'f_max_ratio' => array('default'=>null,
                             'type'=>WS_TYPE_FLOAT|WS_TYPE_POSITIVE),
      'f_max_level' => array('default'=>null,
                             'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
      'f_min_date_available' => array('default'=>null),
      'f_max_date_available' => array('default'=>null),
      'f_min_date_created' =>   array('default'=>null),
      'f_max_date_created' =>   array('default'=>null),
      );

  // TAGS FUNCTIONS

  $service->addMethod(
            'piwigo_client.tags.getImages',
        'ws_tags_getImages_cliext',
        array_merge(array(
          'tag_id' =>       array('default'=>null,
                                  'flags'=>WS_PARAM_FORCE_ARRAY,
                                  'type'=>WS_TYPE_ID),
          'tag_url_name' => array('default'=>null,
                                  'flags'=>WS_PARAM_FORCE_ARRAY),
          'tag_name' =>     array('default'=>null,
                                  'flags'=>WS_PARAM_FORCE_ARRAY),
          'tag_mode_and' => array('default'=>false,
                                  'type'=>WS_TYPE_BOOL),
          'per_page' =>     array('default'=>100,
                                  'maxValue'=>$conf['ws_max_images_per_page'],
                                  'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
          'page' =>         array('default'=>0,
                                  'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
          'order' =>        array('default'=>null,
                                  'info'=>'id, file, name, hit, rating_score, date_creation, date_available, random'),
          'pwg_token' =>  array('default'=>null),
          ), $f_params),
        'PiwigoClient: Returns elements for the corresponding tags (but logs the user\'s access). Fill at least tag_id, tag_url_name or tag_name.
        <br/>Builds on and is a direct replacement for pwg.images.getInfo',
        $ws_functions_root . 'pwg.tags.php'
      );

  // CATEGORIES FUNCTIONS

  $service->addMethod(
        'piwigo_client.categories.getImages',
        'ws_categories_getImages_cliext',
        array_merge(array(
          'cat_id' =>     array('default'=>null,
                                'flags'=>WS_PARAM_FORCE_ARRAY,
                                'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
          'recursive' =>  array('default'=>false,
                                'type'=>WS_TYPE_BOOL),
          'per_page' =>   array('default'=>100,
                                'maxValue'=>$conf['ws_max_images_per_page'],
                                'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
          'page' =>       array('default'=>0,
                                'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
          'order' =>      array('default'=>null,
                                'info'=>'id, file, name, hit, rating_score, date_creation, date_available, random'),
          'pwg_token' =>  array('default'=>null)
          ), $f_params),
        'PiwigoClient: Returns elements for the corresponding categories (but logs the user\'s access).
        <br/>Builds on and is a direct replacement for pwg.images.getInfo
        <br><b>cat_id</b> can be empty if <b>recursive</b> is true.
        <br><b>order</b> comma separated fields for sorting',
        $ws_functions_root . 'pwg.categories.php'
      );
  
  $service->addMethod(
      'piwigo_client.categories.getList',
      'ws_categories_getList_cliext',
      array(
          'cat_id' =>       array('default'=>null,
              'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE,
              'info'=>'Parent category. "0" or empty for root.'),
          'recursive' =>    array('default'=>false,
              'type'=>WS_TYPE_BOOL),
          'public' =>       array('default'=>false,
              'type'=>WS_TYPE_BOOL),
          'tree_output' =>  array('default'=>false,
              'type'=>WS_TYPE_BOOL),
          'fullname' =>     array('default'=>false,
              'type'=>WS_TYPE_BOOL),
          'thumbnail_size' => array(
              'default' => IMG_THUMB,
              'info' => implode(',', array_keys(ImageStdParams::get_defined_type_map()))),
          'pwg_token' =>  array('default'=>null)
      ),
      'Extends the standard Returns a list of categories to support Extended Descriptions plugin tags.',
      $ws_functions_root . 'pwg.categories.php'
      );
  
  $service->addMethod(
      'piwigo_client.categories.getAdminList',
      'ws_categories_getAdminList_cliext',
      null,
      'Extends the standard - Get albums list as displayed on admin page - to support Extended Descriptions plugin tags.',
      $ws_functions_root . 'pwg.categories.php',
      array('admin_only'=>true)
      );

  // IMAGES FUNCTIONS

  $service->addMethod(
        'piwigo_client.images.getInfo',
        'ws_images_getInfo_cliext',
        array(
          'image_id' =>           array('type'=>WS_TYPE_ID),
          'comments_page' =>      array('default'=>0,
                                        'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
          'comments_per_page' =>  array('default'=>$conf['nb_comment_page'],
                                        'maxValue'=>2*$conf['nb_comment_page'],
                                        'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
          ),
        'PiwigoClient: Returns information about an image  (but logs the user\'s access)
        <br/>Builds on and is a direct replacement for pwg.images.getInfo',
        $ws_functions_root . 'pwg.images.php'
      );

  $service->addMethod(
        'piwigo_client.images.getOrphans',
        'ws_images_listOrphans_cliext',
        array(
            'per_page' =>     array('default'=>100,
                                    'maxValue'=>$conf['ws_max_images_per_page'],
                                    'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
            'page' =>         array('default'=>0,
                                    'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
          ),
        'PiwigoClient: Lists orphaned images',
        $ws_functions_root . 'pwg.images.php',
        array('admin_only'=>true, 'post_only'=>false)
      );

  // FAVORITES FUNCTIONS

  $service->addMethod(
          'piwigo_client.favorites.getImages',
          'ws_favorites_getImages_cliext',
          array_merge(array(
            'per_page' =>   array('default'=>100,
                                  'maxValue'=>$conf['ws_max_images_per_page'],
                                  'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
            'page' =>       array('default'=>0,
                                  'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
            'order' =>      array('default'=>null,
                                  'info'=>'id, file, name, hit, rating_score, date_creation, date_available, random'),
            'pwg_token' =>  array('default'=>null)
            ), $f_params),
          'PiwigoClient: Retrieves a block of the present user\'s favorite images (result is identical format to categories.getImages).',
          $ws_functions_root . 'pwg.favorites.php',
          array('post_only'=>false)
        );

  $service->addMethod(
        'piwigo_client.favorites.addImage',
        'ws_favorites_add_image_cliext',
        array(
          'image_id' => array('type'=>WS_TYPE_ID)
          ),
        'PiwigoClient: Adds an image to the present user\'s list of favorites.',
        $ws_functions_root . 'pwg.favorites.php',
        array('post_only'=>true)
      );

      $service->addMethod(
        'piwigo_client.favorites.removeImage',
        'ws_favorites_remove_image_cliext',
        array(
          'image_id' => array('type'=>WS_TYPE_ID)
          ),
        'PiwigoClient: Removes an image from the present user\'s list of favorites.',
        $ws_functions_root . 'pwg.favorites.php',
        array('post_only'=>true)
      );
      $service->addMethod(
        'piwigo_client.favorites.getList',
        'ws_favorites_get_list_cliext',
        array(
          'per_page' =>     array('default'=>100,
                                          'maxValue'=>$conf['ws_max_images_per_page'],
                                          'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
          'page' =>         array('default'=>0,
                                  'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
          'pwg_token' =>  array('default'=>null)
          ),
        'PiwigoClient: List ids for all images on the present user\'s list of favorites.',
        $ws_functions_root . 'pwg.favorites.php',
        array('post_only'=>false)
      );
      $service->addMethod(
        'piwigo_client.favorites.removeAll',
        'ws_favorites_remove_all_cliext',
        array(),
        'PiwigoClient: Remove all images from the present user\'s list of favorites.',
        $ws_functions_root . 'pwg.favorites.php',
        array('post_only'=>true)
      );


    //UPLOAD FUNCTIONS
    $service->addMethod(
        'piwigo_client.upload.clean',
        'ws_upload_clean',
        array(
            'pwg_token' =>            array(),
        ),
        'PiwigoClient: Clears all failed partial uploads made by PiwigoClient from the server',
        $ws_functions_root . 'pwg.upload.php',
        array(
            'post_only'=>true,
            'admin_only' => true, // you can restrict access to admins only
        )
      );

      //GENERAL INFO FUNCTIONS
      $service->addMethod(
          'piwigo_client.getPluginDetails',
          'ws_plugin_version',
          array(
          ),
          'PiwigoClient: Retrieves information about this plugin and its current settings to clients',
          $ws_functions_root . 'piwigo.client.php',
          array(
              'post_only'=>false,
          )
        );

        $service->addMethod(
            'piwigo_client.gallery.getConfig',
            'ws_gallery_config',
            array(
                        'show_comments' => array('default'=>false,
                                                          'type'=>WS_TYPE_BOOL),
                    ),
            'PiwigoClient: Retrieves information about the PIWIGO gallery and relevant settings for clients
            <br/>Set the show_comments parameter to true if you wish to see a definition for the values returned',
            $ws_functions_root . 'pwg.gallery.php',
            array(
                'post_only'=>false,
            )
          );


  /* EXAMPLE USAGE

  // only the first two parameters are mandatory
  $service->addMethod(
    'pwg.PHPinfo', // method name
    'ws_php_info', // linked PHP function
    array( // list of parameters
      'what' => array(
        'default' => 'INFO_ALL', // default value
        'info' => 'This parameter has a default value', // parameter description
        ),
      'ids' => array(
        'flags' => WS_PARAM_OPTIONAL|WS_PARAM_FORCE_ARRAY, // flags are WS_PARAM_OPTIONAL, WS_PARAM_ACCEPT_ARRAY, WS_PARAM_FORCE_ARRAY
        'type' => WS_TYPE_INT|WS_TYPE_POSITIVE|WS_TYPE_NOTnull // types are WS_TYPE_BOOL, WS_TYPE_INT, WS_TYPE_FLOAT, WS_TYPE_POSITIVE, WS_TYPE_NOTNULL, WS_TYPE_ID
        'info' => 'This one must be an array',
        ),
      'count' => array(
        'flags' => WS_PARAM_OPTIONAL,
        'type' => WS_TYPE_INT|WS_TYPE_POSITIVE,
        'maxValue' => 100, // maximum value for ints and floats
        ),
      ),
    'Returns phpinfo', // method description
    null // file to include after param check and before function exec
    array(
      'hidden' => false, // you can hide your method from reflection.getMethodList method
      'admin_only' => true, // you can restrict access to admins only
      'post_only' => false, // you can disallow GET resquests for this method
      )
    );

    */
}

function ws_php_info($params, &$service)
{
  return phpinfo(constant($params['what']));
}

?>