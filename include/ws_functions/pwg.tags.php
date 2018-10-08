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
 * Returns a list of images for tags
 * @param mixed[] $params
 *    @option int[] tag_id (optional)
 *    @option string[] tag_url_name (optional)
 *    @option string[] tag_name (optional)
 *    @option bool tag_mode_and
 *    @option int per_page
 *    @option int page
 *    @option string order
 */
function ws_tags_getImages_cliext($params, &$service)
{
    global $page;

    include_once(PHPWG_ROOT_PATH.'include/ws_functions/pwg.tags.php');

    if(count($params['tag_id']) == 1)
    {
       # This is called to retrieve all images in just one tag, so is reasonable to assume the user is viewing the tag contents
       $page['section'] = 'tags';
       $page['tag_ids'] = $params['tag_id'];
       pwg_log();
    }

    return ws_tags_getImages($params, $service);
}

?>