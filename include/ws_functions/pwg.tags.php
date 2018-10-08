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

    return ws_tags_getImages($params, &$service);
}