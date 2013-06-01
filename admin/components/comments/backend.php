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

function cpStripComment($text){

	$text = strip_tags($text);

    if (sizeof($text) < 120) { return $text; }

    return mb_substr($text, 0, 120) . '...';

}

$opt = cmsCore::request('opt', 'str', 'list');
$id  = cmsCore::request('id', 'int', 0);

cpAddPathway('Комментарии пользователей', '?view=components&do=config&id='.$id);

$toolmenu[1]['icon'] = 'listcomments.gif';
$toolmenu[1]['title'] = 'Все комментарии';
$toolmenu[1]['link'] = '?view=components&do=config&id='.$id.'&opt=list';

$toolmenu[2]['icon'] = 'config.gif';
$toolmenu[2]['title'] = 'Настройки компонента';
$toolmenu[2]['link'] = '?view=components&do=config&id='.$id.'&opt=config';

cpToolMenu($toolmenu);

cmsCore::loadModel('comments');
$model = new cms_model_comments();

$cfg = $model->config;

if ($opt=='saveconfig'){

    if (!cmsCore::validateForm()) { cmsCore::error404(); }

	$cfg['email']          = cmsCore::request('email', 'email', '');
	$cfg['regcap']         = cmsCore::request('regcap', 'int');
	$cfg['subscribe']      = cmsCore::request('subscribe', 'int');
	$cfg['min_karma'] 	   = cmsCore::request('min_karma', 'int');
	$cfg['min_karma_show'] = cmsCore::request('min_karma_show', 'int');
	$cfg['min_karma_add']  = cmsCore::request('min_karma_add', 'int');
	$cfg['perpage'] 	   = cmsCore::request('perpage', 'int');
	$cfg['cmm_ajax'] 	   = cmsCore::request('cmm_ajax', 'int');
	$cfg['cmm_ip'] 		   = cmsCore::request('cmm_ip', 'int');
	$cfg['max_level'] 	   = cmsCore::request('max_level', 'int');
	$cfg['edit_minutes']   = cmsCore::request('edit_minutes', 'int');
	$cfg['watermark'] 	   = cmsCore::request('watermark', 'int');

	$inCore->saveComponentConfig('comments', $cfg);

    cmsCore::addSessionMessage('Настройки успешно сохранены', 'success');

    cmsUser::clearCsrfToken();

	cmsCore::redirectBack();

}

if ($opt == 'show_comment'){
	if(isset($_REQUEST['item_id'])) {
		$item_id = cmsCore::request('item_id', 'int');
		$sql = "UPDATE cms_comments SET published = 1 WHERE id = $item_id";
		$inDB->query($sql) ;
		echo '1'; exit;
	}
}

if ($opt == 'hide_comment'){
	if(isset($_REQUEST['item_id'])) {
		$item_id = cmsCore::request('item_id', 'int');
		$sql = "UPDATE cms_comments SET published = 0 WHERE id = $item_id";
		$inDB->query($sql) ;
		echo '1'; exit;
	}
}

if ($opt == 'update'){

    if (!cmsCore::validateForm()) { cmsCore::error404(); }

	if(isset($_REQUEST['item_id'])) {

		$item_id = cmsCore::request('item_id', 'int');

		$guestname = cmsCore::request('guestname', 'str', '');
		$pubdate   = cmsCore::request('pubdate', 'str');
		$published = cmsCore::request('published', 'int');
		$content   = cmsCore::request('content', 'html');
		$content   = $inDB->escape_string($content);

		$sql = "UPDATE cms_comments
				SET guestname = '$guestname',
					pubdate = '$pubdate',
					published=$published,
					content='$content'
				WHERE id = $item_id
				LIMIT 1";
		$inDB->query($sql) ;

        cmsUser::clearCsrfToken();

        cmsCore::redirect('index.php?view=components&do=config&id='.$id.'&opt=list');

	}

}

if($opt == 'delete'){
	if(isset($_REQUEST['item_id'])) {

		$model->deleteComment(cmsCore::request('item_id', 'int'));
		cmsCore::redirect('index.php?view=components&do=config&id='.$id.'&opt=list');
	}
}

