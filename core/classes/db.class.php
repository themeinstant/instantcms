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

class cmsDatabase {

    private static $instance;

    public $q_count = 0;
    public $q_dump  = '';

    public $join     = '';
    public $select   = '';
    public $where    = '';
    public $group_by = '';
    public $order_by = '';
    public $limit    = '1000';
    public $page     = 1;
    public $perpage  = 10;

	private $cache = array(); // кеш некоторых запросов

    public $db_link;

// ============================================================================ //
// ============================================================================ //

	private function __construct(){

		$this->db_link = self::initConnection();

	}

// ============================================================================ //
// ============================================================================ //

	public static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new self;
		}
		return self::$instance;
	}

// ============================================================================ //
// ============================================================================ //
	/**
	 * Реинициализирует соединение с базой
	 */
	public static function reinitializedConnection(){

		self::getInstance()->db_link = self::initConnection();

		return true;

	}

// ============================================================================ //
// ============================================================================ //
	/**
	 * Устанавливает соединение с базой
     * @return resource $db_link
	 */
	private static function initConnection(){

		$inConf = cmsConfig::getInstance();

		$db_link = mysql_connect($inConf->db_host, $inConf->db_user, $inConf->db_pass) or die('Cannot connect to MySQL server');

		mysql_select_db($inConf->db_base, $db_link) or die('Cannot select "'.$inConf->db_base.'" database');

		mysql_set_charset('utf8');

		return $db_link;

	}

// ============================================================================ //
// ============================================================================ //
	/**
	 * Сбрасывает условия
	 */
    public function resetConditions(){

        $this->where    = '';
		$this->select   = '';
		$this->join     = '';
        $this->group_by = '';
        $this->order_by = '';
        $this->limit    = '';

    }

    public function addJoin($join){
        $this->join .= $join . "\n";
    }

    public function addSelect($condition){
        $this->select .= ', '.$condition;
    }

    public function where($condition){
        $this->where .= ' AND ('.$condition.')' . "\n";
    }

    public function groupBy($field){
        $this->group_by = 'GROUP BY '.$field;
    }

    public function orderBy($field, $direction='ASC'){
        $this->order_by = 'ORDER BY '.$field.' '.$direction;
    }

    public function limit($howmany) {
        $this->limitIs(0, $howmany);
    }

    public function limitIs($from, $howmany='') {
        $this->limit = (int)$from;
        if ($howmany){
            $this->limit .= ', '.$howmany;
        }
    }

    public function limitPage($page, $perpage){
		$this->page = $page; $this->perpage = $perpage;
        $this->limitIs(($page-1)*$perpage, $perpage);
    }

// ============================================================================ //
// ============================================================================ //

	protected function replacePrefix($sql, $prefix='cms_'){

		return trim(str_replace($prefix, cmsConfig::getConfig('db_prefix').'_', $sql));

	}

// ============================================================================ //
// ============================================================================ //

	public function query($sql, $ignore_errors=false, $replace_prefix = true){

        $sql = $replace_prefix ? $this->replacePrefix($sql) : $sql;

		$result = mysql_query($sql, $this->db_link);

		if (cmsConfig::getConfig('debug')){
			$this->q_count  += 1;
			$this->q_dump   .= '<pre>'.$sql.'</pre><hr/>';
		}

		if (mysql_error() && cmsConfig::getConfig('debug') && !$ignore_errors){
			die('<div style="margin:2px;border:solid 1px gray;padding:10px">DATABASE ERROR: <pre>'.$sql.'</pre>'.mysql_error().'</div>');
		}

		return $result;

	}

// ============================================================================ //
// ============================================================================ //
	public function num_rows($result){
		return (int)mysql_num_rows($result);
	}
// ============================================================================ //
// ============================================================================ //
	public function fetch_assoc($result){
		return mysql_fetch_assoc($result);
	}
// ============================================================================ //
// ============================================================================ //
	public function fetch_row($result){
		return mysql_fetch_row($result);
	}

