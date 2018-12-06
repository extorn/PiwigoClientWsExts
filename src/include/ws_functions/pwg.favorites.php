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
 * Add image to the list
 * @param mixed[] $params
 *    @option int image_id
 */
function ws_favorites_add_image_cliext($params, $service)
{
  global $user, $conf;

  $query = '
      INSERT INTO '.FAVORITES_TABLE.'
        (image_id,user_id)
        VALUES
        ('.$params['image_id'].','.$user['id'].')
      ;';
  pwg_query($query);
}

/**
 * API method
 * Remove image from the list
 * @param mixed[] $params
 *    @option int image_id
 */
function ws_favorites_remove_image_cliext($params, $service)
{

  global $user, $conf;
  $query = '
      DELETE FROM '.FAVORITES_TABLE.'
         WHERE user_id = '.$user['id'].'
         AND image_id = '.$params['image_id'].'
         ;';
  pwg_query($query);
}

/**
 * API method
 * Returns all images on the list
 * @param mixed[] $params
 *    @option int per_page
 *    @option int page
 */
function ws_favorites_get_list_cliext($params, $service)
{
  global $user, $conf;

  $images = array();

  $query = '
  SELECT SQL_CALC_FOUND_ROWS image_id FROM '.FAVORITES_TABLE.'
    WHERE user_id = '.$user['id'].'
    ORDER BY image_id ASC
    LIMIT '. $params['per_page'] .'
    OFFSET '. ($params['per_page']*$params['page']) .'
  ;';

  $result = pwg_query($query);

  while ($row = pwg_db_fetch_assoc($result))
  {
    $image = array();
    foreach (array('image_id') as $k)
    {
      if (isset($row[$k]))
      {
        $image[$k] = (int)$row[$k];
      }
    }
    $images[] = $image;
  }

  list($total_images) = pwg_db_fetch_row(pwg_query('SELECT FOUND_ROWS()'));

    return array(
      'paging' => new PwgNamedStruct(
        array(
          'page' => $params['page'],
          'per_page' => $params['per_page'],
          'count' => count($images),
          'total_count' => $total_images
          )
        ),
      'images' => new PwgNamedArray(
        $images, 'image',
        ws_std_get_image_xml_attributes()
        )
      );
}


/**
 * API method
 * Returns images marked as a favorite for the current user
 * @param mixed[] $params
 *    @option int per_page
 *    @option int page
 *    @option string order (optional)
 */
function ws_favorites_getImages_cliext($params, &$service)
{
  global $user, $conf, $page;

    $images = array();

    //------------------------------------------------- get the related categories
    $where_clauses = array();

    $where_clauses[] = get_sql_condition_FandF(
      array('forbidden_categories' => 'id'),
      null, true
      );

//select * from piwigo_categories where id in (select distinct(category_id) from piwigo_image_category ic inner join piwigo_favorites f ON ic.image_id=f.image_id WHERE f.user_id = ?)
    $query = '
        SELECT id, name, permalink, image_order
          FROM '. CATEGORIES_TABLE .'
          WHERE '. implode("\n    AND ", $where_clauses) .'
          AND id IN
            (SELECT DISTINCT(category_id)
            FROM '. IMAGE_CATEGORY_TABLE .' ic
            INNER JOIN '.FAVORITES_TABLE.' f ON ic.image_id=f.image_id
            WHERE user_id = '.$user['id'].')
        ;';
    $result = pwg_query($query);

    $cats = array();
    while ($row = pwg_db_fetch_assoc($result))
    {
      $row['id'] = (int)$row['id'];
      $cats[ $row['id'] ] = $row;
    }

    //-------------------------------------------------------- get the images
    if (!empty($cats))
    {
      $where_clauses = ws_std_image_sql_filter($params, 'i.');
      $where_clauses[] = 'category_id IN ('. implode(',', array_keys($cats)) .')';
      $where_clauses[] = get_sql_condition_FandF(
        array('visible_images' => 'i.id'),
        null, true
        );

      $order_by = ws_std_image_sql_order($params, 'i.');
      $order_by = empty($order_by) ? $conf['order_by'] : 'ORDER BY '.$order_by;

// SELECT SQL_CALC_FOUND_ROWS i.*, GROUP_CONCAT(category_id) AS cat_ids
//            FROM piwigo_images i
//              INNER JOIN piwigo_favorites f ON i.id=f.image_id
//              INNER JOIN piwigo_image_category ic ON i.id=ic.image_id
//              WHERE f.user_id = 1
//              GROUP BY i.id;

    $query = '
        SELECT SQL_CALC_FOUND_ROWS i.*, GROUP_CONCAT(category_id) AS cat_ids
          FROM '. IMAGES_TABLE .' i
            INNER JOIN '. FAVORITES_TABLE .' f ON i.id=f.image_id
            INNER JOIN '. IMAGE_CATEGORY_TABLE .' ic ON i.id=ic.image_id
                WHERE f.user_id = '.$user['id'].'
                AND '. implode("\n    AND ", $where_clauses) .'
            GROUP BY i.id
            '. $order_by .'
            LIMIT '. $params['per_page'] .'
            OFFSET '. ($params['per_page']*$params['page']) .'
            ;';
    $result = pwg_query($query);

      while ($row = pwg_db_fetch_assoc($result))
      {
        $image = array();
        foreach (array('id', 'width', 'height', 'hit') as $k)
        {
          if (isset($row[$k]))
          {
            $image[$k] = (int)$row[$k];
          }
        }
        foreach (array('file', 'name', 'comment', 'date_creation', 'date_available') as $k)
        {
          $image[$k] = $row[$k];
        }
        $image = array_merge($image, ws_std_get_urls($row));

        $image_cats = array();
        foreach (explode(',', $row['cat_ids']) as $cat_id)
        {
          $url = make_index_url(
            array(
              'category' => $cats[$cat_id],
              )
            );
          $page_url = make_picture_url(
            array(
              'category' => $cats[$cat_id],
              'image_id' => $row['id'],
              'image_file' => $row['file'],
              )
            );
          $image_cats[] = array(
            'id' => (int)$cat_id,
            'url' => $url,
            'page_url' => $page_url,
            );
        }

        $image['categories'] = new PwgNamedArray(
          $image_cats,
          'category',
          array('id', 'url', 'page_url')
          );
        $images[] = $image;
      }
    }

    list($total_images) = pwg_db_fetch_row(pwg_query('SELECT FOUND_ROWS()'));

    return array(
      'paging' => new PwgNamedStruct(
        array(
          'page' => $params['page'],
          'per_page' => $params['per_page'],
          'count' => count($images),
          'total_count' => $total_images
          )
        ),
      'images' => new PwgNamedArray(
        $images, 'image',
        ws_std_get_image_xml_attributes()
        )
      );
}


function ws_favorites_remove_all_cliext($params, $service)
{
  global $user, $conf;
  $query = '
      DELETE FROM '.FAVORITES_TABLE.'
         WHERE user_id = '.$user['id'].'
         ;';
  pwg_query($query);
}

?>