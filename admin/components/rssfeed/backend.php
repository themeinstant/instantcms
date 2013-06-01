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

	$opt = cmsCore::request('opt', 'str', 'config');
	$id  = cmsCore::request('id', 'int');

	cpAddPathway('RSS генератор', '?view=components&do=config&id='.$id);

	echo '<h3>RSS генератор</h3>';

	$toolmenu = array();

	$toolmenu[0]['icon'] = 'save.gif';
	$toolmenu[0]['title'] = 'Сохранить';
	$toolmenu[0]['link'] = 'javascript:document.optform.submit();';

	$toolmenu[1]['icon'] = 'cancel.gif';
	$toolmenu[1]['title'] = 'Отмена';
	$toolmenu[1]['link'] = '?view=components';

	cpToolMenu($toolmenu);

	$cfg = $inCore->loadComponentConfig('rssfeed');

	if($opt=='saveconfig'){

        if (!cmsCore::validateForm()) { cmsCore::error404(); }

		$cfg = array();
		$cfg['addsite']  = cmsCore::request('addsite', 'int');
		$cfg['maxitems'] = cmsCore::request('maxitems', 'int');
		$cfg['icon_on']  = cmsCore::request('icon_on', 'int');
		$cfg['icon_url'] = cmsCore::request('icon_url', 'str', '');

		$inCore->saveComponentConfig('rssfeed', $cfg);

		cmsCore::addSessionMessage('Настройки успешно сохранены', 'success');

        cmsUser::clearCsrfToken();

		cmsCore::redirectBack();

	}

?>
<form action="index.php?view=components&amp;do=config&amp;id=<?php echo $id;?>" method="post" name="optform" target="_self" id="form1">
<input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
        <table width="650" border="0" cellpadding="10" cellspacing="0" class="proptable">
          <tr>
            <td colspan="2" bgcolor="#EBEBEB"><strong>Каналы</strong></td>
          </tr>
          <tr>
            <td>Добавлять название сайта в заголовки RSS-каналов:</td>
            <td width="300" valign="top">
            <label><input name="addsite" type="radio" value="1" <?php if ($cfg['addsite']) { echo 'checked="checked"'; } ?>/> Да</label>
            <label><input name="addsite" type="radio" value="0" <?php if (!$cfg['addsite']) { echo 'checked="checked"'; } ?>/> Нет</label>
            </td>
          </tr>
          <tr>
            <td>Максимальное число записей для вывода: </td>
            <td valign="top"><input name="maxitems" type="text" id="maxitems" size="6" value="<?php echo $cfg['maxitems'];?>"/> шт.</td>
          </tr>
        </table>
        <table width="650" border="0" cellpadding="10" cellspacing="0" class="proptable" style="margin-top:2px">
          <tr>
            <td colspan="2" bgcolor="#EBEBEB"><strong>Иконка RSS-каналов </strong></td>
          </tr>
          <tr>
            <td>Использовать иконку:</td>
            <td width="300" valign="top">
            <label><input name="icon_on" type="radio" value="1" <?php if ($cfg['icon_on']) { echo 'checked="checked"'; } ?>/> Да</label>
            <label><input name="icon_on" type="radio" value="0" <?php if (!$cfg['icon_on']) { echo 'checked="checked"'; } ?>/> Нет</label>
            </td>
          </tr>
          <tr>
            <td>URL иконки (включая адрес сайта): </td>
            <td valign="top"><input name="icon_url" type="text" id="icon_url" size="45" value="<?php echo $cfg['icon_url'];?>"/></td>
          </tr>
        </table>
        <p>
          <input name="opt" type="hidden" value="saveconfig" />
          <input name="save" type="submit" id="save" value="Сохранить" />
          <input name="back" type="button" id="back" value="Отмена" onclick="window.location.href='index.php?view=components';"/>
        </p>
</form>