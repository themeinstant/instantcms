<?php
if(!defined('VALID_CMS_ADMIN')) { die('ACCESS DENIED'); }
/******************************************************************************/
//                                                                            //
//                             InstantCMS v1.10                               //
//                        http://www.instantcms.ru/                           //
//                                                                            //
//                   written by InstantCMS Team, 2007-2012                    //
//                produced by InstantSoft, (www.instantsoft.ru)               //
//                                                                            //
//                        LICENSED BY GNU/GPL v2                              //
//                                                                            //
/******************************************************************************/
function uploadCategoryIcon($file='') {

    cmsCore::loadClass('upload_photo');
    $inUploadPhoto = cmsUploadPhoto::getInstance();
    $inUploadPhoto->upload_dir    = PATH.'/upload/forum/';
    $inUploadPhoto->dir_medium    = 'cat_icons/';
    $inUploadPhoto->medium_size_w = 32;
    $inUploadPhoto->medium_size_h = 32;
    $inUploadPhoto->only_medium   = true;
    $inUploadPhoto->is_watermark  = false;
    $files = $inUploadPhoto->uploadPhoto($file);
    $icon = $files['filename'] ? $files['filename'] : $file;
    return $icon;

}

define('IS_BILLING', $inCore->isComponentInstalled('billing'));
if (IS_BILLING) { cmsCore::loadClass('billing'); }

$opt = cmsCore::request('opt', 'str', 'list_forums');
$id  = cmsCore::request('id', 'int', 0);

cmsCore::loadModel('forum');
$model = new cms_model_forum();

$cfg = $model->config;

cpAddPathway('Форум', '?view=components&do=config&id='.$id);

$toolmenu = array();

if ($opt=='list_forums' || $opt=='list_cats' || $opt=='config'){

    $toolmenu[0]['icon'] = 'newfolder.gif';
    $toolmenu[0]['title'] = 'Новая категория';
    $toolmenu[0]['link'] = '?view=components&do=config&id='.$id.'&opt=add_cat';

    $toolmenu[2]['icon'] = 'newforum.gif';
    $toolmenu[2]['title'] = 'Новый форум';
    $toolmenu[2]['link'] = '?view=components&do=config&id='.$id.'&opt=add_forum';

    $toolmenu[1]['icon'] = 'folders.gif';
    $toolmenu[1]['title'] = 'Категории форумов';
    $toolmenu[1]['link'] = '?view=components&do=config&id='.$id.'&opt=list_cats';

    $toolmenu[3]['icon'] = 'listforums.gif';
    $toolmenu[3]['title'] = 'Все форумы';
    $toolmenu[3]['link'] = '?view=components&do=config&id='.$id.'&opt=list_forums';

    $toolmenu[4]['icon'] = 'ranks.gif';
    $toolmenu[4]['title'] = 'Звания на форуме';
    $toolmenu[4]['link'] = '?view=components&do=config&id='.$id.'&opt=list_ranks';

    $toolmenu[5]['icon'] = 'config.gif';
    $toolmenu[5]['title'] = 'Настройки';
    $toolmenu[5]['link'] = '?view=components&do=config&id='.$id.'&opt=config';

}
if($opt=='list_forums'){

    $toolmenu[11]['icon'] = 'edit.gif';
    $toolmenu[11]['title'] = 'Редактировать выбранные';
    $toolmenu[11]['link'] = "javascript:checkSel('?view=components&do=config&id=".$id."&opt=edit_forum&multiple=1');";

    $toolmenu[12]['icon'] = 'show.gif';
    $toolmenu[12]['title'] = 'Публиковать выбранные';
    $toolmenu[12]['link'] = "javascript:checkSel('?view=components&do=config&id=".$id."&opt=show_forum&multiple=1');";

    $toolmenu[13]['icon'] = 'hide.gif';
    $toolmenu[13]['title'] = 'Скрыть выбранные';
    $toolmenu[13]['link'] = "javascript:checkSel('?view=components&do=config&id=".$id."&opt=hide_forum&multiple=1');";

}

if ($opt=='list_forums' || $opt=='list_cats' || $opt=='config'){
} else {

    $toolmenu[20]['icon'] = 'save.gif';
    $toolmenu[20]['title'] = 'Сохранить';
    $toolmenu[20]['link'] = 'javascript:document.addform.submit();';

    $toolmenu[21]['icon'] = 'cancel.gif';
    $toolmenu[21]['title'] = 'Отмена';
    $toolmenu[21]['link'] = '?view=components&do=config&id='.$id;

}

cpToolMenu($toolmenu);

