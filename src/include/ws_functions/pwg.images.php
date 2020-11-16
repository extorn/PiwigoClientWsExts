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

function ws_images_getInfo_cliext($params, $service)
{
  global $user, $conf;

  # This is called to retrieve all details for an image, so is reasonable to assume the user is viewing the image.
  pwg_log($params['image_id'], 'picture');

  $query='
SELECT *
  FROM '. IMAGES_TABLE .'
  LEFT OUTER JOIN '.FAVORITES_TABLE.' ft
  ON id=ft.image_id
  WHERE id='. $params['image_id'] .
    get_sql_condition_FandF(
      array('visible_images' => 'id'),
      ' AND'
      ).'
LIMIT 1
;';
  $result = pwg_query($query);

  if (pwg_db_num_rows($result) == 0)
  {
    return new PwgError(404, 'image_id not found');
  }

  $image_row = pwg_db_fetch_assoc($result);
  $image_row = array_merge($image_row, ws_std_get_urls($image_row));

  $isFavorite = 'false';
  if($image_row['image_id'] != null)
  {
    $isFavorite = 'true';
  }


  //-------------------------------------------------------- related categories
  $query = '
SELECT id, name, permalink, uppercats, global_rank, commentable
  FROM '. IMAGE_CATEGORY_TABLE .'
    INNER JOIN '. CATEGORIES_TABLE .' ON category_id = id
  WHERE image_id = '. $image_row['id'] .
    get_sql_condition_FandF(
      array('forbidden_categories' => 'category_id'),
      ' AND'
      ).'
;';
  $result = pwg_query($query);

  $is_commentable = false;
  $related_categories = array();
  while ($row = pwg_db_fetch_assoc($result))
  {
    if ($row['commentable']=='true')
    {
      $is_commentable = true;
    }
    unset($row['commentable']);

    $row['url'] = make_index_url(
      array(
        'category' => $row
        )
      );

    $row['page_url'] = make_picture_url(
      array(
        'image_id' => $image_row['id'],
        'image_file' => $image_row['file'],
        'category' => $row
        )
      );

    $row['id']=(int)$row['id'];
    $related_categories[] = $row;
  }
  usort($related_categories, 'global_rank_compare');

  if (empty($related_categories))
  {
    return new PwgError(401, 'Access denied');
  }

  //-------------------------------------------------------------- related tags
  $related_tags = get_common_tags(array($image_row['id']), -1);
  foreach ($related_tags as $i=>$tag)
  {
    $tag['url'] = make_index_url(
      array(
        'tags' => array($tag)
        )
      );
    $tag['page_url'] = make_picture_url(
      array(
        'image_id' => $image_row['id'],
        'image_file' => $image_row['file'],
        'tags' => array($tag),
        )
      );

    unset($tag['counter']);
    $tag['id'] = (int)$tag['id'];
    $related_tags[$i] = $tag;
  }

  //------------------------------------------------------------- related rates
	$rating = array(
    'score' => $image_row['rating_score'],
    'count' => 0,
    'average' => null,
    'my_rating' => null,
    );
	if (isset($rating['score']))
	{
		$query = '
            SELECT COUNT(rate) AS count, ROUND(AVG(rate),2) AS average
              FROM '. RATE_TABLE .'
              WHERE element_id = '. $image_row['id'] .'
            ;';
		$row = pwg_db_fetch_assoc(pwg_query($query));

		$query = '
            SELECT rate as my_rating
              FROM '. RATE_TABLE .'
              WHERE element_id = '. $image_row['id'] .'
              AND user_id = '.$user['id'].'
            ;';
        $rowB = pwg_db_fetch_assoc(pwg_query($query));

		$rating['score'] = (float)$rating['score'];
		$rating['average'] = (float)$row['average'];
		$rating['count'] = (int)$row['count'];
		$rating['my_rating'] = (int)$rowB['my_rating'];
	}

  //---------------------------------------------------------- related comments
  $related_comments = array();

  $where_comments = 'image_id = '.$image_row['id'];
  if (!is_admin())
  {
    $where_comments .= ' AND validated="true"';
  }

  $query = '
SELECT COUNT(id) AS nb_comments
  FROM '. COMMENTS_TABLE .'
  WHERE '. $where_comments .'
;';
  list($nb_comments) = query2array($query, null, 'nb_comments');
  $nb_comments = (int)$nb_comments;

  if ($nb_comments>0 and $params['comments_per_page']>0)
  {
    $query = '
SELECT id, date, author, content
  FROM '. COMMENTS_TABLE .'
  WHERE '. $where_comments .'
  ORDER BY date
  LIMIT '. (int)$params['comments_per_page'] .'
  OFFSET '. (int)($params['comments_per_page']*$params['comments_page']) .'
;';
    $result = pwg_query($query);

    while ($row = pwg_db_fetch_assoc($result))
    {
      $row['id'] = (int)$row['id'];
      $related_comments[] = $row;
    }
  }

  $comment_post_data = null;
  if ($is_commentable and
      (!is_a_guest()
        or (is_a_guest() and $conf['comments_forall'] )
      )
    )
  {
    $comment_post_data['author'] = stripslashes($user['username']);
    $comment_post_data['key'] = get_ephemeral_key(2, $params['image_id']);
  }

  $ret = $image_row;
  $ret['isFavorite']=$isFavorite;
  foreach (array('id','width','height','hit','filesize') as $k)
  {
    if (isset($ret[$k]))
    {
      $ret[$k] = (int)$ret[$k];
    }
  }
  foreach (array('path', 'storage_category_id') as $k)
  {
    unset($ret[$k]);
  }

  $ret['rates'] = array(
    WS_XML_ATTRIBUTES => $rating
    );
  $ret['categories'] = new PwgNamedArray(
    $related_categories,
    'category',
    array('id','url', 'page_url')
    );
  $ret['tags'] = new PwgNamedArray(
    $related_tags,
    'tag',
    ws_std_get_tag_xml_attributes()
    );
  if (isset($comment_post_data))
  {
    $ret['comment_post'] = array(
      WS_XML_ATTRIBUTES => $comment_post_data
      );
  }
  $ret['comments_paging'] = new PwgNamedStruct(
    array(
      'page' => $params['comments_page'],
      'per_page' => $params['comments_per_page'],
      'count' => count($related_comments),
      'total_count' => $nb_comments,
      )
    );
  $ret['comments'] = new PwgNamedArray(
    $related_comments,
    'comment',
    array('id','date')
    );

  if ($service->_responseFormat != 'rest')
  {
    return $ret; // for backward compatibility only
  }
  else
  {
    return array(
      'image' => new PwgNamedStruct($ret, null, array('name','comment'))
      );
  }
}

/**
 * API method
 * Returns all orphaned images
 * @param mixed[] $params
 *    @option int per_page
 *    @option int page
 */
function ws_images_listOrphans_cliext($params, $service)
{
    global $conf, $logger;

    # $logger->debug(__FUNCTION__, 'WS', $params);

    $images = array();

    $query = '
    SELECT
        SQL_CALC_FOUND_ROWS id
      FROM '.IMAGES_TABLE.'
        LEFT JOIN '.IMAGE_CATEGORY_TABLE.' ON id = image_id
      WHERE category_id is null
      ORDER BY id ASC
      LIMIT '. $params['per_page'] .'
          OFFSET '. ($params['per_page']*$params['page']) .'
    ;';

  $result = pwg_query($query);

  while ($row = pwg_db_fetch_assoc($result))
    {
      $image = array();
      foreach (array('id') as $k)
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

?>