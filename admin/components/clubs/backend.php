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
$inDB = cmsDatabase::getInstance();

$cfg = $inCore->loadComponentConfig('clubs');

$opt = cmsCore::request('opt', 'str', 'list');

$inCore->loadModel('clubs');
$model = new cms_model_clubs();

cpAddPathway('Клубы пользователей', '?view=components&do=config&id='.$_REQUEST['id'].'&opt=list');

if($opt=='list'){

    $toolmenu = array();

    $toolmenu[0]['icon'] = 'new.gif';
    $toolmenu[0]['title'] = 'Новый клуб';
    $toolmenu[0]['link'] = '?view=components&do=config&id='.$_REQUEST['id'].'&opt=add';

    $toolmenu[11]['icon'] = 'edit.gif';
    $toolmenu[11]['title'] = 'Редактировать выбранные';
    $toolmenu[11]['link'] = "javascript:checkSel('?view=components&do=config&id=".$_REQUEST['id']."&opt=edit&multiple=1');";

    $toolmenu[12]['icon'] = 'show.gif';
    $toolmenu[12]['title'] = 'Включить выбранные';
    $toolmenu[12]['link'] = "javascript:checkSel('?view=components&do=config&id=".$_REQUEST['id']."&opt=show_club&multiple=1');";

    $toolmenu[13]['icon'] = 'hide.gif';
    $toolmenu[13]['title'] = 'Отключить выбранные';
    $toolmenu[13]['link'] = "javascript:checkSel('?view=components&do=config&id=".$_REQUEST['id']."&opt=hide_club&multiple=1');";

    $toolmenu[14]['icon'] = 'config.gif';
    $toolmenu[14]['title'] = 'Настройки';
    $toolmenu[14]['link'] = '?view=components&do=config&id='.$_REQUEST['id'].'&opt=config';

}

if (in_array($opt, array('add', 'edit', 'config'))){

    $toolmenu[20]['icon'] = 'save.gif';
    $toolmenu[20]['title'] = 'Сохранить';
    $toolmenu[20]['link'] = 'javascript:document.addform.submit();';

    $toolmenu[21]['icon'] = 'cancel.gif';
    $toolmenu[21]['title'] = 'Отмена';
    $toolmenu[21]['link'] = '?view=components&do=config&id='.(int)$_REQUEST['id'];

}

if ($opt=='saveconfig'){
    if (!cmsCore::validateForm()) { cmsCore::error404(); }
    $cfg = array();
	$cfg['seo_club']        = $inCore->request('seo_club', 'str');
    $cfg['enabled_blogs']   = $inCore->request('enabled_blogs', 'str');
    $cfg['enabled_photos']  = $inCore->request('enabled_photos', 'str');
    $cfg['thumb1']          = $inCore->request('thumb1', 'int');
    $cfg['thumb2']          = $inCore->request('thumb2', 'int');
    $cfg['thumbsqr']        = $inCore->request('thumbsqr', 'int');
    $cfg['cancreate']       = $inCore->request('cancreate', 'int');
    $cfg['perpage']         = $inCore->request('perpage', 'int');
    $cfg['member_perpage']  = $inCore->request('member_perpage', 'int');
    $cfg['club_perpage']    = $inCore->request('club_perpage', 'int');
	$cfg['wall_perpage']    = $inCore->request('wall_perpage', 'int');
	$cfg['club_album_perpage'] = $inCore->request('club_album_perpage', 'int');
	$cfg['posts_perpage'] = $inCore->request('posts_perpage', 'int');
	$cfg['club_posts_perpage'] = $inCore->request('club_posts_perpage', 'int');
	$cfg['photo_perpage']   = $inCore->request('photo_perpage', 'int');
    $cfg['create_min_karma']  = $inCore->request('create_min_karma', 'int');
    $cfg['create_min_rating'] = $inCore->request('create_min_rating', 'int');
    $cfg['notify_in']       = $inCore->request('notify_in', 'int');
    $cfg['notify_out']      = $inCore->request('notify_out', 'int');
	$cfg['every_karma']     = $inCore->request('every_karma', 'int', 100);
	$cfg['photo_watermark'] = $inCore->request('photo_watermark', 'int', 0);
	$cfg['photo_thumb_small'] = $inCore->request('photo_thumb_small', 'int', 96);
	$cfg['photo_thumbsqr']    = $inCore->request('photo_thumbsqr', 'int', 0);
	$cfg['photo_thumb_medium'] = $inCore->request('photo_thumb_medium', 'int', 450);
	$cfg['photo_maxcols'] = $inCore->request('photo_maxcols', 'int', 4);

    $inCore->saveComponentConfig('clubs', $cfg);

    cmsCore::addSessionMessage('Настройки успешно сохранены', 'success');

    cmsUser::clearCsrfToken();

	cmsCore::redirectBack();

}