// ============================================================================ //
// ============================================================================ //
	public function fetch_all($result){
	  $array = array();
	  if ($this->num_rows($result)){
		while ($object = mysql_fetch_object($result)){
		  $array[] = $object;
		}
	  }
	  return $array;
	}
// ============================================================================ //
// ============================================================================ //
	public function affected_rows(){
		return mysql_affected_rows($this->db_link);
	}
// ============================================================================ //
// ============================================================================ //

	public function get_last_id($table){

		$result = $this->query("SELECT LAST_INSERT_ID() as lastid FROM $table LIMIT 1");

		if ($this->num_rows($result)){
			$data = $this->fetch_assoc($result);
			return $data['lastid'];
		} else {
			return 0;
		}

	}

// ============================================================================ //
// ============================================================================ //

	public function rows_count($table, $where, $limit=0){

		$sql = "SELECT 1 FROM $table WHERE $where";
		if ($limit) { $sql .= " LIMIT ".(int)$limit; }
		$result = $this->query($sql);

		return $this->num_rows($result);

	}

// ============================================================================ //
// ============================================================================ //

	public function get_field($table, $where, $field){

		$sql    = "SELECT $field as getfield FROM $table WHERE $where LIMIT 1";
		$result = $this->query($sql);

		if ($this->num_rows($result)){
			$data = $this->fetch_assoc($result);
			return $data['getfield'];
		} else {
			return false;
		}

	}

// ============================================================================ //
// ============================================================================ //

	public function get_fields($table, $where, $fields, $order='id ASC'){

		$sql    = "SELECT $fields FROM $table WHERE $where ORDER BY $order LIMIT 1";
		$result = $this->query($sql);

		if ($this->num_rows($result)){
			$data = $this->fetch_assoc($result);
			return $data;
		} else {
			return false;
		}
	}

// ============================================================================ //
// ============================================================================ //

	public function get_table($table, $where='', $fields='*'){

		$list = array();

		$sql = "SELECT $fields FROM $table";
		if ($where) { $sql .= ' WHERE '.$where; }
		$result = $this->query($sql);

		if ($this->num_rows($result)){
			while($data = $this->fetch_assoc($result)){
				$list[] = $data;
			}
			return $list;
		} else {
			return false;
		}

	}

// ============================================================================ //
// ============================================================================ //
	public function errno() {
		return mysql_errno($this->db_link);
	}
// ============================================================================ //
// ============================================================================ //
	public function error() {
		return mysql_error($this->db_link);
	}
// ============================================================================ //
// ============================================================================ //
	public static function escape_string($value){

        if(is_array($value)){

            foreach ($value as $key=>$string) {
                $value[$key] = self::escape_string($string);
            }

            return $value;

        }

		return mysql_real_escape_string(stripcslashes($value));

	}
// ============================================================================ //
// ============================================================================ //

	public function isFieldExists($table, $field){

		$sql    = "SHOW COLUMNS FROM $table WHERE Field = '$field'";
		$result = $this->query($sql);

		if ($this->errno()) { return false; }

		return (bool)$this->num_rows($result);

	}

// ============================================================================ //
// ============================================================================ //

	public function isFieldType($table, $field, $type){

		$sql    = "SHOW COLUMNS FROM $table WHERE Field = '$field' AND Type = '$type'";
		$result = $this->query($sql);

		if ($this->errno()) { return false; }

		return (bool)$this->num_rows($result);

	}

// ============================================================================ //
// ============================================================================ //

	public function isTableExists($table){

		$sql    = "SELECT 1 FROM $table LIMIT 1";
		$result = $this->query($sql, true);

		if ($this->errno()){ return false; }

		return true;

	}

