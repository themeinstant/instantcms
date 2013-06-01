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
	$inDB 	= cmsDatabase::getInstance();
	$rootid = $inDB->get_field('cms_menu', 'parent_id=0', 'id');

	$ns     = $inCore->nestedSetsInit('cms_menu');
	$myid   = $ns->AddNode($rootid);

    $link   = $inCore->getMenuLink('content', $id, $myid);

	$sql = "UPDATE cms_menu
			SET menu='$menu',
				title='$title',
				link='$link',
				linktype='content',
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

function applet_content(){

    $inCore = cmsCore::getInstance();
    $inUser = cmsUser::getInstance();
	$inDB 	= cmsDatabase::getInstance();

	//check access
	global $adminAccess;
	if (!cmsUser::isAdminCan('admin/content', $adminAccess)) { cpAccessDenied(); }

    $cfg = $inCore->loadComponentConfig('content');

    cmsCore::loadModel('content');
    $model = new cms_model_content();

    $GLOBALS['cp_page_title'] = 'Статьи сайта';
    cpAddPathway('Статьи сайта', 'index.php?view=tree');

	$GLOBALS['cp_page_head'][] = '<script language="JavaScript" type="text/javascript" src="js/content.js"></script>';

	$do = cmsCore::request('do', 'str', 'add');
	$id = cmsCore::request('id', 'int', -1);

	if ($do == 'arhive_on'){
		$inDB->query("UPDATE cms_content SET is_arhive = 1 WHERE id = '$id'");
		cmsCore::addSessionMessage('Статья успешно перенесена в архив', 'success');
		cmsCore::redirectBack();
	}

	if ($do == 'move'){

        $item_id = cmsCore::request('id', 'int', 0);
        $cat_id  = cmsCore::request('cat_id', 'int', 0);

        $dir     = $_REQUEST['dir'];
        $step    = 1;

        $model->moveItem($item_id, $cat_id, $dir, $step);
        echo '1'; exit;

	}

    if ($do == 'move_to_cat'){

        $items      = cmsCore::request('item', 'array_int');
        $to_cat_id  = cmsCore::request('obj_id', 'int', 0);

        if ($items && $to_cat_id){

			$last_ordering = (int)$inDB->get_field('cms_content', "category_id = '{$to_cat_id}' ORDER BY ordering DESC", 'ordering');

			$ids = rtrim(implode(',', $items), ',');
			$inDB->query("UPDATE cms_content SET category_id = '{$to_cat_id}' WHERE id IN ({$ids})");

			foreach($items as $item_id){
				$article = $model->getArticle($item_id);
				if(!$article) { continue; }
				$last_ordering++;

                $model->updateArticle($article['id'], array('seolink'=>$seolink,
                                                            'category_id'=>$to_cat_id,
                                                            'ordering'=>$last_ordering,
                                                            'url'=>$article['url'],
                                                            'title'=>$inDB->escape_string($article['title']),
                                                            'id'=>$article['id'],
                                                            'user_id'=>$article['user_id']));

			}

			cmsCore::addSessionMessage('Статьи успешно перенесены', 'success');

        }

        cmsCore::redirect('?view=tree&cat_id='.$to_cat_id);

    }

	if ($do == 'show'){
		if (!isset($_REQUEST['item'])){
			if ($id >= 0){ dbShow('cms_content', $id);  }
			echo '1'; exit;
		} else {
			dbShowList('cms_content', cmsCore::request('item', 'array_int'));
			cmsCore::redirectBack();
		}

	}

	if ($do == 'hide'){
		if (!isset($_REQUEST['item'])){
			if ($id >= 0){ dbHide('cms_content', $id);  }
			echo '1'; exit;
		} else {
			dbHideList('cms_content', cmsCore::request('item', 'array_int'));
			cmsCore::redirectBack();
		}
	}

	if ($do == 'delete'){

		if (!isset($_REQUEST['item'])){
			if ($id >= 0){
				$model->deleteArticle($id);
				cmsCore::addSessionMessage('Статья успешно удалена', 'success');
			}
		} else {
			$model->deleteArticles(cmsCore::request('item', 'array_int'));
			cmsCore::addSessionMessage('Статьи успешно удалены', 'success');
		}
		cmsCore::redirectBack();
	}

	if ($do == 'update'){
        if (!cmsCore::validateForm()) { cmsCore::error404(); }
		if(isset($_REQUEST['id'])) {

			$id                     = cmsCore::request('id', 'int', 0);
			$article['category_id'] = cmsCore::request('category_id', 'int', 1);
			$article['title']       = cmsCore::request('title', 'str');
			$article['url']         = cmsCore::request('url', 'str');
			$article['showtitle']   = cmsCore::request('showtitle', 'int', 0);
			$article['description'] = cmsCore::request('description', 'html', '');
			$article['description'] = $inDB->escape_string($article['description']);
			$article['content']     = cmsCore::request('content', 'html', '');
			$article['content']    	= $inDB->escape_string($article['content']);
			$article['published']   = cmsCore::request('published', 'int', 0);

			$article['showdate']    = cmsCore::request('showdate', 'int', 0);
			$article['showlatest']  = cmsCore::request('showlatest', 'int', 0);
			$article['showpath']    = cmsCore::request('showpath', 'int', 0);
			$article['comments']    = cmsCore::request('comments', 'int', 0);
			$article['canrate']     = cmsCore::request('canrate', 'int', 0);

			$article['enddate']     = cmsCore::request('enddate', 'str', '');
			$article['is_end']      = cmsCore::request('is_end', 'int', 0);
            $article['pagetitle']   = cmsCore::request('pagetitle', 'str', '');

			$article['tags']        = cmsCore::request('tags', 'str');

            $olddate                = cmsCore::request('olddate', 'str', '');
			$pubdate                = cmsCore::request('pubdate', 'str', '');

            $article['user_id']     = cmsCore::request('user_id', 'int', $inUser->id);

			$article['tpl'] 		= cmsCore::request('tpl', 'str', 'com_content_read.tpl');

            $date = explode('.', $pubdate);
            $article['pubdate'] = $date[2] . '-' . $date[1] . '-' . $date[0] . ' ' .date('H:i');

            $autokeys               = cmsCore::request('autokeys', 'int');

            switch($autokeys){
                case 1: $article['meta_keys'] = $inCore->getKeywords($article['content']);
                        $article['meta_desc'] = $article['title'];
                        break;

                case 2: $article['meta_desc'] = strip_tags($article['description']);
                        $article['meta_keys'] = $article['tags'];
                        break;

                case 3: $article['meta_desc'] = cmsCore::request('meta_desc', 'str');
                        $article['meta_keys'] = cmsCore::request('meta_keys', 'str');
                        break;
            }

			$model->updateArticle($id, $article);

			if (!cmsCore::request('is_public', 'int', 0)){
				$showfor = $_REQUEST['showfor'];
				cmsCore::setAccess($id, $showfor, 'material');
			} else {
				cmsCore::clearAccess($id, 'material');
            }


            $file = 'article'.$id.'.jpg';

            if (cmsCore::request('delete_image', 'int', 0)){
                @unlink(PATH."/images/photos/small/$file");
                @unlink(PATH."/images/photos/medium/$file");
            } else {

				// Загружаем класс загрузки фото
				cmsCore::loadClass('upload_photo');
				$inUploadPhoto = cmsUploadPhoto::getInstance();
				// Выставляем конфигурационные параметры
				$inUploadPhoto->upload_dir    = PATH.'/images/photos/';
				$inUploadPhoto->small_size_w  = $model->config['img_small_w'];
				$inUploadPhoto->medium_size_w = $model->config['img_big_w'];
				$inUploadPhoto->thumbsqr      = $model->config['img_sqr'];
				$inUploadPhoto->is_watermark  = $model->config['watermark'];
				$inUploadPhoto->input_name    = 'picture';
				$inUploadPhoto->filename      = $file;
				// Процесс загрузки фото
				$inUploadPhoto->uploadPhoto();

            }
			cmsCore::addSessionMessage('Статья успешно сохранена', 'success');

            cmsUser::clearCsrfToken();

			if (!isset($_SESSION['editlist']) || @sizeof($_SESSION['editlist'])==0){
				cmsCore::redirect('?view=tree&cat_id='.$article['category_id']);
			} else {
				cmsCore::redirect('?view=content&do=edit');
			}
		}
	}

	if ($do == 'submit'){
        if (!cmsCore::validateForm()) { cmsCore::error404(); }
        $article['category_id'] = cmsCore::request('category_id', 'int', 1);
        $article['title']       = cmsCore::request('title', 'str');
        $article['url']         = cmsCore::request('url', 'str');
        $article['showtitle']   = cmsCore::request('showtitle', 'int', 0);
		$article['description'] = cmsCore::request('description', 'html', '');
		$article['description'] = $inDB->escape_string($article['description']);
		$article['content']     = cmsCore::request('content', 'html', '');
		$article['content']    	= $inDB->escape_string($article['content']);

        $article['published']   = cmsCore::request('published', 'int', 0);

        $article['showdate']    = cmsCore::request('showdate', 'int', 0);
        $article['showlatest']  = cmsCore::request('showlatest', 'int', 0);
        $article['showpath']    = cmsCore::request('showpath', 'int', 0);
        $article['comments']    = cmsCore::request('comments', 'int', 0);
        $article['canrate']     = cmsCore::request('canrate', 'int', 0);

        $article['enddate']     = $_REQUEST['enddate'];
        $article['is_end']      = cmsCore::request('is_end', 'int', 0);
        $article['pagetitle']   = cmsCore::request('pagetitle', 'str', '');

        $article['tags']        = cmsCore::request('tags', 'str');

        $article['pubdate']     = $_REQUEST['pubdate'];
        $date                   = explode('.', $article['pubdate']);
		$article['pubdate']     = $date[2] . '-' . $date[1] . '-' . $date[0] . ' ' .date('H:i');

		$article['user_id']     = cmsCore::request('user_id', 'int', $inUser->id);

		$article['tpl'] 		= cmsCore::request('tpl', 'str', 'com_content_read.tpl');

        $autokeys               = cmsCore::request('autokeys', 'int');

        switch($autokeys){
            case 1: $article['meta_keys'] = $inCore->getKeywords($article['content']);
                    $article['meta_desc'] = $article['title'];
                    break;

            case 2: $article['meta_desc'] = strip_tags($article['description']);
                    $article['meta_keys'] = $article['tags'];
                    break;

            case 3: $article['meta_desc'] = cmsCore::request('meta_desc', 'str');
                    $article['meta_keys'] = cmsCore::request('meta_keys', 'str');
                    break;
        }

        $article['id'] = $model->addArticle($article);

		if (!cmsCore::request('is_public', 'int', 0)){
			$showfor = $_REQUEST['showfor'];
			if (sizeof($showfor)>0  && !cmsCore::request('is_public', 'int', 0)){
				cmsCore::setAccess($article['id'], $showfor, 'material');
            }
		}

        $inmenu = cmsCore::request('createmenu', 'str', '');

		if ($inmenu){
			createMenuItem($inmenu, $article['id'], $article['title']);
		}

		// Загружаем класс загрузки фото
		cmsCore::loadClass('upload_photo');
		$inUploadPhoto = cmsUploadPhoto::getInstance();
		// Выставляем конфигурационные параметры
		$inUploadPhoto->upload_dir    = PATH.'/images/photos/';
		$inUploadPhoto->small_size_w  = $model->config['img_small_w'];
		$inUploadPhoto->medium_size_w = $model->config['img_big_w'];
		$inUploadPhoto->thumbsqr      = $model->config['img_sqr'];
		$inUploadPhoto->is_watermark  = $model->config['watermark'];
		$inUploadPhoto->input_name    = 'picture';
		$inUploadPhoto->filename      = 'article'.$article['id'].'.jpg';
		// Процесс загрузки фото
		$inUploadPhoto->uploadPhoto();

		cmsCore::addSessionMessage('Статья успешно добавлена', 'success');

        cmsUser::clearCsrfToken();

		cmsCore::redirect('?view=tree&cat_id='.$article['category_id']);

	}

   if ($do == 'add' || $do == 'edit'){

	   	require('../includes/jwtabs.php');
		$GLOBALS['cp_page_head'][] = jwHeader();

 		$toolmenu = array();
		$toolmenu[0]['icon'] = 'save.gif';
		$toolmenu[0]['title'] = 'Сохранить';
		$toolmenu[0]['link'] = 'javascript:document.addform.submit();';

		$toolmenu[1]['icon'] = 'cancel.gif';
		$toolmenu[1]['title'] = 'Отмена';
		$toolmenu[1]['link'] = 'javascript:history.go(-1);';

		cpToolMenu($toolmenu);

		if ($do=='add'){
			 echo '<h3>Добавить статью</h3>';
 	 		 cpAddPathway('Добавить статью', 'index.php?view=content&do=add');
			 $mod['category_id'] = (int)$_REQUEST['to'];
			 $mod['showpath'] = 1;
			 $mod['tpl'] = 'com_content_read.tpl';
		} else {
			if (isset($_REQUEST['item'])){
				$_SESSION['editlist'] = $_REQUEST['item'];
			}

			 $ostatok = '';

			 if (isset($_SESSION['editlist'])){
				$id = array_shift($_SESSION['editlist']);
				if (sizeof($_SESSION['editlist'])==0) { unset($_SESSION['editlist']); } else
				{ $ostatok = '(На очереди: '.sizeof($_SESSION['editlist']).')'; }
			 } else { $id = (int)$_REQUEST['id']; }

			 $sql = "SELECT *, (TO_DAYS(enddate) - TO_DAYS(CURDATE())) as daysleft, DATE_FORMAT(pubdate, '%d.%m.%Y') as pubdate
					 FROM cms_content
					 WHERE id = $id LIMIT 1";
			 $result = dbQuery($sql) ;
			 if (mysql_num_rows($result)){
				$mod = mysql_fetch_assoc($result);
			 }

			 echo '<h3>Редактировать статью '.$ostatok.'</h3>';
			 cpAddPathway($mod['title'], 'index.php?view=content&do=edit&id='.$mod['id']);
		}
	?>
    <form id="addform" name="addform" method="post" action="index.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
        <input type="hidden" name="view" value="content" />

        <table class="proptable" width="100%" cellpadding="15" cellspacing="2">
            <tr>

                <!-- главная ячейка -->
                <td valign="top">

                    <table width="100%" cellpadding="0" cellspacing="4" border="0">
                        <tr>
                            <td valign="top">
                                <div><strong>Название статьи</strong></div>
                                <div>
                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tr>
                                            <td><input name="title" type="text" id="title" style="width:100%" value="<?php echo htmlspecialchars($mod['title']);?>" /></td>
                                            <td style="width:15px;padding-left:10px;padding-right:10px;">
                                                <input type="checkbox" title="Показывать заголовок" name="showtitle" <?php if ($mod['showtitle'] || $do=='add') { echo 'checked="checked"'; } ?> value="1">
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                            <td width="130" valign="top">
                                <div><strong>Дата публикации</strong></div>
                                <div>
                                    <input name="pubdate" type="text" id="pubdate" style="width:100px" <?php if(@!$mod['pubdate']) { echo 'value="'.date('Y-m-d').'"'; } else { echo 'value="'.$mod['pubdate'].'"'; } ?>/>
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
                                    <input type="hidden" name="olddate" value="<?php echo @$mod['pubdate']?>" />
                                </div>
                            </td>
                            <td width="16" valign="bottom" style="padding-bottom:10px">
                                <input type="checkbox" name="showdate" id="showdate" title="Показывать дату и автора" value="1" <?php if ($mod['showdate'] || $do=='add') { echo 'checked="checked"'; } ?>/>
                            </td>
                            <td width="160" valign="top">
                                <div><strong>Шаблон статьи</strong></div>
                                <div><input name="tpl" type="text" style="width:160px" value="<?php echo @$mod['tpl'];?>"></div>
                            </td>

                        </tr>
                    </table>

                    <div><strong>Анонс статьи (не обязательно)</strong></div>
                    <div><?php $inCore->insertEditor('description', $mod['description'], '200', '100%'); ?></div>

                    <div><strong>Полный текст статьи</strong></div>
                    <?php insertPanel(); ?>
                    <div><?php $inCore->insertEditor('content', $mod['content'], '400', '100%'); ?></div>

                    <div><strong>Теги статьи</strong></div>
                    <div><input name="tags" type="text" id="tags" style="width:99%" value="<?php if (isset($mod['id'])) { echo cmsTagLine('content', $mod['id'], false); } ?>" /></div>

                    <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist">
                        <tr>
                            <td width="20">
                                <input type="radio" name="autokeys" id="autokeys1" <?php if ($do=='add' && $cfg['autokeys']){ ?>checked="checked"<?php } ?> value="1"/>
                            </td>
                            <td>
                                <label for="autokeys1"><strong>Автоматически сгенерировать ключевые слова и описание</strong></label>
                            </td>
                        </tr>
                        <tr>
                            <td width="20">
                                <input type="radio" name="autokeys" id="autokeys2" value="2"/>
                            </td>
                            <td>
                                <label for="autokeys2"><strong>Использовать теги и анонс как ключевые слова и описание</strong></label>
                            </td>
                        </tr>
                        <tr>
                            <td width="20">
                                <input type="radio" name="autokeys" id="autokeys3" value="3" <?php if ($do=='edit' || !$cfg['autokeys']){ ?>checked="checked"<?php } ?>/>
                            </td>
                            <td>
                                <label for="autokeys3"><strong>Заполнить ключевые слова и описание вручную</strong></label>
                            </td>
                        </tr>

                        <?php if ($cfg['af_on'] && $do=='add') { ?>
                        <tr>
                            <td width="20"><input type="checkbox" name="noforum" id="noforum" value="1" /> </td>
                            <td><label for="noforum"><strong>Не создавать тему на форуме для обсуждения статьи</strong></label></td>
                        </tr>
                        <?php } ?>
                    </table>

                </td>

                <!-- боковая ячейка -->
                <td width="300" valign="top" style="background:#ECECEC;">

                    <?php ob_start(); ?>

                    {tab=Публикация}

                    <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist">
                        <tr>
                            <td width="20"><input type="checkbox" name="published" id="published" value="1" <?php if ($mod['published'] || $do=='add') { echo 'checked="checked"'; } ?>/></td>
                            <td><label for="published"><strong>Публиковать статью</strong></label></td>
                        </tr>
                    </table>

                    <div style="margin-top:7px">
                        <select name="category_id" size="10" id="category_id" style="width:99%;height:200px">
                            <option value="1" <?php if (@$mod['category_id']==1 || !isset($mod['category_id'])) { echo 'selected="selected"'; }?>>-- Корневой раздел --</option>
                            <?php
                                if (isset($mod['category_id'])){
                                    echo $inCore->getListItemsNS('cms_category', $mod['category_id']);
                                } else {
                                    echo $inCore->getListItemsNS('cms_category');
                                }
                            ?>
                        </select>
                    </div>

                    <div style="margin-bottom:10px">
                        <select name="showpath" id="showpath" style="width:99%">
                            <option value="0" <?php if (@!$mod['showpath']) { echo 'selected="selected"'; } ?>>Глубиномер: Только название</option>
                            <option value="1" <?php if (@$mod['showpath']) { echo 'selected="selected"'; } ?>>Глубиномер: Полный путь</option>
                        </select>
                    </div>

                    <div style="margin-top:15px">
                        <strong>URL страницы</strong><br/>
                        <div style="color:gray">Если не указан, генерируется из заголовка</div>
                    </div>
                    <div>
                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td><input type="text" name="url" value="<?php echo $mod['url']; ?>" style="width:100%"/></td>
                                <td width="40" align="center">.html</td>
                            </tr>
                        </table>
                    </div>

                    <div style="margin-top:10px">
                        <strong>Автор статьи</strong>
                    </div>
                    <div>
                        <select name="user_id" id="user_id" style="width:99%">
                          <?php
                              if (isset($mod['user_id'])) {
                                    echo $inCore->getListItems('cms_users', $mod['user_id'], 'nickname', 'ASC', 'is_deleted=0 AND is_locked=0', 'id', 'nickname');
                              } else {
                                    echo $inCore->getListItems('cms_users', $inUser->id, 'nickname', 'ASC', 'is_deleted=0 AND is_locked=0', 'id', 'nickname');
                              }
                          ?>
                        </select>
                    </div>

                    <div style="margin-top:12px"><strong>Фотография</strong></div>
                    <div style="margin-bottom:10px">
                        <?php
                            if ($do=='edit'){
                                if (file_exists(PATH.'/images/photos/small/article'.$mod['id'].'.jpg')){
                        ?>
                        <div style="margin-top:3px;margin-bottom:3px;padding:10px;border:solid 1px gray;text-align:center">
                            <img src="/images/photos/small/article<?php echo $id; ?>.jpg" border="0" />
                        </div>
                        <table cellpadding="0" cellspacing="0" border="0">
                            <tr>
                                <td width="16"><input type="checkbox" id="delete_image" name="delete_image" value="1" /></td>
                                <td><label for="delete_image">Удалить фотографию</label></td>
                            </tr>
                        </table>
                        <?php
                                }
                            }
                        ?>
                        <input type="file" name="picture" style="width:100%" />
                    </div>

                    <div style="margin-top:25px"><strong>Параметры публикации</strong></div>
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" class="checklist">
                        <tr>
                            <td width="20"><input type="checkbox" name="showlatest" id="showlatest" value="1" <?php if ($mod['showlatest'] || $do=='add') { echo 'checked="checked"'; } ?>/></td>
                            <td><label for="showlatest">Показывать в "новых статьях"</label></td>
                        </tr>
                        <tr>
                            <td width="20"><input type="checkbox" name="comments" id="comments" value="1" <?php if ($mod['comments'] || $do=='add') { echo 'checked="checked"'; } ?>/></td>
                            <td><label for="comments">Разрешить комментарии</label></td>
                        </tr>
                        <tr>
                            <td width="20"><input type="checkbox" name="canrate" id="canrate" value="1" <?php if ($mod['canrate']) { echo 'checked="checked"'; } ?>/></td>
                            <td><label for="canrate">Разрешить рейтинг</label></td>
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

                    {tab=Сроки}

                    <div style="margin-top:5px">
                        <strong>Срок показа статьи</strong>
                    </div>
                    <div>
                        <select name="is_end" id="is_end" style="width:99%">
                            <option value="0" <?php if (@!$mod['is_end']) { echo 'selected="selected"'; } ?>>Не ограничен</option>
                            <option value="1" <?php if (@$mod['is_end']) { echo 'selected="selected"'; } ?>>По дату окончания</option>
                        </select>
                    </div>

                    <div style="margin-top:20px">
                        <strong>Дата окончания:</strong><br/>
                        <span class="hinttext">В формате ГГГГ-ММ-ДД</span>
                    </div>
                    <div><input name="enddate" type="text" style="width:99%" <?php if(@!$mod['is_end']) { echo 'value="'.date('Y-m-d').'"'; } else { echo 'value="'.$mod['enddate'].'"'; } ?>id="enddate" /></div>


                    {tab=SEO}

                    <div style="margin-top:5px">
                        <strong>Заголовок страницы</strong><br/>
                        <span class="hinttext">Если не указан, будет совпадать с названием</span>
                    </div>
                    <div>
                        <input name="pagetitle" type="text" id="pagetitle" style="width:99%" value="<?php if (isset($mod['pagetitle'])) { echo htmlspecialchars($mod['pagetitle']); } ?>" />
                    </div>

                    <div style="margin-top:20px">
                        <strong>Ключевые слова</strong><br/>
                        <span class="hinttext">Через запятую, 10-15 слов</span>
                    </div>
                    <div>
                         <textarea name="meta_keys" style="width:97%" rows="2" id="meta_keys"><?php echo htmlspecialchars($mod['meta_keys']);?></textarea>
                    </div>

                    <div style="margin-top:20px">
                        <strong>Описание</strong><br/>
                        <span class="hinttext">Не более 250 символов</span>
                    </div>
                    <div>
                         <textarea name="meta_desc" style="width:97%" rows="4" id="meta_desc"><?php echo htmlspecialchars($mod['meta_desc']);?></textarea>
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

                                        $sql2 = "SELECT * FROM cms_content_access WHERE content_id = ".$mod['id']." AND content_type = 'material'";
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
                                <input name="is_public" type="checkbox" id="is_public" onclick="checkGroupList()" value="1" <?php echo $public?> />
                            </td>
                            <td><label for="is_public"><strong>Общий доступ</strong></label></td>
                        </tr>
                    </table>
                    <div style="padding:5px">
                        <span class="hinttext">
                            Если отмечено, материал виден всем посетителям. Снимите галочку, чтобы вручную выбрать разрешенные группы пользователей.
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
            <input name="add_mod" type="submit" id="add_mod" <?php if ($do=='add') { echo 'value="Создать материал"'; } else { echo 'value="Сохранить материал"'; } ?> />
            <input name="back" type="button" id="back" value="Отмена" onclick="window.history.back();"/>
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

} ?>
