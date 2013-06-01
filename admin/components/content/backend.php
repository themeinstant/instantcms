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

	$opt = $inCore->request('opt', 'str', 'list');
	$id  = $inCore->request('id', 'int', 0);

	cpAddPathway('Каталог статей', '?view=components&do=config&id='.$id);

	echo '<h3>Каталог статей</h3>';

	$toolmenu = array();

	$toolmenu[0]['icon'] = 'save.gif';
	$toolmenu[0]['title'] = 'Сохранить';
	$toolmenu[0]['link'] = 'javascript:document.optform.submit();';

	$toolmenu[1]['icon'] = 'cancel.gif';
	$toolmenu[1]['title'] = 'Отмена';
	$toolmenu[1]['link'] = '?view=components';

	cpToolMenu($toolmenu);

	$cfg = $inCore->loadComponentConfig('content');

	if($opt=='saveconfig'){

		if(!cmsCore::validateForm()) { cmsCore::error404(); }

		$cfg = array();
        $cfg['readdesc']    = $inCore->request('readdesc', 'int', 0);
		$cfg['is_url_cyrillic'] = $inCore->request('is_url_cyrillic', 'int', 0);
		$cfg['rating']      = $inCore->request('rating', 'int', 0);
		$cfg['perpage']     = $inCore->request('perpage', 'int', 0);
        $cfg['pt_show']     = $inCore->request('pt_show', 'int', 0);
		$cfg['pt_disp']     = $inCore->request('pt_disp', 'int', 0);
		$cfg['pt_hide']     = $inCore->request('pt_hide', 'int', 0);
		$cfg['autokeys']    = $inCore->request('autokeys', 'int', 0);

        $cfg['img_small_w'] = $inCore->request('img_small_w', 'int', 100);
        $cfg['img_big_w']   = $inCore->request('img_big_w', 'int', 200);
        $cfg['img_sqr']     = $inCore->request('img_sqr', 'int', 1);
        $cfg['img_users']   = $inCore->request('img_users', 'int', 1);
		$cfg['watermark']   = $inCore->request('watermark', 'int', 0);
		$cfg['watermark_only_big']   = $inCore->request('watermark_only_big', 'int', 0);

		$inCore->saveComponentConfig('content', $cfg);

		cmsCore::addSessionMessage('Настройки успешно сохранены', 'success');

        cmsUser::clearCsrfToken();

		$inCore->redirect('?view=components&do=config&id='.$id.'&opt=config');

	}

    require('../includes/jwtabs.php');
    $GLOBALS['cp_page_head'][] = jwHeader();

?>


