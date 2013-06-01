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

define('CORE_VERSION', 		'1.10.2');
define('CORE_BUILD', 		'1');
define('CORE_VERSION_DATE', '2013-02-14');
define('CORE_BUILD_DATE', 	'2013-02-14');

if (!defined('USER_UPDATER')) { define('USER_UPDATER', -1); }
if (!defined('USER_MASSMAIL')) { define('USER_MASSMAIL', -2); }
if (!defined('ONLINE_INTERVAL')) { define('ONLINE_INTERVAL', 3); } // интервал в минутах, когда пользователь считается online

class cmsCore {

    private static  $instance;

	private static  $jevix;

    private         $start_time;

    private         $menu_item;
    private         $menu_id = 0;
    private         $menu_struct;
    private         $is_menu_id_strict;

    private         $uri;
	private         $real_uri;
    public          $component;
    public          $do;
	public          $components = array();
	public          $plugins = array();
	private static  $filters;
    private         $url_without_com_name = false;

    private         $module_configs = array();
    private         $component_configs = array();

    private         $smarty = false;

    private function __construct($install_mode=false) {

        if ($install_mode){ return; }

        //подключим базу и конфиг
        self::loadClass('db');
        self::loadClass('config');
        self::loadClass('plugin');

		self::loadLanguage('lang');

        $inConf = cmsConfig::getInstance();

        //проверяем был ли переопределен шаблон через сессию
        //например, из модуля "выбор шаблона"
        if (isset($_SESSION['template'])) { $inConf->template = $_SESSION['template']; }

        define('TEMPLATE', $inConf->template);
        define('TEMPLATE_DIR', PATH.'/templates/'.$inConf->template.'/');
        define('DEFAULT_TEMPLATE_DIR', PATH.'/templates/_default_/');

        //загрузим структуру меню в память
        $this->loadMenuStruct();

        //получим URI
        $this->uri = $this->detectURI();

        //определим компонент
        $this->component = $this->detectComponent();

        //загрузим все компоненты в память
        $this->components = $this->getAllComponents();

        //загрузим все события плагинов в память
        $this->plugins = $this->getAllPlugins();

    }

    private function __clone() {}

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public $single_run_plugins = array('wysiwyg');

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public static function getInstance($install_mode=false) {
        if (self::$instance === null) {
            self::$instance = new self($install_mode);
        }
        return self::$instance;
    }

