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

	function viewAct($value){
		if ($value) {
			$value = '<span style="color:green;">да</span>';
		} else {
			$value = '<span style="color:red;">Нет</span>';
		}
		return $value;
	}

    $cfg = $inCore->loadComponentConfig('photos');

    $inDB = cmsDatabase::getInstance();

	cmsCore::loadClass('photo');
    cmsCore::loadModel('photos');
    $model = new cms_model_photos();

    $opt = cmsCore::request('opt', 'str', 'list_albums');
	$id  = cmsCore::request('id', 'int');

	cpAddPathway('Фотогалерея', '?view=components&do=config&id='.$id);
	echo '<h3>Фотогалерея</h3>';

//=================================================================================================//
//=================================================================================================//

	$toolmenu = array();

	if($opt=='saveconfig'){

		if(!cmsCore::validateForm()) { cmsCore::error404(); }

		$cfg = array();
		$cfg['link']        = cmsCore::request('show_link', 'int', 0);
		$cfg['saveorig']    = cmsCore::request('saveorig', 'int', 0);
		$cfg['maxcols']     = cmsCore::request('maxcols', 'int', 0);
		$cfg['orderby']     = cmsCore::request('orderby', 'str', '');
		$cfg['orderto']     = cmsCore::request('orderto', 'str', '');
		$cfg['showlat']     = cmsCore::request('showlat', 'int', 0);
		$cfg['watermark']   = cmsCore::request('watermark', 'int', 0);
		$cfg['best_latest_perpage'] = cmsCore::request('best_latest_perpage', 'int', 0);
		$cfg['best_latest_maxcols'] = cmsCore::request('best_latest_maxcols', 'int', 0);

		$inCore->saveComponentConfig('photos', $cfg);

		cmsCore::addSessionMessage('Настройки успешно сохранены', 'success');

        cmsUser::clearCsrfToken();

		cmsCore::redirectBack();

	}

//=================================================================================================//
//=================================================================================================//

	if ($opt=='list_albums'){

		$toolmenu[0]['icon'] = 'newfolder.gif';
		$toolmenu[0]['title'] = 'Новый альбом';
		$toolmenu[0]['link'] = '?view=components&do=config&id='.$id.'&opt=add_album';

		$toolmenu[3]['icon'] = 'folders.gif';
		$toolmenu[3]['title'] = 'Фотоальбомы';
		$toolmenu[3]['link'] = '?view=components&do=config&id='.$id.'&opt=list_albums';

		$toolmenu[5]['icon'] = 'config.gif';
		$toolmenu[5]['title'] = 'Настройки';
		$toolmenu[5]['link'] = '?view=components&do=config&id='.$id.'&opt=config';

	}

//=================================================================================================//
//=================================================================================================//

	if (in_array($opt, array('config','add_album','edit_album'))){

		$toolmenu[20]['icon'] = 'save.gif';
		$toolmenu[20]['title'] = 'Сохранить';
		$toolmenu[20]['link'] = 'javascript:document.addform.submit();';

		$toolmenu[21]['icon'] = 'cancel.gif';
		$toolmenu[21]['title'] = 'Отмена';
		$toolmenu[21]['link'] = '?view=components&do=config&id='.$id;

	}

	cpToolMenu($toolmenu);