if ($opt == 'show_club'){
    if (!isset($_REQUEST['item'])){
        if (isset($_REQUEST['item_id'])){ dbShow('cms_clubs', $_REQUEST['item_id']);  }
        echo '1'; exit;
    } else {
        dbShowList('cms_clubs', $_REQUEST['item']);
        cmsCore::redirectBack();
    }
}

if ($opt == 'hide_club'){
    if (!isset($_REQUEST['item'])){
        if (isset($_REQUEST['item_id'])){ dbHide('cms_clubs', $_REQUEST['item_id']);  }
        echo '1'; exit;
    } else {
        dbHideList('cms_clubs', $_REQUEST['item']);
        cmsCore::redirectBack();
    }
}

if ($opt == 'submit'){
    if (!cmsCore::validateForm()) { cmsCore::error404(); }
    $title 			= $inCore->request('title', 'str', 'Клуб без названия');
    $description 	= $inCore->request('description', 'html');
    $description    = $inDB->escape_string($description);
    $published 		= $inCore->request('published', 'int');
    $admin_id 		= $inCore->request('admin_id', 'int');
    $clubtype		= $inCore->request('clubtype', 'str');
    $maxsize 		= $inCore->request('maxsize', 'int');
    $enabled_blogs	= $inCore->request('enabled_blogs', 'int');
    $enabled_photos	= $inCore->request('enabled_photos', 'int');

    $date = explode('.', $_REQUEST['pubdate']);
    $pubdate = $date[2] . '-' . $date[1] . '-' . $date[0];

	$new_imageurl = $model->uploadClubImage();
	$filename = @$new_imageurl['filename'] ? $new_imageurl['filename'] : '';

	$id = $model->addClub(array('admin_id'=>$admin_id,
									 'title'=>$title,
									 'description'=>$description,
									 'imageurl'=>$filename,
									 'pubdate'=>$pubdate,
									 'clubtype'=>$clubtype,
									 'published'=>$published,
									 'maxsize'=>$maxsize,
									 'create_karma'=>cmsUser::getKarma($admin_id),
									 'enabled_blogs'=>$enabled_blogs,
									 'enabled_photos'=>$enabled_photos));

	cmsCore::addSessionMessage('Клуб успешно создан', 'success');
    cmsUser::clearCsrfToken();
	$inCore->redirect('index.php?view=components&do=config&opt=list&id='.$_REQUEST['id']);

}

