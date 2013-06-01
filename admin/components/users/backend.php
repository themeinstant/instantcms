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
$inCore->loadModel('users');
$model = new cms_model_users();

$opt = cmsCore::request('opt', 'str', 'list');
$id  = cmsCore::request('id', 'int', 0);

cpAddPathway('Профили пользователей', '?view=components&do=config&id='.$id);
echo '<h3>Профили пользователей</h3>';

$toolmenu = array();

$toolmenu[0]['icon'] = 'save.gif';
$toolmenu[0]['title'] = 'Сохранить';
$toolmenu[0]['link'] = 'javascript:document.optform.submit();';

$toolmenu[1]['icon'] = 'cancel.gif';
$toolmenu[1]['title'] = 'Отмена';
$toolmenu[1]['link'] = '?view=components';

cpToolMenu($toolmenu);

$GLOBALS['cp_page_head'][] = '<script type="text/javascript" src="/includes/jquery/jquery.form.js"></script>';
$GLOBALS['cp_page_head'][] = '<script type="text/javascript" src="/includes/jquery/tabs/jquery.ui.min.js"></script>';
$GLOBALS['cp_page_head'][] = '<link href="/includes/jquery/tabs/tabs.css" rel="stylesheet" type="text/css" />';

if ($opt=='saveconfig'){
    if (!cmsCore::validateForm()) { cmsCore::error404(); }
	$cfg = array();

    $cfg['sw_comm']   = cmsCore::request('sw_comm', 'int', 0);
    $cfg['sw_search'] = cmsCore::request('sw_search', 'int', 0);
    $cfg['sw_forum']  = cmsCore::request('sw_forum', 'int', 0);
    $cfg['sw_photo']  = cmsCore::request('sw_photo', 'int', 0);
    $cfg['sw_wall']   = cmsCore::request('sw_wall', 'int', 0);
    $cfg['sw_blogs']  = cmsCore::request('sw_blogs', 'int', 0);
    $cfg['sw_clubs']  = cmsCore::request('sw_clubs', 'int', 0);
    $cfg['sw_feed']   = cmsCore::request('sw_feed', 'int', 0);
    $cfg['sw_awards'] = cmsCore::request('sw_awards', 'int', 0);
    $cfg['sw_board']  = cmsCore::request('sw_board', 'int', 0);
    $cfg['sw_msg']    = cmsCore::request('sw_msg', 'int', 0);
    $cfg['sw_guest']  = cmsCore::request('sw_guest', 'int', 0);
    $cfg['sw_files']  = cmsCore::request('sw_files', 'int', 0);

    $cfg['karmatime'] = cmsCore::request('karmatime', 'int', 0);
    $cfg['karmaint']  = cmsCore::request('karmaint', 'str', 'DAY');

    $cfg['photosize'] = cmsCore::request('photosize', 'int', 0);
    $cfg['watermark'] = cmsCore::request('watermark', 'int', 0);
    $cfg['smallw']    = cmsCore::request('smallw', 'int', 64);
    $cfg['medw']      = cmsCore::request('medw', 'int', 200);
    $cfg['medh']      = cmsCore::request('medh', 'int', 200);

    $cfg['filessize'] = cmsCore::request('filessize', 'int', 0);
	$cfg['filestype'] = mb_strtolower(cmsCore::request('filestype', 'str', 'jpeg,gif,png,jpg,bmp,zip,rar,tar'));

    $cfg['privforms'] = cmsCore::request('privforms', 'array_int');

	$cfg['deltime']   = cmsCore::request('deltime', 'int', 0);
	$cfg['users_perpage'] = cmsCore::request('users_perpage', 'int', 10);
	$cfg['wall_perpage']  = cmsCore::request('wall_perpage', 'int', 10);

    $inCore->saveComponentConfig('users', $cfg);

    cmsCore::addSessionMessage('Настройки сохранены.', 'success');

    cmsUser::clearCsrfToken();

	$inCore->redirect('?view=components&do=config&id='.$id.'&opt=config');

}

?>
<?php cpCheckWritable('/images/users/avatars', 'folder'); ?>
<?php cpCheckWritable('/images/users/avatars/small', 'folder'); ?>
<?php cpCheckWritable('/images/users/photos', 'folder'); ?>
<?php cpCheckWritable('/images/users/photos/small', 'folder'); ?>
<?php cpCheckWritable('/images/users/photos/medium', 'folder'); ?>

