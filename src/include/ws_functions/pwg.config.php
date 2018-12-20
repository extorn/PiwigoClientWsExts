<?php
// +-----------------------------------------------------------------------+
// | Piwigo - a PHP based photo gallery                                    |
// +-----------------------------------------------------------------------+
// | Copyright(C) 2008-2016 Piwigo Team                  http://piwigo.org |
// | Copyright(C) 2003-2008 PhpWebGallery Team    http://phpwebgallery.net |
// | Copyright(C) 2002-2003 Pierrick LE GALL   http://le-gall.net/pierrick |
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

    $query = '
      SELECT param,value,comment FROM  '.CONFIG_TABLE.'
        WHERE param in ("gallery_title", "gallery_locked", ""rate", "rate_anonymous", "activate_comments", "comments_forall", "comments_author_mandatory", "comments_email_mandatory", "user_can_edit_comment", "user_can_delete_comment")
        ORDER BY param ASC;';

      $result = pwg_query($query);

      $configItems = array();
      while ($row = pwg_db_fetch_assoc($result))
      {
        $configItems[] = $row;
      }

      return array(
          'configItems' => $configItems);
}

?>
