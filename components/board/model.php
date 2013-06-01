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

if(!defined('VALID_CMS')) { die('ACCESS DENIED'); }

class cms_model_board{

	public $root_cat  = array();
	public $config    = array();
	public $is_can_add_by_group   = false;
	public $is_moderator_by_group = false;

/* ==================================================================================================== */
/* ==================================================================================================== */

	public function __construct(){
        $this->inDB        = cmsDatabase::getInstance();
		$this->inCore      = cmsCore::getInstance();
		$this->config      = $this->inCore->loadComponentConfig('board');
		$this->root_cat    = $this->inDB->get_fields('cms_board_cats', 'parent_id=0', '*');
		$this->category_id = cmsCore::request('category_id', 'int', $this->root_cat['id']);
		$this->item_id     = cmsCore::request('id', 'int', 0);
		$this->page        = cmsCore::request('page', 'int', 1);
		$this->city        = cmsCore::getSearchVar('city');
		$this->obtype      = cmsCore::getSearchVar('obtype');
		$this->is_can_add_by_group   = cmsUser::isUserCan('board/add');
		$this->is_moderator_by_group = cmsUser::isUserCan('board/moderate');
		cmsCore::loadClass('form');
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getCommentTarget($target, $target_id) {

        $result = array();

        switch($target){

            case 'boarditem': $item = $this->inDB->get_fields('cms_board_items', "id='{$target_id}'", 'title, obtype');
                              if (!$item) { return false; }
                              $result['link']  = '/board/read'.$target_id.'.html';
                              $result['title'] = $item['obtype'].' '.$item['title'];
                              break;

        }

        return ($result ? $result : false);

    }

/* ========================================================================== */
/* ========================================================================== */

    public static function getDefaultConfig() {

        $cfg = array(
                     'showlat'=>1,
                     'photos'=>1,
                     'maxcols'=>1,
					 'maxcols_on_home'=>1,
                     'public'=>1,
					 'home_perpage'=>15,
					 'publish_after_edit'=>0,
                     'srok'=>1,
                     'pubdays'=>14,
                     'watermark'=>0,
                     'comments'=>1,
                     'aftertime'=>'',
                     'extend'=>0,
                     'vip_enabled'=>0,
                     'vip_prolong'=>0,
                     'vip_max_days'=>30,
                     'vip_day_cost'=>5
               );

        return $cfg;

    }

// ============================================================================ //
// ============================================================================ //

    public function whereCatIs($cat_id){
        $this->inDB->where("i.category_id = '$cat_id'");
        return;
    }

    public function whereThisAndNestedCats($left_key, $right_key) {
        $this->inDB->where("cat.NSLeft >= $left_key AND cat.NSRight <= $right_key AND cat.parent_id > 0");
    }

    public function whereCityIs($city) {
        $this->inDB->where("LOWER(i.city) = '$city'");
    }

    public function whereVip($flag) {
        $this->inDB->where("i.is_vip = $flag");
    }

    public function whereTypeIs($type) {
        $this->inDB->where("LOWER(i.obtype) = '$type'");
    }

    public function whereUserIs($user_id) {
        $this->inDB->where("i.user_id = '$user_id'");
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getCategory($category_id = 0) {

		if($category_id == $this->root_cat['id']){
			$category = $this->root_cat;
			$category['perpage'] = $this->config['home_perpage'];
			$category['maxcols'] = $this->config['maxcols_on_home'];
		} else {
	        $category = $this->inDB->get_fields('cms_board_cats', "id = '{$category_id}'", '*');
		}
		if(!$category) { return false; }

		$category['perpage'] = $category['perpage'] ? $category['perpage'] : $this->config['home_perpage'];
		$category['is_can_add'] = $this->checkAdd($category);

        if (!$category['obtypes']){
            $category['obtypes'] = $this->inDB->get_field('cms_board_cats', "NSLeft <= {$category['NSLeft']} AND NSRight >= {$category['NSRight']} AND obtypes <> ''", 'obtypes');
			if(!$category['obtypes']) { $category['obtypes'] = $this->config['obtypes']; }
        }

		$category['cat_city'] = $this->getCatCity($category['id']);

		$category['ob_links'] = $this->getTypesLinks($category['id'], $category['obtypes']);

        $category = cmsCore::callEvent('GET_BOARD_CAT', $category);

        return $category;
    }

/* ==================================================================================================== */
/* ==================================================================================================== */
    /**
     * Возвращает категории
     * @param int $category_id - id категории
     * @return array
     */
    public function getSubCats($category_id){

        $cats = array();

        $sql = "SELECT *
                FROM cms_board_cats
                WHERE published = 1 AND parent_id = '$category_id'
                ORDER BY title ASC";
        $result = $this->inDB->query($sql);

        if (!$this->inDB->num_rows($result)){ return false; }

        while($cat = $this->inDB->fetch_assoc($result)){
            if (!$cat['obtypes']){
                $cat['obtypes'] = $this->inDB->get_field('cms_board_cats', "NSLeft <= {$cat['NSLeft']} AND NSRight >= {$cat['NSRight']} AND obtypes <> ''", 'obtypes');
            }
			$cat['content_count'] = $this->getAdvertsCountFromCat($cat['NSLeft'], $cat['NSRight']);
			$cat['ob_links'] = $this->getTypesLinks($cat['id'], $cat['obtypes']);
			$cat['icon'] = $cat['icon'] ? $cat['icon'] : 'folder_grey.png';
            $cats[] = $cat;
        }

        $cats = cmsCore::callEvent('GET_BOARD_SUBCATS', $cats);

        return $cats;
    }

    /**
     * Возвращает количество объвлений в категории и подкатегориях
     * @return int
     */
    public function getAdvertsCountFromCat($left_key, $right_key) {

		$sql = "SELECT i.id
				FROM cms_board_items i
				INNER JOIN cms_board_cats cat ON cat.id = i.category_id AND cat.NSLeft >= '$left_key' AND cat.NSRight <= '$right_key'
				WHERE i.published = 1";

        $result = $this->inDB->query($sql);

        return $this->inDB->num_rows($result);

    }

/* ==================================================================================================== */
/* ==================================================================================================== */
    /**
     * Возвращает элементы option для категорий, в которые разрешено добавление
     * @param int $sel - выбранныая категория
     * @param bool $is_edit - флаг редактирования
     * @return string
     */
    public function getPublicCats($sel = '', $is_edit = false) {

        $nested_sets = $this->inCore->nestedSetsInit('cms_board_cats');
        $rs_rows     = $nested_sets->SelectSubNodes($this->root_cat['id']);

        if ($rs_rows){
			$html = '';
            while($node = $this->inDB->fetch_assoc($rs_rows)){
                if($this->checkAdd($node) || ($is_edit && $sel==$node['id'])){
                    if ($sel==$node['id']){
                        $s = 'selected="selected"';
                    } else {
                        $s = '';
                    }
                    $padding = str_repeat('--', $node['NSLevel']) . ' ';
                    $html .= '<option value="'.$node['id'].'" '.$s.'>'.$padding.$node['title'].'</option>';
				}
            }
        }

        return $html;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */
	public function getAdverts($show_all = false, $is_users = false, $is_coments = false, $is_cats = false){

        $this->deleteOldRecords();
        $this->clearOldVips();

        //подготовим условия
        $pub_where = ($show_all ? '1=1' : 'i.published = 1');
        $r_join    = $is_users ? " LEFT JOIN cms_users u ON u.id = i.user_id \n" : '';
		$r_join   .= $is_cats ? " INNER JOIN cms_board_cats cat ON cat.id = i.category_id" : '';

		$r_select  = $is_users ? ', u.login, u.nickname' : '';
		$r_select .= $is_cats ? ', cat.title as cat_title, cat.obtypes' : '';

        $sql = "SELECT i.*{$r_select}

                FROM cms_board_items i
				{$r_join}
                WHERE {$pub_where}
                      {$this->inDB->where}

                {$this->inDB->group_by}

                {$this->inDB->order_by}\n";

        if ($this->inDB->limit){
            $sql .= "LIMIT {$this->inDB->limit}";
        }

		$result = $this->inDB->query($sql);

		if(!$this->inDB->num_rows($result)){ return false; }

		$records = array();

		while ($item = $this->inDB->fetch_assoc($result)){

			if($is_coments){
				$item['comments'] = cmsCore::getCommentsCount('boarditem', $item['id']);
			}
            $item['content']  = nl2br($item['content']);
			$item['content']  = $this->config['auto_link'] ? $this->inCore->parseSmiles($item['content']) : $item['content'];
			$item['title']    = $item['obtype'].' '.$item['title'];
			$item['fpubdate'] = cmsCore::dateformat($item['pubdate']);
			$item['enc_city'] = urlencode($item['city']);
            if (!$item['file'] || !file_exists(PATH.'/images/board/small/'.$item['file'])){
				$item['file'] = 'nopic.jpg';
			}
            // Права доступа
            $item['moderator'] = $this->checkAccess($item['user_id']);
			$timedifference    = strtotime("now") - strtotime($item['pubdate']);
			$item['is_overdue'] = round($timedifference / 86400) > $item['pubdays'] && $item['pubdays'] > 0;

			$records[] = $item;

		}

		$this->inDB->resetConditions();

		$records = cmsCore::callEvent('GET_BOARD_RECORDS', $records);

		return $records;

	}

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getAdvertsCount($show_all = false){

        //подготовим условия
        $pub_where = ($show_all ? '1=1' : 'i.published = 1');

        $sql = "SELECT 1

                FROM cms_board_items i

                WHERE {$pub_where}
                      {$this->inDB->where}

                {$this->inDB->group_by}\n";

		$result = $this->inDB->query($sql);

		return $this->inDB->num_rows($result);

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getRecord($item_id) {

        $this->deleteOldRecords();
        $this->clearOldVips();

        $sql = "SELECT i.*,
                       a.id as cat_id,
					   a.form_id,
                       a.NSLeft as NSLeft,
                       a.NSRight as NSRight,
                       a.title as cat_title,
                       a.title as category,
                       a.public as public,
                       a.thumb1 as thumb1,
                       a.thumb2 as thumb2,
                       a.thumbsqr as thumbsqr,
                       u.nickname as user,
                       u.login as user_login
                FROM cms_board_items i
				INNER JOIN cms_board_cats a ON a.id = i.category_id
				LEFT JOIN cms_users u ON u.id = i.user_id
                WHERE i.id = '$item_id'";

        $result = $this->inDB->query($sql);

        if (!$this->inDB->num_rows($result)){ return false; }

        $record = $this->inDB->fetch_assoc($result);

		$timedifference 	  = strtotime("now") - strtotime($record['pubdate']);
		$record['is_overdue'] = round($timedifference / 86400) > $record['pubdays'] && $record['pubdays'] > 0;
		$record['fpubdate']   = $record['pubdate'];
		$record['pubdate'] 	  = cmsCore::dateFormat($record['pubdate']);
		$record['vipdate'] 	  = cmsCore::dateFormat($record['vipdate']);
		$record['enc_city']   = urlencode($record['city']);
		$record['moderator']  = $this->checkAccess($record['user_id']);
		if (!$record['file'] || !file_exists(PATH.'/images/board/small/'.$record['file'])){
			$record['file'] = '';
		}

		if (!$record['formsdata']){
			$record['form_array'] = array();
		} else {
			$record['form_array'] = cmsCore::yamlToArray($record['formsdata']);
		}

        $record = cmsCore::callEvent('GET_BOARD_RECORD', $record);

        return $record;
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function addRecord($item){

		$inUser = cmsUser::getInstance();
        $item = cmsCore::callEvent('ADD_BOARD_RECORD', $item);

        $sql = "INSERT INTO cms_board_items (category_id, user_id, obtype, title , content, formsdata, city, pubdate, pubdays, published, file, hits, ip)
                VALUES ({$item['category_id']}, {$item['user_id']}, '{$item['obtype']}', '{$item['title']}', '{$item['content']}', '{$item['formsdata']}',
                        '{$item['city']}', NOW(), {$item['pubdays']}, {$item['published']}, '{$item['file']}', 0, INET_ATON('{$inUser->ip}'))";

        $this->inDB->query($sql);

		$item_id = $this->inDB->get_last_id('cms_board_items');

        return $item_id ? $item_id : false;
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function updateRecord($id, $item){

        $item = cmsCore::callEvent('UPDATE_BOARD_RECORD', $item);

        $this->inDB->update('cms_board_items', $item, $id);

        return true;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function deleteRecord($item_id) {

        cmsCore::callEvent('DELETE_BOARD_RECORD', $item_id);

        $item = $this->getRecord($item_id);
		if(!$item) { return false; }

        @unlink(PATH.'/images/board/'.$item['file']);
        @unlink(PATH.'/images/board/small/'.$item['file']);
        @unlink(PATH.'/images/board/medium/'.$item['file']);

        $this->inDB->delete('cms_board_items', " id = '$item_id'", 1);

		cmsCore::deleteComments('boarditem', $item_id);

		cmsActions::removeObjectLog('add_board', $item_id);

        return true;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function deleteOldRecords() {

        if ($this->config['aftertime']){
            $time_sql = '';
            switch ($this->config['aftertime']){
                case 'delete':  $time_sql = "DELETE FROM cms_board_items WHERE DATEDIFF(NOW(), pubdate) > pubdays AND pubdays > 0"; break;
                case 'hide':    $time_sql = "UPDATE cms_board_items SET published = 0 WHERE DATEDIFF(NOW(), pubdate) > pubdays AND pubdays > 0"; break;
            }
            if ($time_sql){
                $this->inDB->query($time_sql);
            }
        }

        return true;

    }

    public function clearOldVips() {

        return $this->inDB->query("UPDATE cms_board_items SET is_vip=0 WHERE DATE(vipdate) <= CURRENT_DATE");

    }

    public function deleteVip($item_id) {

        return $this->inDB->query("UPDATE cms_board_items SET is_vip=0 WHERE id = '{$item_id}'");

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function setVip($id, $days){

        // Установить статус VIP и дату окончания считая от текущей,
        // если до этого статуса VIP не было
        $sql = "UPDATE cms_board_items
                SET is_vip = 1, vipdate = DATE_ADD(NOW(), INTERVAL {$days} DAY)
                WHERE id='{$id}' AND is_vip=0
                LIMIT 1";

        $this->inDB->query($sql);

        // Продлить имеющуюся дату VIP, если VIP-статус уже был
        $sql = "UPDATE cms_board_items
                SET vipdate = DATE_ADD(vipdate, INTERVAL {$days} DAY)
                WHERE id='{$id}' AND is_vip=1
                LIMIT 1";

        $this->inDB->query($sql);

        return true;

    }
/* ==================================================================================================== */
/* ==================================================================================================== */

	public function uploadPhoto($old_file = '', $cat) {

		// Загружаем класс загрузки фото
		cmsCore::loadClass('upload_photo');
		$inUploadPhoto = cmsUploadPhoto::getInstance();
		// Выставляем конфигурационные параметры
		$inUploadPhoto->upload_dir    = PATH.'/images/board/';
		$inUploadPhoto->small_size_w  = $cat['thumb1'];
		$inUploadPhoto->medium_size_w = $cat['thumb2'];
		$inUploadPhoto->thumbsqr      = $cat['thumbsqr'];
		$inUploadPhoto->is_watermark  = $this->config['watermark'];
		// Процесс загрузки фото
		$file = $inUploadPhoto->uploadPhoto($old_file);

		return $file;

	}

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getOrder($order='', $default='') {

		if (cmsCore::inRequest($order)) {
			$orders = cmsCore::request($order, 'str');
			cmsUser::sessionPut('ad_'.$order, $orders);
		} elseif(cmsUser::sessionGet('ad_'.$order)) {
			$orders = cmsUser::sessionGet('ad_'.$order);
		} else {
			$orders = $default;
		}

		return $orders;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getCatCity($cat_id = 0){

		$cat_city = array();

		$cat_id = ($cat_id == $this->root_cat['id']) ? 0 : $cat_id;
		$cat_sql = $cat_id ? "category_id = '$cat_id'" : '1=1';

        $sql = "SELECT city FROM cms_board_items WHERE published = 1 AND {$cat_sql} GROUP BY city";
        $result = $this->inDB->query($sql);

		if ($this->inDB->num_rows($result)){
			while($c = $this->inDB->fetch_assoc($result)){
				$cat_city[] = $c['city'];
			}
		}

        return $cat_city;
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

    public function getBoardCities($selected='', $cat=array()){

		global $_LANG;

        $html = '<select name="city" onchange="$(\'form#obform\').submit();" style="width:130px">';
        $html .= '<option value="all">'.$_LANG['ALL_CITY'].'</option>';
		if(!$cat['cat_city']) { $cat['cat_city'] = $this->getCatCity(); }
		if ($cat['cat_city']){
			foreach($cat['cat_city'] as $cat_city){
				if (mb_strtolower($selected)==mb_strtolower($cat_city)){
					$s = 'selected="selected"';
				} else {
					$s = '';
				}
				$pretty = htmlspecialchars(icms_ucfirst($cat_city));
				$html .= '<option value="'.$pretty.'" '.$s.'>'.$pretty.'</option>';
			}
		}
        $html .= '</select>';
        return $html;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */
	public function getTypesLinks($cat_id, $types){

		$html  = '';
		$types = explode("\n", $types);
		foreach($types as $id=>$type){
			$type = trim($type);
			$html .= '<a class="board_cats_a" href="/board/'.$cat_id.'/type/'.urlencode(icms_ucfirst($type)).'">'.icms_ucfirst($type).'</a>, ';
		}
		$html = rtrim($html, ', ');
		return $html;

	}
/* ==================================================================================================== */
/* ==================================================================================================== */
	public function getTypesOptions($types='', $selected=''){

		$html  = '';

        if (!$types){
            $types = explode("\n", $this->config['obtypes']);
        } else {
            $types = explode("\n", $types);
        }

		foreach($types as $type){
			$type = icms_ucfirst(htmlspecialchars(trim($type)));
			if (mb_strtolower($selected) == mb_strtolower($type)){ $sel = 'selected="selected"'; } else { $sel = ''; }
			$html .= '<option value="'.$type.'" '.$sel.'>'.$type.'</option>';
		}
		return $html;

	}
/* ==================================================================================================== */
/* ==================================================================================================== */
	public function orderForm($orderby, $orderto, $category){

		$smarty = $this->inCore->initSmarty('components', 'com_board_order_form.tpl');
		$smarty->assign('btype', $this->obtype);
		$smarty->assign('btypes', $this->getTypesOptions($category['obtypes'], $this->obtype));
		$smarty->assign('bcity', $this->city);
		$smarty->assign('bcities', $this->getBoardCities($this->city, $category));
		$smarty->assign('orderby', $orderby);
		$smarty->assign('orderto', $orderto);
		$smarty->assign('action_url', '/board/'.$category['id']);
		ob_start();
		$smarty->display('com_board_order_form.tpl');
		return ob_get_clean();

	}
/* ==================================================================================================== */
/* ==================================================================================================== */
	public function checkLoadedByUser24h($cat){

		$inUser = cmsUser::getInstance();

		if(!$cat['uplimit']) { return true; }

		if($inUser->id){
			$where = " AND user_id = '{$inUser->id}'";
		} elseif ($inUser->ip && $inUser->ip != '127.0.0.1'){
			$where = " AND ip = INET_ATON('{$inUser->ip}')";
		} else {
			return false;
		}

		$u_count = $this->inDB->rows_count('cms_board_items', "category_id = '{$cat['id']}' {$where} AND pubdate >= DATE_SUB(NOW(), INTERVAL 1 DAY)");

		if($u_count<=$cat['uplimit']) { return true; }

		return false;
	}

/* ==================================================================================================== */
/* ==================================================================================================== */

	public function checkAccess($user_id){

		$inUser = cmsUser::getInstance();

		if ($inUser->id){
			$access = ($inUser->is_admin || $this->is_moderator_by_group || $user_id == $inUser->id);
		} else {
			$access = false;
		}
		return $access;

	}

/* ==================================================================================================== */
/* ==================================================================================================== */

	public function checkAdd($cat){

		// администраторы могут всегда
		if(cmsUser::getInstance()->is_admin) { return true; }

		// настройки группы всегда приоритетней
		if(!$this->is_can_add_by_group) { return false; }

		// наследование от настроек компонента
		if ($cat['public'] == -1) { $cat['public'] = $this->config['public']; }

		if($cat['public']>0) { return true; }

		return false;

	}

/* ==================================================================================================== */
/* ==================================================================================================== */

	public function checkPublished($cat, $is_edit = false){

		// админы и модераторы добавляют всегда без модерации
		if(cmsUser::getInstance()->is_admin || $this->is_moderator_by_group) { return 1; }

		// при редактировании объявления смотрим опцию publish_after_edit
		if($is_edit){
			if($this->config['publish_after_edit']) { return 1; }
		}

		// наследование от настроек компонента
		if ($cat['public'] == -1) { $cat['public'] = $this->config['public']; }

        if ($cat['public']==2 && $this->inCore->isUserCan('board/autoadd')) { return 1; }

		return 0;

	}

/* ==================================================================================================== */
/* ==================================================================================================== */

}