if ($opt=='saveconfig'){

    if (!cmsCore::validateForm()) { cmsCore::error404(); }

    $cfg['is_rss']      = cmsCore::request('is_rss', 'int', 1);
    $cfg['pp_thread']   = cmsCore::request('pp_thread', 'int', 15);
    $cfg['pp_forum']    = cmsCore::request('pp_forum', 'int', 15);

    $cfg['showimg']     = cmsCore::request('showimg', 'int', 1);

    $cfg['img_on']      = cmsCore::request('img_on', 'int', 1);
    $cfg['img_max']     = cmsCore::request('img_max', 'int', 1);

    $cfg['fast_on']     = cmsCore::request('fast_on', 'int', 1);
    $cfg['fast_bb']     = cmsCore::request('fast_bb', 'int', 1);

    $cfg['fa_on']       = cmsCore::request('fa_on', 'int');
    $cfg['fa_max']      = cmsCore::request('fa_max', 'int');
    $cfg['fa_ext']      = cmsCore::request('fa_ext', 'str');
    while (mb_strpos($cfg['fa_ext'], 'htm') || mb_strpos($cfg['fa_ext'], 'php')) {
        $cfg['fa_ext']  = str_replace(array('htm','php'), '', $cfg['fa_ext']);
    }
    $cfg['fa_size']     = cmsCore::request('fa_size', 'int');
    $cfg['edit_minutes'] = cmsCore::request('edit_minutes', 'int');
    $cfg['watermark']   = cmsCore::request('watermark', 'int');

    $is_access = cmsCore::request('is_access', 'int', '');
    if (!$is_access){
        $cfg['group_access'] = cmsCore::request('allow_group', 'array_int', '');
    } else { $cfg['group_access'] = ''; }

    $inCore->saveComponentConfig('forum', $cfg);

    cmsCore::addSessionMessage('Настройки сохранены', 'info');

    cmsUser::clearCsrfToken();

    cmsCore::redirectBack();

}

if ($opt=='saveranks'){

    if (!cmsCore::validateForm()) { cmsCore::error404(); }

    $cfg['ranks']   = cmsCore::request('rank', 'array_str', array());
    $cfg['modrank'] = cmsCore::request('modrank', 'int');

    $inCore->saveComponentConfig('forum', $cfg);

    cmsCore::addSessionMessage('Звания сохранены.', 'info');

    cmsUser::clearCsrfToken();

    cmsCore::redirectBack();

}

if ($opt == 'show_forum'){
    if (!isset($_REQUEST['item'])){
        if (isset($_REQUEST['item_id'])){ dbShow('cms_forums', $_REQUEST['item_id']);  }
        echo '1'; exit;
    } else {
        dbShowList('cms_forums', $_REQUEST['item']);
        cmsCore::redirectBack();
    }
}

if ($opt == 'hide_forum'){
    if (!isset($_REQUEST['item'])){
        if (isset($_REQUEST['item_id'])){ dbHide('cms_forums', $_REQUEST['item_id']);  }
        echo '1'; exit;
    } else {
        dbHideList('cms_forums', $_REQUEST['item']);
        cmsCore::redirectBack();
    }
}

if ($opt == 'submit_forum'){

    if (!cmsCore::validateForm()) { cmsCore::error404(); }

    $category_id = cmsCore::request('category_id', 'int');
    $title       = cmsCore::request('title', 'str', 'Форум без названия');
    $published   = cmsCore::request('published', 'int');
    $parent_id   = cmsCore::request('parent_id', 'int');
    $description = cmsCore::request('description', 'str');
    $topic_cost  = cmsCore::request('topic_cost', 'int', 0);
    $moder_list  = cmsCore::request('moder_list', 'array_int', array());
    $moder_list  = $moder_list ? cmsCore::arrayToYaml($moder_list) : '';

    $is_access = cmsCore::request('is_access', 'int', '');
    if (!$is_access){
        $access_list = cmsCore::request('access_list', 'array_int');
        $group_access = $access_list ? cmsCore::arrayToYaml($access_list) : '';
    } else {
        $group_access = '';
    }

    $icon = uploadCategoryIcon();

    $inDB->addNsCategory('cms_forums', array('category_id'=>$category_id,
                                             'parent_id'=>$parent_id,
                                             'title'=>$title,
                                             'description'=>$description,
                                             'access_list'=>$group_access,
                                             'moder_list'=>$moder_list,
                                             'published'=>$published,
                                             'icon'=>$icon,
                                             'topic_cost'=>$topic_cost));

    cmsCore::addSessionMessage('Форум "'.$title.'" успешно создан!', 'info');

    cmsUser::clearCsrfToken();

    cmsCore::redirect('?view=components&do=config&opt=list_forums&id='.$id);

}

