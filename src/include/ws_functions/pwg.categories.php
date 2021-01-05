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
 * Returns images per category
 * @param mixed[] $params
 *    @option int[] cat_id (optional)
 *    @option bool recursive
 *    @option int per_page
 *    @option int page
 *    @option string order (optional)
 */
function ws_categories_getImages_cliext($params, &$service)
{
  global $user, $conf, $page;

  include_once(PHPWG_ROOT_PATH.'include/ws_functions/pwg.categories.php');

  if(count($params['cat_id']) == 1)
  {
     # This is called to retrieve all images in just one category, so is reasonable to assume the user is viewing the category contents
     $page['section'] = 'categories';
     $page['category']['id'] = $params['cat_id'][0];
     pwg_log();
  }
  return ws_categories_getImages($params, $service);
}

/**
 * API method
 * Returns a list of categories
 * @param mixed[] $params
 *    @option int cat_id (optional)
 *    @option bool recursive
 *    @option bool public
 *    @option bool tree_output
 *    @option bool fullname
 */
function ws_categories_getList_cliExt($params, &$service)
{
    global $user, $conf;
    
    if (!in_array($params['thumbnail_size'], array_keys(ImageStdParams::get_defined_type_map())))
    {
        return new PwgError(WS_ERR_INVALID_PARAM, "Invalid thumbnail_size");
    }
    
    $where = array('1=1');
    $join_type = 'INNER';
    $join_user = $user['id'];
    
    if (!$params['recursive'])
    {
        if ($params['cat_id']>0)
        {
            $where[] = '(
        id_uppercat = '. (int)($params['cat_id']) .'
        OR id='.(int)($params['cat_id']).'
      )';
        }
        else
        {
            $where[] = 'id_uppercat IS NULL';
        }
    }
    elseif ($params['cat_id']>0)
    {
        $where[] = 'uppercats '. DB_REGEX_OPERATOR .' \'(^|,)'.
            (int)($params['cat_id']) .'(,|$)\'';
    }
    
    if ($params['public'])
    {
        $where[] = 'status = "public"';
        $where[] = 'visible = "true"';
        
        $join_user = $conf['guest_id'];
    }
    elseif (is_admin())
    {
        // in this very specific case, we don't want to hide empty
        // categories. Function calculate_permissions will only return
        // categories that are either locked or private and not permitted
        //
        // calculate_permissions does not consider empty categories as forbidden
        $forbidden_categories = calculate_permissions($user['id'], $user['status']);
        $where[]= 'id NOT IN ('.$forbidden_categories.')';
        $join_type = 'LEFT';
    }
    
    $query = '
SELECT
    id, name, comment, permalink, status,
    uppercats, global_rank, id_uppercat,
    nb_images, count_images AS total_nb_images,
    representative_picture_id, user_representative_picture_id, count_images, count_categories,
    date_last, max_date_last, count_categories AS nb_categories
  FROM '. CATEGORIES_TABLE .'
    '.$join_type.' JOIN '. USER_CACHE_CATEGORIES_TABLE .'
    ON id=cat_id AND user_id='.$join_user.'
  WHERE '. implode("\n    AND ", $where) .'
