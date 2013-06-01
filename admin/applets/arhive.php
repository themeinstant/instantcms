<?php
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

if(!defined('VALID_CMS_ADMIN')) { die('ACCESS DENIED'); }

function applet_arhive(){

    $inCore = cmsCore::getInstance();
    $inDB   = cmsDatabase::getInstance();

	$GLOBALS['cp_page_title'] = 'Архив статей';

	$cfg = $inCore->loadComponentConfig('content');
	$cfg_arhive = $inCore->loadComponentConfig('arhive');
    cmsCore::loadModel('content');
    $model = new cms_model_content();

	cpAddPathway('Статьи сайта', 'index.php?view=tree');
	cpAddPathway('Архив статей', 'index.php?view=arhive');

	$do = cmsCore::request('do', 'str', 'list');
	$id = cmsCore::request('id', 'int', -1);

    if ($do=='saveconfig'){

        if (!cmsCore::validateForm()) { cmsCore::error404(); }
		$cfg['source'] = cmsCore::request('source', 'str', '');
		$inCore->saveComponentConfig('arhive', $cfg);
        cmsUser::clearCsrfToken();
        cmsCore::redirect('?view=arhive&do=config');

	}

    if ($do=='config'){
		$toolmenu = array();
		$toolmenu[0]['icon'] = 'folders.gif';
		$toolmenu[0]['title'] = 'Список статей в архиве';
		$toolmenu[0]['link'] = '?view=arhive';

		cpToolMenu($toolmenu);
		cpAddPathway('Настройки', 'index.php?view=arhive&do=config');
?>
<form action="index.php?view=arhive&do=saveconfig" method="post" name="optform" target="_self" id="form1">
    <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
    <table width="609" border="0" cellpadding="10" cellspacing="0" class="proptable">
        <tr>
            <td valign="top"><strong>Источник материалов при просмотре архива на сайте: </strong></td>
            <td width="100" valign="top">
                <select name="source" id="source" style="width:285px">
                    <option value="content" <?php if ($cfg_arhive['source']=='content') { echo 'selected="selected"'; } ?>>Каталог статей</option>
                    <option value="arhive" <?php if ($cfg_arhive['source']=='arhive') { echo 'selected="selected"'; } ?>>Архив статей</option>
                    <option value="both" <?php if ($cfg_arhive['source']=='both') { echo 'selected="selected"'; } ?>>Каталог и архив</option>
                </select>
            </td>
        </tr>
    </table>
    <p>
        <input name="opt" type="hidden" value="saveconfig" />
        <input name="save" type="submit" id="save" value="Сохранить" />
        <input name="back" type="button" id="back" value="Отмена" onclick="window.location.href='index.php?view=arhive';" />
    </p>
</form>
<?php }

	if ($do == 'list'){
		$toolmenu = array();
		$toolmenu[0]['icon'] = 'config.gif';
		$toolmenu[0]['title'] = 'Настройки';
		$toolmenu[0]['link'] = '?view=arhive&do=config';

		$toolmenu[1]['icon'] = 'delete.gif';
		$toolmenu[1]['title'] = 'Удалить выбранные';
		$toolmenu[1]['link'] = "javascript:checkSel('?view=arhive&do=delete&multiple=1');";

		cpToolMenu($toolmenu);

		//TABLE COLUMNS
		$fields = array();

		$fields[0]['title'] = 'id';			$fields[0]['field'] = 'id';				$fields[0]['width'] = '30';

		$fields[1]['title'] = 'Создан';		$fields[1]['field'] = 'pubdate';		$fields[1]['width'] = '80';		$fields[1]['filter'] = 15;
		$fields[1]['fdate'] = '%d/%m/%Y';

		$fields[2]['title'] = 'Название';	$fields[2]['field'] = 'title';			$fields[2]['width'] = '';		$fields[2]['link'] = '?view=content&do=edit&id=%id%';
		$fields[2]['filter'] = 15;

		$fields[3]['title'] = 'Раздел';		$fields[3]['field'] = 'category_id';	$fields[3]['width'] = '100';	$fields[3]['filter'] = 1;
		$fields[3]['prc'] = 'cpCatById';	$fields[3]['filterlist'] = cpGetList('cms_category');

		//ACTIONS
		$actions = array();
		$actions[0]['title'] = 'В каталог статей';
		$actions[0]['icon']  = 'arhive_off.gif';
		$actions[0]['link']  = '?view=arhive&do=arhive_off&id=%id%';

		$actions[2]['title'] = 'Удалить';
		$actions[2]['icon']  = 'delete.gif';
		$actions[2]['confirm'] = 'Удалить материал?';
		$actions[2]['link']  = '?view=content&do=delete&id=%id%';

		//Print table
		cpListTable('cms_content', $fields, $actions, 'is_arhive=1');
	}

	if ($do == 'arhive_off'){
		if(isset($_REQUEST['id'])) {
			$sql = "UPDATE cms_content SET is_arhive = 0 WHERE id = '$id'";
			$inDB->query($sql) ;
            cmsCore::redirect('?view=arhive');
		}
	}

	if ($do == 'delete'){
		if (!isset($_REQUEST['item'])){
			if ($id >= 0){
				$model->deleteArticle($id, $cfg['af_delete']);
			}
		} else {
			$model->deleteArticles($_REQUEST['item'], $cfg['af_delete']);
		}
        cmsCore::redirect('?view=arhive');
	}

}

?>