//=================================================================================================//
//=================================================================================================//

	if ($opt == 'config') {

        cpAddPathway('Настройки', '?view=components&do=config&id='.$id.'&opt=config');

		?>
		<?php cpCheckWritable('/images/photos', 'folder'); ?>
		<?php cpCheckWritable('/images/photos/medium', 'folder'); ?>
		<?php cpCheckWritable('/images/photos/small', 'folder'); ?>

        <form action="index.php?view=components&amp;do=config&amp;id=<?php echo $id; ?>" method="post" enctype="multipart/form-data" name="optform">
        <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
          <table width="" border="0" cellpadding="10" cellspacing="0" class="proptable">
            <tr>
              <td width="300"><strong>Показывать ссылки на оригинал: </strong></td>
              <td width="250">
                <label><input name="show_link" type="radio" value="1" <?php if ($cfg['link']) { echo 'checked="checked"'; } ?>/> Да</label>
                <label><input name="show_link" type="radio" value="0" <?php if (!$cfg['link']) { echo 'checked="checked"'; } ?>/> Нет</label>
              </td>
            </tr>
            <tr>
              <td><strong>Сохранять оригиналы при загрузке<br />
              фотографий пользователями:</strong> </td>
              <td>
                  <label><input name="saveorig" type="radio" value="1" <?php if ($cfg['saveorig']) { echo 'checked="checked"'; } ?>/> Да</label>
                  <label><input name="saveorig" type="radio" value="0" <?php if (!$cfg['saveorig']) { echo 'checked="checked"'; } ?>/> Нет	</label>			  </td>
            </tr>
            <tr>
              <td><strong>Количество колонок для<br />вывода списка альбомов: </strong></td>
              <td><input name="maxcols" type="text" id="maxcols" size="5" value="<?php echo $cfg['maxcols'];?>"/> шт</td>
            </tr>
            <tr>
              <td valign="top"><strong>Сортировать список альбомов: </strong></td>
              <td><select name="orderby" style="width:190px">
                <option value="title" <?php if($cfg['orderby']=='title') { echo 'selected'; } ?>>По алфавиту</option>
                <option value="pubdate" <?php if($cfg['orderby']=='pubdate') { echo 'selected'; } ?>>По дате</option>
              </select>
                <select name="orderto" style="width:190px">
                  <option value="desc" <?php if($cfg['orderto']=='desc') { echo 'selected'; } ?>>по убыванию</option>
                  <option value="asc" <?php if($cfg['orderto']=='asc') { echo 'selected'; } ?>>по возрастанию</option>
                </select></td>
            </tr>
            <tr>
              <td><strong>Показывать ссылки на последние и лучшие фото: </strong></td>
              <td>
                <label><input name="showlat" type="radio" value="1" <?php if ($cfg['showlat']) { echo 'checked="checked"'; } ?>/> Да</label>
                <label><input name="showlat" type="radio" value="0" <?php if (!$cfg['showlat']) { echo 'checked="checked"'; } ?>/> Нет</label>
              </td>
            </tr>
            <tr>
              <td><strong>Количество последних/лучших фото на странице: </strong></td>
              <td>
                <input name="best_latest_perpage" type="text" size="5" value="<?php echo $cfg['best_latest_perpage']; ?>"/> шт
              </td>
            </tr>
            <tr>
              <td><strong>Количество колонок последних/лучших фото: </strong></td>
              <td>
                <input name="best_latest_maxcols" type="text" size="5" value="<?php echo $cfg['best_latest_maxcols']; ?>"/> шт
              </td>
            </tr>
            <tr>
              <td>
                  <strong>Наносить водяной знак:</strong><br />
                  <span class="hinttext">Если включено, то на все загружаемые фотографии будет наносится изображение из файла "<a href="/images/watermark.png" target="_blank">/images/watermark.png</a>"</span></td>
              <td>
                <label><input name="watermark" type="radio" value="1" <?php if ($cfg['watermark']) { echo 'checked="checked"'; } ?>/> Да</label>
                <label><input name="watermark" type="radio" value="0" <?php if (!$cfg['watermark']) { echo 'checked="checked"'; } ?>/> Нет	</label>  				  </td>
            </tr>
          </table>
          <p>
            <input name="opt" type="hidden" value="saveconfig" />
            <input name="save" type="submit" id="save" value="Сохранить" />
          </p>
    </form>
		<?php
	}

//=================================================================================================//
//=================================================================================================//

	if ($opt == 'show_album'){
		if(isset($_REQUEST['item_id'])) {
			$item_id = cmsCore::request('item_id', 'int');
			$sql = "UPDATE cms_photo_albums SET published = 1 WHERE id = '$item_id'";
			$inDB->query($sql) ;
			echo '1'; exit;
		}
	}