;';
    $result = pwg_query($query);
    
    // management of the album thumbnail -- starts here
    $image_ids = array();
    $categories = array();
    $user_representative_updates_for = array();
    // management of the album thumbnail -- stops here
    
    $cats = array();
    while ($row = pwg_db_fetch_assoc($result))
    {
        $row['url'] = make_index_url(
            array(
                'category' => $row
            )
            );
        foreach (array('id','nb_images','total_nb_images','nb_categories') as $key)
        {
            $row[$key] = (int)$row[$key];
        }
        
        $render_params = array('isSubject' => $row['id'] == $params['cat_id'], 
                               'text' => $row['name']);
        
        if ($params['fullname'])
        {
            $row['name'] = strip_tags(get_cat_display_name_cache($row['uppercats'], null));
        }
        else
        {
            # render_category_name 'vars' => array('string', 'category_name', 'string', 'location')
            $row['name'] = trigger_change(
                'render_category_name', # action
                $render_params['text'], # the value to send
                'ws_categories_getList'); # any params (here it is the caller of the event).
            if ($row['name'] == 'ws_categories_getList') {
                $row['name'] = $render_params['text'];
            }
            $row['name'] = strip_tags($row['name']);
                
        }
        
        // change the text to the comment now.
        $render_params['text'] = $row['comment'];
        
        # render_category_description 'vars' => array('string', 'category_description', 'string', 'action'),
        $row['comment'] = trigger_change(
            'render_category_description', # action
            'ws_categories_getList', # this should be category description really but we use this as a flag so we know we invoked the event.
            $render_params  # any params - we pass the value in here with some other data as an array. Because we catch this event before anyone else, we can do this.
            );
        if ($row['comment'] == 'ws_categories_getList') {
            $row['comment'] = $render_params['text'];
        }
        $row['comment'] = strip_tags($row['comment']);
        
        
        
        // management of the album thumbnail -- starts here
        //
        // on branch 2.3, the algorithm is duplicated from
        // include/category_cats, but we should use a common code for Piwigo 2.4
        //
        // warning : if the API method is called with $params['public'], the
        // album thumbnail may be not accurate. The thumbnail can be viewed by
        // the connected user, but maybe not by the guest. Changing the
        // filtering method would be too complicated for now. We will simply
        // avoid to persist the user_representative_picture_id in the database
        // if $params['public']
        if (!empty($row['user_representative_picture_id']))
        {
            $image_id = $row['user_representative_picture_id'];
        }
        elseif (!empty($row['representative_picture_id']))
        { // if a representative picture is set, it has priority
            $image_id = $row['representative_picture_id'];
        }
        elseif ($conf['allow_random_representative'])
        {
            // searching a random representant among elements in sub-categories
            $image_id = get_random_image_in_category($row);
        }
        else
        { // searching a random representant among representant of sub-categories
            if ($row['count_categories']>0 and $row['count_images']>0)
            {
                $query = '
SELECT representative_picture_id
  FROM '. CATEGORIES_TABLE .'
    INNER JOIN '. USER_CACHE_CATEGORIES_TABLE .'
    ON id=cat_id AND user_id='.$user['id'].'
  WHERE uppercats LIKE \''.$row['uppercats'].',%\'
    AND representative_picture_id IS NOT NULL
        '.get_sql_condition_FandF(
            array('visible_categories' => 'id'),
            "\n  AND"
            ).'
  ORDER BY '. DB_RANDOM_FUNCTION .'()
  LIMIT 1
;';
            $subresult = pwg_query($query);
            
            if (pwg_db_num_rows($subresult) > 0)
            {
                list($image_id) = pwg_db_fetch_row($subresult);
            }
            }
        }
        
        if (isset($image_id))
        {
            if ($conf['representative_cache_on_subcats'] and $row['user_representative_picture_id'] != $image_id)
            {
                $user_representative_updates_for[ $row['id'] ] = $image_id;
            }
            
            $row['representative_picture_id'] = $image_id;
            $image_ids[] = $image_id;
            $categories[] = $row;
        }
        unset($image_id);
        // management of the album thumbnail -- stops here
        
        $cats[] = $row;
    }
    usort($cats, 'global_rank_compare');
    
    // management of the album thumbnail -- starts here
    if (count($categories) > 0)
    {
        $thumbnail_src_of = array();
        $new_image_ids = array();
        
        $query = '
SELECT id, path, representative_ext, level
  FROM '. IMAGES_TABLE .'
  WHERE id IN ('. implode(',', $image_ids) .')
;';
        $result = pwg_query($query);
        
        while ($row = pwg_db_fetch_assoc($result))
        {
            if ($row['level'] <= $user['level'])
            {
                $thumbnail_src_of[$row['id']] = DerivativeImage::url($params['thumbnail_size'], $row);
            }
            else
            {
                // problem: we must not display the thumbnail of a photo which has a
                // higher privacy level than user privacy level
                //
                // * what is the represented category?
                // * find a random photo matching user permissions
                // * register it at user_representative_picture_id
                // * set it as the representative_picture_id for the category
                foreach ($categories as &$category)
                {
                    if ($row['id'] == $category['representative_picture_id'])
                    {
                        // searching a random representant among elements in sub-categories
                        $image_id = get_random_image_in_category($category);
                        
                        if (isset($image_id) and !in_array($image_id, $image_ids))
                        {
                            $new_image_ids[] = $image_id;
                        }
                        if ($conf['representative_cache_on_level'])
                        {
                            $user_representative_updates_for[ $category['id'] ] = $image_id;
                        }
                        
                        $category['representative_picture_id'] = $image_id;
                    }
                }
                unset($category);
            }
        }
        
        if (count($new_image_ids) > 0)
        {
            $query = '
SELECT id, path, representative_ext
  FROM '. IMAGES_TABLE .'
  WHERE id IN ('. implode(',', $new_image_ids) .')
;';
            $result = pwg_query($query);
            
            while ($row = pwg_db_fetch_assoc($result))
            {
                $thumbnail_src_of[ $row['id'] ] = DerivativeImage::url($params['thumbnail_size'], $row);
            }
        }
    }
    
    // compared to code in include/category_cats, we only persist the new
    // user_representative if we have used $user['id'] and not the guest id,
    // or else the real guest may see thumbnail that he should not
    if (!$params['public'] and count($user_representative_updates_for))
    {
        $updates = array();
        
        foreach ($user_representative_updates_for as $cat_id => $image_id)
        {
            $updates[] = array(
                'user_id' => $user['id'],
                'cat_id' => $cat_id,
                'user_representative_picture_id' => $image_id,
            );
        }
        
        mass_updates(
            USER_CACHE_CATEGORIES_TABLE,
            array(
                'primary' => array('user_id', 'cat_id'),
                'update'  => array('user_representative_picture_id')
            ),
            $updates
            );
    }
    
    foreach ($cats as &$cat)
    {
        foreach ($categories as $category)
        {
            if ($category['id'] == $cat['id'] and isset($category['representative_picture_id']))
            {
                $cat['tn_url'] = $thumbnail_src_of[$category['representative_picture_id']];
            }
        }
        // we don't want them in the output
        unset($cat['user_representative_picture_id'], $cat['count_images'], $cat['count_categories']);
    }
    unset($cat);
    // management of the album thumbnail -- stops here
    
    if ($params['tree_output'])
    {
        return categories_flatlist_to_tree($cats);
    }
    
    return array(
        'categories' => new PwgNamedArray(
            $cats,
            'category',
            ws_std_get_category_xml_attributes()
            )
    );
}