if ($opt == 'list'){

	echo '<h3>Все комментарии</h3>';

	//TABLE COLUMNS
	$fields = array();

	$fields[0]['title'] = 'id';			$fields[0]['field'] = 'id';			$fields[0]['width'] = '30';

	$fields[1]['title'] = 'Дата';		$fields[1]['field'] = 'pubdate';	$fields[1]['width'] = '100';

	$fields[2]['title'] = 'Текст';		$fields[2]['field'] = 'content';	$fields[2]['width'] = '';
	$fields[2]['prc'] = 'cpStripComment';

	$fields[3]['title'] = 'IP';			$fields[3]['field'] = 'ip';			$fields[3]['width'] = '80';

	$fields[4]['title'] = 'Показ';      $fields[4]['field'] = 'published';	$fields[4]['width'] = '50';
	$fields[4]['do'] = 'opt';           $fields[4]['do_suffix'] = '_comment';

	$fields[5]['title'] = 'Автор';		$fields[5]['field'] = 'id';			$fields[5]['width'] = '180';
	$fields[5]['prc'] = 'cpCommentAuthor';

	$fields[6]['title'] = 'Цель';		$fields[6]['field'] = 'id';			$fields[6]['width'] = '220';
	$fields[6]['prc'] = 'cpCommentTarget';

	//ACTIONS
	$actions = array();
	$actions[0]['title'] = 'Редактировать';
	$actions[0]['icon']  = 'edit.gif';
	$actions[0]['link']  = '?view=components&do=config&id='.$id.'&opt=edit&item_id=%id%';

	$actions[1]['title'] = 'Удалить';
	$actions[1]['icon']  = 'delete.gif';
	$actions[1]['confirm'] = 'Удалить комментарий?';
	$actions[1]['link']  = '?view=components&do=config&id='.$id.'&opt=delete&item_id=%id%';

	//Print table
	cpListTable('cms_comments', $fields, $actions, '', 'pubdate DESC');
}

if($opt=='edit'){

    $mod = $model->getComment(cmsCore::request('item_id', 'int'));
    if(!$mod) { cmsCore::error404(); }

    if($mod['user_id']==0) { $author = '<input name="guestname" type="text" id="title" size="30" value="'.$mod['guestname'].'"/>'; }
    else {
        $author = $mod['nickname'].' (<a target="_blank" href="/admin/index.php?view=users&do=edit&id='.$mod['user_id'].'">'.$mod['login'].'</a>)';
    }
    $target='N/A';
    switch($mod['target']){
        case 'article': $target = '<a href="/index.php?view=content&do=read&id='.$mod['target_id'].'">Статья</a> (ID='.$mod['target_id'].')'; break;
        case 'photo': $target = '<a href="/index.php?view=content&do=viewphoto&id='.$mod['target_id'].'">Фото</a> (ID='.$mod['target_id'].')'; break;
        case 'user': $target = '<a href="/index.php?view=profile&do=view&id='.$mod['target_id'].'">Пользователь</a> (ID='.$mod['user_id'].')'; break;
    }

    cpAddPathway('Редактировать комментарий', '?view=components&do=config&id='.$id.'&opt=edit&item_id='.$mod['id']);
    echo '<h3>Редактировать комментарий</h3>';

?>

<form id="addform" name="addform" method="post" action="index.php?view=components&do=config&id=<?php echo $id;?>">
<input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
	<table width="662" border="0" cellspacing="5" class="proptable">
	  <tr>
		<td width="200"><strong>Автор комментария: </strong></td>
		<td><?php echo $author?></td>
	  </tr>
	  <tr>
		<td><strong>Дата подачи: </strong></td>
		<td><input name="pubdate" type="text" id="title3" size="30" value="<?php echo $mod['pubdate'];?>"/></td>
	  </tr>
	  <tr>
		<td><strong>Публиковать комментарий?</strong></td>
		<td><label><input name="published" type="radio" value="1" <?php if ($mod['published']) { echo 'checked="checked"'; } ?> /> Да </label>
		  <label> <input name="published" type="radio" value="0"  <?php if (!$mod['published']) { echo 'checked="checked"'; } ?> /> Нет </label></td>
	  </tr>
	</table>
		<?php cmsCore::insertEditor('content', $mod['content'], '250', '100%'); ?>
	<p>
	  <label>
	  <input name="add_mod" type="submit" id="add_mod" value="Сохранить изменения"/>
	  </label>
	  <label>
	  <input name="back" type="button" id="back" value="Отмена" onclick="window.location.href='index.php?view=components';"/>
	  </label>
	  <input name="opt" type="hidden" id="do" value="update" />
	  <input name="item_id" type="hidden" value="<?php echo $mod['id']?>" />
	</p>
</form>
	<?php

}//if (add || edit)

