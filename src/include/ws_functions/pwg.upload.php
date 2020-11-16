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
 * Clears all failed partial uploads made by PiwigoClient from the server
 * @param mixed[] $params
 *    @option string pwg_token
 */
function ws_upload_clean($params, &$service)
{
    global $page;

    if (get_pwg_token() != $params['pwg_token'])
    {
        return new PwgError(403, 'Invalid security token');
    }

    global $conf;

      $upload_dir = $conf['upload_dir'].'/buffer';
      $pattern = '/PiwigoClient_Upload_.*.part/';
      $failedUploads = array();

      if ($handle = opendir($upload_dir))
      {
        while (false !== ($file = readdir($handle)))
        {
          if (preg_match($pattern, $file))
          {
            $failedUploads[] = $upload_dir.'/'.$file;
          }
        }
        closedir($handle);
      }

      foreach ($failedUploads as $failedUploadFile)
      {
        unlink($failedUploadFile);
      }

      return array('filesRemoved' => count($failedUploads));
}

?>