// ============================================================================ //
// ============================================================================ //

	public static function optimizeTables($tlist=''){

		$inDB = self::getInstance();

		if(is_array($tlist)) {

			foreach($tlist as $tname) {
				$inDB->query("OPTIMIZE TABLE $tname", true);
				$inDB->query("ANALYZE TABLE $tname", true);
			}

		} else if($inDB->isTableExists('information_schema.tables')) {

            $base = cmsConfig::getConfig('db_base');

			$tlist  = $inDB->get_table('information_schema.tables', "table_schema = '{$base}'", 'table_name');

			if (!is_array($tlist)) { return false; }

			foreach($tlist as $tname) {
				$inDB->query("OPTIMIZE TABLE {$tname['table_name']}", true);
				$inDB->query("ANALYZE TABLE {$tname['table_name']}", true);
			}

		}

		if ($inDB->errno()){ return false; }

		return true;

	}

// ============================================================================ //
// ============================================================================ //
    /**
     * Добавляет массив записей в таблицу
	 * ключи массива должны совпадать с полями в таблице
     */
	public function insert($table, $insert_array){

		// убираем из массива ненужные ячейки
		$insert_array = $this->removeTheMissingCell($table, $insert_array);
		$set = '';
		// формируем запрос на вставку в базу
		foreach($insert_array as $field=>$value){
			$set .= "{$field} = '{$value}',";
		}
		// убираем последнюю запятую
		$set = rtrim($set, ',');

		$this->query("INSERT INTO {$table} SET {$set}");

		if ($this->errno()) { return false; }

		return $this->get_last_id($table);

	}

// ============================================================================ //
// ============================================================================ //
    /**
     * Обновляет данные в таблице
	 * ключи массива должны совпадать с полями в таблице
     */
	public function update($table, $update_array, $id){

        if(isset($update_array['id'])){
            unset($update_array['id']);
        }

		// убираем из массива ненужные ячейки
		$update_array = $this->removeTheMissingCell($table, $update_array);

		$set = '';
		// формируем запрос на вставку в базу
		foreach($update_array as $field=>$value){
			$set .= "{$field} = '{$value}',";
		}
		// убираем последнюю запятую
		$set = rtrim($set, ',');

		$this->query("UPDATE {$table} SET {$set} WHERE id = '{$id}' LIMIT 1");

		if ($this->errno()) { return false; }

		return true;

	}

// ============================================================================ //
// ============================================================================ //
    /**
     * Убирает из массива ячейки, которых нет в таблице назначения
	 * используется при вставке/обновлении значений таблицы
     */
	public function removeTheMissingCell($table, $array){

		$result = $this->query("SHOW COLUMNS FROM `{$table}`");
		$list = array();
        while($data = $this->fetch_assoc($result)){
            $list[$data['Field']] = '';
        }
		// убираем ненужные ячейки массива
		foreach($array as $k=>$v){
		   if (!isset($list[$k])) { unset($array[$k]); }
		}

		if(!$array || !is_array($array)) { return array(); }

		return $array;

	}

// ============================================================================ //
// ============================================================================ //

	public function delete($table, $where='', $limit=0) {

		$sql = "DELETE FROM {$table} WHERE {$where}";

		if ($limit) { $sql .= " LIMIT {$limit}"; }

		$result = $this->query($sql, true);

		if ($this->errno()){ return false; }

		return true;

	}

// ============================================================================ //
// ============================================================================ //

    public function setFlag($table, $id, $flag, $value) {
        $this->query("UPDATE {$table} SET {$flag} = '{$value}' WHERE id='{$id}'");
        return true;
    }

    public function setFlags($table, $items, $flag, $value) {
        foreach($items as $id){
            $this->setMovieFlag($table, $id, $flag, $value);
        }
        return true;
    }

// ============================================================================ //
// ============================================================================ //

	public function deleteNS($table, $id, $differ='') {

		return cmsCore::getInstance()->nestedSetsInit($table)->DeleteNode($id, $differ);;

	}

