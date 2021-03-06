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

function viewAct($value){
	if (!$value) {
		$value = '<span style="color:green;">да</span>';
	} else {
		$value = '<span style="color:red;">Нет</span>';
	}
	return $value;
}
function viewDel($value){
	if (!$value) {
		$value = '<span style="color:green;">Нет</span>';
	} else {
		$value = '<span style="color:red;">да</span>';
	}
	return $value;
}
function setRating($value){
	return '<a href="?view=users&do=rerating&user_id='.$value.'">пересчитать</a>';
}

function applet_users(){

    $inCore = cmsCore::getInstance();
	$inCore->loadClass('actions');

	//check access
	global $adminAccess;
	if (!cmsUser::isAdminCan('admin/users', $adminAccess)) { cpAccessDenied(); }

	$GLOBALS['cp_page_title'] = 'Пользователи';
 	cpAddPathway('Пользователи', 'index.php?view=users');

    $do = $inCore->request('do', 'str', 'list');
	$id = $inCore->request('id', 'int', -1);

    $inDB = cmsDatabase::getInstance();

    $inCore->loadModel('users');
    $model = new cms_model_users();

	if ($do == 'list'){
		$toolmenu = array();
		$toolmenu[0]['icon'] = 'useradd.gif';
		$toolmenu[0]['title'] = 'Создать пользователя';
		$toolmenu[0]['link'] = "?view=users&do=add";

		$toolmenu[1]['icon'] = 'useredit.gif';
		$toolmenu[1]['title'] = 'Редактировать выбранных';
		$toolmenu[1]['link'] = "javascript:checkSel('?view=users&do=edit&multiple=1');";

		$toolmenu[4]['icon'] = 'userdelete.gif';
		$toolmenu[4]['title'] = 'Удалить выбранных';
		$toolmenu[4]['link'] = "javascript:if(confirm('Удалить выбранных пользователей?')) { checkSel('?view=users&do=delete&multiple=1'); }";

		$toolmenu[5]['icon'] = 'usergroup.gif';
		$toolmenu[5]['title'] = 'Группы пользователей';
		$toolmenu[5]['link'] = "?view=usergroups";

		$toolmenu[6]['icon'] = 'userbanlist.gif';
		$toolmenu[6]['title'] = 'Бан-лист';
		$toolmenu[6]['link'] = "?view=userbanlist";

		$toolmenu[7]['icon'] = 'user_go.png';
		$toolmenu[7]['title'] = 'Активировать выбранных';
		$toolmenu[7]['link'] = "javascript:if(confirm('Активировать выбранных пользователей?')) { checkSel('?view=users&do=activate&multiple=1'); }";

		$toolmenu[8]['icon'] = 'help.gif';
		$toolmenu[8]['title'] = 'Помощь';
		$toolmenu[8]['link'] = "?view=help&topic=users";

		cpToolMenu($toolmenu);

		//TABLE COLUMNS
		$fields = array();

		$fields[0]['title'] = 'id';			$fields[0]['field'] = 'id';			$fields[0]['width'] = '20';

		$fields[1]['title'] = 'Логин';		$fields[1]['field'] = 'login';		$fields[1]['width'] = '130';		$fields[1]['link'] = '?view=users&do=edit&id=%id%';
		$fields[1]['filter'] = 12;

		$fields[2]['title'] = 'Никнейм';	$fields[2]['field'] = 'nickname';	$fields[2]['width'] = '';		$fields[2]['link'] = '?view=users&do=edit&id=%id%';
		$fields[2]['filter'] = 12;

		$fields[3]['title'] = 'Рейтинг'; $fields[3]['field'] = 'rating'; $fields[3]['width'] = '60';
		$fields[4]['title'] = 'Пересчитать<br/>рейтинг'; $fields[4]['field'] = 'id'; $fields[4]['width'] = '60'; $fields[4]['prc'] = 'setRating';

		$fields[5]['title'] = 'Группа';		$fields[5]['field'] = 'group_id';	$fields[5]['width'] = '120';
		$fields[5]['prc'] = 'cpGroupById';  $fields[5]['filter']= '1';		    $fields[5]['filterlist'] = cpGetList('cms_user_groups');

		$fields[6]['title'] = 'E-Mail';		$fields[6]['field'] = 'email';		$fields[6]['width'] = '120';

		$fields[7]['title'] = 'Дата<br/>регистрации';	$fields[7]['field'] = 'regdate';	$fields[7]['width'] = '100';

		$fields[8]['title'] = 'Последний<br/>вход';		$fields[8]['field'] = 'logdate';	$fields[8]['width'] = '100';
		$fields[9]['title'] = 'Последний<br/>IP';		$fields[9]['field'] = 'last_ip';	$fields[9]['width'] = '90';
		$fields[10]['title'] = 'Активирован?';		$fields[10]['field'] = 'is_locked';	$fields[10]['width'] = '95';
		$fields[10]['prc'] = 'viewAct';
		$fields[11]['title'] = 'Удален?';		$fields[11]['field'] = 'is_deleted';	$fields[11]['width'] = '70';
		$fields[11]['prc'] = 'viewDel';

		//ACTIONS
		$actions = array();
		$actions[0]['title'] = 'Профиль';
		$actions[0]['icon']  = 'profile.gif';
		$actions[0]['link']  = '/users/%login%';

		$actions[3]['title'] = 'Забанить';
		$actions[3]['icon']  = 'ban.gif';
		$actions[3]['link']  = '/admin/index.php?view=userbanlist&do=add&to=%id%';

		$actions[4]['title'] = 'Удалить';
		$actions[4]['icon']  = 'delete.gif';
		$actions[4]['confirm'] = 'Удалить пользователя?';
		$actions[4]['link']  = '?view=users&do=delete&id=%id%';

		$actions[5]['title'] = 'Удалить полностью';
		$actions[5]['icon']  = 'off.gif';
		$actions[5]['confirm'] = 'Удалить пользователя без возможности восстановления?';
		$actions[5]['link']  = '?view=users&do=delete_full&id=%id%';

		//Print table
		cpListTable('cms_users', $fields, $actions, '1=1', 'regdate DESC');
	}

	if ($do == 'rerating'){

		$user_id = $inCore->request('user_id', 'int');
		if(!$user_id) { $inCore->redirectBack(); }

		$rating = cmsUser::getRating($user_id);

        $user_sql = "UPDATE cms_users
                     SET rating = {$rating}
                     WHERE id = '{$user_id}'";

        $inDB->query($user_sql);

		$inCore->redirectBack();

	}

	if ($do == 'activate'){

		$user_ids = $inCore->request('item', 'array_int');
		if(!$user_ids) { $inCore->redirectBack(); }

        foreach($user_ids as $key=>$user_id){

			$code = $inDB->get_field('cms_users_activate', "user_id = '$user_id'", 'code');

			$sql = "UPDATE cms_users SET is_locked = 0 WHERE id = '$user_id'";
			$inDB->query($sql);

			$sql = "DELETE FROM cms_users_activate WHERE code = '$code'";
			$inDB->query($sql);

			cmsCore::callEvent('USER_ACTIVATED', $user_id);

			// Регистрируем событие
			cmsActions::log('add_user', array(
					'object' => '',
					'user_id' => $user_id,
					'object_url' => '',
					'object_id' => $user_id,
					'target' => '',
					'target_url' => '',
					'target_id' => 0,
					'description' => ''
			));

        }

		$inCore->redirectBack();

	}

	if ($do == 'delete'){
        if (!isset($_REQUEST['item'])){
			if ($id >= 0){
				$model->deleteUser($id);
			}
		} else {
			$model->deleteUsers($inCore->request('item', 'array_int'));
		}
		$inCore->redirectBack();
	}

	if ($do == 'delete_full'){
		$model->deleteUser($id, true);
		$inCore->redirectBack();
	}

	if ($do == 'submit'){

        if (!cmsCore::validateForm()) { cmsCore::error404(); }

		$login      = htmlspecialchars($inCore->request('login', 'str',''));
		$nickname   = htmlspecialchars($inCore->request('nickname', 'str',''));
		$email      = $inCore->request('email', 'email','');
		$group_id   = $inCore->request('group_id', 'int');
		$is_locked  = $inCore->request('is_locked', 'int');

        $pass       = $inCore->request('pass', 'str');
        $pass2      = $inCore->request('pass2', 'str');

        $error      = '';

        if (!$login) { $error .= 'Нужно указать логин пользователя!<br/>'; }
        if (!$nickname) { $error .= 'Нужно указать никнейм!<br/>'; }
        if (!$email) { $error .= 'Нужно указать адрес e-mail!<br/>'; }
        if ($pass != $pass2) { $error .= 'Пароли не совпали!'; }

        if(!$error){
            $login_exists = $inDB->get_field('cms_users', "login='{$login}' AND is_deleted = 0", 'id');
            $email_exists = $inDB->get_field('cms_users', "email='{$email}'", 'id');
            if ($login_exists) { $error .= 'Указанный логин занят.<br/>'; }
            if ($email_exists) { $error .= 'Указанный email занят.<br/>'; }
        }

        if (!$error){

            $pass       = md5($pass);

            //create user record
            $sql = "INSERT INTO cms_users (group_id, login, nickname, password, email, icq, regdate, logdate, birthdate, is_locked)
                    VALUES ('$group_id', '$login', '$nickname', '$pass', '$email', '', NOW(), NOW(), '', '$is_locked')";
            dbQuery($sql);

            //create advanced user profile
            $sql = "SELECT id FROM cms_users WHERE login = '$login' AND password = '$pass'";
            $result = dbQuery($sql);

            if (mysql_num_rows($result)==1){
                $usr = mysql_fetch_assoc($result);
                $sql = "INSERT INTO cms_user_profiles (user_id, city, description, showmail, showbirth, showicq, karma, imageurl, allow_who)
                        VALUES (".$usr['id'].", '', '', '0', '0', '1', '0', '', 'all')";
                dbQuery($sql);
            }
            cmsUser::clearCsrfToken();
            $inCore->redirect('?view=users');

        }

        if ($error){

            $mod['login']       = $login;
            $mod['nickname']    = $nickname;
            $mod['email']       = $email;

            $do = 'add';

        }

	}

	if ($do == 'update'){
        if (!cmsCore::validateForm()) { cmsCore::error404(); }
        if (isset($_REQUEST['id'])) {
			$id = (int)$_REQUEST['id'];

            $login      = htmlspecialchars($inCore->request('login', 'str',''));
            $nickname   = htmlspecialchars($inCore->request('nickname', 'str',''));
            $email      = $inCore->request('email', 'email');
			if($id == 1) {
				$group_id   = 2;
				$is_locked  = 0;
			} else {
				$group_id   = $inCore->request('group_id', 'int');
				$is_locked  = $inCore->request('is_locked', 'int');
			}

			if (isset($_REQUEST['pass']) && isset($_REQUEST['pass2'])){
				$pass = $_REQUEST['pass'];
				$pass2 = $_REQUEST['pass2'];
				if (($pass == $pass2) && ($pass!='')) {
					$pass_sql = ", password = '".md5($pass)."' ";
				} else {$pass_sql = ' ';	}
			}

			$sql = "UPDATE cms_users
					SET login = '$login',
						nickname = '$nickname',
						email = '$email',
						group_id = $group_id,
						is_locked = $is_locked $pass_sql
					WHERE id = $id
					LIMIT 1";
			dbQuery($sql) ;
		}
        cmsUser::clearCsrfToken();
		if (!isset($_SESSION['editlist']) || @sizeof($_SESSION['editlist'])==0){
			$inCore->redirect('?view=users');
		} else {
			$inCore->redirect('?view=users&do=edit');
		}
	}

   if ($do == 'edit' || $do== 'add'){

 		$toolmenu = array();
		$toolmenu[0]['icon'] = 'save.gif';
		$toolmenu[0]['title'] = 'Сохранить';
		$toolmenu[0]['link'] = 'javascript:document.addform.submit();';

		$toolmenu[1]['icon'] = 'cancel.gif';
		$toolmenu[1]['title'] = 'Отмена';
		$toolmenu[1]['link'] = 'javascript:history.go(-1);';

		cpToolMenu($toolmenu);

		if ($do=='edit'){

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

					 $sql = "SELECT * FROM cms_users WHERE id = $id LIMIT 1";
					 $result = dbQuery($sql) ;
					 if (mysql_num_rows($result)){
						$mod = mysql_fetch_assoc($result);
					 }

					 echo '<h3>Редактировать пользователя '.$ostatok.'</h3>';
					 cpAddPathway($mod['nickname'], 'index.php?view=users&do=edit&id='.$mod['id']);

		} else {
					 echo '<h3>Создать пользователя</h3>';
					 cpAddPathway('Создать пользователя', 'index.php?view=users&do=add');
		}
		$GLOBALS['cp_page_head'][] = '<script type="text/javascript" src="/components/registration/js/check.js"></script>';
	?>
      <?php if ($error){ ?>
          <div style="color:red;margin-bottom:10px;">
            <?php echo $error; ?>
          </div>
      <?php } ?>
      <form action="index.php?view=users" method="post" enctype="multipart/form-data" name="addform" id="addform">
        <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
        <table width="600" border="0" cellpadding="0" cellspacing="10" class="proptable">
          <tr>
            <td width="" valign="middle"><strong>Логин: </strong></td>
            <td width="220" valign="middle">
                <input name="login" type="text" id="logininput" style="width:220px" value="<?php echo @$mod['login'];?>" onchange="checkLogin()" />
				<div id="logincheck"></div>
			</td>
            <td width="22">
                <?php
                    if ($do=='edit'){
                        echo '<a target="_blank" href="/users/'.$mod['login'].'" title="Профиль на сайте"><img src="images/icons/site.png" border="0" alt="Профиль на сайте"/></a>';
                    }
                ?>
            </td>
          </tr>
          <tr>
            <td valign="middle"><strong>Никнейм:</strong></td>
            <td valign="middle"><input name="nickname" type="text" id="login" style="width:220px" value="<?php echo htmlspecialchars($mod['nickname']);?>"/></td>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td valign="middle"><strong>Email: </strong></td>
            <td valign="middle"><input name="email" type="text" id="nickname" style="width:220px" value="<?php echo @$mod['email'];?>"/></td>
            <td>&nbsp;</td>
          </tr>
          <tr>
		  	<?php if($do=='edit') { ?>
	            <td valign="middle"><strong>Новый пароль:</strong></td>
			<?php } else { ?>
	            <td valign="middle"><strong>Пароль:</strong> </td>
			<?php } ?>
            <td><input name="pass" type="password" id="pass" style="width:220px"/></td>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td valign="middle"><strong>Повтор пароля:</strong> </td>
            <td valign="middle"><input name="pass2" type="password" id="pass2" style="width:220px"/></td>
            <td>&nbsp;</td>
          </tr>
          <tr>
            <td valign="middle"><strong>Группа:</strong></td>
            <td valign="middle">
			<select name="group_id" id="group_id" style="width:225px">
                <?php
                    if (isset($mod['group_id'])) {
                        echo $inCore->getListItems('cms_user_groups', $mod['group_id']);
                    } else {
                        echo $inCore->getListItems('cms_user_groups');
                    }
                ?>
            </select>
			</td>
            <td>
                <?php
                    if ($do=='edit'){
                        if (isset($mod['group_id'])) {
                            echo '<a target="_blank" href="?view=usergroups&do=edit&id='.$mod['group_id'].'"><img src="images/icons/edit.png" border="0" alt="Редактировать группу"/></a>';
                        } else { echo 'Администраторы'; echo '<input type="hidden" name="group_id" value="2" />';}
                    }
                ?>
            </td>
          </tr>
          <tr>
            <td valign="middle"><strong>Заблокировать аккаунт?</strong></td>
            <td valign="middle"><input name="is_locked" type="radio" value="1" <?php if ($mod['is_locked']) { echo 'checked="checked"'; } ?> />
              Да
              <label>
          <input name="is_locked" type="radio" value="0"  <?php if (!$mod['is_locked']) { echo 'checked="checked"'; } ?> />
            Нет</label></td>
            <td>&nbsp;</td>
          </tr>
        </table>
        <p>
		  <?php if($do=='edit'){ ?>
	          <input name="do" type="hidden" id="do" value="update" />
	          <input name="add_mod" type="submit" id="add_mod" value="Сохранить профиль" />
		  <?php } else { ?>
	          <input name="do" type="hidden" id="do" value="submit" />
	          <input name="add_mod" type="submit" id="add_mod" value="Создать профиль" />
		  <?php } ?>
          <span style="margin-top:15px">
          <input name="back2" type="button" id="back2" value="Отмена" onclick="window.history.back();" />
          </span>
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