/**
 * API method
 * Returns the list of categories as you can see them in administration
 * @param mixed[] $params
 *
 * Only admin can run this method and permissions are not taken into
 * account.
 */
function ws_categories_getAdminList_cliExt($params, &$service)
{
    $query = '
SELECT category_id, COUNT(*) AS counter
  FROM '. IMAGE_CATEGORY_TABLE .'
  GROUP BY category_id
;';
    $nb_images_of = query2array($query, 'category_id', 'counter');
    
    $query = '
SELECT id, name, comment, uppercats, global_rank, dir, status
  FROM '. CATEGORIES_TABLE .'
;';
    $result = pwg_query($query);
    
    $cats = array();
    while ($row = pwg_db_fetch_assoc($result))
    {
        $id = $row['id'];
        $row['nb_images'] = isset($nb_images_of[$id]) ? $nb_images_of[$id] : 0;
        
        $render_params = array('isSubject' => false,
                                'text' => $row['name']);
        
        $row['name'] = strip_tags(
            trigger_change(
                'render_category_name',
                $row['name'],
                'ws_categories_getAdminList'
                )
            );
        
        
        $row['fullname'] = strip_tags(
            get_cat_display_name_cache(
                $row['uppercats'],
                null
                )
            );
        
        // change the text to the comment now.
        $render_params['text'] = $row['comment'];
        
        # render_category_description 'vars' => array('string', 'category_description', 'string', 'action'),
        $row['comment'] = trigger_change(
            'render_category_description', # action
            'ws_categories_getAdminList', # this should be category description really but we use this as a flag so we know we invoked the event.
            $render_params  # any params - we pass the value in here with some other data as an array. Because we catch this event before anyone else, we can do this.
            );
        if ($row['comment'] == 'ws_categories_getAdminList') {
            $row['comment'] = $render_params['text'];
        }
        $row['comment'] = strip_tags($row['comment']);
        
        $cats[] = $row;
    }
    
    usort($cats, 'global_rank_compare');
    return array(
        'categories' => new PwgNamedArray(
            $cats,
            'category',
            array('id', 'nb_images', 'name', 'uppercats', 'global_rank', 'status')
            )
    );
}

?>