//=================================================================================================//
//=================================================================================================//

	if ($opt == 'hide_album'){
		if(isset($_REQUEST['item_id'])) {
			$item_id = cmsCore::request('item_id', 'int');
			$sql = "UPDATE cms_photo_albums SET published = 0 WHERE id = '$item_id'";
			$inDB->query($sql) ;
			echo '1'; exit;
		}
	}

//=================================================================================================//
//=================================================================================================//

	if ($opt == 'submit_album'){

		if(!cmsCore::validateForm()) { cmsCore::error404(); }

        $album['title']         = cmsCore::request('title', 'str');
		if(!$album['title']) { $album['title'] = 'Альбом без названия'; }
		$album['description']   = cmsCore::request('description', 'str');
		$album['published']     = cmsCore::request('published', 'int');
		$album['showdate']      = cmsCore::request('showdate', 'int');
		$album['parent_id']     = cmsCore::request('parent_id', 'int');
		$album['showtype']      = cmsCore::request('showtype', 'str');
		$album['public']        = cmsCore::request('public', 'int');
		$album['orderby']       = cmsCore::request('orderby', 'str');
		$album['orderto']       = cmsCore::request('orderto', 'str');
		$album['perpage']       = cmsCore::request('perpage', 'int');
		$album['thumb1']        = cmsCore::request('thumb1', 'int');
		$album['thumb2']        = cmsCore::request('thumb2', 'int');
		$album['thumbsqr']      = cmsCore::request('thumbsqr', 'int');
		$album['cssprefix']     = cmsCore::request('cssprefix', 'str');
		$album['nav']           = cmsCore::request('nav', 'int');
		$album['uplimit']       = cmsCore::request('uplimit', 'int');
		$album['maxcols']       = cmsCore::request('maxcols', 'int');
		$album['orderform']     = cmsCore::request('orderform', 'int');
		$album['showtags']      = cmsCore::request('showtags', 'int');
		$album['bbcode']        = cmsCore::request('bbcode', 'int');
        $album['is_comments']   = cmsCore::request('is_comments', 'int');

		$album  = cmsCore::callEvent('ADD_ALBUM', $album);

		$inDB->addNsCategory('cms_photo_albums', $album);

		cmsCore::addSessionMessage('Альбом "'.stripslashes($album['title']).'" успешно создан', 'success');

        cmsUser::clearCsrfToken();

		cmsCore::redirect('?view=components&do=config&id='.$id.'&opt=list_albums');

	}

//=================================================================================================//
//=================================================================================================//

	if($opt == 'delete_album'){

		if(cmsCore::inRequest('item_id')){

			$album = $inDB->getNsCategory('cms_photo_albums', cmsCore::request('item_id', 'int'));
			if (!$album) { cmsCore::redirect('?view=components&do=config&id='.$id.'&opt=list_albums'); }

			cmsCore::addSessionMessage('Альбом "'.stripslashes($album['title']).'", вложенные в него и все фотографии в них удалены.', 'success');

			cmsPhoto::getInstance()->deleteAlbum($album['id'], '', $model->initUploadClass($album));

		}

		cmsCore::redirect('?view=components&do=config&id='.$id.'&opt=list_albums');

	}

