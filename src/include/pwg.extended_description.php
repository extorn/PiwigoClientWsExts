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
// | Added missing support for extended descriptions                       |
// +-----------------------------------------------------------------------+
function get_extended_desc_cliext($param1, $params)
{
    if (is_array($params)) {
        // This has been called by our code (It's a string if it hasn't)
        $extDescType = 'subcatify_category_description';
        if($params['isSubject']) {
            $extDescType = 'main_page_category_description';
        }
        return trigger_change(
            'render_category_description',
            $params['text'],
            $extDescType
            );
        //This category                 : main_page_category_description
        //Sub category summary version  : subcatify_category_description
    }
    // NOTE: $param1 is the caller method in our code (because we pass the other data in the params object),
    //       but the raw description text in the Piwigo Team Code.
    return $param1;
}