<form action="index.php?view=components&amp;do=config&amp;id=<?php echo $id;?>" method="post" name="optform" target="_self" id="form1">
	<input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
    <div id="config_tabs" style="margin-top:12px;">

    <ul id="tabs">
        <li><a href="#basic"><span>Настройки профилей</span></a></li>
        <li><a href="#avatars"><span>Аватары</span></a></li>
        <li><a href="#proftabs"><span>Вкладки профилей</span></a></li>
        <li><a href="#forms"><span>Дополнительные поля</span></a></li>
        <li><a href="#photos"><span>Фотоальбомы</span></a></li>
        <li><a href="#files"><span>Файловые архивы</span></a></li>
        <li><a href="#reg"><span>Регистрация</span></a></li>
    </ul>

    <div id="basic">
        <table width="605" border="0" cellpadding="10" cellspacing="0" class="proptable" style="border:none">
            <tr>
                <td><strong>Разрешить гостям просматривать профили: </strong></td>
                <td width="182">
                    <label><input name="sw_guest" type="radio" value="1" <?php if ($model->config['sw_guest']) { echo 'checked="checked"'; } ?>/> Да</label>
                    <label><input name="sw_guest" type="radio" value="0" <?php if (!$model->config['sw_guest']) { echo 'checked="checked"'; } ?>/> Нет</label>
                </td>
            </tr>
            <tr>
                <td><strong>Поиск пользователей: </strong></td>
                <td>
                    <label><input name="sw_search" type="radio" value="1" <?php if ($model->config['sw_search']) { echo 'checked="checked"'; } ?>/> Вкл</label>
                    <label><input name="sw_search" type="radio" value="0" <?php if (!$model->config['sw_search']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
            <tr>
                <td><strong>Показывать число комментариев: </strong></td>
                <td width="182">
                    <label><input name="sw_comm" type="radio" value="1" <?php if ($model->config['sw_comm']) { echo 'checked="checked"'; } ?>/> Вкл</label>
                    <label><input name="sw_comm" type="radio" value="0" <?php if (!$model->config['sw_comm']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
            <tr>
                <td><strong>Показывать число сообщений на форуме: </strong></td>
                <td>
                    <label><input name="sw_forum" type="radio" value="1" <?php if ($model->config['sw_forum']) { echo 'checked="checked"'; } ?>/> Вкл</label>
                    <label><input name="sw_forum" type="radio" value="0" <?php if (!$model->config['sw_forum']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
            <tr>
                <td><strong>Стена пользователя: </strong></td>
                <td>
                    <label><input name="sw_wall" type="radio" value="1" <?php if ($model->config['sw_wall']) { echo 'checked="checked"'; } ?>/> Вкл</label>
                    <label><input name="sw_wall" type="radio" value="0" <?php if (!$model->config['sw_wall']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
            <tr>
                <td><strong>Личные блоги:</strong></td>
                <td>
                    <label><input name="sw_blogs" type="radio" value="1" <?php if ($model->config['sw_blogs']) { echo 'checked="checked"'; } ?>/> Вкл</label>
                    <label><input name="sw_blogs" type="radio" value="0" <?php if (!$model->config['sw_blogs']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
            <tr>
                <td><strong>Показывать объявления пользователя:</strong></td>
                <td>
                    <label><input name="sw_board" type="radio" value="1" <?php if ($model->config['sw_board']) { echo 'checked="checked"'; } ?>/> Вкл</label>
                    <label><input name="sw_board" type="radio" value="0" <?php if (!$model->config['sw_board']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
            <tr>
                <td><strong>Личные сообщения:</strong> </td>
                <td>
                    <label><input name="sw_msg" type="radio" value="1" <?php if ($model->config['sw_msg']) { echo 'checked="checked"'; } ?>/> Вкл</label>
                    <label><input name="sw_msg" type="radio" value="0" <?php if (!$model->config['sw_msg']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
            <tr>
                <td><strong>Текст уведомления о новых сообщениях: </strong></td>
                <td><a href="/includes/letters/newmessage.txt">/includes/letters/newmessage.txt</a></td>
            </tr>
            <tr>
                <td>
                    <strong>Период голосования за карму:</strong><br />
                    <span class="hinttext">Пользователь может изменить карму другого пользователя только 1 раз за указанное время </span>
                </td>
                <td valign="top">
                    <input name="karmatime" type="text" id="int_1" size="5" value="<?php echo $model->config['karmatime']?>"/>
                    <select name="karmaint" id="int_2">
                        <option value="MINUTE"  <?php if (mb_strstr($model->config['karmaint'], 'MINUTE')) { echo 'selected="selected"'; } ?>>минут</option>
                        <option value="HOUR"  <?php if (mb_strstr($model->config['karmaint'], 'HOUR')) { echo 'selected="selected"'; } ?>>часов</option>
                        <option value="DAY" <?php if (mb_strstr($model->config['karmaint'], 'DAY')) { echo 'selected="selected"'; } ?>>дней</option>
                        <option value="MONTH" <?php if (mb_strstr($model->config['karmaint'], 'MONTH')) { echo 'selected="selected"'; } ?>>месяцев</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Период удаления неактивных аккаунтов:</strong><br />
                    <span class="hinttext">Работает, если включен CRON и существует активная задача</span>
                </td>
                <td valign="top">
                    <input name="deltime" type="text" id="deltime" size="5" value="<?php echo $model->config['deltime']; ?>"/> месяцев
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Количество пользователей на странице в списке:</strong>
                </td>
                <td valign="top">
                    <input name="users_perpage" type="text" id="users_perpage" size="5" value="<?php echo $model->config['users_perpage']; ?>"/>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Количество записей на стене в профиле:</strong>
                </td>
                <td valign="top">
                    <input name="wall_perpage" type="text" id="wall_perpage" size="5" value="<?php echo $model->config['wall_perpage']; ?>"/>
                </td>
            </tr>
        </table>
    </div>

    <div id="avatars">
        <table width="605" border="0" cellpadding="10" cellspacing="0" class="proptable" style="border:none">
            <tr>
                <td><strong>Ширина маленького аватара: </strong></td>
                <td><input name="smallw" type="text" id="smallw" size="5" value="<?php echo $model->config['smallw'];?>"/> пикс.</td>
            </tr>
            <tr>
                <td><strong>Ширина большого аватара: </strong></td>
                <td><input name="medw" type="text" id="medw" size="5" value="<?php echo $model->config['medw'];?>"/> пикс.</td>
            </tr>
            <tr>
                <td><strong>Высота большого аватара: </strong></td>
                <td><input name="medh" type="text" id="medh" size="5" value="<?php echo $model->config['medh'];?>"/> пикс.</td>
            </tr>
        </table>
    </div>


    <div id="proftabs">
        <table width="605" border="0" cellpadding="10" cellspacing="0" class="proptable" style="border:none">
            <tr>
                <td><strong>Вкладка "Лента":</strong></td>
                <td>
                    <label><input name="sw_feed" type="radio" value="1" <?php if ($model->config['sw_feed']) { echo 'checked="checked"'; } ?>/> Вкл</label>
                    <label><input name="sw_feed" type="radio" value="0" <?php if (!$model->config['sw_feed']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
            <tr>
                <td><strong>Вкладка "Клубы":</strong></td>
                <td>
                    <label><input name="sw_clubs" type="radio" value="1" <?php if ($model->config['sw_clubs']) { echo 'checked="checked"'; } ?>/> Вкл</label>
                    <label><input name="sw_clubs" type="radio" value="0" <?php if (!$model->config['sw_clubs']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
            <tr>
                <td><strong>Вкладка "Награды":</strong></td>
                <td>
                    <label><input name="sw_awards" type="radio" value="1" <?php if ($model->config['sw_awards']) { echo 'checked="checked"'; } ?>/> Вкл</label>
                    <label><input name="sw_awards" type="radio" value="0" <?php if (!$model->config['sw_awards']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
        </table>
    </div>

    <div id="forms">
        <table width="605" border="0" cellspacing="0" cellpadding="10" class="proptable" style="border:none">
            <tr>
                <td valign="top">
                    <p>Выберите, какие формы должны присутствовать для заполнения пользователями в профилях: </p>
                    <p>
                        <select name="privforms[]" size="10" style="width:100%; border:solid 1px silver;" multiple="multiple">
                            <?php

                            $sql = "SELECT * FROM cms_forms";
                            $rs = dbQuery($sql);

                            if (mysql_num_rows($rs)){
                                while($f = mysql_fetch_assoc($rs)){
                                    if (in_array($f['id'], $model->config['privforms'])) { $selected='selected="selected"'; } else { $selected = ''; }
                                    echo '<option value="'.$f['id'].'" '.$selected.'>'.$f['title'].'</option>';
                                }
                            }

                            ?>
                        </select>
                    </p>
                    <p>Можно выбрать несколько форм, удерживая CTRL.</p>
                    <p>Формы можно редактировать в настройках компонента <a href="index.php?view=components&do=config&id=<?php echo $inDB->get_field('cms_components', "link='forms'", 'id');?>">Конструктор форм</a>.</p>
                </td>
            </tr>
        </table>
    </div>

    <div id="photos">
        <table width="605" border="0" cellpadding="10" cellspacing="0" class="proptable" style="border:none">
            <tr>
                <td><strong>Фотоальбомы: </strong></td>
                <td width="182">
                    <label><input name="sw_photo" type="radio" value="1" <?php if ($model->config['sw_photo']) { echo 'checked="checked"'; } ?>/> Вкл</label>
                    <label><input name="sw_photo" type="radio" value="0" <?php if (!$model->config['sw_photo']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Наносить водяной знак:</strong> <br />
                    <span class="hinttext">Если включено, то на все загружаемые фотографии будет наносится изображение из файла &quot;<a href="/images/watermark.png" target="_blank">/images/watermark.png</a>&quot;</span>
                </td>
                <td valign="top">
                    <label><input name="watermark" type="radio" value="1" <?php if ($model->config['watermark']) { echo 'checked="checked"'; } ?>/> Вкл</label>
                    <label><input name="watermark" type="radio" value="0" <?php if (!$model->config['watermark']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Максимум фотографий в альбоме:</strong><br />
                    <span class="hinttext">Установите &quot;0&quot; для бесконечного количества</span>
                </td>
                <td><input name="photosize" type="text" id="photosize" size="5" value="<?php echo $model->config['photosize'];?>"/> шт.</td>
            </tr>
        </table>
    </div>

    <div id="files">
         <table width="605" border="0" cellpadding="10" cellspacing="0" class="proptable" style="border:none">
            <tr>
                <td><strong>Файлы пользователя: </strong></td>
                <td width="182">
                    <label><input name="sw_files" type="radio" value="1" <?php if ($model->config['sw_files']) { echo 'checked="checked"'; } ?>/> Вкл</label>
                    <label><input name="sw_files" type="radio" value="0" <?php if (!$model->config['sw_files']) { echo 'checked="checked"'; } ?>/> Выкл</label>
                </td>
            </tr>
            <tr>
                <td>
                    <strong>Выделять каждому пользователю на диске:</strong><br />
                    <span class="hinttext">Установите &quot;0&quot; для бесконечного размера</span>
                </td>
                <td><input name="filessize" type="text" id="filessize" size="5" value="<?php echo $model->config['filessize'];?>"/> Мб</td>
            </tr>
            <tr>
                <td>
                    <strong>Доступные типы файлов:</strong><br />
                    <span class="hinttext">Введите через запятую расширения для доступных типов файлов</span>
                </td>
                <td><input name="filestype" type="text" id="filestype" size="30" value="<?php echo $model->config['filestype'];?>"/></td>
            </tr>
        </table>
    </div>

    <div id="reg">
        <table width="605" border="0" cellpadding="10" cellspacing="0" class="proptable" style="border:none">
            <tr>
                <td>
                    <a href="index.php?view=components&do=config&link=registration">Перейти к настройкам регистрации</a>
                </td>
            </tr>
        </table>
    </div>

</div>

    <p>
        <input name="opt" type="hidden" value="saveconfig" />
        <input name="save" type="submit" id="save" value="Сохранить" />
        <input name="back" type="button" id="back" value="Отмена" onclick="window.location.href='index.php?view=components';"/>
    </p>
</form>

<script type="text/javascript">$('#config_tabs > ul#tabs').tabs();</script>