if($opt=='config'){

    $GLOBALS['cp_page_head'][] = '<script type="text/javascript" src="/includes/jquery/tabs/jquery.ui.min.js"></script>';
    $GLOBALS['cp_page_head'][] = '<link href="/includes/jquery/tabs/tabs.css" rel="stylesheet" type="text/css" />';

    cpAddPathway('Настройки', '?view=components&do=config&id='.$id.'&opt=config');
    echo '<h3>Настройки комментариев</h3>';

?>

<form action="index.php?view=components&do=config&id=<?php echo $id;?>" method="post" name="optform" target="_self" id="form1">
<input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
<div id="config_tabs" style="margin-top:12px;">

    <ul id="tabs">
        <li><a href="#basic"><span>Общие</span></a></li>
        <li><a href="#format"><span>Формат</span></a></li>
        <li><a href="#access"><span>Доступ</span></a></li>
        <li><a href="#restrict"><span>Ограничения</span></a></li>
    </ul>

    <div id="basic">
        <table width="671" border="0" cellpadding="10" cellspacing="0" class="proptable">
            <tr>
                <td width="316" valign="top">
                    <strong>E-mail для комментариев:</strong><br/>
                    <span class="hinttext">Оставьте пустым, если вы не хотите получать комментарии по почте</span>
                </td>
                <td width="313" valign="top">
                    <input name="email" type="text" id="email" size="30" value="<?php echo $cfg['email'];?>"/>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <strong>Подписка на уведомления: </strong><br />
                    <span class="hinttext">Позволяет пользователям получать личные сообщения с уведомлениями о новых комментариях</span>
                </td>
                <td valign="top">
                    <label><input name="subscribe" type="radio" value="1" <?php if ($cfg['subscribe']) { echo 'checked="checked"'; } ?> /> Да</label>
                    <label><input name="subscribe" type="radio" value="0"  <?php if (!$cfg['subscribe']) { echo 'checked="checked"'; } ?> /> Нет</label>
                </td>
            </tr>
        </table>
    </div>

    <div id="format">
        <table width="671" border="0" cellpadding="10" cellspacing="0" class="proptable">
            <tr>
                <td width="316" valign="top">
                    <strong>Загружать комментарии, используя ajax?</strong>
                </td>
                <td width="313" valign="top">
                    <label><input name="cmm_ajax" type="radio" value="1" <?php if ($cfg['cmm_ajax']) { echo 'checked="checked"'; } ?> /> Да</label>
                    <label><input name="cmm_ajax" type="radio" value="0"  <?php if (!$cfg['cmm_ajax']) { echo 'checked="checked"'; } ?> /> Нет</label>
                </td>
            </tr>
            <tr>
                <td valign="top"><strong>Водяной знак для фотографий</strong></td>
                <td valign="top">
                    <label><input name="watermark" type="radio" value="1" <?php if ($cfg['watermark']) { echo 'checked="checked"'; } ?> /> Да</label>
                    <label><input name="watermark" type="radio" value="0"  <?php if (!$cfg['watermark']) { echo 'checked="checked"'; } ?> /> Нет</label>
                </td>
            </tr>
            <tr>
                <td valign="top"><strong>Текст уведомления о новых комментариях:</strong></td>
                <td valign="top"><a href="/includes/letters/newcomment.txt">/includes/letters/newcomment.txt</a></td>
            </tr>
            <tr>
                <td valign="top"><strong>Максимальный уровень вложенности:</strong></td>
                <td valign="top"><input name="max_level" type="text" id="max_level" value="<?php echo $cfg['max_level'];?>" size="3" /></td>
            </tr>
            <tr>
                <td valign="top"><strong>Количество комментариев на странице при просмотре всех комментариев сайта:</strong></td>
                <td valign="top"><input name="perpage" type="text" id="perpage" value="<?php echo $cfg['perpage'];?>" size="3" /></td>
            </tr>
            <tr>
                <td valign="middle"><strong>Показывать ip комментаторов администраторам: </strong></td>
                <td>
                    <select name="cmm_ip" id="cmm_ip" style="width:220px">
                        <option value="0" <?php if($cfg['cmm_ip']==0) { echo 'selected'; } ?>>не показывать</option>
                        <option value="1" <?php if($cfg['cmm_ip']==1) { echo 'selected'; } ?>>только гостей</option>
                        <option value="2" <?php if($cfg['cmm_ip']==2) { echo 'selected'; } ?>>всех</option>
                    </select>
                </td>
            </tr>
        </table>
    </div>

    <div id="access">
        <table width="671" border="0" cellpadding="10" cellspacing="0" class="proptable">
            <tr>
                <td valign="top">
        			<strong>Требовать защитный код:</strong><br />
            		<span class="hinttext">Каким пользователям показывать капчу при добавлении комментария </span>
                </td>
                <td valign="top">
                    <select name="regcap" id="regcap" style="width:220px">
                        <option value="0" <?php if($cfg['regcap']==0) { echo 'selected'; } ?>>Для гостей</option>
                        <option value="1" <?php if($cfg['regcap']==1) { echo 'selected'; } ?>>Для всех</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td valign="top"><strong>Запрещать редактирование через:</strong><br />
                    <span class="hinttext">Спустя указанное время после добавления комментария его редактирование станет невозможным для пользователя</span>
                </td>
                <td valign="top">
                    <select name="edit_minutes" id="regcap" style="width:220px">
                        <option value="0" <?php if(!$cfg['edit_minutes']) { echo 'selected'; } ?>>запрещать сразу</option>
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
    </div>

    <div id="restrict">
        <table width="671" border="0" cellpadding="10" cellspacing="0" class="proptable">
            <tr>
                <td width="316" valign="top">
                    <strong>Использовать ограничения:</strong><br />
                    <span class="hinttext">Если выключено, разрешенные пользователи смогут добавлять комментарии, независимо от значения своей кармы </span>
                </td>
                <td width="313" valign="top">
                    <label><input name="min_karma" type="radio" value="1" <?php if ($cfg['min_karma']) { echo 'checked="checked"'; } ?> /> Да</label>
                    <label><input name="min_karma" type="radio" value="0" <?php if (!$cfg['min_karma']) { echo 'checked="checked"'; } ?>/> Нет</label>
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <strong>Добавление комментария:</strong><br />
                    <span class="hinttext">Сколько очков кармы нужно для добавления комментария </span>
                </td>
                <td valign="top">
                    <input name="min_karma_add" type="text" id="min_karma_add" value="<?php echo $cfg['min_karma_add'];?>" size="5" />
                </td>
            </tr>
            <tr>
                <td valign="top">
                    <strong>Сворачивать комментарии, с рейтингом ниже:</strong><br />
                    <span class="hinttext">Комментарии c рейтингом ниже указанного будут выводится в свернутом виде </span>
                </td>
                <td valign="top">
                    <input name="min_karma_show" type="text" id="min_karma_show" value="<?php echo $cfg['min_karma_show'];?>" size="5" />
                </td>
            </tr>
        </table>
    </div>

</div>

<p>
  <input name="opt" type="hidden" id="do" value="saveconfig" />
  <input name="save" type="submit" id="save" value="Сохранить" />
  <input name="back" type="button" id="back" value="Отмена" onclick="window.location.href='index.php?view=components&do=config&id=<?php echo $id; ?>';"/>
</p>
</form>

<script type="text/javascript">$('#config_tabs > ul#tabs').tabs();</script>

<?php
	}
?>