if ($opt == 'update'){
    if (!cmsCore::validateForm()) { cmsCore::error404(); }
	$id = $inCore->request('item_id', 'int');
	$new_club['title'] 			= $inCore->request('title', 'str', 'Клуб без названия');
	$description 	= $inCore->request('description', 'html');
	$new_club['description']    = $inDB->escape_string($description);
	$new_club['published'] 		= $inCore->request('published', 'int');
	$new_club['admin_id'] 		= $inCore->request('admin_id', 'int');
	$new_club['clubtype']		= $inCore->request('clubtype', 'str');
	$new_club['maxsize'] 		= $inCore->request('maxsize', 'int');
    $new_club['enabled_blogs']	= $inCore->request('enabled_blogs', 'int');
    $new_club['enabled_photos']	= $inCore->request('enabled_photos', 'int');

	$olddate 		= $inCore->request('olddate', 'str');
	$pubdate 		= $inCore->request('pubdate', 'str');

	$club = $model->getClub($id);
	if(!$club){	cmsCore::error404(); }

	if ($olddate != $pubdate){
		$date = explode('.', $pubdate);
		$new_club['pubdate'] = $date[2] . '-' . $date[1] . '-' . $date[0];
	}

	$new_imageurl = $model->uploadClubImage($club['imageurl']);
	$new_club['imageurl'] = @$new_imageurl['filename'] ? $new_imageurl['filename'] : $club['imageurl'];

	$model->updateClub($id, $new_club);

	cmsCore::addSessionMessage('Настройки клуба "'.$club['title'].'" обновлены', 'success');

    cmsUser::clearCsrfToken();

    if (!isset($_SESSION['editlist']) || @sizeof($_SESSION['editlist'])==0){
        $inCore->redirect('index.php?view=components&do=config&id='.$_REQUEST['id'].'&opt=list');
    } else {
        $inCore->redirect('index.php?view=components&do=config&id='.$_REQUEST['id'].'&opt=edit');
    }

}

if($opt == 'delete'){
    if(isset($_REQUEST['item_id'])) {
        $id     = (int)$_REQUEST['item_id'];
        $model->deleteClub($id);
    }
    $inCore->redirect('index.php?view=components&do=config&id='.$_REQUEST['id'].'&opt=list');
}

cpToolMenu($toolmenu);

if ($opt == 'list'){
    echo '<h3>Клубы пользователей</h3>';

    //TABLE COLUMNS
    $fields = array();

    $fields[0]['title'] = 'id';			$fields[0]['field'] = 'id';			$fields[0]['width'] = '30';

    $fields[1]['title'] = 'Дата';		$fields[1]['field'] = 'pubdate';		$fields[1]['width'] = '100';		$fields[1]['filter'] = 15;
    $fields[1]['fdate'] = '%d/%m/%Y';

    $fields[2]['title'] = 'Название';	$fields[2]['field'] = 'title';		$fields[2]['width'] = '';
    $fields[2]['filter'] = 15;
    $fields[2]['link'] = '?view=components&do=config&id='.$_REQUEST['id'].'&opt=edit&item_id=%id%';

    $fields[3]['title'] = 'Тип';	$fields[3]['field'] = 'clubtype';		$fields[3]['width'] = '100';

    $fields[4]['title'] = 'Участников';	$fields[4]['field'] = 'members_count';		$fields[4]['width'] = '80';

    $fields[5]['title'] = 'Активен';		$fields[5]['field'] = 'published';		$fields[5]['width'] = '100';
    $fields[5]['do'] = 'opt'; $fields[5]['do_suffix'] = '_club';

    //ACTIONS
    $actions = array();
    $actions[0]['title'] = 'Редактировать';
    $actions[0]['icon']  = 'edit.gif';
    $actions[0]['link']  = '?view=components&do=config&id='.$_REQUEST['id'].'&opt=edit&item_id=%id%';

    $actions[1]['title'] = 'Удалить';
    $actions[1]['icon']  = 'delete.gif';
    $actions[1]['confirm'] = 'Удалить клуб?';
    $actions[1]['link']  = '?view=components&do=config&id='.$_REQUEST['id'].'&opt=delete&item_id=%id%';

    //Print table
    cpListTable('cms_clubs', $fields, $actions, '', 'pubdate DESC');
}

