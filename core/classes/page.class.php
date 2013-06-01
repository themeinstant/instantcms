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

class cmsPage {

    public $title     = '';
    public $is_ajax   = false;

    public $page_head = array();
    public $page_keys = '';
    public $page_desc = '';
    public $page_body = '';

    public $pathway   = array();

    private $modules;

    public $captcha_count = 1;

    private static $instance;

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    private function __construct() {
        $this->title = $this->homeTitle();
		$this->addHeadJS('includes/jquery/jquery.js');
		$this->addHeadJS('core/js/common.js');
    }

    private function __clone() {}

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self;
        }
        return self::$instance;
    }
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Добавляет указанный тег в <head> страницы
 * @param string $tag
 * @return true
 */
public function addHead($tag){
	if (!in_array($tag, $this->page_head)){ $this->page_head[] = $tag; }
    return true;
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Добавляет тег <script> с указанным путем
 * @param string $src - Первый слеш не требуется
 * @return true
 */
public function addHeadJS($src){

    $js_tag = '<script language="JavaScript" type="text/javascript" src="/'.$src.'"></script>';

	if($this->is_ajax) { echo $js_tag; return true; }

	$this->addHead($js_tag);

    return true;

}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Добавляет тег <link> с указанным путем к CSS-файлу
 * @param string $src - Первый слеш не требуется
 * @return true
 */
public function addHeadCSS($src){

    $css_tag = '<link href="/'.$src.'" rel="stylesheet" type="text/css" />';

	$this->addHead($css_tag);

    return true;

}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Возвращает заголовок главной страницы
 * @return string
 */
public function homeTitle(){
    $inConf = cmsConfig::getInstance();
    if (isset($inConf->hometitle)) {
        if ($inConf->hometitle) { return htmlspecialchars($inConf->hometitle); }
        else { return htmlspecialchars($inConf->sitename); }
    }
    else { return htmlspecialchars($inConf->sitename); }
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Устанавливает заголовок страницы
 */
public function setTitle($title=''){
    $this->title = strip_tags($title);
    return true;
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Устанавливает ключевые слова страницы
 */
public function setKeywords($keywords){
    $this->page_keys = strip_tags($keywords);
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Устанавливает описание страницы
 */
public function setDescription($text){
    $this->page_desc = strip_tags($text);
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Печатает название сайта из конфига
 * @return true
 */
public static function printSitename(){
    echo cmsConfig::getConfig('sitename');
    return true;
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Печатает головную область страницы
 */
public function printHead(){

    $inCore = cmsCore::getInstance();
    $inConf = cmsConfig::getInstance();

    $this->page_head = cmsCore::callEvent('PRINT_PAGE_HEAD', $this->page_head);

    ob_start();

    //Заголовок
    $title = htmlspecialchars($this->title);
	// Если есть пагинация и страница больше первой, добавляем "страница №"
	if($inConf->title_and_page){
		$page=cmsCore::request('page', 'int', 1);
		if($page > 1){
			global $_LANG;
			$title = $title.' &mdash; '.$_LANG['PAGE'].' №'.$page;
		}
	}
    $title = ($inCore->menuId()==1 ? $this->homeTitle() : $title.($inConf->title_and_sitename ? ' &mdash; '.$inConf->sitename : ''));
    echo '<title>'.$title.'</title>' ."\n";
    //Ключевые слова
    if (!$this->page_keys) { $this->page_keys = $inConf->keywords; }
    echo '<meta name="keywords" content="'.htmlspecialchars($this->page_keys).'" />' ."\n";
    //Описание
    if (!$this->page_desc) { $this->page_desc = $inConf->metadesc; }
    echo '<meta name="description" content="'.htmlspecialchars($this->page_desc).'" />' ."\n";
    //Генератор
    echo '<meta name="generator" content="InstantCMS - www.instantcms.ru"/>' ."\n";

    //JS-файлы
    foreach($this->page_head as $key=>$value) {
        if(mb_strstr($this->page_head[$key], '<script')) {
            echo $this->page_head[$key] ."\n"; unset($this->page_head[$key]);
        }
    }

    //CSS-файлы
    foreach($this->page_head as $key=>$value) {
        if(mb_strstr($this->page_head[$key], '<link')) {
            echo $this->page_head[$key] ."\n"; unset($this->page_head[$key]);
        }
    }

    //Оставшиеся теги
    foreach($this->page_head as $key=>$value) { echo $this->page_head[$key] ."\n"; }

    echo ob_get_clean();

}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Выводит тело страницы (результат работы компонента)
 */
public function printBody(){

    if (cmsConfig::getConfig('slight')){
		$searchquery = cmsUser::sessionGet('searchquery');
        if ($searchquery){
            if ($_REQUEST['view']!='search'){
                $this->page_body = str_ireplace($searchquery, '<span class="search_match">'.$searchquery.'</span>', $this->page_body);
                cmsUser::sessionDel('searchquery');
            }
        }
    }

    $this->page_body = cmsCore::callEvent('PRINT_PAGE_BODY', $this->page_body);

    echo $this->page_body;
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Печатает глубиномер
 * @param string $separator
 */
public function printPathway($separator='&rarr;'){

    $inCore = cmsCore::getInstance();
    $inConf = cmsConfig::getInstance();

    //Проверяем, на главной мы или нет
    if (($inCore->menuId()==1 && !$inConf->index_pw) || !$inConf->show_pw) { return false; }

    if ($inConf->short_pw){ unset($this->pathway[sizeof($this->pathway)-1]); }

    if (is_array($this->pathway)){
        echo '<div class="pathway">';
        foreach($this->pathway as $key => $value){
            echo '<a href="'.$this->pathway[$key]['link'].'" class="pathwaylink">'.$this->pathway[$key]['title'].'</a> ';
            if ($key<sizeof($this->pathway)-1) {
                echo ' '.$separator.' ';
            }
        }
        echo '</div>';
    }

}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Добавляет звено к глубиномеру
 * @param string $title
 * @param string $link
 * @return bool
 */
public function addPathway($title, $link=''){
    //Если ссылка не указана, берем текущий URI
    if (empty($link)) { $link = htmlspecialchars($_SERVER['REQUEST_URI']); }
    //Проверяем, есть ли уже в глубиномере такое звено
    $already = false;
    foreach($this->pathway as $key => $val){
        if ($this->pathway[$key]['link'] == $link){
            $already = true;
        }
    }
    //Если такого звена еще нет, добавляем его
    if(!$already){
		// проверяем нет ли на ссылку пункта меню, если есть, меняем заголовок
		$title = ($menu_title = cmsCore::getInstance()->getLinkInMenu($link)) ? $menu_title : $title;
        $this->pathway[] = array('title'=>$title, 'link'=>$link);
    }
    return true;
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Выводит на экран шаблон сайта
 * Какой именно шаблон выводить определяют константы TEMPLATE и TEMPLATE_DIR
 * Эти константы задаются в файле /index.php
 */
public function showTemplate(){

    $inCore = cmsCore::getInstance();

    $menu_template = $inCore->menuTemplate($inCore->menuId());

    if ($menu_template && file_exists(PATH.'/templates/'.$menu_template.'/template.php')){
        require(PATH.'/templates/'.$menu_template.'/template.php');
        return;
    }

    if (file_exists(TEMPLATE_DIR.'template.php')){
        require(TEMPLATE_DIR.'template.php');
        return;
    }

    $inCore->halt('Шаблон "'.TEMPLATE.'" не найден.');

}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Подключает файл из папки с шаблоном
 * Если в папке текущего шаблона такой файл не найден, ищет в дефолтном
 * @param string $file, например "special/error404.html"
 * @return <type>
 */
public static function includeTemplateFile($file, $data=array()){

	extract($data);

    if (file_exists(TEMPLATE_DIR.$file)){
        include(TEMPLATE_DIR.$file);
        return true;
    }

    if (file_exists(DEFAULT_TEMPLATE_DIR.$file)){
        include(DEFAULT_TEMPLATE_DIR.$file);
        return true;
    }

    return false;

}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
public static function showSplash(){

    if (self::includeTemplateFile('splash/splash.php')){

        cmsCore::setCookie('splash', md5('splash'), time()+60*60*24*30);
        $_SESSION['splash'] = 1;
        return true;

    }

    return false;

}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Загружает все модули для данного пункта меню
 * @return bool
 */
private function loadModulesForMenuItem() {

    if(isset($this->modules)){ return true; }

    $modules = array();

    $inCore = cmsCore::getInstance();
    $inDB   = cmsDatabase::getInstance();

    if (!$inCore->isMenuIdStrict()){ $strict_sql = "AND (m.is_strict_bind = 0)"; } else { $strict_sql = ''; }

	$menuid = $inCore->menuId();

    $sql = "SELECT m.*, mb.position as mb_position
            FROM cms_modules m
            INNER JOIN cms_modules_bind mb ON mb.module_id = m.id AND mb.menu_id IN ($menuid, 0)
            WHERE m.published = 1 $strict_sql
            ORDER BY m.ordering ASC";

    $result = $inDB->query($sql);

    if(!$inDB->num_rows($result)){ $this->modules = $modules; return false; }

	while ($mod = $inDB->fetch_assoc($result)){

		if (!cmsCore::checkContentAccess($mod['access_list'])) { continue; }

        // список модулей на позицию
        $modules[$mod['mb_position']][] = $mod;
        // общий список модулей
        $modules['all_modules'][] = $mod;

	}

    $this->modules = $modules;

    return true;

}

public function countModules($position){

    $this->loadModulesForMenuItem();

    if(!isset($this->modules[$position])){ return 0; }

    return sizeof($this->modules[$position]);

}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Выводит модули для указанной позиции и текущего пункта меню
 * @param string $position
 * @return html
 */
public function printModules($position){

    $this->loadModulesForMenuItem();

    if(!isset($this->modules[$position])){ return false; }

    $inCore = cmsCore::getInstance();
    $inUser = cmsUser::getInstance();

    global $_LANG;

    foreach ($this->modules[$position] as $mod) {

        $modulefile = PATH.'/modules/'.$mod['content'].'/module.php';
        $callback   = true;

        if (!$mod['user']) { cmsCore::loadLanguage('modules/'.$mod['content']); }

        // Собственный модуль, созданный в админке
        if( !$mod['is_external'] ){

            $mod['content'] = cmsCore::processFilters($mod['content']);

            $mod['body'] = $mod['content'];

        }
        // Отдельный модуль
        if( $mod['is_external'] ){
            if (file_exists($modulefile)){

                require_once $modulefile;
                // Если есть кеш, берем тело модуля из него
                if ($mod['cache'] && cmsCore::isCached('module', $mod['id'], $mod['cachetime'], $mod['cacheint'])){

                    $mod['body'] = cmsCore::getCache('module', $mod['id']);
                    $callback = true;

                } else {

                    $config = cmsCore::yamlToArray($mod['config']);
                    $inCore->cacheModuleConfig($mod['id'], $config);

                    ob_start();
                    $callback = $mod['content']($mod['id']);
                    $mod['body'] = ob_get_clean();
                    if($mod['cache']) { cmsCore::saveCache('module', $mod['id'], $mod['body']); }

                }

            }
        }

        // выводим модуль в шаблоне
        if ( $callback ){

            $smarty = $inCore->initSmartyModule();

            if (cmsConfig::getConfig('fastcfg') && $inUser->is_admin){
                $smarty->assign('cfglink', true);
            }

            $smarty->assign('mod', $mod);

            $module_tpl = file_exists($smarty->template_dir.'/'.$mod['template']) ? $mod['template'] : 'module.tpl';

            $smarty->display($module_tpl);

        }

    }

    return true;

}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Возвращает html-код каптчи
 * @param string $input_name
 * @return html
 */
public static function getCaptcha($input_name='code'){
    ob_start();
    $captcha_count = self::getInstance()->captcha_count;
    $input_id = 'kcaptcha' . $captcha_count;
	self::includeTemplateFile('special/captcha.php', array('input_id' => $input_id, 'input_name' => $input_name));
    self::getInstance()->captcha_count += 1;
    return ob_get_clean();
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Разбивает текст на слова и делает каждое слово ссылкой, добавляя в его начало $link
 * @param string $link
 * @param string $text
 * @return html
 */
public static function getMetaSearchLink($link, $text){

	if(!$text) { return ''; }

    $text = html_entity_decode(trim(trim(strip_tags($text)), '.'));

    if (!mb_strstr($text, ',')){

        $html = '<a href="'.$link.urlencode($text).'">'.$text.'</a>';

    } else {

        $text  = str_replace(', ', ',', $text);
        $words = explode(',', $text);
		$html  = '';

        foreach($words as $value){

            $value = str_replace("\r", '', $value);
            $value = str_replace("\n", '', $value);
            $value = trim($value, '.');
            $html .= '<a href="'.$link.urlencode($value).'">'.$value.'</a>, ';

        }

		$html = rtrim($html, ', ');

    }

    return $html;

}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Возвращает html-код панели для вставки BBCode
 * @param string $field_id
 * @param bool $images
 * @param string $placekind
 * @return html
 */
public static function getBBCodeToolbar($field_id, $images=0, $component='forum', $target='post', $target_id=0){

    // Поддержка плагинов панели ббкодов (ее замены)
    $p_toolbar = cmsCore::callEvent('REPLACE_BBCODE_BUTTONS', array('html' => '',
                                                                'field_id' => $field_id,
																'images' => $images,
																'component' => $component,
																'target' => $target,
																'target_id' => $target_id));

    if($p_toolbar['html']){ return $p_toolbar['html']; }

    $inPage = self::getInstance();

    $inPage->addHeadJS('core/js/smiles.js');
    if($images){
        $inPage->addHeadJS('includes/jquery/upload/ajaxfileupload.js');
    }

	ob_start();
	self::includeTemplateFile('special/bbcode_panel.php', array('field_id' => $field_id,
																'images' => $images,
																'component' => $component,
																'target' => $target,
																'target_id' => $target_id));

    return cmsCore::callEvent('GET_BBCODE_BUTTON', ob_get_clean());

}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Возвращает html-код панели со смайлами
 * @param string $for_field_id
 * @return html
 */
public static function getSmilesPanel($for_field_id){

    $p_html = cmsCore::callEvent('REPLACE_SMILES', array('html' => '', 'for_field_id'=>$for_field_id));
    if($p_html['html']){ return $p_html['html']; }

    $html = '<div class="usr_msg_smilebox" id="smilespanel-'.$for_field_id.'" style="display:none">';
    if ($handle = opendir(PATH.'/images/smilies')) {
        while (false !== ($file = readdir($handle))) {
            if ($file != '.' && $file != '..' && mb_strstr($file, '.gif')){
             $tag = str_replace('.gif', '', $file);
             $dir = '/images/smilies/';

             $html .= '<a href="javascript:addSmile(\''.$tag.'\', \''.$for_field_id.'\');"><img src="'.$dir.$file.'" border="0" /></a> ';
            }
        }

        closedir($handle);
    }
    $html .= '</div>';
    return $html;
}

// AUTOCOMPLETE PLUGIN  /////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Подключает JS и CSS для автокомплита
 */
public function initAutocomplete(){
    $this->addHeadJS('includes/jquery/autocomplete/jquery.autocomplete.min.js');
    $this->addHeadCSS('includes/jquery/autocomplete/jquery.autocomplete.css');
}

/**
 * Возвращает JS-код инициализации автокомплита для указанного поля ввода и скрипта
 * @param string $script
 * @param string $field_id
 * @param bool $multiple
 * @return js
 */
public function getAutocompleteJS($script, $field_id='tags', $multiple=true){
    $multiple = $multiple ? 'true' : 'false';
    return '$("#'.$field_id.'").autocomplete(
                "/core/ajax/'.$script.'.php",
                {
                    width: 280,
                    selectFirst: false,
                    multiple: '.$multiple.'
                }
            );';
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/**
 * Возвращает код панели для постраничной навигации
 * @param int $total
 * @param int $page
 * @param int $perpage
 * @param string $link
 * @param array $params
 * @return html
 */
public static function getPagebar($total, $page, $perpage, $link, $params=array()){

	$pagebar = cmsCore::callEvent('GET_PAGEBAR', array($total, $page, $perpage, $link, $params));

	if(!is_array($pagebar) && $pagebar) { return $pagebar; }

    global $_LANG;

    $html  = '<div class="pagebar">';
    $html .= '<span class="pagebar_title"><strong>'.$_LANG['PAGES'].': </strong></span>';

    $total_pages = ceil($total / $perpage);

    if ($total_pages < 2) { return; }

    //if more than one page of results
    if($total_pages!=1){

        //configure for the starting links per page
        $max = 4;

        //used in the loop
        $max_links = $max+1;
        $h=1;

        //if page is above max link
        if($page>$max_links){
            //start of loop
            $h=(($h+$page)-$max_links);
        }

        //if page is not page one
        if($page>=1){
            //top of the loop extends
            $max_links = $max_links+($page-1);
        }

        //if the top page is visible then reset the top of the loop to the $total_pages
        if($max_links>$total_pages){
            $max_links=$total_pages+1;
        }

        //next and prev buttons
        if($page>1){

            $href = $link;
            if (is_array($params)){
                foreach($params as $param=>$value){
                    $href = str_replace('%'.$param.'%', $value, $href);
                }
            }
            $html .= ' <a href="'.str_replace('%page%', 1, $href).'" class="pagebar_page">'.$_LANG['FIRST'].'</a> ';
            $html .= ' <a href="'.str_replace('%page%', ($page-1), $href).'" class="pagebar_page">'.$_LANG['PREVIOUS'].'</a> ';

        }

        //create the page links
        for ($i=$h;$i<$max_links;$i++){
            if($i==$page){
                $html .= '<span class="pagebar_current">'.$i.'</span>';
            }
            else{
                $href = $link;
                if (is_array($params)){
                    foreach($params as $param=>$value){
                        $href = str_replace('%'.$param.'%', $value, $href);
                    }
                }
                $href = str_replace('%page%', $i, $href);
                $html .= ' <a href="'.$href.'" class="pagebar_page">'.$i.'</a> ';
            }
        }

        //Next and last buttons
        if(($page >= 1)&&($page!=$total_pages)){
            $href = $link;
            if (is_array($params)){
                foreach($params as $param=>$value){
                    $href = str_replace('%'.$param.'%', $value, $href);
                }
            }
            $html .= ' <a href="'.str_replace('%page%', ($page+1), $href).'" class="pagebar_page">'.$_LANG['NEXT'].'</a> ';
            $html .= ' <a href="'.str_replace('%page%', $total_pages, $href).'" class="pagebar_page">'.$_LANG['LAST'].'</a> ';
        }
    }

    //if one page of results
    else{
        $href = $link;
        if (is_array($params)){
            foreach($params as $param=>$value){
                $href = str_replace('%'.$param.'%', $value, $href);
            }
        }
        $href = str_replace('%page%', 1, $href);
        $html .= ' <a href="'.$href.'" class="pagebar_page">1</a> ';
    }

    $html.='</div>';

    return $html;
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
public static function getModuleTemplates() {

    $tpl_dir    = is_dir(TEMPLATE_DIR.'modules') ? TEMPLATE_DIR.'modules' : PATH.'/templates/_default_/modules';
    $pdir       = opendir($tpl_dir);

    $templates  = array();

    while ($nextfile = readdir($pdir)){
        if (
                ($nextfile != '.')  &&
                ($nextfile != '..') &&
                !is_dir($tpl_dir.'/'.$nextfile) &&
                ($nextfile!='.svn') &&
                (mb_substr($nextfile, 0, 6)=='module')
           ) {
            $templates[$nextfile] = $nextfile;
        }
    }

    if (!sizeof($templates)){ return false; }

    return $templates;
}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}

?>