    public static function getHost(){

        self::loadClass('idna_convert');

        $IDN = new idna_convert();

        $host = $IDN->decode($_SERVER['HTTP_HOST']);

        return $host;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function startGenTimer() {

        $start_time         = microtime();
        $start_array        = explode(" ",$start_time);
        $this->start_time   = $start_array[1] + $start_array[0];
        return true;

    }

    public function getGenTime(){

        $end_time   = microtime();
        $end_array  = explode(" ", $end_time);
        $end_time   = $end_array[1] + $end_array[0];
        $time       = $end_time - $this->start_time;

        return $time;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public static function loadLanguage($file) {

        global $_LANG;

        $langfile = PATH.'/languages/'.cmsConfig::getConfig('lang').'/'.$file.'.php';

        if (!file_exists($langfile)){ $langfile = PATH.'/languages/ru/'.$file.'.php'; }
        if (!file_exists($langfile)){ return false; }

        include_once($langfile);

        return true;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Преобразует массив в YAML
     * @param array $array
     * @return string
     */
    public static function arrayToYaml($input_array) {

        self::includeFile('includes/spyc/spyc.php');

        if($input_array){
            foreach ($input_array as $key => $value) {
                $array[str_replace(array('[',']'), '', $key)] = $value;
            }
        } else { $array = array(); }

        return Spyc::YAMLDump($array,2,40);

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Преобразует YAML в массив
     * @param string $yaml
     * @return array
     */
    public static function yamlToArray($yaml) {

        self::includeFile('includes/spyc/spyc.php');

        return Spyc::YAMLLoad($yaml);

    }
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Задает полный список зарегистрированных
	 * событий в соответствии с включенными плагинами
     * @return array
     */
    public function getAllPlugins() {

		// если уже получали, возвращаемся
		if($this->plugins && is_array($this->plugins)) { return $this->plugins; }

		// Получаем список компонентов
		$this->plugins = cmsDatabase::getInstance()->get_table('cms_plugins p, cms_event_hooks e', 'p.published = 1 AND e.plugin_id = p.id', 'p.id, p.plugin, p.config, e.event');
		if (!$this->plugins){ $this->plugins = array(); }

        return $this->plugins;

    }
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Производит событие, вызывая все назначенные на него плагины
     * @param string $event
     * @param mixed $item
     * @return mixed
     */
    public static function callEvent($event, $item){

        //получаем все активные плагины, привязанные к указанному событию
        $plugins = self::getInstance()->getEventPlugins($event);

        //если активных плагинов нет, возвращаем элемент $item без изменений
        if (!$plugins) { return $item; }

        //перебираем плагины и вызываем каждый из них, передавая элемент $item
        foreach($plugins as $plugin_name){

            $plugin = self::getInstance()->loadPlugin($plugin_name);

            if ($plugin!==false){
                $item = $plugin->execute($event, $item);
                self::getInstance()->unloadPlugin($plugin);

				if(isset($plugin->info['type'])){
					if (in_array($plugin->info['type'], self::getInstance()->single_run_plugins)) {
						return $item;
					}
				}
            }

        }

        //возращаем $item обратно
        return $item;

    }
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * ====== DEPRECATED =========
     */
    public function executePluginRoute(){
        return true;
    }
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает массив с именами плагинов, привязанных к событию $event
     * @param string $event
     * @return array
     */
    public function getEventPlugins($event) {

        $plugins_list = array();

		foreach ($this->plugins as $plugin){
		   if($plugin['event'] == $event){
			  $plugins_list[] = $plugin['plugin'];
		   }
		}

        return $plugins_list;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Устанавливает плагин и делает его привязку к событиям
     * Возвращает ID установленного плагина
     * @param array $plugin
     * @param array $events
     * @param array $config
     * @return int
     */
    public function installPlugin($plugin, $events, $config) {

        $inDB = cmsDatabase::getInstance();

        if (!@$plugin['type']) { $plugin['type'] = 'plugin'; }

        $config_yaml = self::arrayToYaml($config);
        if (!$config_yaml) { $config_yaml = ''; }
		$plugin['config'] = $inDB->escape_string($config_yaml);

        //добавляем плагин в базу
        $plugin_id = $inDB->insert('cms_plugins', $plugin);

        //возвращаем ложь, если плагин не установился
        if (!$plugin_id)    { return false; }

        //добавляем хуки событий для плагина
        foreach($events as $event){
            $inDB->insert('cms_event_hooks', array('event'=>$event, 'plugin_id'=>$plugin_id));
        }

        //возращаем ID установленного плагина
        return $plugin_id;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Делает апгрейд установленного плагина
     * @param array $plugin
     * @param array $events
     * @param array $config
     * @return bool
     */
    public function upgradePlugin($plugin, $events, $config) {

        $inDB = cmsDatabase::getInstance();

        //находим ID установленной версии
        $plugin_id = $this->getPluginId($plugin['plugin']);

        //если плагин еще не был установлен, выходим
        if (!$plugin_id) { return false; }

        //загружаем текущие настройки плагина
        $old_config = $this->loadPluginConfig($plugin['plugin']);

        //удаляем настройки, которые больше не нужны
        foreach($old_config as $param=>$value){
            if ( !isset($config[$param]) ){
                unset($old_config[$param]);
            }
        }

        //добавляем настройки, которых раньше не было
        foreach($config as $param=>$value){
            if ( !isset($old_config[$param]) ){
                $old_config[$param] = $value;
            }
        }

        //конвертируем массив настроек в YAML
		$plugin['config'] = $inDB->escape_string(self::arrayToYaml($old_config));

        //обновляем плагин в базе
		$inDB->update('cms_plugins', $plugin, $plugin_id);

        //добавляем новые хуки событий для плагина
        foreach($events as $event){
            if (!$this->isPluginHook($plugin_id, $event)){
                $inDB->insert('cms_event_hooks', array('event'=>$event, 'plugin_id'=>$plugin_id));
            }
        }

        //плагин успешно обновлен
        return true;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Удаляет установленный плагин
     * @param array $plugin
     * @param array $events
     * @return bool
     */
    public function removePlugin($plugin_id){

        $inDB = cmsDatabase::getInstance();

        //если плагин не был установлен, выходим
        if (!$plugin_id) { return false; }

        //удаляем плагин из базы
        $inDB->delete('cms_plugins', "id = '$plugin_id'");

        //Удаляем хуки событий плагина
		$inDB->delete('cms_event_hooks', "plugin_id = '$plugin_id'");

        //плагин успешно удален
        return true;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает список плагинов, имеющихся на диске, но не установленных
     * @return array
     */
    public function getNewPlugins() {

        $new_plugins    = array();
        $all_plugins    = $this->getPluginsDirs();

        if (!$all_plugins) { return false; }

        foreach($all_plugins as $plugin){
            $installed = cmsDatabase::getInstance()->rows_count('cms_plugins', "plugin='{$plugin}'", 1);
            if (!$installed){
                $new_plugins[] = $plugin;
            }
        }

        if (!$new_plugins) { return false; }

        return $new_plugins;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает список плагинов, версия которых изменилась в большую сторону
     * @return array
     */
    public function getUpdatedPlugins() {

        $upd_plugins    = array();
        $all_plugins    = $this->getPluginsDirs();

        if (!$all_plugins) { return false; }

        foreach($all_plugins as $plugin){
            $plugin_obj = $this->loadPlugin($plugin);
            $version    = $this->getPluginVersion($plugin);
            if ($version){
                if ($version < $plugin_obj->info['version']){
                    $upd_plugins[] = $plugin;
                }
            }
        }

        if (!$upd_plugins) { return false; }

        return $upd_plugins;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает список папок с плагинами
     * @return array
     */
    public static function getPluginsDirs(){

        $dir  = PATH . '/plugins';
        $pdir = opendir($dir);

        $plugins = array();

        while ($nextfile = readdir($pdir)){
            if (
                    ($nextfile != '.')  &&
                    ($nextfile != '..') &&
                    is_dir($dir.'/'.$nextfile) &&
                    ($nextfile!='.svn') &&
                    (mb_substr($nextfile, 0, 2)=='p_')
               ) {
                $plugins[$nextfile] = $nextfile;
            }
        }

        if (!sizeof($plugins)){ return false; }

        return $plugins;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает ID плагина по названию
     * @param string $plugin
     * @return int
     */
    public function getPluginId($plugin){

        return cmsDatabase::getInstance()->get_field('cms_plugins', "plugin='{$plugin}'", 'id');

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает название плагина по ID
     * @param int $plugin_id
     * @return string
     */
    public function getPluginById($plugin_id){

        return cmsDatabase::getInstance()->get_field('cms_plugins', "id='{$plugin_id}'", 'plugin');

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает версию плагина по названию
     * @param string $plugin
     * @return float
     */
    public function getPluginVersion($plugin){

        return cmsDatabase::getInstance()->get_field('cms_plugins', "plugin='{$plugin}'", 'version');

    }

    /**
     * Задает полный список компонентов
     * @return array
     */
    public function getAllComponents() {

		// если уже получали, возвращаемся
		if($this->components && is_array($this->components)) { return $this->components; }

		// Получаем список компонентов
		$this->components = cmsDatabase::getInstance()->get_table('cms_components', '1=1 ORDER BY title', 'id, title, link, config, internal, published, version, system');
		if (!$this->components){ die('kernel panic'); }

        return $this->components;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Устанавливает компонент
     * Возвращает ID установленного плагина
     * @param array $component
     * @param array $config
     * @return int
     */
    public function installComponent($component, $config) {

        $inDB = cmsDatabase::getInstance();

        $config_yaml = self::arrayToYaml($config);
        if (!$config_yaml) { $config_yaml = ''; }
		$component['config'] = $inDB->escape_string($config_yaml);

        //добавляем компонент в базу
        $component_id = $inDB->insert('cms_components', $component);

        //возращаем ID установленного компонента
        return $component_id ? $component_id : false;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Делает апгрейд установленного компонента
     * @param array $component
     * @param array $config
     * @return bool
     */
    public function upgradeComponent($component, $config){

        $inDB = cmsDatabase::getInstance();

        //находим ID установленной версии
        $component_id = $this->getComponentId( $component['link'] );

        //если компонент еще не был установлен, выходим
        if (!$component_id) { return false; }

        //загружаем текущие настройки компонента
        $old_config = $this->loadComponentConfig( $component['link'] );

        //удаляем настройки, которые больше не нужны
        foreach($old_config as $param=>$value){
            if ( !isset($config[$param]) ){
                unset($old_config[$param]);
            }
        }

        //добавляем настройки, которых раньше не было
        foreach($config as $param=>$value){
            if ( !isset($old_config[$param]) ){
                $old_config[$param] = $value;
            }
        }

        //конвертируем массив настроек в YAML
		$component['config'] = $inDB->escape_string(self::arrayToYaml($old_config));

        //обновляем компонент в базе
		return $inDB->update('cms_components', $component, $component_id);

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Удаляет установленный компонент
     * @param int $component_id
     * @return bool
     */
    public function removeComponent($component_id) {

        //если компонент не был установлен, выходим
        if (!$component_id) { return false; }

        //определяем название компонента по id
        $component = $this->getComponentById($component_id);

        //удаляем зависимые модули компонента
        if (self::loadComponentInstaller($component)){
            $_component = call_user_func('info_component_'.$component);
            if (isset($_component['modules'])){
                if (is_array($_component['modules'])){
                    foreach($_component['modules'] as $module=>$title){
                        $module_id = self::getModuleId($module);
                        if ($module_id) { self::removeModule($module_id); }
                    }
                }
            }
        }

        //удаляем компонент из базы, но только если он не системный
        cmsDatabase::getInstance()->delete('cms_components', "id = '$component_id' AND system = 0");

        //компонент успешно удален
        return true;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает список компонентов, имеющихся на диске, но не установленных
     * @return array
     */
    public function getNewComponents() {

        $new_components = array();
        $all_components = self::getComponentsDirs();

        if (!$all_components) { return false; }

        foreach($all_components as $component){

            $installer_file = PATH . '/components/' . $component . '/install.php';

            if (file_exists($installer_file)){

                if (!$this->isComponentInstalled($component)){
                    $new_components[] = $component;
                }

            }

        }

        if (!$new_components) { return false; }

        return $new_components;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает список компонентов, версия которых изменилась в большую сторону
     * @return array
     */
    public function getUpdatedComponents() {

        $upd_components = array();

        foreach($this->components as $component){
            if(self::loadComponentInstaller($component['link'])){
                $version    = $component['version'];
                $_component = call_user_func('info_component_'.$component['link']);
                if ($version){
                    if ($version < $_component['version']){
                        $upd_components[] = $component['link'];
                    }
                }
            }
        }

        if (!$upd_components) { return false; }

        return $upd_components;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает список папок с компонентами
     * @return array
     */
    public static function getComponentsDirs(){

        $dir  = PATH . '/components';
        $pdir = opendir($dir);

        $components = array();

        while ($nextfile = readdir($pdir)){
            if (
                    ($nextfile != '.')  &&
                    ($nextfile != '..') &&
                    is_dir($dir.'/'.$nextfile) &&
                    ($nextfile!='.svn')
               ) {
                $components[$nextfile] = $nextfile;
            }
        }

        if (!sizeof($components)){ return false; }

        return $components;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает ID компонента по названию
     * @param string $component
     * @return int
     */
    public function getComponentId($component){

		$component_id = 0;

		foreach ($this->components as $inst_component){
		   if($inst_component['link'] == $component){
			  $component_id = $inst_component['id']; break;
		   }
		}

        return $component_id;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Проверяет включен ли компонент
     * @param string $component
     * @return bool
     */
    public function isComponentEnable($component){

        $enable = false;

		foreach ($this->components as $inst_component){
		   if($inst_component['link'] == $component){
			  $enable = (bool)$inst_component['published']; break;
		   }
		}

        return $enable;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает название компонента по ID
     * @param int $component_id
     * @return string
     */
    public function getComponentById($component_id){

		$link = '';

		foreach ($this->components as $inst_component){
		   if($inst_component['id'] == $component_id){
			  $link = $inst_component['link']; break;
		   }
		}

        return $link;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает массив компонента по ID
     * @param int $component_id
     * @return array
     */
    public function getComponent($component_id){

		$c = array();

		foreach ($this->components as $inst_component){
		   if($inst_component['id'] == $component_id){
			  $c = $inst_component; break;
		   }
		}

        return $c;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает версию компонента по названию
     * @param string $component
     * @return float
     */
    public function getComponentVersion($component){

		$version = '';

		foreach ($this->components as $inst_component){
		   if($inst_component['link'] == $component){
			  $version = $inst_component['version']; break;
		   }
		}

        return $version;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function loadComponentInstaller($component){

        return self::includeFile('components/'.$component.'/install.php');;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Устанавливает модуль
     * Возвращает ID установленного модуля
     * @param array $module
     * @param array $config
     * @return int
     */
    public function installModule($module, $config) {

        $inDB = cmsDatabase::getInstance();

        $config_yaml = self::arrayToYaml($config);
        if (!$config_yaml) { $config_yaml = ''; }
		$module['config'] = $inDB->escape_string($config_yaml);
        // Помечаем, что модуль внешний
        $module['is_external'] = 1;
        // переходной костыль
        // в модулях теперь нужно вместо, например
        // $_module['link'] = 'mod_actions';
        // писать
        // $_module['content'] = 'mod_actions';
        if (isset($module['link'])) {
            $module['content'] = $module['link'];
        }

        //возращаем ID установленного модуля
        return $inDB->insert('cms_modules', $module);

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Делает апгрейд установленного модуля
     * @param array $component
     * @param array $config
     * @return bool
     */
    public function upgradeModule($module, $config) {

        $inDB = cmsDatabase::getInstance();

        // удалить в следующем обновлении
        if (isset($module['link'])) {
            $module['content'] = $module['link'];
        }

        //находим ID установленной версии
        $module_id = self::getModuleId($module['content']);

        //если модуль еще не был установлен, выходим
        if (!$module_id) { return false; }

        //загружаем текущие настройки модуля
        $old_config = $this->loadModuleConfig( $module_id );

        //удаляем настройки, которые больше не нужны
        foreach($old_config as $param=>$value){
            if ( !isset($config[$param]) ){
                unset($old_config[$param]);
            }
        }

        //добавляем настройки, которых раньше не было
        foreach($config as $param=>$value){
            if ( !isset($old_config[$param]) ){
                $old_config[$param] = $value;
            }
        }

        //конвертируем массив настроек в YAML
		$module['config'] = $inDB->escape_string(self::arrayToYaml($old_config));

		unset($module['position']);

        //обновляем модуль в базе
        $inDB->update('cms_modules', $module, $module_id);

        //модуль успешно обновлен
        return true;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Удаляет установленный модуль
     * @param int $module_id
     * @return bool
     */
    public static function removeModule($module_id) {

		return cmsDatabase::getInstance()->delete('cms_modules', "id = '{$module_id}'", 1);

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает список модулей, имеющихся на диске, но не установленных
     * @return array
     */
    public function getNewModules() {

        $new_modules = array();
        $all_modules = self::getModulesDirs();

        if (!$all_modules) { return false; }

        foreach($all_modules as $module){

            $installer_file = PATH . '/modules/' . $module . '/install.php';

            if (file_exists($installer_file)){

                $installed = cmsDatabase::getInstance()->rows_count('cms_modules', "content='{$module}' AND user=0", 1);
                if (!$installed){
                    $new_modules[] = $module;
                }

            }

        }

        if (!$new_modules) { return false; }

        return $new_modules;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает список модулей, версия которых изменилась в большую сторону
     * @return array
     */
    public function getUpdatedModules() {

        $upd_modules = array();
        $all_modules = cmsDatabase::getInstance()->get_table('cms_modules', 'user=0');

        if (!$all_modules) { return false; }

        foreach($all_modules as $module){
            if($this->loadModuleInstaller($module['content'])){
                $version = $module['version'];
                $_module = call_user_func('info_module_'.$module['content']);
                if ($version){
                    if ($version < $_module['version']){
                        $upd_modules[] = $module['content'];
                    }
                }
            }
        }

        if (!$upd_modules) { return false; }

        return $upd_modules;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает список папок с модулями
     * @return array
     */
    public static function getModulesDirs() {

        $dir  = PATH . '/modules';
        $pdir = opendir($dir);

        $modules = array();

        while ($nextfile = readdir($pdir)){
            if (
                    ($nextfile != '.')  &&
                    ($nextfile != '..') &&
                    is_dir($dir.'/'.$nextfile) &&
                    ($nextfile!='.svn')
               ) {
                $modules[$nextfile] = $nextfile;
            }
        }

        if (!sizeof($modules)){ return false; }

        return $modules;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает ID модуля по названию
     * @param string $component
     * @return int
     */
    public static function getModuleId($module){

        return cmsDatabase::getInstance()->get_field('cms_modules', "content='{$module}' AND user=0", 'id');

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает название модуля по ID
     * @param int $component_id
     * @return string
     */
    public static function getModuleById($module_id){

        return cmsDatabase::getInstance()->get_field('cms_modules', "id='{$module_id}' AND user=0", 'content');

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает версию модуля по названию
     * @param string $component
     * @return float
     */
    public static function getModuleVersion($module){

        return cmsDatabase::getInstance()->get_field('cms_modules', "content='{$module}' AND user=0", 'version');

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function loadModuleInstaller($module){

        return self::includeFile('modules/'.$module.'/install.php');;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает кофигурацию плагина в виде массива
     * @param string $plugin
     * @return float
     */
    public function loadPluginConfig($plugin_name){

		$config = array();

		foreach ($this->plugins as $plugin){
		   if($plugin['plugin'] == $plugin_name){
			  $config = self::yamlToArray($plugin['config']);
			  break;
		   }
		}

        return $config;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Сохраняет настройки плагина в базу
     * @param string $plugin_name
     * @param array $config
     * @return bool
     */
    public function savePluginConfig($plugin_name, $config) {

        $inDB = cmsDatabase::getInstance();

        //конвертируем массив настроек в YAML
		$config_yaml = $inDB->escape_string(self::arrayToYaml($config));

        //обновляем плагин в базе
        $update_query  = "UPDATE cms_plugins
                          SET config='{$config_yaml}'
                          WHERE plugin = '{$plugin_name}'";

        $inDB->query($update_query);

        //настройки успешно сохранены
        return true;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Проверяет привязку плагина к событию
     * @param int $plugin_id
     * @param string $event
     * @return bool
     */
    public function isPluginHook($plugin_id, $event) {

        return cmsDatabase::getInstance()->rows_count('cms_event_hooks', "plugin_id='{$plugin_id}' AND event='{$event}'");

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Загружает плагин и возвращает его объект
     * @param string $plugin
     * @return cmsPlugin
     */
    public static function loadPlugin($plugin) {
        $plugin_file = PATH.'/plugins/'.$plugin.'/plugin.php';
        if (file_exists($plugin_file)){
            include_once($plugin_file);
            $plugin_obj = new $plugin();
            return $plugin_obj;
        }
        return false;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Уничтожает объект плагина
     * @param cmsPlugin $plugin_obj
     * @return true
     */
    public static function unloadPlugin($plugin_obj) {
        unset($plugin_obj);
        return true;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Загружает библиотеку из файла /core/lib_XXX.php, где XXX = $lib
     * @param string $lib
     * @return bool
     */
    public static function loadLib($lib){
        return self::includeFile('core/lib_'.$lib.'.php');
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Загружает класс из файла /core/classes/XXX.class.php, где XXX = $class
     * @param string $class
     * @return bool
     */
    public static function loadClass($class){
        return self::includeFile('core/classes/'.$class.'.class.php');
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Загружает модель для указанного компонента
     * @param string $component
     * @return bool
     */
    public static function loadModel($component){
        return self::includeFile('components/'.preg_replace('/[^a-z_]/iu', '', $component).'/model.php');
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Подключает внешний файл
     * @param string $file
     */
    public static function includeFile($file){
		if (file_exists(PATH.'/'.$file)){
        	include_once PATH.'/'.$file;
			return true;
		} else {
			return false;
		}
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Подключает функции для работы с графикой
     */
    public static function includeGraphics(){
        include_once PATH.'/includes/graphic.inc.php';
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Подключает файл конфигурации
     */
    public static function includeConfig(){
        include_once PATH.'/includes/config.inc.php';
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public static function insertEditor($name, $text='', $height='350', $width='500') {

        $editor = self::callEvent('INSERT_WYSIWYG', array(
                                                        'name'=>$name,
                                                        'text'=>$text,
                                                        'height'=>$height,
                                                        'width'=>$width
                                                    ));

        if (!is_array($editor)){ echo $editor; return; }

        echo '<p>
                <div>Визуальный редактор не найден либо не включен.</div>
                <div>Если редактор установлен, включите его в админке (меню <em>Дополнения</em> &rarr; <em>Плагины</em>).</div>
              </p>';

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Устанавливает кукис посетителю
     * @param string $name
     * @param string $value
     * @param int $time
     */
    public static function setCookie($name, $value, $time){
        setcookie('InstantCMS['.$name.']', $value, $time, '/', null, false, true);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Удаляет кукис пользователя
     * @param string $name
     */
    public static function unsetCookie($name){
        setcookie('InstantCMS['.$name.']', '', time()-3600, '/');
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает значение кукиса
     * @param string $name
     * @return string || false
     */
    public static function getCookie($name){
        if (isset($_COOKIE['InstantCMS'][$name])){
            return $_COOKIE['InstantCMS'][$name];
        } else {
            return false;
        }
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Добавляет сообщение в сессию
     * @param string $message
     * @param string $class
     */
    public static function addSessionMessage($message, $class='info'){
        $_SESSION['core_message'][] = '<div class="message_'.$class.'">'.$message.'</div>';
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /*
     * Возвращает массив сообщений сохраненных в сессии
     */
    public static function getSessionMessages(){

        if (isset($_SESSION['core_message'])){
            $messages = $_SESSION['core_message'];
        } else {
            $messages = false;
        }

        self::clearSessionMessages();
        return $messages;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /*
     * Очищает очередь сообщений сессии
     */
    public static function clearSessionMessages(){
        unset($_SESSION['core_message']);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Обновляет статистику посещений сайта
     */
    public function onlineStats(){

        $inDB = cmsDatabase::getInstance();

        $bots = array();
        $bots['Aport']              ='Aport';
        $bots['msnbot']             ='MSNbot';
        $bots['Yandex']             ='Yandex';
        $bots['Lycos.com']          ='Lucos';
        $bots['Googlebot']          ='Google';
        $bots['Openbot']            ='Openfind';
        $bots['FAST-WebCrawler']    ='AllTheWeb';
        $bots['TurtleScanner']      ='TurtleScanner';
        $bots['Yahoo-MMCrawler']    ='Y!MMCrawler';
        $bots['Yahoo!']             ='Yahoo!';
        $bots['rambler']            ='Rambler';
        $bots['W3C_Validator']      ='W3C Validator';
		$bots['bingbot']            ='bingbot';
		$bots['magpie-crawler']     ='magpie-crawler';

        //удаляем старые записи
        $sql = "DELETE FROM cms_online WHERE lastdate <= DATE_SUB(NOW(), INTERVAL ".ONLINE_INTERVAL." MINUTE) LIMIT 5";
        $inDB->query($sql) ;

        //собираем информацию о текущем пользователе
        $sess_id   = session_id();
        $ip        = self::strClear($_SERVER['REMOTE_ADDR']);
        $useragent = self::strClear($_SERVER['HTTP_USER_AGENT']);
        $page      = self::strClear($_SERVER['REQUEST_URI']);
        $refer     = self::strClear($_SERVER['HTTP_REFERER']);

        $user_id   = cmsUser::getInstance()->id;

		//Проверяем, пользователь это или поисковый бот
		$crawler = false;
		foreach($bots as $bot=>$uagent){ if (mb_strpos($useragent, $uagent)) { $crawler = true; break; }	}
		//Если не бот, вставляем/обновляем запись в "кто онлайн"
		if (!$crawler){
            // При аякс запросах не к чему записывать url
            $page_sql = (@$_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') ? '' : ", viewurl = '$page'";
			$inDB->query("INSERT IGNORE INTO cms_online (ip, sess_id) VALUES ('$ip', '$sess_id') ON DUPLICATE KEY UPDATE user_id = '$user_id', agent = '$useragent' {$page_sql}");
		}

		//если включен сбор статистики на сайте
        if (cmsConfig::getConfig('stats')){
            //смотрим, есть ли запись про текущего пользователя
            $sql = "SELECT id FROM cms_stats WHERE (ip = '$ip' AND page = '$page')";
            $result = $inDB->query($sql) ;
            //если записи нет - добавляем
            if (!$inDB->num_rows($result)){
                $sql = "INSERT DELAYED INTO cms_stats (ip, logdate, page, agent, refer) VALUES ('$ip', NOW(), '$page', '$useragent', '$refer')";
                $inDB->query($sql) ;
            }
        }

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает текущий URI
     * Нужна для того, чтобы иметь возможность переопределить URI.
     * По сути является эмулятором внутреннего mod_rewrite
     * @return string
     */
    private function detectURI(){

        $uri   = self::strClear($_SERVER['REQUEST_URI']);
        $uri   = ltrim($uri, '/');
        $rules = array();

        $folder = rtrim($uri, '/');

        if (mb_strstr($uri, "?") && !preg_match('/^admin\/(.*)/ui', $uri) && !mb_strstr($uri, 'go/url=') && !mb_strstr($uri, 'load/url=')){
            $query_str = mb_substr($uri, mb_strpos($uri, "?")+1);
            $uri = mb_substr($uri, 0, mb_strpos($uri, "?"));
            mb_parse_str($query_str, $temp_request);
			$_REQUEST = array_merge($_REQUEST, $temp_request);
        }

        if (in_array($folder, array('admin', 'install', 'migrate', 'index.php'))) { return; }

        //специальный хак для поиска по сайту, для совместимости со старыми шаблонами
        if (mb_strstr($_SERVER['QUERY_STRING'], 'view=search')){ $uri = 'search'; }

        if(file_exists(PATH.'/url_rewrite.php')) {
            //подключаем список rewrite-правил
            self::includeFile('url_rewrite.php');
            if(function_exists('rewrite_rules')){
                //получаем правила
                $rules = rewrite_rules();
            }
        }

        if(file_exists(PATH.'/custom_rewrite.php')) {
            //подключаем список пользовательских rewrite-правил
            self::includeFile('custom_rewrite.php');
            if(function_exists('custom_rewrite_rules')){
                //добавляем к полученным ранее правилам пользовательские
                $rules = array_merge($rules, custom_rewrite_rules());
            }
        }

        $found = false;

		// Запоминаем реальный uri
		$this->real_uri = $uri;

        if ($rules){
            //перебираем правила
            foreach($rules as $rule_id=>$rule) {
                //небольшая валидация правила
                if (!$rule['source'] || !$rule['target'] || !$rule['action']) { continue; }
                //проверяем совпадение выражения source с текущим uri
                if (preg_match($rule['source'], $uri, $matches)){

                    //перебираем совпавшие сегменты и добавляем их в target
                    //чтобы сохранить параметры из $uri в новом адресе
                    foreach($matches as $key=>$value){
                        if (!$key) { continue; }
                        if (mb_strstr($rule['target'], '{'.$key.'}')){
                            $rule['target'] = str_replace('{'.$key.'}', $value, $rule['target']);
                        }
                    }

                    //действие по-умолчанию: rewrite
                    if (!$rule['action']) { $rule['action'] = 'rewrite'; }

                    //выполняем действие
                    switch($rule['action']){
                        case 'rewrite'      : $uri = $rule['target']; $found = true; break;
                        case 'redirect'     : self::redirect($rule['target']); break;
                        case 'redirect-301' : self::redirect($rule['target'], '301'); break;
                        case 'alias'        :
                            // Разбираем $rule['target'] на путь к файлу и его параметры
                            $t = parse_url($rule['target']);
                            // Для удобства формируем массив $include_query
                            // переменные будут сохранены в элементах массива
                            if(!empty($t['query'])){
                                mb_parse_str($t['query'], $include_query);
                            }
                            if (file_exists(PATH.'/'.$t['path'])){
                                include_once PATH.'/'.$t['path'];
                            }
                            self::halt();
                    }

                }

                if ($found) { break; }

            }
        }

        return $uri;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Определяет текущий компонент
     * Считается, что компонент указан в первом сегменте URI,
     * иначе подключается компонент для главной страницы
     * @return string $component
     */
    private function detectComponent(){

        $component = '';

        //компонент на главной
        if (!$this->uri && cmsConfig::getConfig('homecom')) { return cmsConfig::getConfig('homecom'); }

        //определяем, есть ли слэши в адресе
        $first_slash_pos = mb_strpos($this->uri, '/');

        if ($first_slash_pos){
            //если есть слэши, то компонент это сегмент до первого слэша
            $component  = mb_substr($this->uri, 0, $first_slash_pos);
        } else {
            //если слэшей нет, то компонент совпадает с адресом
            $component  = $this->uri;
        }

        if (is_dir(PATH.'/components/'.$component)){
            //если компонент определен и существует
            return $component;
        } else {
            //если компонент не существует, считаем что это content
            $this->uri = cmsConfig::getConfig('com_without_name_in_url').'/'.$this->uri;
            $this->url_without_com_name = true;
            return cmsConfig::getConfig('com_without_name_in_url');
        }

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Функция подключает файл router.php из папки с текущим компонентом
     * и вызывает метод route_component(), которые возвращает массив правил
     * для анализа URI. Если в массиве найдено совпадение с текущим URI,
     * то URI парсится и переменные, содержащиеся в нем, забиваются в массив $_REQUEST.
     * @return boolean
     */
    private function parseComponentRoute(){

        $component = $this->component;

        //проверяем что компонент указаны
        if (!$component) { return false; }
		//если uri нет, все равно возвращаем истину - для опции "компонент на главной"
        if (!$this->uri) { return true; }

		// если uri совпадает с названием компонента, возвращаем истину
		if($this->uri == $component) { return true; }

        //подключаем список маршрутов компонента
        if(!self::includeFile('components/'.$component.'/router.php')){ return false; }

        $routes = call_user_func('routes_'.$component);
		$routes = self::callEvent('GET_ROUTE_'.mb_strtoupper($component), $routes);
		// Флаг удачного перебора
		$is_found = false;
        //перебираем все маршруты
		if($routes){
			foreach($routes as $route_id=>$route){

				//сравниваем шаблон маршрута с текущим URI
				preg_match($route['_uri'], $this->uri, $matches);

				//Если найдено совпадение
				if ($matches){

					//удаляем шаблон из параметров маршрута, чтобы не мешал при переборе
					unset($route['_uri']);

					//перебираем параметры маршрута в виде ключ=>значение
					foreach($route as $key=>$value){
						if (is_integer($key)){
							//Если ключ - целое число, то значением является сегмент URI
							$_REQUEST[$value] = $matches[$key];
						} else {
							//иначе, значение берется из маршрута
							$_REQUEST[$key]   = $value;
						}
					}
					// совпадение есть
					$is_found = true;
					//раз найдено совпадение, прерываем цикл
					break;

				}

			}
		}

		// Если в маршруте нет совпадений
		if(!$is_found) { return false; }

        return true;

    }

    /**
     * Узнаем действие компонента
     */
    private function detectAction(){

		$do = preg_replace('/[^a-z_]/iu', '', self::request('do', 'str', 'view'));
		$this->do = $do ? $do : 'view';

        return true;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Генерирует тело страницы, вызывая нужный компонент
     */
    public function proceedBody(){

        $component = $this->component;

        //проверяем что компонент указан
        if (!$component) { return false; }

        //проверяем что в названии только буквы и цифры
        if (!preg_match("/^([a-z0-9])+$/u", $component)){ self::error404(); }

        // компонент включен?
        if(!$this->isComponentEnable($component)) { self::error404(); }

        self::loadLanguage('components/'.$component);

        //проверяем наличие компонента
        if(!file_exists('components/'.$component.'/frontend.php')){ self::error404(); }

        //парсим адрес и заполняем массив $_REQUEST (временное решение)
        if(!$this->parseComponentRoute()) { self::error404(); }
        // узнаем действие в компоненте
        $this->detectAction();

        ob_start();

        // Вызываем сначала плагин (если он есть) на действие
        // Успешность выполнения должна определяться в методе execute плагина
        // Он должен вернуть true
        if(!cmsCore::callEvent(mb_strtoupper('get_'.$component.'_action_'.$this->do), false)){

            require('components/'.$component.'/frontend.php');

            call_user_func($component);

        }

        cmsPage::getInstance()->page_body = cmsCore::callEvent('AFTER_COMPONENT_'.mb_strtoupper($component), ob_get_clean());

        return true;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public static function error404(){

        self::loadClass('page');

        header("HTTP/1.0 404 Not Found");
        header("HTTP/1.1 404 Not Found");
        header("Status: 404 Not Found");

        if (!cmsPage::includeTemplateFile('special/error404.php')){
            echo '<h1>404</h1>';
        }

        self::halt();

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Инициализирует вложенные множества и возвращает объект CCelkoNastedSet
     * @return object NS
     */
    public static function nestedSetsInit($table){

        self::includeFile('includes/nestedsets.php');
        $ns = new CCelkoNastedSet();
        $ns->MyLink     = cmsDatabase::getInstance()->db_link;
        $ns->TableName  = $table;
        return $ns;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Проверяет, нужно ли показывать сплеш-страницу (приветствие)
     * @return bool
     */
    public static function isSplash(){

        if (cmsConfig::getConfig('splash')){
            $show_splash = !(self::getCookie('splash') || isset($_SESSION['splash']));
            return $show_splash;
        } else { return false; }

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает ключевые слова для заданного текста
     * @param string $text
     * @return string
     */
    public static function getKeywords($text){

        self::includeFile('includes/keywords.inc.php');

        $params['content'] = $text; //page content
        $params['min_word_length'] = 5;  //minimum length of single words
        $params['min_word_occur'] = 2;  //minimum occur of single words

        $params['min_2words_length'] = 5;  //minimum length of words for 2 word phrases
        $params['min_2words_phrase_length'] = 10; //minimum length of 2 word phrases
        $params['min_2words_phrase_occur'] = 2; //minimum occur of 2 words phrase

        $params['min_3words_length'] = 5;  //minimum length of words for 3 word phrases
        $params['min_3words_phrase_length'] = 10; //minimum length of 3 word phrases
        $params['min_3words_phrase_occur'] = 2; //minimum occur of 3 words phrase

        $keyword = new autokeyword($params, "UTF-8");

        return $keyword->get_keywords();

    }

    // REQUESTS /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Проверяет наличие переменной $var во входных параметрах
     * @param string $var
     * @return bool
     */
    public static function inRequest($var){
        return isset($_REQUEST[$var]);
    }
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Проверяет наличие переменной $var во входных параметрах
     * @param string $var
     * @param string $type = int | str | html
     * @param string $default
     */
    public static function request($var, $type='str', $default=false){

        if (isset($_REQUEST[$var])){
            switch($type){
                case 'int':   return (int)$_REQUEST[$var]; break;
                case 'str':   if ($_REQUEST[$var]) { return self::strClear($_REQUEST[$var]); } else { return $default; } break;
                case 'email': if(preg_match("/^([a-zA-Z0-9\._-]+)@([a-zA-Z0-9\._-]+)\.([a-zA-Z]{2,4})$/ui", $_REQUEST[$var])){ return $_REQUEST[$var]; } else { return $default; } break;
                case 'html':  if ($_REQUEST[$var]) { return self::strClear($_REQUEST[$var], false); } else { return $default; } break;
                case 'array': if (is_array($_REQUEST[$var])) { foreach($_REQUEST[$var] as $k=>$s){ $arr[$k] = self::strClear($s, false); } return $arr; } else { return $default; } break;
                case 'array_int': if (is_array($_REQUEST[$var])) { foreach($_REQUEST[$var] as $k=>$i){ $arr[$k] = (int)$i; } return $arr; } else { return $default; } break;
                case 'array_str': if (is_array($_REQUEST[$var])) { foreach($_REQUEST[$var] as $k=>$s){ $arr[$k] = self::strClear($s); } return $arr; } else { return $default; } break;
            }
        } else {
            return $default;
        }

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Получет из request переменную $search и кладет в сессию
     * при отсутствии в request переменной $search берет из сессии
     * или возвращает $default
     * @return str
     */
    public static function getSearchVar($search = '', $default='') {

		$value = self::strClear(mb_strtolower(urldecode(self::request($search, 'html'))));

		$com = self::getInstance()->component;

		if ($value) {
			if($value == 'all'){
				cmsUser::sessionDel($com.'_'.$search);
				$value = '';
			} else {
				cmsUser::sessionPut($com.'_'.$search, $value);
			}
		} elseif(cmsUser::sessionGet($com.'_'.$search)) {
			$value = cmsUser::sessionGet($com.'_'.$search);
		} else {
			$value = $default;
		}

		return $value;

    }
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public static function redirectBack(){
        if(isset($_SERVER['HTTP_REFERER'])){
            $back_url = self::strClear($_SERVER['HTTP_REFERER']);
        } else {
            $back_url = '/';
        }
        self::redirect($back_url);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public static function redirect($url, $code='303'){
        if ($code == '301'){
            header('HTTP/1.1 301 Moved Permanently');
        } else {
            header('HTTP/1.1 303 See Other');
        }
        header('Location:'.$url);
        self::halt();
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает предыдущий URL для редиректа назад. Если находит переменную $_REQUEST['back'], то возвращает ее
     * @return string
     */
    public static function getBackURL(){
        if(self::inRequest('back')){
            $back = self::request('back');
        } else {
            if (isset($_SERVER['HTTP_REFERER'])){
                $back = self::strClear($_SERVER['HTTP_REFERER']);
            } else { $back = '/'; }
        }
        return $back;
    }

    // FILE UPLOADING //////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Закачивает файл на сервер и отслеживает ошибки
     * @param string $source
     * @param string $destination
     * @param int $errorCode
     * @return bool
     */
    public static function moveUploadedFile($source, $destination, $errorCode){

        $max_size = ini_get('upload_max_filesize');
        $max_size = str_replace('M', 'Мб', $max_size);
        $max_size = str_replace('K', 'Кб', $max_size);

        //Possible upload errors
        $uploadErrors = array(
            UPLOAD_ERR_OK => 'Файл успешно загружен',
            UPLOAD_ERR_INI_SIZE => 'Размер файла превышает допустимый &mdash; '.$max_size,
            UPLOAD_ERR_FORM_SIZE => 'Размер файла превышает допустимый',
            UPLOAD_ERR_PARTIAL => 'Файл был загружен не полностью',
            UPLOAD_ERR_NO_FILE => 'Файл не был загружен',
            UPLOAD_ERR_NO_TMP_DIR => 'Не найдена папка для временных файлов на сервере',
            UPLOAD_ERR_CANT_WRITE => 'Ошибка записи файла на диск',
            UPLOAD_ERR_EXTENSION => 'Загрузка файла была прервана расширением PHP'
        );

        if($errorCode !== UPLOAD_ERR_OK && isset($uploadErrors[$errorCode])){
            //if is error, save it and return false
            $_SESSION['file_upload_error'] = $uploadErrors[$errorCode];

            return false;

        } else {

            //clear error, if upload is ok
            $_SESSION['file_upload_error'] = '';
            //get upload directory and check it is writable
            $upload_dir = dirname($destination);
            if (!is_writable($upload_dir)){
				@chmod($upload_dir, 0777);
			}
            //move uploaded file
            return @move_uploaded_file($source, $destination);

        }

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public static function uploadError(){
        if ($_SESSION['file_upload_error']){ return $_SESSION['file_upload_error']; } else { return false; }
    }

    // SMARTY //////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает класс Smarty
     */
    public function getSmartyObj(){

        if(is_object($this->smarty)){ return $this->smarty; }

        self::includeFile('/includes/smarty/libs/Smarty.class.php');

        $smarty = new Smarty();

        $smarty->compile_dir = PATH.'/cache';
        $smarty->register_modifier("NoSpam", "cmsSmartyNoSpam");
        $smarty->register_function('wysiwyg', 'cmsSmartyWysiwyg');
        $smarty->register_function('profile_url', 'cmsSmartyProfileURL');
        $smarty->register_function('component', 'cmsSmartyCurrentComponent');
        $smarty->register_function('template', 'cmsSmartyCurrentTemplate');

        return $this->smarty = $smarty;

    }

    /**
     * Очистка кеша
     */
    public function clearSmartyCache($file = null){
        return $this->getSmartyObj()->clear_compiled_tpl($file);
    }
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает объект Smarty для дальнейшей работы с шаблоном
     * @param string $tpl_folder = modules / components / plugins
     * @param string $tpl_file
     * @return obj
     */
    public function initSmarty($tpl_folder='modules', $tpl_file=''){

        global $_LANG;

        $smarty = $this->getSmartyObj();

        $is_exists_tpl_file = file_exists(TEMPLATE_DIR . "{$tpl_folder}/{$tpl_file}");

        $smarty->template_dir = $is_exists_tpl_file ? TEMPLATE_DIR . $tpl_folder : DEFAULT_TEMPLATE_DIR . $tpl_folder;
        $smarty->compile_id   = $is_exists_tpl_file ? TEMPLATE : '_default_';

        $smarty->register_function('add_js', 'cmsSmartyAddJS');
        $smarty->register_function('add_css', 'cmsSmartyAddCSS');
        $smarty->register_function('comments', 'cmsSmartyComments');
        $smarty->assign('LANG', $_LANG);

        return $smarty;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function initSmartyModule(){

        $smarty = $this->getSmartyObj();

        $smarty->template_dir = is_dir(TEMPLATE_DIR.'modules') ? TEMPLATE_DIR.'modules' : DEFAULT_TEMPLATE_DIR.'modules';

        return $smarty;

    }

    // CONFIGS //////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает массив с настройками модуля
     * @param int $module_id
     * @return array
     */
    public function loadModuleConfig($module_id){

        $config = array();

        if (isset($this->module_configs[$module_id])) { return $this->module_configs[$module_id]; }

        $config_yaml = cmsDatabase::getInstance()->get_field('cms_modules', "id='{$module_id}'", 'config');

        $config = self::yamlToArray($config_yaml);

        $this->cacheModuleConfig($module_id, $config);

        return $config;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Сохраняет настройки модуля в базу
     * @param string $plugin_name
     * @param array $config
     * @return bool
     */
    public function saveModuleConfig($module_id, $config) {

        $inDB = cmsDatabase::getInstance();

        //конвертируем массив настроек в YAML
		$config_yaml = $inDB->escape_string(self::arrayToYaml($config));

        //обновляем модуль в базе
        $update_query  = "UPDATE cms_modules
                          SET config='{$config_yaml}'
                          WHERE id = '{$module_id}'";

        $inDB->query($update_query);

        //настройки успешно сохранены
        return true;

    }

    /**
     * Кэширует конфигурацию модуля на время выполнения скрипта
     * @param int $module_id
     * @param array $config
     * @return boolean
     */
    public function cacheModuleConfig($module_id, $config){
        $this->module_configs[$module_id] = $config;
        return true;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает кофигурацию компонента в виде массива
     * @param string $plugin
     * @return float
     */
    public function loadComponentConfig($component){

        if (isset($this->component_configs[$component])) { return $this->component_configs[$component]; }

		$config = array();

		foreach ($this->components as $inst_component){
			if($inst_component['link'] == $component){
				$config = self::yamlToArray($inst_component['config']);
				// проверяем настройки по умолчанию в модели
                $is_model_loaded = true;
				if(!class_exists('cms_model_'.$component)){
					$is_model_loaded = self::loadModel($component);
				}
				if($is_model_loaded && method_exists('cms_model_'.$component, 'getDefaultConfig')){
					$default_cfg = call_user_func(array('cms_model_'.$component, 'getDefaultConfig'));
					$config = array_merge($default_cfg, $config);
				}
				$config['component_enabled'] = $inst_component['published'];
				break;
			}
		}

        $this->cacheComponentConfig($component, $config);

        return $config;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Сохраняет настройки компонента в базу
     * @param string $plugin_name
     * @param array $config
     * @return bool
     */
    public function saveComponentConfig($component, $config) {

        $inDB = cmsDatabase::getInstance();

        //конвертируем массив настроек в YAML
		$config_yaml = $inDB->escape_string(self::arrayToYaml($config));

        //обновляем плагин в базе
        $update_query  = "UPDATE cms_components
                          SET config='{$config_yaml}'
                          WHERE link = '{$component}'";

        $inDB->query($update_query);

        //настройки успешно сохранены
        return true;

    }

    /**
     * Кэширует конфигурацию компонента на время выполнения скрипта
     * @param string $component
     * @param array $config
     * @return boolean
     */
    public function cacheComponentConfig($component, $config){
        $this->component_configs[$component] = $config;
        return true;
    }


    // FILTERS //////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает массив с установленными в системе фильтрами
     * @return array or false
     */
    public static function getFilters(){

        if(isset(self::$filters)) { return self::$filters; }

        $inDB   = cmsDatabase::getInstance();
        $sql    = "SELECT * FROM cms_filters WHERE published = 1 ORDER BY id ASC";
        $result = $inDB->query($sql);
        $filters = array();
        if($inDB->num_rows($result)){
            while($f = $inDB->fetch_assoc($result)){
                $filters[$f['id']] = $f;
            }
        }

        self::$filters = $filters;

        return $filters;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public static function processFilters($content) {

        $filters = self::getFilters();

        if ($filters){
            foreach($filters as $id=>$_filter){
                if(self::includeFile('filters/'.$_filter['link'].'/filter.php')){
                    $_filter['link']($content);
                }
            }
        }

        return $content;

    }

    // FILE DOWNLOADS STATS /////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает количество загрузок файла
     * @param string $fileurl
     * @return int
     */
    public static function fileDownloadCount($fileurl){

        $fileurl = cmsDatabase::escape_string($fileurl);

		$hits = cmsDatabase::getInstance()->get_field('cms_downloads', "fileurl = '{$fileurl}'", 'hits');

		return $hits ? $hits : 0;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает тег <img> с иконкой, соответствующей типу файла
     * @param string $filename
     * @return int
     */
    public static function fileIcon($filename){
        $standart_icon = 'file.gif';
        $ftypes[0]['ext'] = 'avi mpeg mpg mp4 flv divx xvid vob';
        $ftypes[0]['icon'] = 'video.gif';
        $ftypes[1]['ext'] = 'mp3 ogg wav';
        $ftypes[1]['icon'] = 'audio.gif';
        $ftypes[2]['ext'] = 'zip rar gz arj 7zip';
        $ftypes[2]['icon'] = 'archive.gif';
        $ftypes[3]['ext'] = 'zip rar gz arj 7zip';
        $ftypes[3]['icon'] = 'archive.gif';
        $ftypes[4]['ext'] = 'gif jpg jpeg png bmp pcx wmf cdr ai';
        $ftypes[4]['icon'] = 'image.gif';
        $ftypes[5]['ext'] = 'pdf djvu';
        $ftypes[5]['icon'] = 'pdf.gif';
        $ftypes[6]['ext'] = 'doc';
        $ftypes[6]['icon'] = 'word.gif';
        $ftypes[7]['ext'] = 'iso mds mdf 000';
        $ftypes[7]['icon'] = 'cd.gif';

        $path_parts = pathinfo($filename);
        $ext = $path_parts['extension'];
        $icon = '';
        foreach($ftypes as $key=>$value){
            if (mb_strstr($ftypes[$key]['ext'], $ext)) { $icon = $ftypes[$key]['icon']; break; }
        }

        if ($icon == '') { $icon = $standart_icon; }

        $html = '<img src="/images/icons/filetypes/'.$icon.'" border="0" />';
        return $html;
    }

    // MENU //////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Перетирает содержание страницы
     * в случае остутствия у группы доступа к текущему пункту меню
     */
    public function checkMenuAccess(){

		$inPage = cmsPage::getInstance();

		$menuid = $this->menuId();

		if (!$this->menu_item) { $this->menu_item = $this->getMenuItem($menuid); }

		$access_list = $this->menu_item['access_list'];

		if (!self::checkContentAccess($access_list) && $menuid != 0) {

            ob_start();

            cmsPage::includeTemplateFile('special/accessdenied.php');

			$inPage->page_body = ob_get_clean();

            return false;

		} else {

			return true;

		}

    }

    /**
     * Проверяет наличие ссылки в пункте меню
     * в случае обнаружения, возвращает его заголовок
     * @param str $link
     * @return string
     */
	public function getLinkInMenu($link){

		if (!$this->menu_item) { return ''; }

		foreach($this->menu_struct as $menu){
			if($menu['link'] == $link){ return $menu['title']; }
		}

		return '';

	}

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает заголовок текущего пункта меню
     * @return string
     */
    public function menuTitle(){

        if ($this->menuId()==1) { return ''; }

        if (!$this->menu_item) { $this->menu_item = $this->getMenuItem($this->menuId()); }

        return $this->menu_item['title'];

    }
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает ссылку на пункт меню
     * @param string $link
     * @param string $linktype
     * @param int $menuid
     * @return string
     */
    public function menuSeoLink($link, $linktype, $menuid=1){
        return $link;
    }
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает название шаблона, назначенного на пункт меню
     * Если используется шаблон по-умолчанию, то возвращает false
     * @param int $menuid
     * @return string or false
     */
    public function menuTemplate($menuid){

        if (!$this->menu_item) { $this->menu_item = $this->getMenuItem($menuid); }

        return $this->menu_item['template'];

    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает true если URI страницы и ссылка активного пункта меню совпали полностью
     * @return boolean
     */
    public function isMenuIdStrict() {

        return $this->is_menu_id_strict;

    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает ID текущего пункта меню
     * @return int
     */
    public function menuId(){

        //если menu_id был определен ранее, то вернем и выйдем
        if ($this->menu_id) { return $this->menu_id; }

        if ($this->url_without_com_name){
            $uri = mb_substr($this->uri, mb_strlen(cmsConfig::getConfig('com_without_name_in_url').'/'));
        } else {
            $uri = $this->uri;
        }

        $uri      = '/'.$uri;
		$real_uri = '/'.$this->real_uri;

        //флаг, показывающий было совпадение URI и ссылки пунта меню
        //полным или частичным
        $is_strict = false;

        //главная страница?
        $menuid = ($uri == '/' ? 1 : 0);
        if ($menuid == 1) {
            $this->is_menu_id_strict = 1;
            return $menuid;
        }

        //перевернем массив меню чтобы перебирать от последнего пункта к первому
        $menu = array_reverse($this->menu_struct);

        //перебираем меню в поисках текущего пункта
        foreach($menu as $item){

            if (!$item['link']) { continue; }

			// uri с учетом имени хоста
			$full_uri = HOST . $uri;

            //полное совпадение ссылки и адреса?
            if (in_array($item['link'], array(urldecode($uri), urldecode($full_uri), urldecode($real_uri)))){
                $menuid = $item['id'];
                $is_strict = true; //полное совпадение
                break;
            }

            //частичное совпадение ссылки и адреса (по началу строки)?
            $uri_first_part = mb_substr(urldecode($uri), 0, mb_strlen($item['link']));
            $real_uri_first_part = mb_substr(urldecode($real_uri), 0, mb_strlen($item['link']));
            if (in_array($item['link'], array($uri_first_part, $real_uri_first_part))){
                $menuid = $item['id'];
                break;
            }

        }

        $this->menu_id           = $menuid;
        $this->is_menu_id_strict = $is_strict;

        return $menuid;

    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает данные о текущем пункте меню
     * @return array
     */
    public function getMenuItem($menuid){

        return $this->menu_struct[$menuid];

    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Загружает всю структуру меню
     */
    private function loadMenuStruct(){

        if (is_array($this->menu_struct)){ return; }

        $inDB = cmsDatabase::getInstance();

        $sql    = "SELECT * FROM cms_menu ORDER BY id ASC";
        $result = $inDB->query($sql);

        if (!$inDB->num_rows($result)){ return; }

        while ($item = $inDB->fetch_assoc($result)){
            $this->menu_struct[$item['id']] = $item;
        }

        return;

    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getMenuStruct() {
        return $this->menu_struct;
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает прямую ссылку на пункт меню по его типу и опции
     * @param string $linktype
     * @param string $linkid
     * @param int $menuid
     * @return string
     */
    public function getMenuLink($linktype, $linkid){

        $inDB = cmsDatabase::getInstance();

        $menulink = '';

        if ($linktype=='component'){
            $menulink = '/'.$linkid;
        }

        if ($linktype=='link'){
            $menulink = $linkid;
        }

        if ($linktype=='category' || $linktype=='content'){
            self::loadModel('content');
            $model = new cms_model_content();
            switch($linktype){
                case 'category': $menulink = $model->getCategoryURL(null, $inDB->get_field('cms_category', "id='{$linkid}'", 'seolink')); break;
                case 'content':  $menulink = $model->getArticleURL(null, $inDB->get_field('cms_content', "id='{$linkid}'", 'seolink')); break;
            }
        }

        if ($linktype=='blog'){
            self::loadModel('blogs');
            $model = new cms_model_blogs();
            $menulink = $model->getBlogURL($inDB->get_field('cms_blogs', "id={$linkid}", 'seolink'));
        }

        if ($linktype=='uccat'){
            $menulink = '/catalog/'.$linkid;
        }

        if ($linktype=='pricecat'){
            $menulink = '/price/'.$linkid;
        }
        if ($linktype=='photoalbum'){
            $menulink = '/photos/'.$linkid;
        }

        return $menulink;

    }

    // LISTS /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает элементы <option> для списка записей из указанной таблицы БД
     * @param string $table
     * @param int $selected
     * @param string $order_by
     * @param string $order_to
     * @param string $where
     * @return html
     */
    public static function getListItems($table, $selected=0, $order_by='id', $order_to='ASC', $where='', $id_field='id', $title_field='title'){
        $inDB = cmsDatabase::getInstance();
        $html = '';
        $sql  = "SELECT {$id_field}, {$title_field} FROM {$table} \n";
        if ($where){
            $sql .= "WHERE {$where} \n";
        }
        $sql .= "ORDER BY {$order_by} {$order_to}";
        $result = $inDB->query($sql) ;

        while($item = $inDB->fetch_assoc($result)){
            if (@$selected==$item[$id_field]){
                $s = 'selected';
            } else {
                $s = '';
            }
            $html .= '<option value="'.htmlspecialchars($item[$id_field]).'" '.$s.'>'.$item[$title_field].'</option>';
        }
        return $html;
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает элементы <option> для списка записей из указанной таблицы БД c вложенными множествами
     * @param string $table таблица
     * @param int $selected id выделенного элемента
     * @param string $differ идентификатор множества (NSDiffer)
     * @param string $need_field выводить только элементы содержащие указанное поле
     * @param int $rootid корневой элемент
     * @return html
     */
    public function getListItemsNS($table, $selected=0, $differ='', $need_field='', $rootid=0, $no_padding=false){
        $inDB = cmsDatabase::getInstance();
        $html = '';
        $nested_sets = $this->nestedSetsInit($table);

        $lookup = "parent_id=0 AND NSDiffer='{$differ}'";

        if(!$rootid) { $rootid = $inDB->get_field($table, $lookup, 'id'); }

        if(!$rootid) { return; }

        $rs_rows = $nested_sets->SelectSubNodes($rootid);

        if ($rs_rows){
            while($node = $inDB->fetch_assoc($rs_rows)){
                if (!$need_field || $node[$need_field]){
                    if (@$selected==$node['id']){
                        $s = 'selected="selected"';
                    } else {
                        $s = '';
                    }
                    if (!$no_padding){
                        $padding = str_repeat('--', $node['NSLevel']) . ' ';
                    } else {
                        $padding = '';
                    }
                    $html .= '<option value="'.htmlspecialchars($node['id']).'" '.$s.'>'.$padding.$node['title'].'</option>';
                }
            }
        }
        return $html;
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает элементы <option> для списка шаблонов
     * @param string $selected
     */
    public static function templatesList($selected=''){
        $dir = PATH.'/templates';
        $tdir = opendir($dir);
		while ($nextfile = readdir($tdir)){
			if(($nextfile!='.')&&($nextfile!='..')&&(is_dir($dir.'/'.$nextfile))&&($nextfile!='.svn')){
				if (@$selected==$nextfile){
					$s = 'selected="selected"';
				} else {
					$s = '';
				}
				echo '<option value="'.$nextfile.'" '.$s.'>'.$nextfile.'</option>';
			}
		}
    }

    /**
     * Возвращает элементы <option> для списка языков
     * @param string $selected
     */
    public static function langList($selected=''){
        $dir = PATH.'/languages';
        $tdir = opendir($dir);
		while ($nextfile = readdir($tdir)){
			if(($nextfile!='.')&&($nextfile!='..')&&(is_dir($dir.'/'.$nextfile))&&($nextfile!='.svn')){
				if (@$selected == $nextfile){
					$s = 'selected="selected"';
				} else {
					$s = '';
				}
				echo '<option value="'.$nextfile.'" '.$s.'>'.$nextfile.'</option>';
			}
		}
    }

    // RATINGS  //////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Регистрирует тип цели для рейтингов в базе
     * @param string $target
     * @param string $component
     * @param boolean $is_user_affect
     * @param int $user_weight
     * @return boolean
     */
    public static function registerRatingsTarget($target, $component, $target_title, $is_user_affect=true, $user_weight=1, $target_table='') {

        $is_user_affect = (int)$is_user_affect;

        $sql  = "INSERT IGNORE INTO cms_rating_targets (target, component, is_user_affect, user_weight, target_table, target_title)
                 VALUES ('$target', '$component', '$is_user_affect', '$user_weight', '$target_table', '$target_title')";

        cmsDatabase::getInstance()->query($sql);

        return true;

    }

    /**
     * Удаляет все рейтинги для указанной цели
     * @param string $target
     * @param int $item_id
     * @return boolean
     */
    public static function deleteRatings($target, $item_id){

        $inDB = cmsDatabase::getInstance();

        $sql  = "DELETE FROM cms_ratings WHERE target='{$target}' AND item_id='{$item_id}'";
        $inDB->query($sql);

        $sql  = "DELETE FROM cms_ratings_total WHERE target='{$target}' AND item_id='{$item_id}'";
        $inDB->query($sql);

        return true;

    }


    // COMMENTS //////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Подключает комментарии
     */
    public static function includeComments(){
        include_once PATH."/components/comments/frontend.php";
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Регистрирует тип цели для комментариев в базе
     * @param string $target - Цель
     * @param string $component - Компонент
     * @param string $title - Название цели во множ.числе (например "Статьи")
     * @param string $target_table - таблица, где хранятся комментируемые записи
     * @param string $title - название цели в родительном падеже (например "вашей статьи")
     */
    public static function registerCommentsTarget($target, $component, $title, $target_table, $subj) {

        $sql  = "INSERT IGNORE INTO cms_comment_targets (target, component, title, target_table, subj)
                 VALUES ('$target', '$component', '$title', '$target_table', '$subj')";

        cmsDatabase::getInstance()->query($sql);

        return true;

    }

    public static function getCommentsTargets() {

        return cmsDatabase::getInstance()->get_table('cms_comment_targets', 'id>0', '*');

    }

    /**
     * Удаляет все комментарии для указанной цели
     * @param string $target
     * @param int $target_id
     * @return boolean
     */
    public static function deleteComments($target, $target_id){

        $inDB = cmsDatabase::getInstance();

		$comments = $inDB->get_table('cms_comments', "target='{$target}' AND target_id='{$target_id}'", 'id');
        if (!$comments){ return false; }

		self::loadClass('actions');

		foreach($comments as $comment){
			cmsActions::removeObjectLog('add_comment', $comment['id']);
		}

		$inDB->delete('cms_comments', "target='{$target}' AND target_id='{$target_id}'");;

        return true;

    }

    /**
     * Возвращает количество комментариев для указанной цели
     * @param string $target
     * @param int $target_id
     * @return int
     */
    public static function getCommentsCount($target, $target_id){

        if (self::getInstance()->isComponentInstalled('comments')){

	        return cmsDatabase::getInstance()->rows_count('cms_comments', "target = '$target' AND target_id = '$target_id' AND published = 1");

        } else { return 0; }

    }

    // UTILS ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Проверяет, установлен ли компонент
     * @param string $component
     * @return bool
     */
    public function isComponentInstalled($component){

		$is_installed = false;

		foreach ($this->components as $inst_component){
		   if($inst_component['link'] == $component){
			  $is_installed = true; break;
		   }
		}

        return $is_installed;
    }

    public function isModuleInstalled($module) {
        return (bool)cmsDatabase::getInstance()->rows_count('cms_modules', "content='{$module}' AND user=0", 1);
    }

    // DATE METHODS /////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Переводит название месяца в дате на русский
     * @param string $datestr
     * @return string
     */
    public function getRusDate($datestr){
        $datestr = str_replace('January', 'Январь', $datestr);
        $datestr = str_replace('February', 'Февраль', $datestr);
        $datestr = str_replace('March', 'Март', $datestr);
        $datestr = str_replace('April', 'Апрель', $datestr);
        $datestr = str_replace('May', 'Май', $datestr);
        $datestr = str_replace('June', 'Июнь', $datestr);
        $datestr = str_replace('July', 'Июль', $datestr);
        $datestr = str_replace('August', 'Август', $datestr);
        $datestr = str_replace('September', 'Сентябрь', $datestr);
        $datestr = str_replace('October', 'Октябрь', $datestr);
        $datestr = str_replace('November', 'Ноябрь', $datestr);
        $datestr = str_replace('December', 'Декабрь', $datestr);
        return $datestr;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	static function dateFormat($date, $is_full_m = true, $is_time=false, $is_now_time = true){

        $inConf = cmsConfig::getInstance();

	    global $_LANG;

		// формируем входную $date с учетом смещения
        $date = date('Y-m-d H:i:s', strtotime($date)+($inConf->timediff*3600));

		// сегодняшняя дата
		$today     = date('Y-m-d', strtotime(date('Y-m-d H:i:s'))+($inConf->timediff*3600));
		// вчерашняя дата
		$yesterday = date('Y-m-d', strtotime(date('Y-m-d H:i:s'))-(86400)+($inConf->timediff*3600));

		// получаем значение даты и времени
		list($day, $time) = explode(' ', $date);
		switch( $day ) {
		// Если дата совпадает с сегодняшней
		case $today:
					$result = ''.$_LANG['TODAY'].'';
					if ($is_now_time && $time) {
						list($h, $m, $s)  = explode(':', $time);
						$result .= ' '.$_LANG['IN'].' '.$h.':'.$m;
					}
					break;
		//Если дата совпадает со вчерашней
		case $yesterday:
					$result = ''.$_LANG['YESTERDAY'].'';
					if ($is_now_time && $time) {
						list($h, $m, $s)  = explode(':', $time);
						$result .= ' '.$_LANG['IN'].' '.$h.':'.$m;
					}
					break;
			default: {
				// Разделяем отображение даты на составляющие
				list($y, $m, $d)  = explode('-', $day);
				$month_full_str = array(
					''.$_LANG['MONTH_01'].'', ''.$_LANG['MONTH_02'].'', ''.$_LANG['MONTH_03'].'',
					''.$_LANG['MONTH_04'].'', ''.$_LANG['MONTH_05'].'', ''.$_LANG['MONTH_06'].'',
					''.$_LANG['MONTH_07'].'', ''.$_LANG['MONTH_08'].'', ''.$_LANG['MONTH_09'].'',
					''.$_LANG['MONTH_10'].'', ''.$_LANG['MONTH_11'].'', ''.$_LANG['MONTH_12'].''
				 );
				 $month_short_str = array(
					''.$_LANG['MONTH_01_SHORT'].'', ''.$_LANG['MONTH_02_SHORT'].'', ''.$_LANG['MONTH_03_SHORT'].'',
					''.$_LANG['MONTH_04_SHORT'].'', ''.$_LANG['MONTH_05_SHORT'].'', ''.$_LANG['MONTH_06_SHORT'].'',
					''.$_LANG['MONTH_07_SHORT'].'', ''.$_LANG['MONTH_08_SHORT'].'', ''.$_LANG['MONTH_09_SHORT'].'',
					''.$_LANG['MONTH_10_SHORT'].'', ''.$_LANG['MONTH_11_SHORT'].'', ''.$_LANG['MONTH_12_SHORT'].''
				 );
				 $month_int = array(
					'01', '02', '03',
					'04', '05', '06',
					'07', '08', '09',
					'10', '11', '12'
				 );
				 $day_int = array(
					'01', '02', '03',
					'04', '05', '06',
					'07', '08', '09'
				 );
				 $day_norm = array(
					'1', '2', '3',
					'4', '5', '6',
					'7', '8', '9'
				 );
				// Замена числового обозначения месяца на словесное (склоненное в падеже)
				if ($is_full_m){
					$m = str_replace($month_int, $month_full_str, $m);
				}else{
					$m = str_replace($month_int, $month_short_str, $m);
				}
				// Замена чисел 01 02 на 1 2
				$d = str_replace($day_int, $day_norm, $d);
				// Формирование окончательного результата
				$result = $d.' '.$m.' '.$y;
				if( $is_time && $time)   {
					// Получаем отдельные составляющие времени
					// Секунды нас не интересуют
					list($h, $m, $s)  = explode(':', $time);
					$result .= ' '.$_LANG['IN'].' '.$h.':'.$m;
				}
			}
		}
		 return $result;
	}

    /**
     * Возвращает день недели по дате
     * @param string $date
     * @return string
     */
    public static function dateToWday($date){

	    global $_LANG;

        $date = date('Y-m-d H:i:s', strtotime($date)+(cmsConfig::getConfig('timediff')*3600));

		// получаем значение даты и времени
		list($day, $time)  = explode(' ', $date);
		list($h, $min, $s) = explode(':', $time);
		list($y, $m, $d)   = explode('-', $day);

		$days_week = array($_LANG['SUNDAY'], $_LANG['MONDAY'], $_LANG['TUESDAY'], $_LANG['WEDNESDAY'], $_LANG['THURSDAY'], $_LANG['FRIDAY'], $_LANG['SATURDAY']);

		$arr  = getdate(mktime($h, $min, $s, $m, $d, $y));

		$wday = $days_week[$arr['wday']];

		return $wday;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public static function initAutoGrowText($element_id){
        $inPage = cmsPage::getInstance();
        $inPage->addHeadJS('includes/jquery/autogrow/jquery.autogrow.js');
        $inPage->addHead('<script type="text/javascript">$(document).ready (function() {$(\''.$element_id.'\').autogrow(); });</script>');
        return true;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public static function getDateForm($element, $seldate=false, $day_default=1, $month_default=1, $year_default=1980){

        $year_from = 1950;
        $year_to = intval(date('Y'));

        $html = '';

        if(@$seldate){
            $parts = explode('-', $seldate);
            if ($parts[2]){
                $day_default = $parts[2];
            }
            if ($parts[1]){
                $month_default = $parts[1];
            }
            if ($parts[0]){
                $year_default = $parts[0];
            }
        }

        $html .= '<select name="'.$element.'[day]">' . "\n";
        for($day=1; $day<=31;$day++){
            if ($day<10){ $day = '0'.$day; }

            if (intval($day)==intval($day_default)){
                $html .= '<option value="'.$day.'" selected="selected">'.$day.'</option>'. "\n";
            } else {
                $html .= '<option value="'.$day.'">'.$day.'</option>'. "\n";
            }
        }
        $html .= '</select>'. "\n";

        $months = array();
        $months['00'] = 'Январь';
        $months['01'] = 'Февраль';
        $months['02'] = 'Март';
        $months['03'] = 'Апрель';
        $months['04'] = 'Май';
        $months['05'] = 'Июнь';
        $months['06'] = 'Июль';
        $months['07'] = 'Август';
        $months['08'] = 'Сентябрь';
        $months['09'] = 'Октябрь';
        $months['10'] = 'Ноябрь';
        $months['11'] = 'Декабрь';

        $html .= '<select name="'.$element.'[month]">' . "\n";
        for($month=0; $month<12; $month++){
            if ($month<10){ $month = '0'.$month; }

            if ((intval($month)+1)==intval($month_default)){
                $html .= '<option value="'.($month+1).'" selected="selected">'.$months[$month].'</option>'. "\n";
            } else {
                $html .= '<option value="'.($month+1).'">'.$months[$month].'</option>'. "\n";
            }
        }
        $html .= '</select>'. "\n";

        $html .= '<select name="'.$element.'[year]">'. "\n";
        for($year=$year_from; $year<=$year_to;$year++){
            if ($year == $year_default){
                $html .= '<option value="'.$year.'" selected="selected">'.$year.'</option>'. "\n";
            } else {
                $html .= '<option value="'.$year.'">'.$year.'</option>'. "\n";
            }
        }
        $html .= '</select>'. "\n";

        return $html;
    }

    // ACCESS ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * ====== DEPRECATED =========
     */
    public static function userIsAdmin($userid){

        return cmsUser::userIsAdmin($userid);

    }
    /**
     * ====== DEPRECATED =========
     */
    public static function checkAdminAccess(){
		return cmsUser::getAdminAccess();
    }
    /**
     * ====== DEPRECATED =========
     */
    public static function userIsEditor($userid){
        return cmsUser::userIsEditor($userid);
    }
    /**
     * ====== DEPRECATED =========
     */
    public function isAdminCan($access_type, $access_list){
        return cmsUser::isAdminCan($access_type, $access_list);
    }
    /**
     * ====== DEPRECATED =========
     */
    public function isUserCan($access_type){
		return cmsUser::isUserCan($access_type);
    }
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Проверяет права доступа к чему-либо
     * @return bool
     */
    public static function checkUserAccess($content_type, $content_id){

        $inUser = cmsUser::getInstance();

		if ($inUser->is_admin) { return true; }

		$access = cmsDatabase::getInstance()->get_table('cms_content_access', "content_type = '$content_type' AND content_id = '$content_id'", 'group_id');

		if (!$access || !is_array($access)) { return true; }

		return in_array(array('group_id' => $inUser->group_id), $access);

    }
    /**
     * Устанавливает права доступа
     * @return bool
     */
    public static function setAccess($id, $showfor_list, $content_type){

        if (!sizeof($showfor_list)){ return true; }

        self::clearAccess($id, $content_type);

        foreach ($showfor_list as $key=>$value){
            cmsDatabase::getInstance()->insert('cms_content_access', array('content_id'=>$id, 'content_type'=>$content_type, 'group_id'=>$value));
        }

        return true;
    }
    /**
     * Очищает права доступа
     * @return bool
     */
    public static function clearAccess($id, $content_type){

        return cmsDatabase::getInstance()->delete('cms_content_access', "content_id = '$id' AND content_type = '$content_type'");

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public static function checkAccessByIp($allow_ips = ''){

		$inUser = cmsUser::getInstance();

		if(!$inUser->ip) { return false; }

		$allow_ips = str_replace(' ', '', $allow_ips);
		if (!$allow_ips) { return true; }
		$allow_ips = explode(',', $allow_ips);

		return in_array($inUser->ip, $allow_ips);

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Проверяет доступ (модуля, меню) к группе пользователя
     * @param $access_list yaml или массив
     * @return bool
     */
    public static function checkContentAccess($access_list){

		$inUser = cmsUser::getInstance();

		// если $access_list пуста, то считаем что доступ для всех
		if (!$access_list) { return true; }

		// администраторам всегда показываем модуль
		if ($inUser->is_admin) { return true; }

        // можем передавать как YAML так и сформированный массив
        $access_list = is_array($access_list) ? $access_list : self::yamlToArray($access_list);

		return in_array($inUser->group_id, $access_list);

    }

    // SECURITY /////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public static function strClear($input, $strip_tags=true){

        if(is_array($input)){

            foreach ($input as $key=>$string) {
                $value[$key] = self::strClear($string, $strip_tags);
            }

            return $value;

        }

        $string = trim($input);
        //Если magic_quotes_gpc = On, сначала убираем экранирование
        $string = (@get_magic_quotes_gpc()) ? stripslashes($string) : $string;
        $string = rtrim($string, ' \\');
        if ($strip_tags) {
            $string = mysql_real_escape_string(strip_tags($string));
        }
        return $string;

    }
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Проверяет значение сессии в POST и сравнивает с текущим
     * @return bool
     */
    public static function validateForm(){

		if(!@$_POST['csrf_token']) { return false; }
		if($_POST['csrf_token'] == cmsUser::getCsrfToken()) { return true; }
        return false;

    }
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Удаляет теги script iframe style meta
     * @param string $string
     * @return str
     */
    public static function badTagClear($string){

		$my_domen_regexp = str_replace('.', '\.', HOST);
		$my_domen_regexp = str_replace('/', '\/', $my_domen_regexp);

        $bad_tags = array (
            "'<script[^>]*?>.*?</script>'siu",
            "'<style[^>]*?>.*?</style>'siu",
            "'<meta[^>]*?>'siu",
            '/<iframe.*?src=(?!"http:\/\/www\.youtube\.com\/embed\/|"http:\/\/vk\.com\/video_ext\.php\?|"'.$my_domen_regexp.').*?>.*?<\/iframe>/iu',
            '/<iframe.*>.+<\/iframe>/iu'
        );

        return self::htmlCleanUp(preg_replace($bad_tags, '', $string));

    }
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Очищает html текст
     * @param string $text
     * @return string
     */
    public static function htmlCleanUp($text){

		if(!isset(self::$jevix)){
			self::loadClass('jevix');
			self::$jevix = new Jevix();
			// Устанавливаем разрешённые теги. (Все не разрешенные теги считаются запрещенными.)
			self::$jevix->cfgAllowTags(array('p','a','img','i','b','u','s','video','em','strong','nobr','li','ol','ul','div','abbr','sup','sub','acronym','h1','h2','h3','h4','h5','h6','br','hr','pre','code','object','param','embed','blockquote','iframe','span','input'));
			// Устанавливаем коротие теги. (не имеющие закрывающего тега)
			self::$jevix->cfgSetTagShort(array('br','img', 'hr', 'input'));
			// Устанавливаем преформатированные теги. (в них все будет заменятся на HTML сущности)
			self::$jevix->cfgSetTagPreformatted(array('code','video'));
			// Устанавливаем теги, которые необходимо вырезать из текста вместе с контентом.
			self::$jevix->cfgSetTagCutWithContent(array('script', 'style', 'meta'));
			// Устанавливаем разрешённые параметры тегов. Также можно устанавливать допустимые значения этих параметров.
			self::$jevix->cfgAllowTagParams('input', array('type'=>'#text', 'style', 'onclick' => '#text', 'value' => '#text'));
			self::$jevix->cfgAllowTagParams('a', array('title', 'href', 'style', 'rel' => '#text', 'name' => '#text'));
			self::$jevix->cfgAllowTagParams('img', array('src' => '#text', 'style', 'alt' => '#text', 'title', 'align' => array('right', 'left', 'center'), 'width' => '#int', 'height' => '#int', 'hspace' => '#int', 'vspace' => '#int'));
			self::$jevix->cfgAllowTagParams('div', array('class' => '#text', 'style','align' => array('right', 'left', 'center')));
			self::$jevix->cfgAllowTagParams('object', array('width' => '#int', 'height' => '#int', 'data' => '#text'));
			self::$jevix->cfgAllowTagParams('param', array('name' => '#text', 'value' => '#text'));
			self::$jevix->cfgAllowTagParams('embed', array('src' => '#image','type' => '#text','allowscriptaccess' => '#text','allowFullScreen' => '#text','width' => '#int','height' => '#int','flashvars'=> '#text','wmode'=> '#text'));
			self::$jevix->cfgAllowTagParams('acronym', array('title'));
			self::$jevix->cfgAllowTagParams('abbr', array('title'));
			self::$jevix->cfgAllowTagParams('span', array('style'));
			self::$jevix->cfgAllowTagParams('li', array('style'));
			self::$jevix->cfgAllowTagParams('p', array('style'));
			self::$jevix->cfgAllowTagParams('iframe', array('width' => '#int', 'frameborder' => '#int', 'allowfullscreen' => '#int', 'height' => '#int', 'src' => array('#domain'=>array('youtube.com','vimeo.com','vk.com', self::getHost()))));
			// Устанавливаем параметры тегов являющиеся обязательными. Без них вырезает тег оставляя содержимое.
			self::$jevix->cfgSetTagParamsRequired('img', 'src');
			self::$jevix->cfgSetTagParamsRequired('a', 'href');
			// Устанавливаем теги которые может содержать тег контейнер
			self::$jevix->cfgSetTagChilds('ul',array('li'),false,true);
			self::$jevix->cfgSetTagChilds('ol',array('li'),false,true);
			self::$jevix->cfgSetTagChilds('object','param',false,true);
			self::$jevix->cfgSetTagChilds('object','embed',false,false);
			// Если нужно оставлять пустые не короткие теги
			self::$jevix->cfgSetTagIsEmpty(array('param','embed','a','iframe'));
			self::$jevix->cfgSetTagParamDefault('embed','wmode','opaque',true);
			// Устанавливаем автозамену
			self::$jevix->cfgSetAutoReplace(array('+/-', '(c)', '(с)', '(r)', '(C)', '(С)', '(R)'), array('±', '©', '©', '®', '©', '©', '®'));
			// выключаем режим замены переноса строк на тег <br/>
			self::$jevix->cfgSetAutoBrMode(false);
			// выключаем режим автоматического определения ссылок
			self::$jevix->cfgSetAutoLinkMode(false);
			// Отключаем типографирование в определенном теге
			self::$jevix->cfgSetTagNoTypography('code','video','object','iframe');
		}

        return self::$jevix->parse($text,$errors);

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Проверяет совпадения кода каптчи с кодом введенным пользователем
     * @param string $code
     * @return bool
     */
    public static function checkCaptchaCode($code){

        if(!isset($_SESSION['captcha_keystring']) || !isset($code)) { return false; }
        if(!$_SESSION['captcha_keystring'] || !$code) { return false; }

        $real_code = $_SESSION['captcha_keystring'];
        unset($_SESSION['captcha_keystring']);

        return ($real_code === $code);

    }

    // MAIL ROUTINES ////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Создает и отправляет письмо электронной почтой
     * @param string $email
     * @param string $subject
     * @param string $message
     * @param string $content
     */
    public function mailText($email, $subject, $message, $content='text/plain'){

        $inConf = cmsConfig::getInstance();

        $message = preg_replace('#([\S]{70})#u', '$1', $message);

		if ($content == 'text/html') {
			$this->sendMail($inConf->sitemail, $inConf->sitename, $email, $subject, $message, 1);
		} else {
			$this->sendMail($inConf->sitemail, $inConf->sitename, $email, $subject, $message);
		}

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    private function createMail( $from='', $fromname='', $subject, $body ) {

        $inConf = cmsConfig::getInstance();

        self::includeFile('includes/phpmailer/phpmailer.php');

        $mail = new mosPHPMailer();

        $mail->PluginDir = PATH . '/includes/phpmailer/';
        $mail->SetLanguage( 'en', PATH . '/includes/phpmailer/language/' );
        $mail->CharSet  = 'utf-8';
        $mail->IsMail();
        $mail->From     = $from ? $from : $inConf->sitemail;
        $mail->FromName = $fromname ? $fromname : $inConf->sitename;
        $mail->Mailer   = $inConf->mailer;

        // Add smtp values if needed
        if ( $inConf->mailer == 'smtp' ) {
            $mail->SMTPAuth = $inConf->smtpauth;
            $mail->Username = $inConf->smtpuser;
            $mail->Password = $inConf->smtppass;
            $mail->Host     = $inConf->smtphost;
        } else

        // Set sendmail path
        if ( $inConf->mailer == 'sendmail' ) {
            if (isset($inConf->sendmail)){
                $mail->Sendmail = $inConf->sendmail;
            } else {
                $mail->Sendmail = '/usr/sbin/sendmail';
            }
        } // if

        $mail->Subject = $subject;
        $mail->Body    = $body;

        return $mail;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    private function sendMail( $from, $fromname, $recipient, $subject, $body, $mode=0, $cc=NULL, $bcc=NULL, $attachment=NULL, $replyto=NULL, $replytoname=NULL ) {
        $inConf = cmsConfig::getInstance();

        // Allow empty $from and $fromname settings (backwards compatibility)
        if ($from == '') { $from = $inConf->sitemail;	}
        if ($fromname == '') { $fromname = $inConf->sitename; }

        $mail = $this->createMail( $from, $fromname, $subject, $body );

        // activate HTML formatted emails
        if ( $mode ) {	$mail->IsHTML(true); }

        if (is_array( $recipient )) {
            foreach ($recipient as $to) {
                $mail->AddAddress( $to );
            }
        } else { $mail->AddAddress( $recipient ); }

        if (isset( $cc )) {
            if (is_array( $cc )) {
                foreach ($cc as $to) {
                    $mail->AddCC($to);
                }
            } else { $mail->AddCC($cc);	}
        }
        if (isset( $bcc )) {
            if (is_array( $bcc )) {
                foreach ($bcc as $to) {
                    $mail->AddBCC( $to );
                }
            } else {
                $mail->AddBCC( $bcc );
            }
        }
        if ($attachment) {
            if (is_array( $attachment )) {
                foreach ($attachment as $fname) {
                    $mail->AddAttachment( $fname[0], $fname[1] );
                }
            } else {
                $mail->AddAttachment($attachment);
            }
        }
        //Important for being able to use mosMail without spoofing...
        if ($replyto) {
            if (is_array( $replyto )) {
                reset( $replytoname );
                foreach ($replyto as $to) {
                    $toname = ((list( $key, $value ) = each( $replytoname )) ? $value : '');
                    $mail->AddReplyTo( $to, $toname );
                }
            } else {
                $mail->AddReplyTo($replyto, $replytoname);
            }
        }

        $mailssend = $mail->Send();

        return $mailssend;
    }

    // UC SEARCH ////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function getUCSearchLink($cat_id, $menuid, $field_id, $text){
        $html='';
        $text = html_entity_decode($text);
        $text = strip_tags($text);
        $text = trim($text);
        if (!mb_strstr($text, ',')){
            $html .= '<a href="/catalog/'.$cat_id.'/find/'.urlencode($text).'">'.$text.'</a>';
        } else {
            $text = str_replace(', ', ',', $text);
            $words = array();
            $words = explode(',', $text);

            $n=0;
            foreach($words as $key=>$value){
                $n++;
                $value = strip_tags($value);
                $value = str_replace("\r", '', $value);
                $value = str_replace("\n", '', $value);
                $value = trim($value);

                $html .= '<a href="/catalog/'.$cat_id.'/find/'.urlencode($value).'">'.$value.'</a>';
                if ($n<sizeof($words)) { $html .= ', '; } else { $html .= '.'; }
            }

        }
        return $html;
    }

    // AJAX IMAGE UPLOAD ////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Добавляет запись о загружаемом изображении
     * @return bool
     */
    public static function registerUploadImages($target_id, $target, $fileurl, $component){

        return cmsDatabase::getInstance()->insert('cms_upload_images', array('target_id'=>$target_id,
																			  'session_id'=>session_id(),
																			  'fileurl'=>$fileurl,
																			  'component'=>$component,
																			  'target'=>$target));

    }
    /**
     * Устанавливает ID места назначения к загруженному изображению
     * @return bool
     */
    public static function setIdUploadImage($target, $target_id){

		$inDB = cmsDatabase::getInstance();
		$sid=session_id();

        return $inDB->query("UPDATE cms_upload_images SET target_id = '{$target_id}' WHERE session_id = '{$sid}' AND target = '{$target}' AND target_id = 0");

    }
    /**
     * Возвращает количество загруженных изображений для текущей сессии данного места назначения
     * @return int
     */
    public static function getTargetCount($target_id=0){

		$sid       = session_id();
        $target_id = (int)$target_id;

        return cmsDatabase::getInstance()->rows_count('cms_upload_images', "target_id = '{$target_id}' AND session_id = '{$sid}'");

    }
    /**
     * Удаляет все изображения места их назначения
     * @return bool
     */
    public static function deleteUploadImages($target_id, $target){
        $inDB = cmsDatabase::getInstance();
        $rs = $inDB->query("SELECT * FROM cms_upload_images WHERE target_id = '$target_id' AND target='$target'");
        if ($inDB->num_rows($rs)){
            while($file = $inDB->fetch_assoc($rs)){
                $filename = PATH.$file['fileurl'];
                if (file_exists($filename)){ @unlink($filename); }
                $inDB->query("DELETE FROM cms_upload_images WHERE id = '{$file['id']}'");
            }
        }
        return true;
    }
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public static function parseSmiles($text, $parse_bbcode=false){

        $_parse_text = self::callEvent('GET_PARSER', array('return'=>'','text'=>$text,'parse_bbcode'=>$parse_bbcode));
        if($_parse_text['return']){ return $_parse_text['return']; }

		self::includeFile('includes/bbcode/bbcode.lib.php');

        if (!$parse_bbcode){
            $text = bbcode::autoLink($text);
        } else {
            //parse bbcode
            $bb = new bbcode($text);
            $text = $bb->get_html();
			// конвертируем в смайлы в изображения
			$text = $bb->replaceEmotionToSmile($text);
        }

	    return $text;

    }

    // PAGE CACHE   /////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Проверяет наличие кэша для указанного контента
     * @param string $target
     * @param int $target_id
     * @param int $cachetime
     * @param string $cacheint
     * @return bool
     */
    public static function isCached($target, $target_id, $cachetime=1, $cacheint='MINUTES'){

        $where     = "target='$target' AND target_id='$target_id' AND cachedate >= DATE_SUB(NOW(), INTERVAL $cachetime $cacheint)";
        $cachefile = cmsDatabase::getInstance()->get_field('cms_cache', $where, 'cachefile');

        if ($cachefile){

            $cachefile = PATH.'/cache/'.$cachefile;
            if (file_exists($cachefile)){
                return true;
            } else {
                return false;
            }

        } else {

            self::deleteCache($target, $target_id);
            return false;

        }

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает кэш указанного контента
     * @param string $target
     * @param int $target_id
     * @return html
     */
    public static function getCache($target, $target_id){

		$cachefile = cmsDatabase::getInstance()->get_field('cms_cache', "target='$target' AND target_id='$target_id'", 'cachefile');

		if($cachefile){

			$cachefile = PATH.'/cache/'.$cachefile;

			if (file_exists($cachefile)){
				$cache = file_get_contents($cachefile);
				return $cache;
			}

		}

        return false;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Сохраняет переданный кэш указанного контента
     * @param string $target
     * @param int $target_id
     * @param string $html
     * @return bool
     */
    public static function saveCache($target, $target_id, $html){

        $filename = md5($target.$target_id).'.html';

        $sql = "INSERT DELAYED INTO cms_cache (target, target_id, cachedate, cachefile)
                VALUES ('$target', $target_id, NOW(), '$filename')";

        cmsDatabase::getInstance()->query($sql);

        $filename = PATH.'/cache/'.$filename;

        file_put_contents($filename, $html);

        return true;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Удаляет кэш указанного контента
     * @param string $target
     * @param int $target_id
     * @return bool
     */
    public static function deleteCache($target, $target_id){

        cmsDatabase::getInstance()->query("DELETE FROM cms_cache WHERE target='$target' AND target_id='$target_id'");

        $oldcache = PATH.'/cache/'.md5($target.$target_id).'.html';

        if (file_exists($oldcache)) { @unlink($oldcache); }

        return true;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public static function strToURL($str, $is_cyr = false){

        $str    = str_replace(' ', '-', mb_strtolower(trim($str)));
        $string = rtrim(preg_replace ('/[^a-zA-Zа-яёА-ЯЁ0-9\-]/iu', '-', $str), '-');

        while(mb_strstr($string, '--')){ $string = str_replace('--', '-', $string); }

		if(!$is_cyr){
			$ru_en = array(
							'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d',
							'е'=>'e','ё'=>'yo','ж'=>'zh','з'=>'z',
							'и'=>'i','й'=>'i','к'=>'k','л'=>'l','м'=>'m',
							'н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s',
							'т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c',
							'ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y',
							'ь'=>'','э'=>'ye','ю'=>'yu','я'=>'ja'
						  );

			foreach($ru_en as $ru=>$en){
				$string = preg_replace('/(['.$ru.']+?)/iu', $en, $string);
			}
		}

        if (!$string){ $string = 'untitled'; }
        if (is_numeric($string)){ $string .= 'untitled'; }

        return $string;

	}

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает seolink для ns категории
     * подразумевается, что категория существующая (созданная)
     * @param array $category
     * @param str $table
     * @param bool $is_cyr
     * @return str
     */
    public static function generateCatSeoLink($category, $table, $is_cyr = false, $differ=''){

		$inDB = cmsDatabase::getInstance();

        $seolink = '';

		$cat = $inDB->getNsCategory($table, $category['id']);
		if(!$cat) { return $seolink;}

		$path_list = $inDB->getNsCategoryPath($table, $cat['NSLeft'], $cat['NSRight'], 'id, title, NSLevel, seolink, url', $differ);
        if (!$path_list){ return $seolink; }

		$path_list[count($path_list)-1] = array_merge($path_list[count($path_list)-1], $category);

		foreach($path_list as $pcat){
			$seolink .= self::strToURL((@$pcat['url'] ? $pcat['url'] : $pcat['title']), $is_cyr) . '/';
		}

		$seolink = rtrim($seolink, '/');

        $is_exists = $inDB->rows_count($table, "seolink='{$seolink}' AND id <> {$category['id']}");

        if ($is_exists) { $seolink .= '-' . $cat['id']; }

        return $seolink;

    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public static function halt($message=''){
        die((string)$message);
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	public static function spellCount($num, $one, $two, $many) {

		if ($num%10==1 && $num%100!=11){
			$str = $one;
		}elseif($num%10>=2 && $num%10<=4 && ($num%100<10 || $num%100>=20)){
			$str = $two;
		}else{
			$str = $many;
		}

		return $num.' '.$str;

	}
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Выводит словами разницу между текущей и указанной датой
     * @param string $date
     * @return string
     */
    public static function dateDiffNow($date) {

		global $_LANG;

        $now  = time();
        $date = strtotime($date);

        if ($date == 0) { return $_LANG['MANY_YARS']; }

        $diff_sec   = $now - $date;

        $diff_day   = round($diff_sec/60/60/24);
        $diff_hour  = round(($diff_sec/60/60) - ($diff_day*24));
        $diff_min   = round(($diff_sec/60)-($diff_hour*60));

        //Выводим разницу в днях
        if ($diff_day > 0){
            return self::spellCount($diff_day, $_LANG['DAY1'], $_LANG['DAY2'], $_LANG['DAY10']);
        }

        //Выводим разницу в часах
        if ($diff_hour > 0){
            return self::spellCount($diff_hour, $_LANG['HOUR1'], $_LANG['HOUR2'], $_LANG['HOUR10']);
        }

        //Выводим разницу в минутах
        if ($diff_min > 0){
            return self::spellCount($diff_min, $_LANG['MINUTU1'], $_LANG['MINUTE2'], $_LANG['MINUTE10']);
        }

        return $_LANG['LESS_MINUTE'];

    }

    public static function jsonOutput($data = array(), $is_header = true){
		if($is_header){
			header('Content-type: application/json; charset=utf-8');
		}
		self::halt(json_encode($data));
    }
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
} //cmsCore

function icms_ucfirst($str) {
    preg_match('/^(.?)(.*)$/us', $str, $matches);
    return mb_strtoupper($matches[1]).$matches[2];
}

function icms_substr_replace($str, $replacement, $offset, $length = NULL){

    $length = ($length === NULL) ? mb_strlen($str) : (int)$length;
    preg_match_all('/./us', $str, $str_array);
    preg_match_all('/./us', $replacement, $replacement_array);

    array_splice($str_array[0], $offset, $length, $replacement_array[0]);

    return implode('', $str_array[0]);

}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////   ====== DEPRECATED =========    ///////////////////////////////////////////
function dbRowsCount($table, $where){
    $inDB = cmsDatabase::getInstance();
    return $inDB->rows_count($table, $where);
}

function dbGetField($table, $where, $field){
    $inDB = cmsDatabase::getInstance();
    return $inDB->get_field($table, $where, $field);
}

function dbGetFields($table, $where, $fields, $order='id ASC'){
    $inDB = cmsDatabase::getInstance();
    return $inDB->get_fields($table, $where, $fields, $order);
}

function dbGetTable($table, $where='', $fields='*'){
    $inDB = cmsDatabase::getInstance();
    return $inDB->get_table($table, $where, $fields);
}

function dbLastId($table){
    $inDB = cmsDatabase::getInstance();
    return $inDB->get_last_id($table);
}

function dbDeleteNS($table, $id){
    cmsDatabase::getInstance()->deleteNS($table, $id);
}

function dbDeleteListNS($table, $list){
    if (is_array($list)){
        foreach($list as $key => $value){
            cmsDatabase::getInstance()->deleteNS($table, $value);
        }
    }
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//Функции ниже оставлены для совместимости со старыми шаблонами
//

function cmsPrintSitename(){
    $inPage = cmsPage::getInstance();
    $inPage->printSitename();
}

function cmsPrintHead(){
    $inPage = cmsPage::getInstance();
    $inPage->printHead();
}

function cmsPathway($separator){
    $inPage = cmsPage::getInstance();
    $inPage->printPathway($separator);
}

function cmsBody(){
    $inPage = cmsPage::getInstance();
    $inPage->printBody();
}

function cmsCountModules($position){
    $inPage = cmsPage::getInstance();
    return $inPage->countModules($position);
}

function cmsModule($position){
    $inPage = cmsPage::getInstance();
    $inPage->printModules($position);
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function cmsSmartyComments($params){

    if (!$params['target']) { return false; }
    if (!$params['target_id']) { return false; }

    cmsCore::includeComments();

    comments($params['target'], $params['target_id'], $params['labels']);

    return;

}

function cmsSmartyNoSpam($email, $filterLevel = 'normal'){
    $email = strrev($email);
    $email = preg_replace('[\.]', '/', $email, 1);
    $email = preg_replace('[@]', '/', $email, 1);

    if($filterLevel == 'low')
    {
        $email = strrev($email);
    }

    return $email;
}

function cmsSmartyAddJS($params){
    cmsPage::getInstance()->addHeadJS($params['file']);
}

function cmsSmartyWysiwyg($params){
    ob_start();
    cmsCore::insertEditor($params['name'], $params['value'], $params['height'], $params['width']);
    return ob_get_clean();
}

function cmsSmartyAddCSS($params){
    cmsPage::getInstance()->addHeadCSS($params['file']);
}

function cmsSmartyProfileURL($params){
    return cmsUser::getProfileURL($params['login']);
}

function cmsSmartyCurrentComponent(){
	return cmsCore::getInstance()->component;
}
function cmsSmartyCurrentTemplate(){
	return TEMPLATE;
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

?>