if ($opt == 'add' || $opt == 'edit'){

    if ($opt=='add'){
        echo '<h3>Добавить клуб</h3>';
		cpAddPathway('Добавить клуб', '?view=components&do=config&id='.$_REQUEST['id'].'&opt=add');
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
            $id = array_shift($_SESSION['editlist']);
            if (sizeof($_SESSION['editlist'])==0) {
                unset($_SESSION['editlist']);
            } else {
                $ostatok = '(На очереди: '.sizeof($_SESSION['editlist']).')';
            }
        } else {
            $id = $_REQUEST['item_id'];
        }

        $mod = $model->getClub($id);
		if(!$mod){ cmsCore::error404(); }

        echo '<h3>'.$mod['title'].' '.$ostatok.'</h3>';
        cpAddPathway($mod['title'], '?view=components&do=config&id='.$_REQUEST['id'].'&opt=edit&item_id='.$id);

    }

    if(!isset($mod['maxsize'])) { $mod['maxsize'] = 0; }
    if(!isset($mod['admin_id'])) { $mod['admin_id'] = $inUser->id; }
    if(!isset($mod['clubtype'])) { $mod['clubtype'] = 'public'; }

    require('../includes/jwtabs.php');
    $GLOBALS['cp_page_head'][] = jwHeader();

    ob_start(); ?>

