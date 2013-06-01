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

function createMenuItem($menu, $id, $title){

    $inCore = cmsCore::getInstance();
	$inDB = cmsDatabase::getInstance();

	$rootid = $inDB->getNsRootCatId('cms_menu');

	$ns = $inCore->nestedSetsInit('cms_menu');
	$myid = $ns->AddNode($rootid);

    $link = $inCore->getMenuLink('category', $id, $myid);

	$sql = "UPDATE cms_menu
			SET menu='$menu',
				title='$title',
				link='$link',
				linktype='category',
				linkid='$id',
				target='_self',
				published='1',
				template='0',
				access_list='',
				iconurl=''
			WHERE id = '$myid'";

	$inDB->query($sql);
	return true;
}

function applet_cats(){

    $inCore = cmsCore::getInstance();
    $inDB   = cmsDatabase::getInstance();

    $GLOBALS['cp_page_title'] = 'Разделы сайта';
    cpAddPathway('Разделы сайта', 'index.php?view=tree');

    cmsCore::loadModel('content');
    $model = new cms_model_content();

	$do = cmsCore::request('do', 'str', 'add');
	$id = cmsCore::request('id', 'int', -1);

    define('IS_BILLING', $inCore->isComponentInstalled('billing'));
    if (IS_BILLING) { cmsCore::loadClass('billing'); }

	if ($do == 'delete'){
        $is_with_content = cmsCore::inRequest('content');
        $model->deleteCategory($id, $is_with_content);
		cmsCore::addSessionMessage('Категория успешно удалена', 'success');
		cmsCore::redirect('?view=tree');
	}

	if ($do == 'update'){
        if (!cmsCore::validateForm()) { cmsCore::error404(); }
        if (isset($_REQUEST['id'])) {

			$category['id']			 = cmsCore::request('id', 'int', 0);
			$category['title']		 = cmsCore::request('title', 'str', 'Раздел без названия');
			$category['parent_id']	 = cmsCore::request('parent_id', 'int');
			$category['description'] = cmsCore::request('description', 'html');
            $category['description'] = $inDB->escape_string($category['description']);
			$category['published'] 	 = cmsCore::request('published', 'int', 0);
			$category['showdate'] 	 = cmsCore::request('showdate', 'int', 0);
			$category['showcomm'] 	 = cmsCore::request('showcomm', 'int', 0);
			$category['orderby'] 	 = cmsCore::request('orderby', 'str');
			$category['orderto']	 = cmsCore::request('orderto', 'str');
			$category['modgrp_id'] 	 = cmsCore::request('modgrp_id', 'int', 0);
			$category['maxcols'] 	 = cmsCore::request('maxcols', 'int', 0);
			$category['showtags'] 	 = cmsCore::request('showtags', 'int', 0);
			$category['showrss'] 	 = cmsCore::request('showrss', 'int', 0);
			$category['showdesc'] 	 = cmsCore::request('showdesc', 'int', 0);
			$category['is_public'] 	 = cmsCore::request('is_public', 'int', 0);
			$category['url']         = cmsCore::request('url', 'str');
			if ($category['url']) { $category['url'] = cmsCore::strToURL($category['url'], $model->config['is_url_cyrillic']); }
			$category['tpl'] 		 = cmsCore::request('tpl', 'str', 'com_content_view.tpl');

            $category['cost']        = cmsCore::request('cost', 'str', '');
            if (!is_numeric($category['cost'])) { $category['cost'] = ''; }

			$album = array();
			$album['id']      = cmsCore::request('album_id', 'int', 0);
			$album['header']  = cmsCore::request('album_header', 'str', '');
			$album['orderby'] = cmsCore::request('album_orderby', 'str', '');
			$album['orderto'] = cmsCore::request('album_orderto', 'str', '');
			$album['maxcols'] = cmsCore::request('album_maxcols', 'int', 0);
			$album['max']	  = cmsCore::request('album_max', 'int', 0);

			if($album['id']){
				$category['photoalbum'] = serialize($album);
			} else {
				$category['photoalbum'] = '';
			}

			// получаем старую категорию
            $old = $inDB->get_fields('cms_category', "id='{$category['id']}'", '*');

			// если сменили категорию
			if($old['parent_id'] != $category['parent_id']){
				// перемещаем ее в дереве
				$inCore->nestedSetsInit('cms_category')->MoveNode($category['id'], $category['parent_id']);
				// обновляем сеолинки категорий
				$inDB->updateNsCategorySeoLink('cms_category', $category['id'], $model->config['is_url_cyrillic']);
                // Обновляем ссылки меню на категории
                $model->updateCatMenu();
				// обновляем сеолинки всех вложенных статей
				$model->updateArticlesSeoLink($category['id']);
				cmsCore::addSessionMessage('URL вложенных разделов и статей изменены согласно новой категории', 'info');
			}

			$inDB->update('cms_category', $category, $category['id']);

			// если пришел запрос на обновление ссылок
			// и категория не менялась - если менялась, мы выше все обновили
			if (cmsCore::inRequest('update_seolink') && ($old['parent_id'] == $category['parent_id'])){
				// обновляем сеолинки категорий
				$inDB->updateNsCategorySeoLink('cms_category', $category['id'], $model->config['is_url_cyrillic']);
                // Обновляем ссылки меню на категории
                $model->updateCatMenu();
				// обновляем сеолинки всех вложенных статей
				$model->updateArticlesSeoLink($category['id']);
				cmsCore::addSessionMessage('URL вложенных разделов и статей обновлены', 'info');
			}

			if (!cmsCore::request('is_access', 'int', 0)){
				$showfor = $_REQUEST['showfor'];
				cmsCore::setAccess($category['id'], $showfor, 'category');
			} else {
				cmsCore::clearAccess($category['id'], 'category');
            }

			cmsCore::addSessionMessage('Категория успешно сохранена', 'success');

            cmsUser::clearCsrfToken();

			if (!isset($_SESSION['editlist']) || @sizeof($_SESSION['editlist'])==0){
				cmsCore::redirect('?view=tree&cat_id='.$category['id']);
			} else {
				cmsCore::redirect('?view=tree');
			}
		}
	}

	if ($do == 'submit'){
        if (!cmsCore::validateForm()) { cmsCore::error404(); }
        $category['title']       = cmsCore::request('title', 'str', 'Категория без названия');
        $category['url']         = cmsCore::request('url', 'str');
		if ($category['url']) { $category['url'] = cmsCore::strToURL($category['url']); }
        $category['parent_id']   = cmsCore::request('parent_id', 'int');
        $category['description'] = cmsCore::request('description', 'html');
        $category['description'] = $inDB->escape_string($category['description']);
        $category['published']   = cmsCore::request('published', 'int', 0);
        $category['showdate']    = cmsCore::request('showdate', 'int', 0);
        $category['showcomm']    = cmsCore::request('showcomm', 'int', 0);
        $category['orderby']     = cmsCore::request('orderby', 'str');
        $category['orderto']     = cmsCore::request('orderto', 'str');
        $category['modgrp_id']   = cmsCore::request('modgrp_id', 'int', 0);
        $category['maxcols']     = cmsCore::request('maxcols', 'int', 0);
        $category['showtags']    = cmsCore::request('showtags', 'int', 0);
        $category['showrss']     = cmsCore::request('showrss', 'int', 0);
        $category['showdesc']    = cmsCore::request('showdesc', 'int', 0);
        $category['is_public']   = cmsCore::request('is_public', 'int', 0);
        $category['tpl']         = cmsCore::request('tpl', 'str', 'com_content_view.tpl');

        $category['cost']        = cmsCore::request('cost', 'str', 0);
        if (!is_numeric($category['cost'])) { $category['cost'] = ''; }

        $album = array();
        $album['id']      = cmsCore::request('album_id', 'int', 0);
        $album['header']  = cmsCore::request('album_header', 'str', '');
        $album['orderby'] = cmsCore::request('album_orderby', 'str', '');
        $album['orderto'] = cmsCore::request('album_orderto', 'str', '');
        $album['maxcols'] = cmsCore::request('album_maxcols', 'int', 0);
        $album['max']	  = cmsCore::request('album_max', 'int', 0);

		if($album['id']){
	        $category['photoalbum'] = serialize($album);
		} else {
			$category['photoalbum'] = '';
		}

        $ns = $inCore->nestedSetsInit('cms_category');
        $category['id'] = $ns->AddNode($category['parent_id']);

        $category['seolink'] = cmsCore::generateCatSeoLink($category, 'cms_category', $model->config['is_url_cyrillic']);

        if ($category['id']){

			$inDB->update('cms_category', $category, $category['id']);

			if (!cmsCore::request('is_access', 'int', 0)){
				$showfor = $_REQUEST['showfor'];
				cmsCore::setAccess($category['id'], $showfor, 'category');
			} else {
				cmsCore::clearAccess($category['id'], 'category');
            }
        }

        $inmenu = cmsCore::request('createmenu', 'str', '');

        if ($inmenu){
            createMenuItem($inmenu, $category['id'], $category['title']);
        }

		cmsCore::addSessionMessage('Категория успешно добавлена', 'success');

        cmsUser::clearCsrfToken();

		cmsCore::redirect('?view=tree');

    }

   if ($do == 'add' || $do == 'edit'){

	 	require('../includes/jwtabs.php');
		$GLOBALS['cp_page_head'][] = jwHeader();
		$GLOBALS['cp_page_head'][] = '<script language="JavaScript" type="text/javascript" src="js/content.js"></script>';

 		$toolmenu = array();
		$toolmenu[0]['icon'] = 'save.gif';
		$toolmenu[0]['title'] = 'Сохранить';
		$toolmenu[0]['link'] = 'javascript:document.addform.submit();';

		$toolmenu[1]['icon'] = 'cancel.gif';
		$toolmenu[1]['title'] = 'Отмена';
		$toolmenu[1]['link'] = 'javascript:history.go(-1);';

		cpToolMenu($toolmenu);

		if ($do=='add'){
			 echo '<h3>Добавить раздел</h3>';
 	 		 cpAddPathway('Добавить раздел', 'index.php?view=cats&do=add');
			 $mod['tpl'] = 'com_content_view.tpl';
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

			 $sql = "SELECT * FROM cms_category WHERE id = $id LIMIT 1";
			 $result = dbQuery($sql) ;
			 if (mysql_num_rows($result)){
				$mod = mysql_fetch_assoc($result);
				if(@$mod['photoalbum']){
					$mod['photoalbum'] = unserialize($mod['photoalbum']);
				}
			 }

			 echo '<h3>Редактировать раздел '.$ostatok.'</h3>';
			 cpAddPathway($mod['title'], 'index.php?view=cats&do=edit&id='.$mod['id']);
		}
	?>

    <form id="addform" name="addform" method="post" action="index.php">
        <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
        <input type="hidden" name="view" value="cats" />
        <table class="proptable" width="100%" cellpadding="15" cellspacing="2">
            <tr>

                <!-- главная ячейка -->
                <td valign="top">
                    <table border="0" cellpadding="0" cellspacing="5" width="100%">
                      <tbody>
                        <tr>
                          <td>
                            <strong>Название раздела</strong>
                          </td>
                          <td width="190" style="padding-left:6px">
                            <strong>Шаблон раздела</strong>
                          </td>
                        </tr>
                        <tr>
                          <td>
                        <input name="title" type="text" id="title" style="width:100%" value="<?php echo htmlspecialchars($mod['title']);?>" />
                          </td>
                          <td style="padding-left:6px">
                            <input name="tpl" type="text" style="width:98%" value="<?php echo @$mod['tpl'];?>" />
                          </td>
                        </tr>
                      </tbody>
                    </table>
                    <div><strong>Родительский раздел</strong></div>
                    <div>
                        <div class="parent_notice" style="color:red;margin:4px 0px;display:none">Раздел будет вложен сам в себя. Выберите другого родителя.</div>
                        <select name="parent_id" size="12" id="parent_id" style="width:100%" onchange="if($(this).val()=='<?php echo $mod['id']; ?>'){ $('.parent_notice').show();$('#add_mod').attr('disabled', 'disabled'); } else { $('.parent_notice').hide();$('#add_mod').attr('disabled', ''); }">
                            <?php $rootid = $inDB->getNsRootCatId('cms_category'); ?>
                            <option value="<?php echo $rootid; ?>" <?php if (@$mod['parent_id']==$rootid || !isset($mod['parent_id'])) { echo 'selected="selected"'; }?>>-- Корневой раздел --</option>
                            <?php
                                if (isset($mod['parent_id'])){
                                    echo $inCore->getListItemsNS('cms_category', $mod['parent_id']);
                                } else {
                                    echo $inCore->getListItemsNS('cms_category');
                                }
                            ?>
                        </select>
                    </div>

                    <div><strong>Описание раздела</strong></div>
                    <div>
                        <?php $inCore->insertEditor('description', $mod['description'], '250', '100%'); ?>
                    </div>

                </td>

                <!-- боковая -->
                <td valign="top" width="300" style="background:#ECECEC;">

                    <?php ob_start(); ?>

                    {tab=Публикация}

                    <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist">
                        <tr>
                            <td width="20"><input type="checkbox" name="published" id="published" value="1" <?php if ($mod['published'] || $do=='add') { echo 'checked="checked"'; } ?>/></td>
                            <td><label for="published"><strong>Публиковать раздел</strong></label></td>
                        </tr>
                    </table>

					<div style=" <?php if ($do=='edit') {  ?>display:none;<?php } ?>" class="url_cat">
                        <div style="margin-top:15px">
                            <strong>URL раздела</strong><br/>
                            <div style="color:gray">Если не указан, генерируется из заголовка</div>
                        </div>
                        <div>
                            <input type="text" name="url" value="<?php echo $mod['url']; ?>" style="width:99%"/>
                        </div>
                    </div>

					<?php if ($do=='edit') {  ?>
                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:15px">
                            <tr>
                                <td width="20"><input type="checkbox" name="update_seolink" id="update_seolink" value="1" onclick="$('.url_cat').slideToggle('fast');" /></td>
                                <td><label for="update_seolink"><strong>Обновить ЧПУ ссылку</strong></label></td>
                            </tr>
                        </table>
                        <div class="url_cat" style="display:none;"><strong style="color:#F00;">Внимание:</strong> ссылки на вложенные разделы и статьи в них изменятся и будут недоступны по старому адресу.</div>
                    <?php } ?>

                    <div style="margin-top:20px"><strong>Сортировка статей</strong></div>
                    <div>
                        <select name="orderby" id="orderby" style="width:100%">
                            <option value="pubdate" <?php if (@$mod['orderby']=='pubdate') { echo 'selected="selected"'; } ?>>По дате</option>
                            <option value="title" <?php if (@$mod['orderby']=='title') { echo 'selected="selected"'; } ?>>По заголовку</option>
                            <option value="ordering" <?php if (@$mod['orderby']=='ordering') { echo 'selected="selected"'; } ?>>По порядку</option>
                            <option value="hits" <?php if (@$mod['orderby']=='hits') { echo 'selected="selected"'; } ?>>По просмотрам</option>
                        </select>
                        <select name="orderto" id="orderto" style="width:100%">
                            <option value="ASC" <?php if (@$mod['orderto']=='ASC') { echo 'selected="selected"'; } ?>>По возрастанию</option>
                            <option value="DESC" <?php if (@$mod['orderto']=='DESC') { echo 'selected="selected"'; } ?>>По убыванию</option>
                        </select>
                    </div>

                    <div style="margin-top:20px"><strong>Число колонок для вывода статей</strong></div>
                    <div>
                        <?php if (!isset($mod['maxcols'])) { $mod['maxcols'] = 1; } ?>
                        <input name="maxcols" type="text" id="maxcols" style="width:99%" value="<?php echo @$mod['maxcols'];?>" />
                    </div>

                    <div style="margin-top:20px"><strong>Параметры публикации</strong></div>
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist">
                        <tr>
                            <td width="20"><input type="checkbox" name="showdesc" id="showdesc" value="1" <?php if ($mod['showdesc'] || $do=='add') { echo 'checked="checked"'; } ?>/></td>
                            <td><label for="showdesc">Показывать анонсы статей</label></td>
                        </tr>
                        <tr>
                            <td width="20"><input type="checkbox" name="showdate" id="showdate" value="1" <?php if ($mod['showdate'] || $do=='add') { echo 'checked="checked"'; } ?>/></td>
                            <td><label for="showdate">Показывать даты статей</label></td>
                        </tr>
                        <tr>
                            <td width="20"><input type="checkbox" name="showcomm" id="showcomm" value="1" <?php if ($mod['showcomm'] || $do=='add') { echo 'checked="checked"'; } ?>/></td>
                            <td><label for="showcomm">Показывать число комментариев</label></td>
                        </tr>
                        <tr>
                            <td width="20"><input type="checkbox" name="showtags" id="showtags" value="1" <?php if ($mod['showtags'] || $do=='add') { echo 'checked="checked"'; } ?>/></td>
                            <td><label for="showtags">Показывать теги статей</label></td>
                        </tr>
                        <tr>
                            <td width="20"><input type="checkbox" name="showrss" id="showrss" value="1" <?php if ($mod['showrss'] || $do=='add') { echo 'checked="checked"'; } ?>/></td>
                            <td><label for="showrss">Показывать иконку RSS</label></td>
                        </tr>
                    </table>

                    <?php if ($do=='add'){ ?>
                        <div style="margin-top:25px">
                            <strong>Создать ссылку в меню</strong>
                        </div>
                        <div>
                            <select name="createmenu" id="createmenu" style="width:99%">
                                <option value="0" selected="selected">-- не создавать --</option>
                                <option value="mainmenu">Главное меню</option>
                                <?php for($m=1;$m<=15;$m++){ ?>
                                    <option value="menu<?php echo $m; ?>">Дополнительное меню <?php echo $m; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                    <?php } ?>

                    {tab=Редакторы}

                        <div style="margin-top:10px">
                            <strong>Принимать статьи от пользователей</strong><br/>
                            <span class="hinttext">Если включено, то раздел отображается в списке доступных для публикации разделов, когда пользователь добавляет статью с сайта.</span>
                        </div>
                        <div>
                            <select name="is_public" id="is_public" style="width:100%">
                                <option value="0" <?php if(!$mod['is_public']) { echo 'selected="selected"'; } ?>>Нет</option>
                                <option value="1" <?php if($mod['is_public']) { echo 'selected="selected"'; } ?>>Да</option>
                            </select>
                        </div>
                        <?php if (IS_BILLING){ ?>
                            <div style="margin-top:15px">
                                <strong>Стоимость добавления статьи</strong><br/>
                                <div style="color:gray">Если не указана здесь, то используется цена по-умолчанию, указанная в настройках биллинга</div>
                            </div>
                            <div>
                                <input type="text" name="cost" value="<?php echo $mod['cost']; ?>" style="width:50px"/> баллов
                            </div>
                        <?php } ?>
                        <div style="margin-top:20px">
                            <strong>Редакторы раздела</strong><br/>
                            <span class="hinttext">Пользователи выбранной группы смогут администрировать статьи данного раздела. Обратите внимание, в настройках группы должен быть включен тип доступа "Принимать статьи без модерации"</span>
                        </div>
                        <div>
                            <select name="modgrp_id" id="modgrp_id" style="width:100%">
                                <option value="0" <?php if (!isset($mod['modgrp_id']) || @$mod['modgrp_id']==0) { echo 'selected'; }?>>Только администраторы</option>
                                <?php
                                    if (@$mod['modgrp_id']) {
                                        echo $inCore->getListItems('cms_user_groups', $mod['modgrp_id'], 'id', 'ASC', 'is_admin = 0');
                                    }	else {
                                        echo $inCore->getListItems('cms_user_groups', 0, 'id', 'ASC', 'is_admin = 0');
                                    }
                                ?>
                            </select>
                        </div>

                    {tab=Фото}

                        <div style="margin-top:10px">
                            <strong>Привязать фотоальбом</strong><br/>
                            <span class="hinttext">При просмотре раздела - над статьями (по умолчанию - вы можете поменять вывод в шаблоне com_content_view.tpl) будут выводиться фотографии из выбранного альбома</span>
                        </div>
                        <div>
                            <select name="album_id" id="album_id" style="width:100%" onchange="choosePhotoAlbum()">
                                <option value="0" <?php if (!isset($mod['photoalbum']['id']) || !@$mod['photoalbum']['id']) { echo 'selected="selected"'; }?>>-- не привязывать --</option>
                                <?php  //FIND ROOT
                                    if (isset($mod['photoalbum']['id'])){
                                        echo $inCore->getListItemsNS('cms_photo_albums', $mod['photoalbum']['id']);
                                    } else {
                                        echo $inCore->getListItemsNS('cms_photo_albums');
                                    }
                                ?>
                            </select>
                        </div>
						<div id="con_photoalbum" <?php if (!isset($mod['photoalbum']['id']) || !$mod['photoalbum']['id']) { echo 'style="display:none;"'; }?>>
                            <div style="margin-top:20px">
                                <strong>Заголовок</strong><br/>
                                <span class="hinttext">Выводится над фотографиями</span>
                            </div>
                            <div>
                                <input name="album_header" type="text" id="album_header" style="width:99%" value="<?php echo @$mod['photoalbum']['header'];?>" />
                            </div>

                            <div style="margin-top:20px">
                                <strong>Сортировка фото</strong>
                            </div>
                            <div>
                                <select name="album_orderby" id="album_orderby" style="width:100%">
                                    <option value="title" <?php if (@$mod['photoalbum']['orderby']=='title') { echo 'selected="selected"'; } ?>>По алфавиту</option>
                                    <option value="pubdate" <?php if (@$mod['photoalbum']['orderby']=='pubdate') { echo 'selected="selected"'; } ?>>По дате</option>
                                    <option value="rating" <?php if (@$mod['photoalbum']['orderby']=='rating') { echo 'selected="selected"'; } ?>>По рейтингу</option>
                                    <option value="hits" <?php if (@$mod['photoalbum']['orderby']=='hits') { echo 'selected="selected"'; } ?>>По просмотрам</option>
                                </select>
                                <select name="album_orderto" id="album_orderto" style="width:100%">
                                    <option value="desc" <?php if (@$mod['photoalbum']['orderto']=='desc') { echo 'selected="selected"'; } ?>>по убыванию</option>
                                    <option value="asc" <?php if (@$mod['photoalbum']['orderto']=='asc') { echo 'selected="selected"'; } ?>>по возрастанию</option>
                                </select>
                            </div>

                            <div style="margin-top:20px">
                                <strong>Число колонок для вывода</strong>
                            </div>
                            <div>
                                <?php if(!isset($mod['photoalbum']['maxcols'])) { $mod['photoalbum']['maxcols'] = 2; } ?>
                                <input name="album_maxcols" type="text" id="album_maxcols" style="width:99%" value="<?php echo @$mod['photoalbum']['maxcols'];?>"/>
                            </div>

                            <div style="margin-top:20px">
                                <strong>Число фотографий</strong>
                            </div>
                            <div>
                                <?php if(!isset($mod['photoalbum']['max'])) { $mod['photoalbum']['max'] = 8; } ?>
                                <input name="album_max" type="text" id="album_max" style="width:99%" value="<?php echo @$mod['photoalbum']['max'];?>"/>
                            </div>
                       </div>
                      {tab=Доступ}

                      <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist" style="margin-top:5px">
                          <tr>
                              <td width="20">
                                  <?php
                                      $sql    = "SELECT * FROM cms_user_groups";
                                      $result = dbQuery($sql) ;

                                      $style  = 'disabled="disabled"';
                                      $public = 'checked="checked"';

                                      if ($do == 'edit'){

                                          $sql2 = "SELECT * FROM cms_content_access WHERE content_id = ".$mod['id']." AND content_type = 'category'";
                                          $result2 = dbQuery($sql2);
                                          $ord = array();

                                          if (mysql_num_rows($result2)){
                                              $public = '';
                                              $style = '';
                                              while ($r = mysql_fetch_assoc($result2)){
                                                  $ord[] = $r['group_id'];
                                              }
                                          }
                                      }
                                  ?>
                                  <input name="is_access" type="checkbox" id="is_public" onclick="checkGroupList()" value="1" <?php echo $public?> />
                              </td>
                              <td><label for="is_public"><strong>Общий доступ</strong></label></td>
                          </tr>
                      </table>
                      <div style="padding:5px">
                          <span class="hinttext">
                              Если отмечено, категория будет видна всем посетителям. Снимите галочку, чтобы вручную выбрать разрешенные группы пользователей.
                          </span>
                      </div>

                      <div style="margin-top:10px;padding:5px;padding-right:0px;" id="grp">
                          <div>
                              <strong>Показывать группам:</strong><br />
                              <span class="hinttext">
                                  Можно выбрать несколько, удерживая CTRL.
                              </span>
                          </div>
                          <div>
                              <?php
                                  echo '<select style="width: 99%" name="showfor[]" id="showin" size="6" multiple="multiple" '.$style.'>';

                                  if (mysql_num_rows($result)){
                                      while ($item=mysql_fetch_assoc($result)){
                                          echo '<option value="'.$item['id'].'"';
                                          if ($do=='edit'){
                                              if (inArray($ord, $item['id'])){
                                                  echo 'selected="selected"';
                                              }
                                          }

                                          echo '>';
                                          echo $item['title'].'</option>';
                                      }
                                  }

                                  echo '</select>';
                              ?>
                          </div>
                      </div>

                    {/tabs}

                    <?php echo jwTabs(ob_get_clean()); ?>

                </td>

            </tr>
        </table>
        <p>
            <input name="add_mod" type="submit" id="add_mod" <?php if ($do=='add') { echo 'value="Создать раздел"'; } else { echo 'value="Сохранить раздел"'; } ?> />
            <input name="back" type="button" id="back" value="Отмена" onclick="window.history.back();"/>
            <input name="do" type="hidden" id="do" <?php if ($do=='add') { echo 'value="submit"'; } else { echo 'value="update"'; } ?> />
            <?php
                if ($do=='edit'){
                    echo '<input name="id" type="hidden" value="'.$mod['id'].'" />';
                }
            ?>
        </p>

    </form>
<script type="text/javascript">
function choosePhotoAlbum(){
	id = $('select[name=album_id]').val();
	if(id != 0){
		$('#con_photoalbum').fadeIn();
	} else {
		$('#con_photoalbum').hide();
	}
}
</script>
<?php
   }
}
?>