// ============================================================================ //
// ============================================================================ //

	public function getNsRootCatId($table, $differ = '') {

		if(isset($this->cache[$table][$differ])) { return $this->cache[$table][$differ]; }

		$root_cat = $this->getNsCategory($table, 0, $differ);

		return $root_cat ? ($this->cache[$table][$differ] = $root_cat['id']) : false;

	}

// ============================================================================ //
// ============================================================================ //

	public function getNsCategory($table, $cat_id_or_link=0, $differ='') {

		if(isset($this->cache[$table][$cat_id_or_link][$differ])) { return $this->cache[$table][$cat_id_or_link][$differ]; }

        if (!$cat_id_or_link){

            $where = 'NSLevel = 0';

        } else {

			if(is_numeric($cat_id_or_link)){ // если пришла цифра, считаем ее cat_id

				$where = "id = '$cat_id_or_link'";

			} else {

				$where = "seolink = '$cat_id_or_link'";

			}

        }

		if(isset($differ)) { $where .= " AND NSDiffer = '{$differ}'"; }

		$cat = $this->get_fields($table, $where, '*');

		return $cat ? $this->cache[$table][$cat_id_or_link][$differ] = $cat : false;

	}

// ============================================================================ //
// ============================================================================ //

    public function moveNsCategory($table, $cat_id, $dir='up') {

        $ns = cmsCore::getInstance()->nestedSetsInit($table);

        if ($dir == 'up'){
            $ns->MoveOrdering($cat_id, -1);
        } else {
            $ns->MoveOrdering($cat_id, 1);
        }

        return true;

    }

// ============================================================================ //
// ============================================================================ //

	public function addNsCategory($table, $cat, $differ=''){

		$cat_id = cmsCore::getInstance()->nestedSetsInit($table)->AddNode($cat['parent_id'], -1, $differ);
		if(!$cat_id) { return false; }

        $this->update($table, $cat, $cat_id);

        return $cat_id;

    }

// ============================================================================ //
// ============================================================================ //

	public function addRootNsCategory($table, $differ='', $cat){

		$cat_id = cmsCore::getInstance()->nestedSetsInit($table)->AddRootNode($differ);
		if(!$cat_id) { return false; }

        $this->update($table, $cat, $cat_id);

        return $cat_id;

    }

// ============================================================================ //
// ============================================================================ /

    public function getNsCategoryPath($table, $left_key, $right_key, $fields='*', $differ='', $only_nested = false) {

		$nested_sql = $only_nested ? '' : '=';

        $path = $this->get_table($table, "NSLeft <$nested_sql $left_key AND NSRight >$nested_sql $right_key AND parent_id > 0 AND NSDiffer = '{$differ}' ORDER BY NSLeft", $fields);

        return $path;

    }

// ============================================================================ //
// ============================================================================ /
    /**
     * Обновляет ссылку на категорию и вложенные в нее
     * Подразумевается, что заголовок категории или поле url изменен заранее
     * @return bool
     */
    public function updateNsCategorySeoLink($table, $cat_id, $is_url_cyrillic = false){

		// получаем изменяемую категорию
		$cat = $this->getNsCategory($table, $cat_id);
		if(!$cat) { return false; }
		// обновляем для нее сеолинк
		$cat_seolink = cmsCore::generateCatSeoLink($cat, $table, $is_url_cyrillic);
		$this->query("UPDATE {$table} SET seolink='{$cat_seolink}' WHERE id = '{$cat['id']}'");

		// Получаем вложенные категории для нее
        $path_list = $this->get_table($table, "NSLeft > {$cat['NSLeft']} AND NSRight < {$cat['NSRight']} AND parent_id > 0 ORDER BY NSLeft");

        if ($path_list){
            foreach($path_list as $pcat){
				$subcat_seolink = cmsCore::generateCatSeoLink($pcat, $table, $is_url_cyrillic);
				$this->query("UPDATE {$table} SET seolink='{$subcat_seolink}' WHERE id = '{$pcat['id']}'");
            }
        }

        return true;

    }
// ============================================================================ //
// ============================================================================ //

}

?>