<form action="index.php?view=components&amp;do=config&amp;id=<?php echo $_REQUEST['id'];?>" method="post" enctype="multipart/form-data" name="addform" id="addform">
<input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
    {tab=Обшие настройки}
    <table width="625" border="0" cellspacing="5" class="proptable">
        <tr>
            <td width="298"><strong>Название клуба: </strong><br />
            <span class="hinttext">Отображается на сайте</span>					</td>
            <td width="308"><input name="title" type="text" id="title" style="width:300px" value="<?php echo htmlspecialchars($mod['title']);?>"/></td>
        </tr>
        <tr>
            <td><strong>Логотип клуба:</strong><br />
            <span class="hinttext">Только GIF, JPG, JPEG, PNG </span>					</td>
            <td>
                <?php if (@$mod['imageurl']){ echo '<div style="margin-bottom:5px;"><img src="/images/clubs/small/'.$mod['imageurl'].'" /></div>'; } ?>
                <input name="picture" type="file" id="picture" size="33" />
            </td>
        </tr>
        <tr>
            <td><strong>Максимальный размер: </strong><br />
                <span class="hinttext">Введите &quot;0&quot; для бесконечного <br />
            числа участников </span></td>
            <td><input name="maxsize" type="text" id="maxsize" style="width:300px" value="<?php echo @$mod['maxsize'];?>"/></td>
        </tr>
        <tr>
            <td><strong>Дата создания клуба:</strong><br />
            <span class="hinttext">Отображается на сайте</span></td>
            <td><input name="pubdate" type="text" id="pubdate" style="width:278px" <?php if(@!$mod['pubdate']) { echo 'value="'.date('Y-m-d').'"'; } else { echo 'value="'.$mod['pubdate'].'"'; } ?>/>
                <?php
                //include javascript
                $GLOBALS['cp_page_head'][] = '<script language="JavaScript" type="text/javascript" src="/includes/jquery/jquery.js"></script>';
                $GLOBALS['cp_page_head'][] = '<script language="JavaScript" type="text/javascript" src="/includes/jquery/datepicker/date_ru_win1251.js"></script>';
                $GLOBALS['cp_page_head'][] = '<script language="JavaScript" type="text/javascript" src="/includes/jquery/datepicker/datepicker.js"></script>';
                $GLOBALS['cp_page_head'][] = '<link href="/includes/jquery/datepicker/datepicker.css" rel="stylesheet" type="text/css" />';
                if (@!$mod['pubdate']){
                    $GLOBALS['cp_page_head'][] = '<script type="text/javascript">$(document).ready(function(){$(\'#pubdate\').datePicker({startDate:\'01/01/1996\'}).val(new Date().asString()).trigger(\'change\');});</script>';
                } else {
                    $GLOBALS['cp_page_head'][] = '<script type="text/javascript">$(document).ready(function(){$(\'#pubdate\').datePicker({startDate:\'01/01/1996\'}).val(\''.$mod['pubdate'].'\').trigger(\'change\');});</script>';
                }
                ?>
            <input type="hidden" name="olddate" value="<?php echo @$mod['pubdate']?>"/></td>
        </tr>
        <tr>
            <td>
                <strong>Публиковать клуб?</strong><br />
                <span class="hinttext">При выключении клуб не отображается в общем списке<br />
                и не работает</span>
            </td>
            <td><input name="published" type="radio" value="1" checked="checked" <?php if (@$mod['published']) { echo 'checked="checked"'; } ?> />
                Да
                <label>
                    <input name="published" type="radio" value="0"  <?php if (@!$mod['published']) { echo 'checked="checked"'; } ?> />
            Нет</label></td>
        </tr>
        <tr>
            <td><strong>Блог:</strong><br />
            <span class="hinttext">Включить/выключить блог клуба</span></td>
            <td>
                <select name="enabled_blogs" id="enabled_blogs" style="width:300px">
                    <option value="-1" <?php if (@$mod['orig_enabled_blogs']=='-1') { echo 'selected="selected"'; } ?>>По-умолчанию</option>
                    <option value="1" <?php if (@$mod['orig_enabled_blogs']=='1') { echo 'selected="selected"'; } ?>>Включен</option>
                    <option value="0" <?php if (@$mod['orig_enabled_blogs']=='0') { echo 'selected="selected"'; } ?>>Отключен</option>
                </select>
            </td>
        </tr>
        <tr>
            <td><strong>Фотоальбомы:</strong><br />
            <span class="hinttext">Включить/выключить фотоальбомы</span></td>
            <td>
                <select name="enabled_photos" id="enabled_photos" style="width:300px">
                    <option value="-1" <?php if (@$mod['orig_enabled_photos']=='-1') { echo 'selected="selected"'; } ?>>По-умолчанию</option>
                    <option value="1" <?php if (@$mod['orig_enabled_photos']=='1') { echo 'selected="selected"'; } ?>>Включены</option>
                    <option value="0" <?php if (@$mod['orig_enabled_photos']=='0') { echo 'selected="selected"'; } ?>>Отключены</option>
                </select>
            </td>
        </tr>
    </table>
    {tab=Описание}
    <table width="100%" border="0" cellspacing="5" class="proptable">
        <tr>
            <td><strong>Описание:</strong> <span class="hinttext">Отображается на первой странице при просмотре клуба </span></td>
        </tr>
        <tr>
            <td>
                <?php

                    $inCore->insertEditor('description', $mod['description'], '400', '100%');

                ?>
            </td>
        </tr>
    </table>
    {tab=Права доступа}
    <table width="625" border="0" cellspacing="5" class="proptable">
        <tr>
            <td width="298"><strong>Главный администратор клуба:</strong><br />
            <span class="hinttext">Назначает модераторов </span></td>
            <td width="308">
                <select name="admin_id" id="admin_id" style="width:300px">
                    <?php
                        if (isset($mod['admin_id'])) {
                            echo $inCore->getListItems('cms_users', $mod['admin_id'], 'nickname', 'ASC', 'is_deleted=0 AND is_locked=0', 'id', 'nickname');
                        } else {
                            echo $inCore->getListItems('cms_users', 0, 'nickname', 'ASC', 'is_deleted=0 AND is_locked=0', 'id', 'nickname');
                        }
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <td><strong>Тип клуба:</strong><br />
            <span class="hinttext">Для кого открыт этот клуб </span></td>
            <td>
                <select name="clubtype" id="clubtype" style="width:300px">
                    <option value="public" <?php if (@$mod['clubtype']=='public') { echo 'selected="selected"'; } ?>>Открыт для всех (public)</option>
                    <option value="private" <?php if (@$mod['clubtype']=='private') { echo 'selected="selected"'; } ?>>Открыт для избранных (private)</option>
                </select>
            </td>
        </tr>
        <tr>
            <td colspan="2">
			<?php if($opt == 'edit'){ ?>
				Участников и модераторов клуба вы можете <a target="_blank" href="/clubs/<?php echo $mod['id']; ?>/config.html#moders">редактировать на сайте</a>.
			<?php } ?>
		    </td>
    	</tr>
    </table>
    {/tabs}
    <p>
        <input name="add_mod" type="submit" id="add_mod" <?php if ($opt=='add') { echo 'value="Создать клуб"'; } else { echo 'value="Сохранить клуб"'; } ?> />
        <input name="back3" type="button" id="back3" value="Отмена" onclick="window.location.href='index.php?view=components';"/>
        <input name="opt" type="hidden" id="opt" <?php if ($opt=='add') { echo 'value="submit"'; } else { echo 'value="update"'; } ?> />
        <?php
        if ($opt=='edit'){
            echo '<input name="item_id" type="hidden" value="'.$mod['id'].'" />';
        }
        ?>
    </p>