//=================================================================================================//
//=================================================================================================//

	if ($opt == 'update_album'){

		if(!cmsCore::validateForm()) { cmsCore::error404(); }

		if(cmsCore::inRequest('item_id')) {

			$item_id = cmsCore::request('item_id', 'int');

			$old_album = $inDB->getNsCategory('cms_photo_albums', $item_id);
			if (!$old_album) { cmsCore::redirect('?view=components&do=config&id='.$id.'&opt=list_albums'); }

            $album['title']         = cmsCore::request('title', 'str');
			if(!$album['title']) { $album['title'] = $old_album['title']; }
            $album['description']   = cmsCore::request('description', 'html');
            $album['description']   = $inDB->escape_string($album['description']);
            $album['published']     = cmsCore::request('published', 'int');
            $album['showdate']      = cmsCore::request('showdate', 'int');
            $album['parent_id']     = cmsCore::request('parent_id', 'int');
            $album['is_comments']   = cmsCore::request('is_comments', 'int');
            $album['showtype']      = cmsCore::request('showtype', 'str');
            $album['public']        = cmsCore::request('public', 'int');
            $album['orderby']       = cmsCore::request('orderby', 'str');
            $album['orderto']       = cmsCore::request('orderto', 'str');
            $album['perpage']       = cmsCore::request('perpage', 'int');
            $album['thumb1']        = cmsCore::request('thumb1', 'int');
            $album['thumb2']        = cmsCore::request('thumb2', 'int');
            $album['thumbsqr']      = cmsCore::request('thumbsqr', 'int');
            $album['cssprefix']     = cmsCore::request('cssprefix', 'str');
            $album['nav']           = cmsCore::request('nav', 'int');
            $album['uplimit']       = cmsCore::request('uplimit', 'int');
            $album['maxcols']       = cmsCore::request('maxcols', 'int');
            $album['orderform']     = cmsCore::request('orderform', 'int');
            $album['showtags']      = cmsCore::request('showtags', 'int');
            $album['bbcode']        = cmsCore::request('bbcode', 'int');
			$album['iconurl']       = cmsCore::request('iconurl', 'str');

			// если сменили категорию
			if($old_album['parent_id'] != $album['parent_id']){
				// перемещаем ее в дереве
				$inCore->nestedSetsInit('cms_photo_albums')->MoveNode($item_id, $album['parent_id']);
			}

			$inDB->update('cms_photo_albums', $album, $item_id);
			cmsCore::addSessionMessage('Альбом "'.stripslashes($album['title']).'" сохранен.', 'success');
            cmsUser::clearCsrfToken();
			cmsCore::redirect('?view=components&do=config&id='.$id.'&opt=list_albums');

		}
	}

//=================================================================================================//
//=================================================================================================//

	if ($opt == 'list_albums'){

		echo '<h3>Фотоальбомы</h3>';

		//TABLE COLUMNS
		$fields = array();

		$fields[0]['title'] = 'id'; $fields[0]['field'] = 'id'; $fields[0]['width'] = '30';

		$fields[1]['title'] = 'Название'; $fields[1]['field'] = 'title'; $fields[1]['width'] = '';
		$fields[1]['link'] = '?view=components&do=config&id='.$id.'&opt=edit_album&item_id=%id%';

		$fields[2]['title'] = 'Комментарии?'; $fields[2]['field'] = 'is_comments'; $fields[2]['width'] = '95';
		$fields[2]['prc'] = 'viewAct';

		$fields[3]['title'] = 'Добавление пользователями'; $fields[3]['field'] = 'public'; $fields[3]['width'] = '100';
		$fields[3]['prc'] = 'viewAct';

		$fields[10]['title'] = 'Показ'; $fields[10]['field'] = 'published'; $fields[10]['width'] = '60';
		$fields[10]['do'] = 'opt'; $fields[10]['do_suffix'] = '_album'; //Чтобы вместо 'do=hide&id=1' было 'opt=hide_album&item_id=1'

		//ACTIONS
		$actions = array();
		$actions[0]['title'] = 'Посмотреть на сайте';
		$actions[0]['icon']  = 'search.gif';
		$actions[0]['link']  = '/photos/%id%';

		$actions[1]['title'] = 'Редактировать';
		$actions[1]['icon']  = 'edit.gif';
		$actions[1]['link']  = '?view=components&do=config&id='.$id.'&opt=edit_album&item_id=%id%';

		$actions[2]['title'] = 'Удалить';
		$actions[2]['icon']  = 'delete.gif';
		$actions[2]['confirm'] = 'Вместе с альбомом будут удалены все фотографии. Удалить фотоальбом?';
		$actions[2]['link']  = '?view=components&do=config&id='.$id.'&opt=delete_album&item_id=%id%';

		//Print table
		cpListTable('cms_photo_albums', $fields, $actions, 'parent_id>0 AND NSDiffer=""', 'NSLeft');

	}