if ($opt == 'update_forum'){

    if (!cmsCore::validateForm()) { cmsCore::error404(); }

    $item_id     = cmsCore::request('item_id', 'int');
    $category_id = cmsCore::request('category_id', 'int');
    $title       = cmsCore::request('title', 'str', 'Форум без названия');
    $published   = cmsCore::request('published', 'int');
    $parent_id   = cmsCore::request('parent_id', 'int');
    $description = cmsCore::request('description', 'str');
    $topic_cost  = cmsCore::request('topic_cost', 'int', 0);
    $moder_list  = cmsCore::request('moder_list', 'array_int', array());
    $moder_list  = $moder_list ? cmsCore::arrayToYaml($moder_list) : '';

    $is_access = cmsCore::request('is_access', 'int', '');
    if (!$is_access){
        $access_list = cmsCore::request('access_list', 'array_int');
        $group_access = $access_list ? cmsCore::arrayToYaml($access_list) : '';
        $inDB->query("UPDATE cms_forum_threads SET is_hidden = 1 WHERE forum_id = '$item_id'");
    } else {
        $group_access = '';
        $inDB->query("UPDATE cms_forum_threads SET is_hidden = 0 WHERE forum_id = '$item_id'");
    }

    $ns = $inCore->nestedSetsInit('cms_forums');
    $old = $inDB->get_fields('cms_forums', "id='$item_id'", '*');

    $icon = uploadCategoryIcon($old['icon']);

    if($parent_id != $old['parent_id']){
        $ns->MoveNode($item_id, $parent_id);
    }

    $sql = "UPDATE cms_forums
            SET category_id=$category_id,
                title='$title',
                description='$description',
                access_list='$group_access',
                moder_list='$moder_list',
                published=$published,
                icon='$icon',
                topic_cost='$topic_cost'
            WHERE id = '$item_id'
            LIMIT 1";

    $inDB->query($sql);

    cmsCore::addSessionMessage('Данные форума "'.$title.'" успешно сохранены!', 'info');

    cmsUser::clearCsrfToken();

    if (!isset($_SESSION['editlist']) || @sizeof($_SESSION['editlist'])==0){
        cmsCore::redirect('?view=components&do=config&id='.$id.'&opt=list_forums');
    } else {
        cmsCore::redirect('?view=components&do=config&id='.$id.'&opt=edit_forum');
    }
}

if($opt == 'delete_forum'){

    $forum = $model->getForum(cmsCore::request('item_id', 'int'));
    if(!$forum){ cmsCore::error404(); }

    $inDB->addJoin('INNER JOIN cms_forums f ON f.id = t.forum_id');
    $model->whereThisAndNestedForum($forum['NSLeft'], $forum['NSRight']);

    $threads = $model->getThreads();

    foreach ($threads as $thread) {
        $model->deleteThread($thread['id']);
    }

    $inDB->deleteNS('cms_forums', $forum['id']);
    if(file_exists(PATH.'/upload/forum/cat_icons/'.$forum['icon'])){
        @chmod(PATH.'/upload/forum/cat_icons/'.$forum['icon'], 0777);
        @unlink(PATH.'/upload/forum/cat_icons/'.$forum['icon']);
    }

    cmsCore::addSessionMessage('Форум, его подфорумы, их темы и сообщения удалены.', 'info');

    cmsCore::redirect('?view=components&do=config&id='.$id.'&opt=list_forums');

}


