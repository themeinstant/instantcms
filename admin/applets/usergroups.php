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

function applet_usergroups(){

    $inCore = cmsCore::getInstance();
	$inDB   = cmsDatabase::getInstance();

	//check access
	global $adminAccess;
	if (!cmsUser::isAdminCan('admin/users', $adminAccess)) { cpAccessDenied(); }

	$GLOBALS['cp_page_title'] = 'Группы пользователей';
 	cpAddPathway('Пользователи', 'index.php?view=users');
 	cpAddPathway('Группы пользователей', 'index.php?view=usergroups');

    $do = cmsCore::request('do', 'str', 'list');
	$id = cmsCore::request('id', 'int', -1);

    $inCore->loadModel('users');
    $model = new cms_model_users();

	if ($do == 'list'){
		$toolmenu = array();
		$toolmenu[0]['icon'] = 'usergroupadd.gif';
		$toolmenu[0]['title'] = 'Создать группу';
		$toolmenu[0]['link'] = "?view=usergroups&do=add";

		$toolmenu[1]['icon'] = 'edit.gif';
		$toolmenu[1]['title'] = 'Редактировать выбранные';
		$toolmenu[1]['link'] = "javascript:checkSel('?view=usergroups&do=edit&multiple=1');";

		$toolmenu[4]['icon'] = 'delete.gif';
		$toolmenu[4]['title'] = 'Удалить выбранные';
		$toolmenu[4]['link'] = "javascript:if(confirm('Вместе с группами будут удалены все их пользователи. Удалить группы?')) { checkSel('?view=users&do=delete&multiple=1'); }";

		$toolmenu[5]['icon'] = 'cancel.gif';
		$toolmenu[5]['title'] = 'Отмена';
		$toolmenu[5]['link'] = "?view=users";

		$toolmenu[6]['icon'] = 'help.gif';
		$toolmenu[6]['title'] = 'Помощь';
		$toolmenu[6]['link'] = "?view=help&topic=usergroups";

		cpToolMenu($toolmenu);

		//TABLE COLUMNS
		$fields = array();

		$fields[0]['title'] = 'id';			$fields[0]['field'] = 'id';			$fields[0]['width'] = '30';

		$fields[1]['title'] = 'Название';	$fields[1]['field'] = 'title';		$fields[1]['width'] = '';		$fields[1]['link'] = '?view=usergroups&do=edit&id=%id%';
		$fields[1]['filter'] = 12;

		$fields[2]['title'] = 'Псевдоним';	$fields[2]['field'] = 'alias';		$fields[2]['width'] = '150';	$fields[2]['link'] = '?view=usergroups&do=edit&id=%id%';
		$fields[2]['filter'] = 12;

		//ACTIONS
		$actions = array();
		$actions[0]['title'] = 'Редактировать';
		$actions[0]['icon']  = 'edit.gif';
		$actions[0]['link']  = '?view=usergroups&do=edit&id=%id%';

		$actions[1]['title'] = 'Удалить';
		$actions[1]['icon']  = 'delete.gif';
		$actions[1]['confirm'] = 'Вместе с группой будут удалены все ее пользователи. Удалить группу?';
		$actions[1]['link']  = '?view=usergroups&do=delete&id=%id%';

		//Print table
		cpListTable('cms_user_groups', $fields, $actions);
	}

	if ($do == 'delete'){
		if (!isset($_REQUEST['item'])){
			if ($id >= 0){
				$model->deleteGroup($id);
			}
		} else {
			$model->deleteGroups($_REQUEST['item']);
		}
		cmsCore::addSessionMessage('Группа успешно удалена', 'success');
		cmsCore::redirect('index.php?view=usergroups');
	}

	if ($do == 'submit'){
        if (!cmsCore::validateForm()) { cmsCore::error404(); }
		$title    = cmsCore::request('title', 'str', '');
		$alias    = cmsCore::request('alias', 'str', '');
		$is_admin = cmsCore::request('is_admin', 'int');
		$access   = cmsCore::request('access', 'array_str');

		$access_text = '';
		foreach($access as $k=>$value){
			$access_text .= $value;
			if ($k<sizeof($access)-1){ $access_text .= ', '; }
		}
		$access = $access_text;

		$sql = "INSERT INTO cms_user_groups (title, alias, is_admin, access)
				VALUES ('$title', '$alias', $is_admin, '$access')";
		$inDB->query($sql);
		cmsCore::addSessionMessage('Группа "'.$title.'" успешно создана.', 'success');
        cmsUser::clearCsrfToken();
		$inCore->redirect('index.php?view=usergroups');
	}

	if ($do == 'update'){
        if (!cmsCore::validateForm()) { cmsCore::error404(); }
        if (isset($_REQUEST['id'])){

			$title    = cmsCore::request('title', 'str', '');
			$alias    = cmsCore::request('alias', 'str', '');
			$is_admin = cmsCore::request('is_admin', 'int');
			$access   = cmsCore::request('access', 'array_str');

			$access_text = '';
			foreach($access as $k=>$value){
				$access_text .= $value;
				if ($k<sizeof($access)-1){ $access_text .= ', '; }
			}
			$access = $access_text;

			$sql = "UPDATE cms_user_groups
					SET title='$title',
						alias='$alias',
						is_admin=$is_admin,
						access='$access'
					WHERE id = '$id'
					LIMIT 1";
			$inDB->query($sql);
			cmsCore::addSessionMessage('Группа "'.$title.'" успешно обновлена.', 'success');
		}
        cmsUser::clearCsrfToken();
		if (!isset($_SESSION['editlist']) || @sizeof($_SESSION['editlist'])==0){
			$inCore->redirect('index.php?view=usergroups');
		} else {
			$inCore->redirect('index.php?view=usergroups&do=edit');
		}
	}

   if ($do == 'add' || $do == 'edit'){

 		$toolmenu = array();
		$toolmenu[0]['icon'] = 'save.gif';
		$toolmenu[0]['title'] = 'Сохранить';
		$toolmenu[0]['link'] = 'javascript:document.addform.submit();';

		$toolmenu[1]['icon'] = 'cancel.gif';
		$toolmenu[1]['title'] = 'Отмена';
		$toolmenu[1]['link'] = 'javascript:history.go(-1);';

		cpToolMenu($toolmenu);

		if ($do=='add'){
			 echo '<h3>Создать группу</h3>';
 	 		 cpAddPathway('Создать группу', 'index.php?view=usergroups&do=add');
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
				if (sizeof($_SESSION['editlist'])==0) { unset($_SESSION['editlist']); } else
				{ $ostatok = '(На очереди: '.sizeof($_SESSION['editlist']).')'; }
			 } else { $id = (int)$_REQUEST['id']; }

			 $sql = "SELECT * FROM cms_user_groups WHERE id = '$id' LIMIT 1";
			 $result = $inDB->query($sql) ;
			 if (mysql_num_rows($result)){
				$mod = mysql_fetch_assoc($result);
			 }

			 echo '<h3>Редактировать группу '.$ostatok.'</h3>';

			 cpAddPathway('Редактировать группу '.$mod['title'], 'index.php?view=usergroups&do=edit&id='.$mod['id']);
	}

	if(isset($mod['access'])){
		$mod['access'] = str_replace(', ', ',', $mod['access']);
		$mod['access'] = explode(',', $mod['access']);
	}

	?>
	<form id="addform" name="addform" method="post" action="index.php?view=usergroups">
        <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
		<table width="660" border="0" cellspacing="5" class="proptable">
			<tr>
				<td width="198" valign="top"><div><strong>Название группы: </strong></div><span class="hinttext">Отображается на сайте и в админке</span></td>
				<td width="475" valign="top"><input name="title" type="text" id="title" size="30" value="<?php echo htmlspecialchars($mod['title']);?>"/></td>
			</tr>
			<tr>
				<td valign="top"><div><strong>Псевдоним:</strong></div><?php if($do=='edit'){ ?><span class="hinttext">Не рекомендуется изменять</span><?php } ?></td>
				<td valign="top"><input name="alias" type="text" id="title3" size="30" value="<?php echo @$mod['alias'];?>"/></td>
			</tr>
			<tr>
				<td><strong>Администраторы?</strong></td>
				<td>
					<label><input name="is_admin" type="radio" value="1" <?php if (@$mod['is_admin']) { echo 'checked="checked"'; } ?> onclick="$('#accesstable').hide();$('#admin_accesstable').show();"/> Да </label>
					<label><input name="is_admin" type="radio" value="0"  <?php if (@!$mod['is_admin']) { echo 'checked="checked"'; } ?> onclick="$('#accesstable').show();$('#admin_accesstable').hide();"/> Нет</label>
				</td>
			</tr>
		</table>

		<!--------------------------------------------------------------------------------------------------------------------------------------------->

		<table width="660" border="0" cellspacing="5" class="proptable" id="admin_accesstable" style="<?php if(@!$mod['is_admin']){echo 'display:none;'; }?>">
			<tr>
				<td width="191" valign="top">
					<div><strong>Доступные разделы панели управления: </strong></div>
					<span class="hinttext">Главному администратору будут доступны все разделы, независимо от выбранных настроек</span>
				</td>
				<td width="475" valign="top">
					<table width="100%" border="0" cellspacing="2" cellpadding="0">
						<tr>
							<td width="16"><input type="checkbox" name="access[]" id="admin_menu" value="admin/menu" <?php if (isset($mod['access'])) { if (in_array('admin/menu', $mod['access'])) { echo 'checked="checked"'; } }?>></td>
							<td><label for="admin_menu">Управление меню</label></td>
						</tr>
						<tr>
							<td width="16"><input type="checkbox" name="access[]" id="admin_modules" value="admin/modules" <?php if (isset($mod['access'])) { if (in_array('admin/modules', $mod['access'])) { echo 'checked="checked"'; } }?>></td>
							<td><label for="admin_modules">Управление модулями</label></td>
						</tr>
						<tr>
							<td width="16"><input type="checkbox" name="access[]" id="admin_content" value="admin/content" <?php if (isset($mod['access'])) { if (in_array('admin/content', $mod['access'])) { echo 'checked="checked"'; } }?>></td>
							<td><label for="admin_content">Управление контентом (статьи и разделы)</label></td>
						</tr>
                        <tr>
							<td width="16"><input type="checkbox" name="access[]" id="admin_plugins" value="admin/plugins" <?php if (isset($mod['access'])) { if (in_array('admin/filters', $mod['access'])) { echo 'checked="checked"'; } }?>></td>
							<td><label for="admin_plugins">Управление плагинами</label></td>
						</tr>
						<tr>
							<td width="16"><input type="checkbox" name="access[]" id="admin_filters" value="admin/filters" <?php if (isset($mod['access'])) { if (in_array('admin/filters', $mod['access'])) { echo 'checked="checked"'; } }?>></td>
							<td><label for="admin_filters">Управление фильтрами (требуется управление плагинами)</label></td>
						</tr>
						<tr>
							<td width="16"><input type="checkbox" name="access[]" id="admin_components" value="admin/components" <?php if (isset($mod['access'])) { if (in_array('admin/components', $mod['access'])) { echo 'checked="checked"'; } }?>></td>
							<td><label for="admin_components">Управление компонентами</label></td>
						</tr>
						<tr>
							<td width="16"><input type="checkbox" name="access[]" id="admin_users" value="admin/users" <?php if (isset($mod['access'])) { if (in_array('admin/users', $mod['access'])) { echo 'checked="checked"'; } }?>></td>
							<td><label for="admin_users">Управление пользователями</label></td>
						</tr>
						<tr>
							<td width="16"><input type="checkbox" name="access[]" id="admin_config" value="admin/config" <?php if (isset($mod['access'])) { if (in_array('admin/config', $mod['access'])) { echo 'checked="checked"'; } }?>></td>
							<td><label for="admin_config">Управление настройками</label></td>
						</tr>
					</table>
                </td>
			</tr>
			<tr>
			  <td valign="top">
			  	<div><strong>Доступные для настройки компоненты: </strong></div>
				<span class="hinttext">Не забудьте включить "Управление компонентами"</span>
			  </td>
			  <td valign="top">
				  <table width="100%" border="0" cellspacing="2" cellpadding="0">

						<?php
							$sql = "SELECT * FROM cms_components WHERE config <> '' ORDER BY title";
							$res = $inDB->query($sql);

							while ($com = mysql_fetch_assoc($res)) {
						?>
						<tr>
							<td width="16"><input type="checkbox" name="access[]" id="admin_com_<?php echo $com['link']; ?>" value="admin/com_<?php echo $com['link']; ?>" <?php if (isset($mod['access'])) { if (in_array('admin/com_'.$com['link'], $mod['access'])) { echo 'checked="checked"'; } }?> /></td>
							<td><label for="admin_com_<?php echo $com['link']; ?>"><?php echo $com['title']; ?></label></td>
						</tr>
						<?php } ?>

				  </table>
			  </td>
		  </tr>
		</table>

		<!--------------------------------------------------------------------------------------------------------------------------------------------->

		<table width="660" border="0" cellspacing="5" class="proptable" id="accesstable" style="<?php if(@$mod['is_admin']){echo 'display:none;'; } ?>">
			<tr>
				<td width="191" valign="top"><strong>Права группы: </strong></td>
				<td width="475" valign="top">
					<table width="100%" border="0" cellspacing="2" cellpadding="0">

					<?php
                        $sql = "SELECT * FROM cms_user_groups_access ORDER BY access_type";
                        $res = $inDB->query($sql);

                        while ($ga = mysql_fetch_assoc($res)) {
                    ?>
						<tr>
							<td width="16"><input type="checkbox" name="access[]" id="<?php echo str_replace('/', '_', $ga['access_type']); ?>" value="<?php echo $ga['access_type']; ?>" <?php if (isset($mod['access'])) { if (in_array($ga['access_type'], $mod['access'])) { echo 'checked="checked"'; } }?>></td>
							<td><label for="<?php echo str_replace('/', '_', $ga['access_type']); ?>"><?php echo $ga['access_name']; ?></label></td>
						</tr>
                    <?php } ?>
					</table>
				</td>
			</tr>
		</table>

		<!--------------------------------------------------------------------------------------------------------------------------------------------->

		<p>
			<input name="add_mod" type="submit" id="add_mod" <?php if ($do=='add') { echo 'value="Создать группу"'; } else { echo 'value="Сохранить группу"'; } ?> />
			<span style="margin-top:15px"><input name="back" type="button" id="back" value="Отмена" onclick="window.history.back();"/></span>
			<input name="do" type="hidden" id="do" <?php if ($do=='add') { echo 'value="submit"'; } else { echo 'value="update"'; } ?> />
			<?php
			if ($do=='edit'){
			 echo '<input name="id" type="hidden" value="'.$mod['id'].'" />';
			}
			?>
		</p>
	</form>
	<?php
   }
}

?>