<form action="index.php?view=components&do=config&id=<?php echo $id;?>" method="post" name="optform" target="_self" id="form1">
<input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
    <?php ob_start(); ?>
    {tab=Общие}
    <table width="550" border="0" cellpadding="10" cellspacing="0" class="proptable">
        <tr>
            <td><strong>Генерировать кириллические url категорий и статей: </strong></td>
            <td width="110">
                <label><input name="is_url_cyrillic" type="radio" value="1" <?php if ($cfg['is_url_cyrillic']) { echo 'checked="checked"'; } ?>/> Да </label>
                <label><input name="is_url_cyrillic" type="radio" value="0" <?php if (!$cfg['is_url_cyrillic']) { echo 'checked="checked"'; } ?>/> Нет </label>
            </td>
        </tr>
        <tr>
            <td><strong>Выводить анонсы при просмотре статей: </strong></td>
            <td width="110">
                <input name="readdesc" type="radio" value="1" <?php if ($cfg['readdesc']) { echo 'checked="checked"'; } ?>/> Да
                <input name="readdesc" type="radio" value="0" <?php if (!$cfg['readdesc']) { echo 'checked="checked"'; } ?>/> Нет
            </td>
        </tr>
        <tr>
            <td><strong>Рейтинг статей: </strong></td>
            <td>
                <input name="rating" type="radio" value="1" <?php if ($cfg['rating']) { echo 'checked="checked"'; } ?>/> Вкл
                <input name="rating" type="radio" value="0" <?php if (!$cfg['rating']) { echo 'checked="checked"'; } ?>/> Выкл
            </td>
        </tr>
        <tr>
            <td>
                <strong>Автоматически генерировать<br />ключевые слова и описания для статей:</strong>
            </td>
            <td valign="top">
                <input name="autokeys" type="radio" value="1" <?php if ($cfg['autokeys']) { echo 'checked="checked"'; } ?>/> Да
                <input name="autokeys" type="radio" value="0" <?php if (!$cfg['autokeys']) { echo 'checked="checked"'; } ?>/> Нет
            </td>
        </tr>
        <tr>
            <td><strong>Количество статей на странице: </strong></td>
            <td><input name="perpage" type="text" id="perpage" value="<?php echo $cfg['perpage'];?>" size="5" /> шт.</td>
        </tr>
    </table>
    <table width="550" border="0" cellpadding="10" cellspacing="0" class="proptable">
        <tr>
            <td><strong>Показывать содержание статей: </strong></td>
            <td width="110">
                <input name="pt_show" type="radio" value="1" <?php if ($cfg['pt_show']) { echo 'checked="checked"'; } ?>/> Да
                <input name="pt_show" type="radio" value="0" <?php if (!$cfg['pt_show']) { echo 'checked="checked"'; } ?>/> Нет
            </td>
        </tr>
        <tr>
            <td><strong>Разворачивать содержание: </strong></td>
            <td>
                <input name="pt_disp" type="radio" value="1" <?php if ($cfg['pt_disp']) { echo 'checked="checked"'; } ?>/> Да
                <input name="pt_disp" type="radio" value="0" <?php if (!$cfg['pt_disp']) { echo 'checked="checked"'; } ?>/> Нет
            </td>
        </tr>
        <tr>
            <td><strong>Ссылка &quot;Скрыть содержание&quot; : </strong></td>
            <td>
                <input name="pt_hide" type="radio" value="1" <?php if ($cfg['pt_hide']) { echo 'checked="checked"'; } ?>/> Да
                <input name="pt_hide" type="radio" value="0" <?php if (!$cfg['pt_hide']) { echo 'checked="checked"'; } ?>/> Нет
            </td>
        </tr>
    </table>
    {tab=Фото статей}
    <table width="550" border="0" cellpadding="10" cellspacing="0" class="proptable">
        <tr>
            <td><strong>Ширина маленькой копии:</strong></td>
            <td width="120">
                <input name="img_small_w" type="text" id="img_small_w" value="<?php echo $cfg['img_small_w'];?>" size="5" /> пикс.
            </td>
        </tr>
        <tr>
            <td><strong>Ширина большой копии:</strong></td>
            <td>
                <input name="img_big_w" type="text" id="img_big_w" value="<?php echo $cfg['img_big_w'];?>" size="5" /> пикс.
            </td>
        </tr>
        <tr>
            <td><strong>Квадратные:</strong></td>
            <td>
                <input name="img_sqr" type="radio" value="1" <?php if ($cfg['img_sqr']) { echo 'checked="checked"'; } ?>/> Да
                <input name="img_sqr" type="radio" value="0" <?php if (!$cfg['img_sqr']) { echo 'checked="checked"'; } ?>/> Нет
            </td>
        </tr>
        <tr>
            <td>
                <strong>Разрешить пользователям:</strong><br/>
                <span class="hinttext">Смогут ли пользователи добавлять фотографии к своим статьям</span>
            </td>
            <td>
                <input name="img_users" type="radio" value="1" <?php if ($cfg['img_users']) { echo 'checked="checked"'; } ?>/> Да
                <input name="img_users" type="radio" value="0" <?php if (!$cfg['img_users']) { echo 'checked="checked"'; } ?>/> Нет
            </td>
        </tr>
        <tr>
           <td><strong>Наносить водяной знак:</strong>  <br />Если включено, то на все загружаемые
			      фотографии (на большую миниатюру) к статьям будет наносится изображение
			      из файла "<a href="/images/watermark.png" target="_blank">/images/watermark.png</a>"</td>
           <td width="260">
               <input name="watermark" type="radio" value="1" <?php if ($cfg['watermark']) { echo 'checked="checked"'; } ?> /> Да
               <input name="watermark" type="radio" value="0"  <?php if (!$cfg['watermark']) { echo 'checked="checked"'; } ?> /> Нет
           </td>
        </tr>
    </table>
    {/tabs}
    <?php echo jwTabs(ob_get_clean()); ?>
    <p>
        <input name="opt" type="hidden" value="saveconfig" />
        <input name="save" type="submit" id="save" value="Сохранить" />
        <input name="back" type="button" id="back" value="Отмена" onclick="window.location.href='index.php?view=components';"/>
    </p>
</form>