if ($opt == 'config') {

    require('../includes/jwtabs.php');
    $GLOBALS['cp_page_head'][] = jwHeader();
    cpAddPathway('Настройки компонента', $_SERVER['REQUEST_URI']);

    ?>
    <form action="index.php?view=components&amp;do=config&amp;id=<?php echo $id;?>" method="post" name="addform" target="_self" id="form1" style="margin-top:10px">
    <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
        <?php ob_start(); ?>
       {tab=Просмотр}
       <table width="609" border="0" cellpadding="10" cellspacing="0" class="proptable">
            <tr>
                <td colspan="2" valign="top" bgcolor="#EBEBEB"><h4>Просмотр форума </h4></td>
            </tr>
            <tr>
                <td valign="top"><strong>Тем на странице: </strong></td>
                <td valign="top"><input name="pp_forum" type="text" id="pp_forum" value="<?php echo $cfg['pp_forum'];?>" size="5" /> шт.</td>
            </tr>
            <tr>
                <td valign="top"><strong>Иконка RSS: </strong></td>
                <td valign="top">
                    <label><input name="is_rss" type="radio" value="1" <?php if ($cfg['is_rss']) { echo 'checked="checked"'; } ?> /> Вкл</label>
                    <label><input name="is_rss" type="radio" value="0" <?php if (!$cfg['is_rss']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
            <tr>
                <td colspan="2" valign="top" bgcolor="#EBEBEB"><h4>Просмотр темы  </h4></td>
            </tr>
            <tr>
                <td valign="top"><strong>Сообщений на странице: </strong></td>
                <td valign="top"><input name="pp_thread" type="text" id="pp_thread" value="<?php echo $cfg['pp_thread'];?>" size="5" /> шт.</td>
            </tr>
            <tr>
                <td valign="top"><strong>Показывать уменьшенные прикрепленные изображения: </strong></td>
                <td valign="top">
                    <label><input name="showimg" type="radio" value="1" <?php if ($cfg['showimg']) { echo 'checked="checked"'; } ?> /> Вкл</label>
                    <label><input name="showimg" type="radio" value="0" <?php if (!$cfg['showimg']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
            <tr>
                <td valign="top"><strong>Форма для быстрого ответа: </strong></td>
                <td valign="top">
                    <label><input name="fast_on" type="radio" value="1" <?php if ($cfg['fast_on'] || !isset($cfg['fast_on'])) { echo 'checked="checked"'; } ?> /> Вкл</label>
                    <label><input name="fast_on" type="radio" value="0" <?php if (!$cfg['fast_on']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
            <tr>
                <td valign="top"><p><strong>ББ-код в быстром ответе</strong><strong>: </strong></p></td>
                <td valign="top">
                    <label><input name="fast_bb" type="radio" value="1" <?php if ($cfg['fast_bb'] || !isset($cfg['fast_bb'])) { echo 'checked="checked"'; } ?> /> Вкл</label>
                    <label><input name="fast_bb" type="radio" value="0" <?php if (!$cfg['fast_bb']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
            <tr>
                <td valign="top"><strong>Запрещать редактирование/удаление через:</strong><br />
                    <span class="hinttext">Спустя указанное время после добавления поста его редактирование/удаление станет невозможным для пользователя</span>
                </td>
                <td valign="top">
                    <select name="edit_minutes" style="width:200px">
                        <option value="0" <?php if(!$cfg['edit_minutes']) { echo 'selected'; } ?>>не запрещать</option>
                        <option value="-1" <?php if($cfg['edit_minutes']==-1) { echo 'selected'; } ?>>запрещать сразу</option>
                        <option value="1" <?php if($cfg['edit_minutes']==1) { echo 'selected'; } ?>>1 минуту</option>
                        <option value="5" <?php if($cfg['edit_minutes']==5) { echo 'selected'; } ?>>5 минут</option>
                        <option value="10" <?php if($cfg['edit_minutes']==10) { echo 'selected'; } ?>>10 минут</option>
                        <option value="15" <?php if($cfg['edit_minutes']==15) { echo 'selected'; } ?>>15 минут</option>
                        <option value="30" <?php if($cfg['edit_minutes']==30) { echo 'selected'; } ?>>30 минут</option>
                        <option value="60" <?php if($cfg['edit_minutes']==60) { echo 'selected'; } ?>>1 час</option>
                    </select>
                </td>
            </tr>
       </table>
       {tab=Изображения}
       <table width="609" border="0" cellpadding="10" cellspacing="0" class="proptable">
            <tr>
                <td colspan="2" valign="top" bgcolor="#EBEBEB"><h4>Вставка изображений в сообщения: </h4></td>
            </tr>
            <tr>
                <td valign="top" width="400px"><strong>Вставка изображений: </strong></td>
                <td valign="top">
                    <label><input name="img_on" type="radio" value="1" <?php if ($cfg['img_on']) { echo 'checked="checked"'; } ?> /> Вкл</label>
                    <label><input name="img_on" type="radio" value="0" <?php if (!$cfg['img_on']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <strong>Максимум изображений:</strong><br />
                    <span class="hinttext">Сколько изображений можно вставить в одно сообщение за раз при вставке из панели BB-code</span>
                </td>
                <td valign="top"><input name="img_max" type="text" id="img_max" value="<?php echo $cfg['img_max'];?>" size="5" /> шт.</td>
            </tr>
            <tr>
                <td valign="top"><strong>Водяной знак для фотографий: </strong></td>
                <td valign="top">
                    <label><input name="watermark" type="radio" value="1" <?php if ($cfg['watermark']) { echo 'checked="checked"'; } ?> /> Вкл</label>
                    <label><input name="watermark" type="radio" value="0" <?php if (!$cfg['watermark']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
       </table>
       {tab=Вложения}
       <table width="609" border="0" cellpadding="10" cellspacing="0" class="proptable">
            <tr>
                <td colspan="2" valign="top" bgcolor="#EBEBEB"><h4>Прикрепление файлов (аттачи) </h4></td>
            </tr>
            <tr>
                <td valign="top"><strong>Прикрепление файлов: </strong></td>
                <td valign="top">
                    <label><input name="fa_on" type="radio" value="1" <?php if ($cfg['fa_on']) { echo 'checked="checked"'; } ?> /> Вкл</label>
                    <label><input name="fa_on" type="radio" value="0" <?php if (!$cfg['fa_on']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <strong>Доступно для групп:</strong><br/>
                    <span class="hinttext">Какой из групп должен принадлежать пользователь, чтобы иметь возможность прикреплять файлы</span>
                </td>
                <td valign="top">
                  <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist" style="margin-top:5px">
                      <tr>
                          <td width="20">
                              <?php
                                $groups = cmsUser::getGroups();

                                $style  = 'disabled="disabled"';
                                $public = 'checked="checked"';

                                if ($cfg['group_access']){
                                    $public = '';
                                    $style  = '';
                                }

                              ?>
                              <input name="is_access" type="checkbox" id="is_access" onclick="checkGroupList()" value="1" <?php echo $public?> />
                          </td>
                          <td><label for="is_access"><strong>Все группы</strong></label></td>
                      </tr>
                  </table>
                  <div style="padding:5px">
                      <span class="hinttext">
                          Если отмечено, все группы пользователей смогут прикреплять файлы.
                      </span>
                  </div>

                  <div style="margin-top:10px;padding:5px;padding-right:0px;" id="grp">
                      <div>
                          <strong>Могут прикреплять только группы:</strong><br />
                          <span class="hinttext">
                              Можно выбрать несколько, удерживая CTRL.
                          </span>
                      </div>
                      <div>
                          <?php
                              echo '<select style="width: 245px" name="allow_group[]" id="showin" size="6" multiple="multiple" '.$style.'>';

                                if ($groups){
                                    foreach($groups as $group){
                                        if($group['alias'] != 'guest' && !$group['is_admin']){
                                            echo '<option value="'.$group['id'].'"';
                                            if ($cfg['group_access']){
                                                if (inArray($cfg['group_access'], $group['id'])){
                                                    echo 'selected';
                                                }
                                            }

                                            echo '>';
                                            echo $group['title'].'</option>';
                                        }
                                    }

                                }

                              echo '</select>';
                          ?>
                      </div>
                  </div>
<script type="text/javascript">
function checkGroupList(){
if($('input#is_access').attr('checked')){
    $('select#showin').attr('disabled', 'disabled');
} else {
    $('select#showin').attr('disabled', '');
}

}
</script>
               </td>
            </tr>
            <tr>
                <td valign="top">
                    <strong>Максимум файлов:</strong><br />
                    <span class="hinttext">Сколько файлов можно прикрепить к одному сообщению</span>
                </td>
                <td valign="top">
                    <input name="fa_max" type="text" id="fa_max" value="<?php echo $cfg['fa_max'];?>" size="5" /> шт.
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <strong>Разрешенные расширения: </strong><br />
                    <span class="hinttext">Список допустимых расширений, через пробел</span>
                </td>
                <td valign="top">
                    <textarea name="fa_ext" cols="35" rows="3" id="fa_ext"><?php echo $cfg['fa_ext'];?></textarea>
                </td>
            </tr>
            <tr>
                <td valign="top"><strong>Максимальный размер файла: </strong></td>
                <td valign="top">
                    <input name="fa_size" type="text" id="fa_size" value="<?php echo $cfg['fa_size'];?>" size="10" /> Кб
                </td>
            </tr>
        </table>
        {/tabs}
        <?php echo jwTabs(ob_get_clean()); ?>
        <p>
            <input name="opt" type="hidden" id="do" value="saveconfig" />
            <input name="save" type="submit" id="save" value="Сохранить" />
        </p>
    </form>
    <?php
}

if ($opt == 'list_ranks') {

    cpAddPathway('Звания на форуме', $_SERVER['REQUEST_URI']);

    ?>
        <form action="index.php?view=components&amp;do=config&amp;id=<?php echo $id;?>" method="post" name="addform" target="_self" id="form1">
        <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
            <table width="500" border="0" cellpadding="10" cellspacing="0" class="proptable" style="margin-bottom:2px">
                <tr>
                    <td align="center" valign="middle"><strong>Показывать звания для модераторов: </strong></td>
                    <td width="120" align="center" valign="middle">
                        <label><input name="modrank" type="radio" value="1" <?php if ($cfg['modrank']) { echo 'checked="checked"'; } ?> /> Да</label>
                        <label><input name="modrank" type="radio" value="0" <?php if (!$cfg['modrank']) { echo 'checked="checked"'; } ?>/> Нет</label>
                    </td>
                </tr>
            </table>
            <table width="500" border="0" cellpadding="10" cellspacing="0" class="proptable">
                <tr>
                    <td align="center" valign="middle" bgcolor="#EBEBEB"><strong>Звание</strong></td>
                    <td width="120" align="center" valign="middle" bgcolor="#EBEBEB"><strong>Необходимое число сообщений </strong></td>
                </tr>
                <?php for($r = 1; $r <= 10; $r++){ ?>
                <tr>
                    <td align="center" valign="top"><input type="text" name="rank[<?php echo $r?>][title]" style="width:250px;" value="<?php echo htmlspecialchars($cfg['ranks'][$r]['title']) ?>"></td>
                    <td align="center" valign="top"><input name="rank[<?php echo $r?>][msg]" type="text" id="" value="<?php echo htmlspecialchars($cfg['ranks'][$r]['msg']) ?>" size="10" /></td>
                </tr>
                <?php } ?>
            </table>
            <p>
                <input name="opt" type="hidden" id="do" value="saveranks" />
                <input name="save" type="submit" id="save" value="Сохранить" />
                <input name="back" type="button" id="back" value="Отмена" onclick="window.location.href='index.php?view=components&amp;do=config&amp;id=<?php echo $id;?>';"/>
            </p>
        </form>
    <?php
}


if ($opt == 'show_cat'){
    if(isset($_REQUEST['item_id'])) {
        $item_id = $_REQUEST['item_id'];
        $sql = "UPDATE cms_forum_cats SET published = 1 WHERE id = $item_id";
        $inDB->query($sql) ;
        echo '1'; exit;
    }
}

if ($opt == 'hide_cat'){
    if(isset($_REQUEST['item_id'])) {
        $item_id = $_REQUEST['item_id'];
        $sql = "UPDATE cms_forum_cats SET published = 0 WHERE id = $item_id";
        $inDB->query($sql) ;
        echo '1'; exit;
    }
}

if ($opt == 'submit_cat'){

    if (!cmsCore::validateForm()) { cmsCore::error404(); }

    $cat['title']     = cmsCore::request('title', 'str', 'Категория без названия');
    $cat['published'] = cmsCore::request('published', 'int');
    $cat['ordering']  = cmsCore::request('ordering', 'int');
    $cat['seolink']   = $model->getCatSeoLink($cat['title']);

    $inDB->insert('cms_forum_cats', $cat);

    cmsCore::addSessionMessage('Категория "'.$cat['title'].'" успешно создана!', 'info');

    cmsUser::clearCsrfToken();

    cmsCore::redirect('?view=components&do=config&id='.$id.'&opt=list_cats');

}

if($opt == 'delete_cat'){

    $item_id = cmsCore::request('item_id', 'int');
    $inDB->query("UPDATE cms_forums SET category_id = 0, published = 0  WHERE category_id = '$item_id'");
    $inDB->query("DELETE FROM cms_forum_cats WHERE id = '$item_id'");

    cmsCore::addSessionMessage('Категория успешно удалена! Привязка категории к форумам убрана, входящие в нее форумы скрыты.', 'info');

    cmsCore::redirect('?view=components&do=config&id='.$id.'&opt=list_cats');

}

if ($opt == 'update_cat'){

    if (!cmsCore::validateForm()) { cmsCore::error404(); }

    $item_id = cmsCore::request('item_id', 'int');

    $cat['title']     = cmsCore::request('title', 'str', 'Категория без названия');
    $cat['published'] = cmsCore::request('published', 'int');
    $cat['ordering']  = cmsCore::request('ordering', 'int');
    $cat['seolink']   = $model->getCatSeoLink($cat['title'], $item_id);

    $inDB->update('cms_forum_cats', $cat, $item_id);

    cmsUser::clearCsrfToken();

    cmsCore::redirect('?view=components&do=config&id='.$id.'&opt=list_cats');

}

if ($opt == 'list_cats'){

    cpAddPathway('Категории форумов', '?view=components&do=config&id='.$id.'&opt=list_cats');
    echo '<h3>Категории форумов</h3>';


    $fields = array();
    $fields[0]['title'] = 'id'; $fields[0]['field'] = 'id'; $fields[0]['width'] = '30';

    $fields[1]['title'] = 'Название'; $fields[1]['field'] = 'title'; $fields[1]['width'] = '';
    $fields[1]['link'] = '?view=components&do=config&id='.$id.'&opt=edit_cat&item_id=%id%';

    $fields[2]['title'] = 'Показ'; $fields[2]['field'] = 'published'; $fields[2]['width'] = '100';
    $fields[2]['do'] = 'opt'; $fields[2]['do_suffix'] = '_cat'; //Чтобы вместо 'do=hide&id=1' было 'opt=hide_albun&item_id=1'

    $actions = array();
    $actions[0]['title'] = 'Редактировать';
    $actions[0]['icon']  = 'edit.gif';
    $actions[0]['link']  = '?view=components&do=config&id='.$id.'&opt=edit_cat&item_id=%id%';

    $actions[1]['title'] = 'Удалить';
    $actions[1]['icon']  = 'delete.gif';
    $actions[1]['confirm'] = 'Удалить категорию?';
    $actions[1]['link']  = '?view=components&do=config&id='.$id.'&opt=delete_cat&item_id=%id%';

    cpListTable('cms_forum_cats', $fields, $actions);

}

if ($opt == 'list_forums'){

    echo '<h3>Форумы</h3>';


    $fields = array();

    $fields[0]['title'] = 'ID'; $fields[0]['field'] = 'id'; $fields[0]['width'] = '30';

    $fields[1]['title'] = 'Название'; $fields[1]['field'] = 'title'; $fields[1]['width'] = '';
    $fields[1]['filter'] = 15;
    $fields[1]['link'] = '?view=components&do=config&id='.$id.'&opt=edit_forum&item_id=%id%';

    $fields[2]['title'] = 'Темы'; $fields[2]['field'] = 'thread_count'; $fields[2]['width'] = '50';
    $fields[3]['title'] = 'Сообщения'; $fields[3]['field'] = 'post_count'; $fields[3]['width'] = '80';

    $fields[6]['title'] = 'Показ'; $fields[6]['field'] = 'published'; $fields[6]['width'] = '60';
    $fields[6]['do'] = 'opt'; $fields[6]['do_suffix'] = '_forum';

    $fields[7]['title'] = 'Категория'; $fields[7]['field'] = 'category_id';	$fields[7]['width'] = '150';
    $fields[7]['prc'] = 'cpForumCatById';  $fields[7]['filter'] = 1;  $fields[7]['filterlist'] = cpGetList('cms_forum_cats');

    $actions = array();
    $actions[0]['title'] = 'Редактировать';
    $actions[0]['icon']  = 'edit.gif';
    $actions[0]['link']  = '?view=components&do=config&id='.$id.'&opt=edit_forum&item_id=%id%';

    $actions[1]['title'] = 'Удалить';
    $actions[1]['icon']  = 'delete.gif';
    $actions[1]['confirm'] = 'Удалить форум?\nВместе с форумом будут удалены его подфорумы, темы и сообщения.';
    $actions[1]['link']  = '?view=components&do=config&id='.$id.'&opt=delete_forum&item_id=%id%';

    cpListTable('cms_forums', $fields, $actions, 'parent_id>0', 'NSLeft');

}

if ($opt == 'add_cat' || $opt == 'edit_cat'){

    if ($opt=='add_cat'){
         echo '<h3>Добавить категорию</h3>';
		 $mod['published'] = 1;
    } else {

        $mod = $model->getForumCat(cmsCore::request('item_id', 'int'));
        if(!$mod){ cmsCore::error404(); }

        echo '<h3>Редактировать категорию</h3>';

   }
    ?>
    <form id="addform" name="addform" method="post" action="index.php?view=components&amp;do=config&amp;id=<?php echo $id;?>">
    <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
        <table width="600" border="0" cellspacing="5" class="proptable">
            <tr>
                <td width="211" valign="top">Заголовок категории: </td>
                <td width="195" valign="top"><input name="title" type="text" id="title" size="30" value="<?php echo htmlspecialchars($mod['title']);?>"/></td>
                <td width="168" valign="top">&nbsp;</td>
            </tr>
            <tr>
                <td valign="top">Публиковать категорию?</td>
                <td valign="top">
                    <label><input name="published" type="radio" value="1" <?php if ($mod['published']) { echo 'checked="checked"'; } ?> /> Да</label>
                    <label><input name="published" type="radio" value="0"  <?php if (!$mod['published']) { echo 'checked="checked"'; } ?> /> Нет</label>
                </td>
                <td valign="top">&nbsp;</td>
            </tr>
            <tr>
                <td valign="top">Порядковый номер: </td>
                <td valign="top"><input name="ordering" type="text" id="ordering" value="<?php echo $mod['ordering'];?>" size="5" /></td>
                <td valign="top">&nbsp;</td>
            </tr>
        </table>
        <p>
            <input name="opt" type="hidden" id="opt" <?php if ($opt=='add_cat') { echo 'value="submit_cat"'; } else { echo 'value="update_cat"'; } ?> />
            <input name="add_mod" type="submit" id="add_mod" <?php if ($opt=='add_cat') { echo 'value="Создать категорию"'; } else { echo 'value="Сохранить категорию"'; } ?> />
            <input name="back2" type="button" id="back2" value="Отмена" onclick="window.location.href='index.php?view=components&do=config&id=<?php echo $id; ?>';"/>
            <?php
                if ($opt=='edit_cat'){
                    echo '<input name="item_id" type="hidden" value="'.$mod['id'].'" />';
                }
            ?>
        </p>
    </form>
    <?php
}

if ($opt == 'add_forum' || $opt == 'edit_forum'){

    if ($opt=='add_forum'){
         echo '<h3>Добавить форум</h3>';
         cpAddPathway('Добавить форум', '?view=components&do=config&id='.$id.'&opt=add_forum');
         $mod['published'] = 1;
    } else {
		if(isset($_REQUEST['multiple'])){
			if (isset($_REQUEST['item'])){
				$_SESSION['editlist'] = $_REQUEST['item'];
			} else {
				echo '<p class="error">Нет выбранных объектов!</p>';
				return;
			}
		 }

		 $ostatok = '';

		 if (isset($_SESSION['editlist'])){
			$item_id = array_shift($_SESSION['editlist']);
			if (sizeof($_SESSION['editlist'])==0) { unset($_SESSION['editlist']); } else
			{ $ostatok = '(На очереди: '.sizeof($_SESSION['editlist']).')'; }
		 } else { $item_id = cmsCore::request('item_id', 'int'); }

        $mod = $model->getForum($item_id);
        if(!$mod){ cmsCore::error404(); }

        echo '<h3>'.$mod['title'].' '.$ostatok.'</h3>';
        cpAddPathway($mod['title'], '?view=components&do=config&id='.$id.'&opt=edit_forum&item_id='.$item_id);

	}
    ?>
    <form action="index.php?view=components&do=config&id=<?php echo $id;?>" method="post" name="addform" id="addform" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
        <table width="614" border="0" cellspacing="10" class="proptable">
            <tr>
                <td width=""><strong>Название форума:</strong></td>
                <td width="450"><input name="title" type="text" id="title" size="30" value="<?php echo htmlspecialchars($mod['title']);?>" style="width:254px"/></td>
            </tr>
            <tr>
                <td valign="top"><strong>Описание форума:</strong></td>
                <td><textarea name="description" cols="35" rows="2" id="description" style="width:250px"><?php echo $mod['description']?></textarea></td>
            </tr>
            <tr>
                <td><strong>Публиковать форум?</strong></td>
                <td>
                    <label><input name="published" type="radio" value="1" checked="checked" <?php if ($mod['published']) { echo 'checked="checked"'; } ?> /> Да</label>
                    <label><input name="published" type="radio" value="0"  <?php if (!$mod['published']) { echo 'checked="checked"'; } ?> /> Нет</label>
                </td>
            </tr>
            <tr>
                <td><strong>Родительский форум:</strong></td>
                <td>
                    <?php $rootid = $inDB->get_field('cms_forums', 'parent_id=0', 'id'); ?>
                    <select name="parent_id" id="parent_id" style="width:260px">
                            <option value="<?php echo $rootid?>" <?php if ($mod['parent_id']==$rootid || !isset($mod['parent_id'])) { echo 'selected'; }?>>-- Корень форумов --</option>
                    <?php
                        if (isset($mod['parent_id'])){
                           echo $inCore->getListItemsNS('cms_forums', $mod['parent_id']);
                        } else {
                           echo $inCore->getListItemsNS('cms_forums');
                        }
                    ?>
                    </select>
               </td>
            </tr>
            <tr>
                <td><strong>Категория:</strong></td>
                <td>
                    <select name="category_id" id="category_id" style="width:260px">
                    <?php
                        if (isset($mod['category_id'])) {
                            echo $inCore->getListItems('cms_forum_cats', $mod['category_id'], 'ordering');
                        } else {
                            if (isset($_REQUEST['addto'])){
                                echo $inCore->getListItems('cms_forum_cats', $_REQUEST['addto'], 'ordering');
                            } else {
                               echo $inCore->getListItems('cms_forum_cats', 0, 'ordering');
                            }
                        }
                    ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td><strong>Показывать группе:</strong><br />
                  <span class="hinttext">
                      Можно выбрать несколько, удерживая CTRL.
                  </span>
                </td>
                <td>
                <?php
                $groups = cmsUser::getGroups();

                $style  = 'disabled="disabled"';
                $public = 'checked="checked"';

                if ($mod['access_list']){
                    $public = '';
                    $style  = '';

                    $access_list = $inCore->yamlToArray($mod['access_list']);

                }

                echo '<select style="width: 260px" name="access_list[]" id="showin" size="6" multiple="multiple" '.$style.'>';

                if ($groups){
                    foreach($groups as $group){
                        if(!$group['is_admin']){
                            echo '<option value="'.$group['id'].'"';
                            if ($access_list){
                                if (inArray($access_list, $group['id'])){
                                    echo 'selected';
                                }
                            }

                            echo '>';
                            echo $group['title'].'</option>';
                        }
                    }

                }

                echo '</select>';
                ?>

                <label><input name="is_access" type="checkbox" id="is_access" onclick="checkAccesList()" value="1" <?php echo $public?> /> <strong>Всем группам</strong></label>
                </td>
            </tr>
            <tr>
                <td><strong>Модераторы форума:</strong><br />
                  <span class="hinttext">
                      Выбранные пользователи будут иметь возможность модерировать темы этого форума.
                  </span>
                </td>
                <td>
                <?php

                if ($mod['moder_list']){
                    $public = '';
                    $style  = '';

                    $moder_list = $inCore->yamlToArray($mod['moder_list']);
                    if($moder_list){
                        $moder_list = cmsUser::getAuthorsList($moder_list, $moder_list);
                    }

                }

                echo '<select style="width: 260px" name="users_list" id="users_list">';
                echo cmsUser::getUsersList();
                echo '</select> <a class="ajaxlink" href="javascript:" onclick="addModer()">добавить выбранного</a><br>';
                ?>

                <select name="moder_list[]" size="8" multiple id="moder_list" style="width:260px; margin: 5px 0 0 0;">
                    <?php
                    if($moder_list){
                        echo $moder_list;
                    }
                    ?>
                </select>  <a class="ajaxlink" href="javascript:" onclick="deleteModer()">удалить выбранных</a>

                </td>
            </tr>
            <tr>
                <td><strong>Иконка форума:</strong><br/>
                    <span class="hinttext">файл размером 32px и менее вставляется оригиналом</span></td>
                <td valign="middle"> <?php if ($mod['icon']) { ?><img src="/upload/forum/cat_icons/<?php echo $mod['icon'];?>" border="0" /><?php } ?>
                    <input name="Filedata" type="file" style="width:215px; margin:0 0 0 5px; vertical-align:top" />
                </td>
            </tr>
            <tr>
                <td width="236">
                    <strong>Стоимость создания темы:</strong><br/>
                    <span class="hinttext">0 &mdash; бесплатно</span>
                </td>
                <td width="259">
                    <?php if (IS_BILLING) { ?>
                        <input name="topic_cost" type="text" id="title" value="<?php echo $mod['topic_cost'];?>" style="width:60px"/> баллов
                    <?php } else { ?>
                        требуется &laquo;<a href="http://www.instantcms.ru/billing/about.html">Биллинг пользователей</a>&raquo;
                    <?php } ?>
                </td>
            </tr>
    </table>
    <p>
        <input name="add_mod" type="submit" id="add_mod" <?php if ($opt=='add_forum') { echo 'value="Создать форум"'; } else { echo 'value="Сохранить форум"'; } ?> />
        <input name="back3" type="button" id="back3" value="Отмена" onclick="window.location.href='index.php?view=components&do=config&id=<?php echo $_REQUEST['id']; ?>';"/>
        <input name="opt" type="hidden" id="opt" <?php if ($opt=='add_forum') { echo 'value="submit_forum"'; } else { echo 'value="update_forum"'; } ?> />
        <?php
        if ($opt=='edit_forum'){
            echo '<input name="item_id" type="hidden" value="'.$mod['id'].'" />';
        }
        ?>
    </p>
    </form>
<script type="text/javascript">
$().ready(function() {
    $("#addform").submit(function() {
          $('#moder_list').each(function(){
              $('#moder_list option').attr("selected","selected");
          });
    });
});
function deleteModer(){
    $('#moder_list option:selected').each(function () {
        $(this).remove();
    });
}
function addModer(){
    $('#users_list option:selected').each(function () {
        $(this).appendTo('#moder_list');
    });
}
function checkAccesList(){
if(document.addform.is_access.checked){
    $('select#showin').attr('disabled', 'disabled');
} else {
    $('select#showin').attr('disabled', '');
}

}
</script>
 <?php
}
?>