//=================================================================================================//
//=================================================================================================//

	if ($opt == 'add_album' || $opt == 'edit_album'){
		if ($opt=='add_album'){
			 cpAddPathway('Фотоальбомы', '?view=components&do=config&id='.$id.'&opt=list_albums');
			 cpAddPathway('Добавить фотоальбом', '?view=components&do=config&id='.$id.'&opt=add_album');
			 echo '<h3>Добавить фотоальбом</h3>';
		} else {

            $item_id = cmsCore::request('item_id', 'int');

			$mod = $inDB->getNsCategory('cms_photo_albums', $item_id);

			 cpAddPathway('Фотоальбомы', '?view=components&do=config&id='.$id.'&opt=list_albums');
			 cpAddPathway('Редактировать фотоальбом', '?view=components&do=config&id='.$id.'&opt=add_album');
			 echo '<h3>Редактировать фотоальбом "'.$mod['title'].'"</h3>';

		}

	   //DEFAULT VALUES
	   if (!isset($mod['thumb1'])) { $mod['thumb1'] = 96; }
	   if (!isset($mod['thumb2'])) { $mod['thumb2'] = 450; }
	   if (!isset($mod['thumbsqr'])) { $mod['thumbsqr'] = 1; }
	   if (!isset($mod['is_comments'])) { $mod['is_comments'] = 0; }
	   if (!isset($mod['maxcols'])) { $mod['maxcols'] = 4; }
	   if (!isset($mod['showtype'])) { $mod['showtype'] = 'lightbox'; }
	   if (!isset($mod['perpage'])) { $mod['perpage'] = '20'; }
	   if (!isset($mod['uplimit'])) { $mod['uplimit'] = 20; }
	   if (!isset($mod['published'])) { $mod['published'] = 1; }
	   if (!isset($mod['orderby'])) { $mod['orderby'] = 'pubdate'; }

		?>
		<script type="text/javascript">
        function showMapMarker(){
            var file = $('select[name=iconurl]').val();
            $('img#marker_demo').attr('src', '/images/photos/small/'+file);
        }
        </script>

        <form id="addform" name="addform" method="post" action="index.php?view=components&do=config&id=<?php echo $id;?>">
        <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
        <table width="610" border="0" cellspacing="5" class="proptable">
            <tr>
                <td width="300">Название альбома:</td>
                <td><input name="title" type="text" id="title" size="30" value="<?php echo htmlspecialchars($mod['title']); ?>"/></td>
            </tr>
            <tr>
                <td valign="top">Родительский альбом:</td>
                <td valign="top">
                    <?php $rootid = $inDB->get_field('cms_photo_albums', "parent_id=0 AND NSDiffer=''", 'id'); ?>
                    <select name="parent_id" size="8" id="parent_id" style="width:285px">
                        <option value="<?php echo $rootid; ?>" <?php if (@$mod['parent_id']==$rootid || !isset($mod['parent_id'])) { echo 'selected'; }?>>-- Корневой альбом --</option>
                        <?php
                            if (isset($mod['parent_id'])){
                                echo $inCore->getListItemsNS('cms_photo_albums', $mod['parent_id']);
                            } else {
                                echo $inCore->getListItemsNS('cms_photo_albums');
                            }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Публиковать альбом?</td>
                    <td>
                        <label><input name="published" type="radio" value="1" <?php if (@$mod['published']) { echo 'checked="checked"'; } ?> /> Да</label>
                        <label><input name="published" type="radio" value="0"  <?php if (@!$mod['published']) { echo 'checked="checked"'; } ?> /> Нет</label>
                    </td>
            </tr>
            <tr>
                <td>Показывать даты и комментарии фото в списке альбома?</td>
                    <td>
                        <label><input name="showdate" type="radio" value="1" checked="checked" <?php if (@$mod['showdate']) { echo 'checked="checked"'; } ?> /> Да</label>
                        <label><input name="showdate" type="radio" value="0"  <?php if (@!$mod['showdate']) { echo 'checked="checked"'; } ?> /> Нет</label>
                    </td>
            </tr>
            <tr>
                <td valign="top">Показывать теги фото:</td>
                <td valign="top">
                    <label><input name="showtags" type="radio" value="1" checked="checked" <?php if (@$mod['showtags']) { echo 'checked="checked"'; } ?> /> Да</label>
                    <label><input name="showtags" type="radio" value="0"  <?php if (@!$mod['showtags']) { echo 'checked="checked"'; } ?> /> Нет</label>
                </td>
            </tr>
            <tr>
                <td valign="top">Показывать код для вставки на форум:</td>
                <td valign="top">
                    <label><input name="bbcode" type="radio" value="1" checked="checked" <?php if (@$mod['bbcode']) { echo 'checked="checked"'; } ?> /> Да</label>
                    <label><input name="bbcode" type="radio" value="0"  <?php if (@!$mod['bbcode']) { echo 'checked="checked"'; } ?> /> Нет</label>
                </td>
            </tr>
            <tr>
                <td valign="top">Комментарии для альбома:</td>
                <td valign="top">
                    <label><input name="is_comments" type="radio" value="1" checked="checked" <?php if (@$mod['is_comments']) { echo 'checked="checked"'; } ?> /> Да</label>
                    <label><input name="is_comments" type="radio" value="0"  <?php if (@!$mod['is_comments']) { echo 'checked="checked"'; } ?> /> Нет</label>
                </td>
            </tr>
            <tr>
                <td>Сортировать фото:</td>
                <td>
                    <select name="orderby" id="orderby" style="width:285px">
                        <option value="title" <?php if(@$mod['orderby']=='title') { echo 'selected'; } ?>>По алфавиту</option>
                        <option value="pubdate" <?php if(@$mod['orderby']=='pubdate') { echo 'selected'; } ?>>По дате</option>
                        <option value="rating" <?php if(@$mod['orderby']=='rating') { echo 'selected'; } ?>>По рейтингу</option>
                        <option value="hits" <?php if(@$mod['orderby']=='hits') { echo 'selected'; } ?>>По просмотрам</option>
                    </select>
                    <select name="orderto" id="orderto" style="width:285px">
                        <option value="desc" <?php if(@$mod['orderto']=='desc') { echo 'selected'; } ?>>по убыванию</option>
                        <option value="asc" <?php if(@$mod['orderto']=='asc') { echo 'selected'; } ?>>по возрастанию</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Вывод фотографий:</td>
                <td>
                    <select name="showtype" id="showtype" style="width:285px">
                        <option value="thumb" <?php if(@$mod['showtype']=='thumb') { echo 'selected'; } ?>>Галерея</option>
                        <option value="lightbox" <?php if(@$mod['showtype']=='lightbox') { echo 'selected'; } ?>>Галерея (лайтбокс)</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Число колонок для вывода:</td>
                <td>
                    <input name="maxcols" type="text" id="maxcols" size="5" value="<?php echo @$mod['maxcols'];?>"/> шт.
                </td>
            </tr>
            <tr>
                <td>Добавление фото пользователями:</td>
                <td>
                    <select name="public" id="select" style="width:285px">
                        <option value="0" <?php if(@$mod['public']=='0') { echo 'selected'; } ?>>Запрещено</option>
                        <option value="1" <?php if(@$mod['public']=='1') { echo 'selected'; } ?>>Разрешено с премодерацией</option>
                        <option value="2" <?php if(@$mod['public']=='2') { echo 'selected'; } ?>>Разрешено без модерации</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td>Макс. загрузок от одного пользователя в сутки:</td>
                <td>
                    <input name="uplimit" type="text" id="uplimit" size="5" value="<?php echo @$mod['uplimit'];?>"/> шт.
                </td>
            </tr>
            <tr>
                <td>Форма сортировки:</td>
                <td>
                    <label><input name="orderform" type="radio" value="1" checked="checked" <?php if (@$mod['orderform']) { echo 'checked="checked"'; } ?> /> Показать</label>
                    <label><input name="orderform" type="radio" value="0"  <?php if (@!$mod['orderform']) { echo 'checked="checked"'; } ?> /> Скрыть</label>
                </td>
            </tr>
            <tr>
                <td>Навигация в альбоме:</td>
                <td>
                    <label><input name="nav" type="radio" value="1" <?php if (@$mod['nav']) { echo 'checked="checked"'; } ?> /> Включена</label>
                    <label><input name="nav" type="radio" value="0"  <?php if (@!$mod['nav']) { echo 'checked="checked"'; } ?> /> Выключена</label>
                </td>
            </tr>
            <tr>
                <td>CSS-префикс фотографий:</td>
                <td><input name="cssprefix" type="text" id="cssprefix" size="10" value="<?php echo @$mod['cssprefix'];?>"/></td>
            </tr>
            <tr>
                <td>Фотографий на странице:</td>
                <td>
                    <input name="perpage" type="text" id="perpage" size="5" value="<?php echo @$mod['perpage'];?>"/> шт.</td>
            </tr>
            <tr>
                <td>Ширина маленькой копии: </td>
                <td>
                    <table border="0" cellspacing="0" cellpadding="1">
                        <tr>
                            <td width="100" valign="middle">
                                <input name="thumb1" type="text" id="thumb1" size="3" value="<?php echo @$mod['thumb1'];?>"/> пикс.
                            </td>
                            <td width="100" align="center" valign="middle" style="background-color:#EBEBEB">Квадратные:</td>
                            <td width="115" align="center" valign="middle" style="background-color:#EBEBEB">
                                <input name="thumbsqr" type="radio" value="1" checked="checked" <?php if (@$mod['thumbsqr']) { echo 'checked="checked"'; } ?> /> Да
                                <input name="thumbsqr" type="radio" value="0"  <?php if (@!$mod['thumbsqr']) { echo 'checked="checked"'; } ?> />Нет
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td>Ширина средней копии: </td>
                <td>
                    <input name="thumb2" type="text" id="thumb2" size="3" value="<?php echo @$mod['thumb2'];?>"/> пикс.
                </td>
            </tr>
            <?php
                if ($opt=='edit_album'){ ?>
            <tr>
                <td valign="top">Мини-эскиз:<br />
                <?php if (@$mod['iconurl']){ ?>
                <img id="marker_demo" src="/images/photos/small/<?php echo $mod['iconurl']; ?>" border="0">
                <?php  } else { ?>
                <img id="marker_demo" src="/images/photos/no_image.png" border="0">
                <?php  } ?>
                </td>
                <td valign="top">
                <?php if ($inDB->rows_count('cms_photo_files', 'album_id = '.$item_id.'')) { ?>
                        <select name="iconurl" id="iconurl" style="width:285px" onchange="showMapMarker()">
                            <?php
                                if ($mod['iconurl']){
                                    echo $inCore->getListItems('cms_photo_files', $mod['iconurl'], 'id', 'ASC', 'album_id = '.$item_id.' AND published = 1', 'file');
                                } else {
                                    echo '<option value="" selected>Выберите мини-эскиз</option>';
                                    echo $inCore->getListItems('cms_photo_files', 0, 'id', 'ASC', 'album_id = '.$item_id.' AND published = 1', 'file');
                                }
                            ?>
                        </select>
                   <?php  } else { ?>
                        В альбоме нет еще фотографий, загрузите фотографии в альбом, после выберите мини-эскиз.
                   <?php  } ?>
                </td>
            </tr>
        <?php
            }
        ?>
        </table>
        <table width="100%" border="0">
            <tr>
                <div style="margin:5px 0px 5px 0px">Описание альбома:</div>
                <textarea name="description" style="width:580px" rows="4"><?php echo @$mod['description']?></textarea>
            </tr>
        </table>

        <p>
            <input name="opt" type="hidden" id="opt" <?php if ($opt=='add_album') { echo 'value="submit_album"'; } else { echo 'value="update_album"'; } ?> />
            <input name="add_mod" type="submit" id="add_mod" <?php if ($opt=='add_album') { echo 'value="Создать альбом"'; } else { echo 'value="Сохранить альбом"'; } ?> />
            <input name="back2" type="button" id="back2" value="Отмена" onclick="window.location.href='index.php?view=components&do=config&id=<?php echo $id; ?>';"/>
            <?php
                if ($opt=='edit_album'){
                    echo '<input name="item_id" type="hidden" value="'.$mod['id'].'" />';
                }
            ?>
        </p>
    </form>
		<?php
	}

//=================================================================================================//
//=================================================================================================//

?>