</form>

    <?php	echo jwTabs(ob_get_clean());

}

if ($opt=='config') {

	$GLOBALS['cp_page_head'][] = '<script type="text/javascript" src="/includes/jquery/tabs/jquery.ui.min.js"></script>';
	$GLOBALS['cp_page_head'][] = '<link href="/includes/jquery/tabs/tabs.css" rel="stylesheet" type="text/css" />';

    cpAddPathway('Настройки', '?view=components&do=config&id='.$_REQUEST['id'].'&opt=config');
    echo '<h3>Клубы пользователей</h3>';

    ?>

<form action="index.php?view=components&do=config&id=<?php echo $_REQUEST['id'];?>" method="post" name="addform" id="addform" target="_self">
<input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
<div id="config_tabs" style="margin-top:12px;">

    <ul id="tabs">
        <li><a href="#basic"><span>Общие</span></a></li>
        <li><a href="#limits"><span>Ограничения списков</span></a></li>
        <li><a href="#photos"><span>Фото</span></a></li>
        <li><a href="#restrict"><span>Ограничения</span></a></li>
    </ul>

	<div id="basic">
    <table width="680" border="0" cellpadding="10" cellspacing="0" class="proptable">
        <tr>
            <td><strong>SEO для клубов:</strong><br />
            <span class="hinttext">Чем заполнять тег meta description при просмотре клуба?</span></td>
            <td width="300">
                <select name="seo_club" id="seo_club" style="width:300px">
                    <option value="deskr" <?php if ($cfg['seo_club']=='deskr') { echo 'selected="selected"'; } ?>>Из описания клуба</option>
                    <option value="title" <?php if ($cfg['seo_club']=='title') { echo 'selected="selected"'; } ?>>Из заголовка клуба</option>
                    <option value="def" <?php if ($cfg['seo_club']=='def') { echo 'selected="selected"'; } ?>>По умолчанию для сайта</option>
            </select>			</td>
        </tr>
        <tr>
            <td><strong>Блоги клубов:</strong><br />
            <span class="hinttext">Включить/выключить блоги</span></td>
            <td width="300">
                <select name="enabled_blogs" id="enabled_blogs" style="width:300px">
                    <option value="1" <?php if ($cfg['enabled_blogs']=='1') { echo 'selected="selected"'; } ?>>Включены</option>
                    <option value="0" <?php if (!$cfg['enabled_blogs']) { echo 'selected="selected"'; } ?>>Отключены</option>
            </select>			</td>
        </tr>
        <tr>
            <td><strong>Ширина маленькой копии лого:</strong><br />
            <span class="hinttext">В пикселях</span></td>
            <td><input name="thumb1" type="text" id="thumb1" style="width:300px" value="<?php echo $cfg['thumb1'];?>"/></td>
        </tr>
        <tr>
            <td><strong>Ширина основной копии лого:</strong><br />
            <span class="hinttext">В пикселях</span></td>
            <td><input name="thumb2" type="text" id="thumb2" style="width:300px" value="<?php echo $cfg['thumb2'];?>"/></td>
        </tr>
        <tr>
            <td><strong>Квадратные логотипы:</strong></td>
            <td>
                <select name="thumbsqr" id="select" style="width:300px">
                    <option value="1" <?php if ($cfg['thumbsqr']=='1') { echo 'selected="selected"'; } ?>>Да</option>
                    <option value="0" <?php if ($cfg['thumbsqr']=='0') { echo 'selected="selected"'; } ?>>Нет</option>
            </select>			</td>
        </tr>
        <tr>
            <td>
                <strong>Уведомления о принятии в клуб:</strong><br />
                <span class="hinttext">Посылать личное сообщение пользователю,<br/>принятому в приватный клуб</span>
            </td>
            <td valign="top">
                <label><input name="notify_in" type="radio" value="1"  <?php if ($cfg['notify_in']) { echo 'checked="checked"'; } ?> /> Да</label>
                <label><input name="notify_in" type="radio" value="0"  <?php if (!$cfg['notify_in']) { echo 'checked="checked"'; } ?> /> Нет</label>
            </td>
        </tr>
        <tr>
            <td>
                <strong>Уведомления о исключении из клуба:</strong><br />
                <span class="hinttext">Посылать личное сообщение пользователю, исключенному из приватного клуба</span>
            </td>
            <td valign="top">
                <label><input name="notify_out" type="radio" value="1"  <?php if ($cfg['notify_out']) { echo 'checked="checked"'; } ?> /> Да</label>
                <label><input name="notify_out" type="radio" value="0"  <?php if (!$cfg['notify_out']) { echo 'checked="checked"'; } ?> /> Нет</label>
            </td>
        </tr>
    </table>
	</div>
	<div id="limits">
    <table width="680" border="0" cellpadding="10" cellspacing="0" class="proptable">
        <tr>
            <td><strong>Количество клубов на странице:</strong><br /></td>
            <td><input name="perpage" type="text" style="width:300px" value="<?php echo $cfg['perpage'];?>"/></td>
        </tr>
        <tr>
            <td><strong>Количество участников на странице клуба:</strong><br /></td>
            <td><input name="club_perpage" type="text" style="width:300px" value="<?php echo $cfg['club_perpage'];?>"/></td>
        </tr>
        <tr>
            <td><strong>Количество участников на странице их списка:</strong><br /></td>
            <td><input name="member_perpage" type="text" style="width:300px" value="<?php echo $cfg['member_perpage'];?>"/></td>
        </tr>
        <tr>
            <td><strong>Количество записей на стене клуба:</strong><br /></td>
            <td><input name="wall_perpage" type="text" style="width:300px" value="<?php echo $cfg['wall_perpage'];?>"/></td>
        </tr>
        <tr>
            <td><strong>Количество постов блога на странице клуба:</strong><br /></td>
            <td><input name="club_posts_perpage" type="text" style="width:300px" value="<?php echo $cfg['club_posts_perpage'];?>"/></td>
        </tr>
        <tr>
            <td><strong>Количество постов при просмотре блога клуба:</strong><br /></td>
            <td><input name="posts_perpage" type="text" style="width:300px" value="<?php echo $cfg['posts_perpage'];?>"/></td>
        </tr>
        <tr>
            <td><strong>Количество фотоальбомов на странице клуба:</strong><br /></td>
            <td><input name="club_album_perpage" type="text" style="width:300px" value="<?php echo $cfg['club_album_perpage'];?>"/></td>
        </tr>
        <tr>
            <td><strong>Количество фото на странице фотоальбома клуба:</strong><br /></td>
            <td><input name="photo_perpage" type="text" style="width:300px" value="<?php echo $cfg['photo_perpage'];?>"/></td>
        </tr>
    </table>
	</div>
    <div id="photos">
    <table width="680" border="0" cellpadding="10" cellspacing="0" class="proptable">
        <tr>
            <td><strong>Фотоальбомы клубов:</strong><br />
            <span class="hinttext">Включить/выключить фотоальбомы </span></td>
            <td>
                <select name="enabled_photos" id="enabled_photos" style="width:300px">
                    <option value="1" <?php if ($cfg['enabled_photos']=='1') { echo 'selected="selected"'; } ?>>Включены</option>
                    <option value="0" <?php if (!$cfg['enabled_photos']) { echo 'selected="selected"'; } ?>>Отключены</option>
            </select>            </td>
        </tr>
        <tr>
          <td><strong>Наносить водяной знак:</strong><br>
            <span class="hinttext">
              Если включено, то на все загружаемые фотографии будет наносится изображение из файла "<a href="/images/watermark.png" target="_blank">/images/watermark.png</a>"
            </span>
          </td>
          <td>
            <label><input name="photo_watermark" type="radio" value="1"  <?php if ($cfg['photo_watermark']) { echo 'checked="checked"'; } ?>> Да</label>
            <label><input name="photo_watermark" type="radio" value="0" <?php if (!$cfg['photo_watermark']) { echo 'checked="checked"'; } ?>> Нет</label>
          </td>
        </tr>
        <tr>
          <td><strong>Ширина маленькой копии:</strong></td>
          <td>
            <table border="0" cellspacing="0" cellpadding="1">
              <tbody>
                <tr>
                  <td width="100" valign="middle">
                    <input name="photo_thumb_small" type="text" size="3" value="<?php echo $cfg['photo_thumb_small']; ?>"> пикс.
                  </td>
                  <td width="100" align="center" valign="middle">Квадратные:</td>
                  <td width="115" align="center" valign="middle">
                    <label><input name="photo_thumbsqr" type="radio" value="1" <?php if ($cfg['photo_thumbsqr']) { echo 'checked="checked"'; } ?>> Да </label>
                    <label><input name="photo_thumbsqr" type="radio" value="0" <?php if (!$cfg['photo_thumbsqr']) { echo 'checked="checked"'; } ?>> Нет</label>
                  </td>
                </tr>
              </tbody>
            </table>
          </td>
        </tr>
        <tr>
          <td><strong>Ширина средней копии:</strong></td>
          <td><input name="photo_thumb_medium" type="text" size="3" value="<?php echo $cfg['photo_thumb_medium']; ?>"> пикс.</td>
        </tr>
        <tr>
          <td><strong>Число колонок для вывода:</strong></td>
          <td><input name="photo_maxcols" type="text" size="5" value="<?php echo $cfg['photo_maxcols']; ?>"></td>
        </tr>
    </table>
    </div>
    <div id="restrict">
    <table width="680" border="0" cellpadding="10" cellspacing="0" class="proptable">
        <tr>
            <td><strong>Создание клубов пользователями:</strong><br />
                <span class="hinttext">Если включено, каждый пользователь может<br />
            создать собственный клуб</span></td>
            <td valign="top">
                <label><input name="cancreate" type="radio" value="1"  <?php if ($cfg['cancreate']) { echo 'checked="checked"'; } ?> /> Да</label>
            	<label><input name="cancreate" type="radio" value="0"  <?php if (!$cfg['cancreate']) { echo 'checked="checked"'; } ?> /> Нет</label>
            </td>
        </tr>
        <tr>
            <td><strong>Шаг кармы для создания нового клуба:</strong><br />
            <span class="hinttext">0 - можно создавать только один клуб</span></td>
            <td valign="top"><input name="every_karma" type="text" id="every_karma" style="width:300px" value="<?php echo $cfg['every_karma'];?>"/></td>
        </tr>
        <tr>
            <td><strong>Ограничение по карме на создание клубов:</strong><br />
            <span class="hinttext">Пользователь должен иметь  карму не ниже указанной, чтобы иметь возможность создавать клубы </span></td>
            <td valign="top"><input name="create_min_karma" type="text" id="create_min_karma" style="width:300px" value="<?php echo $cfg['create_min_karma'];?>"/></td>
        </tr>
        <tr>
            <td><strong>Ограничение по рейтингу на создание клубов:</strong><br />
            <span class="hinttext">Пользователь должен иметь рейтинг не ниже указанного, чтобы иметь возможность создавать клубы</span></td>
            <td valign="top"><input name="create_min_rating" type="text" id="create_min_rating" style="width:300px" value="<?php echo $cfg['create_min_rating'];?>"/></td>
        </tr>
    </table>
    </div>

</div>
    <p>
        <input name="opt" type="hidden" value="saveconfig" />
        <input name="save" type="submit" id="save" value="Сохранить" />
        <input name="back" type="button" id="back" value="Отмена" onclick="window.location.href='index.php?view=components&do=config&id=<?php echo $_REQUEST['id']; ?>'"/>
    </p>
</form>
<script type="text/javascript">$('#config_tabs > ul#tabs').tabs();</script>
<?php } ?>