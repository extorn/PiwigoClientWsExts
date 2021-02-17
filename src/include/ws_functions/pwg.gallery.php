<?php
// +-----------------------------------------------------------------------+
// | Piwigo Client - A plugin for Piwigo - a PHP based photo gallery       |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2020 Gareth Deli                                    |
// +-----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify  |
// | it under the terms of the GNU General Public License as published by  |
// | the Free Software Foundation                                          |
// |                                                                       |
// | This program is distributed in the hope that it will be useful, but   |
// | WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU      |
// | General Public License for more details.                              |
// |                                                                       |
// | You should have received a copy of the GNU General Public License     |
// | along with this program; if not, write to the Free Software           |
// | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, |
// | USA.                                                                  |
// +-----------------------------------------------------------------------+

// +-----------------------------------------------------------------------+
// | UTILITIES                                                             |
// +-----------------------------------------------------------------------+

/**
 * API method
 * Retrieves the current gallery config details
 * @param mixed[] $params
 */
function ws_gallery_config($params, &$service) {
    global $user, $conf;

    $images = array();

    $columns='param,value';
    if ($params['show_comments'])
    {
        $columns .= ',comment';
    }   
    $query = '
      SELECT '.$columns.' FROM  '.CONFIG_TABLE.'
        WHERE param in ("gallery_title", "gallery_locked", "rate", "rate_anonymous", "activate_comments", "comments_forall", "comments_author_mandatory", "comments_email_mandatory", "user_can_edit_comment", "user_can_delete_comment")
        ORDER BY param ASC;';

      $result = pwg_query($query);

      $configItems = array();
      while ($row = pwg_db_fetch_assoc($result))
      {
        $configItems[] = $row;
      }
      
      $sites_query = 'SELECT id, galleries_url from '.SITES_TABLE.';';
      $sites_result = pwg_query($sites_query);
      $sites = array();
      while($sites_row = pwg_db_fetch_assoc($sites_result))
      {
          $site_path = preg_replace('#^\./(.*)\/$#','$1',$sites_row['galleries_url']);
          $sites[] = array('id'   => $sites_row['id'],
              'path' => $site_path);
      }
      
      return array(
          'configItems' => $configItems,
          'sites' => $sites);
      
      
}

?>
