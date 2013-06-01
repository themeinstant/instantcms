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

function cpComponentHasConfig($component){

	return file_exists('components/'.$component.'/backend.php');

}

function cpComponentCanRemove($id){

    $inCore = cmsCore::getInstance();

    $com = $inCore->getComponent($id);

	if($com['system']) { return false; }

	global $adminAccess;

	return cmsUser::isAdminCan('admin/com_'.$com['link'], $adminAccess);

}

function applet_components(){

    $inCore = cmsCore::getInstance();
	$inDB   = cmsDatabase::getInstance();
	$inUser = cmsUser::getInstance();

	//check access
	global $adminAccess;
	if (!cmsUser::isAdminCan('admin/components', $adminAccess)) { cpAccessDenied(); }

	$GLOBALS['cp_page_title'] = 'Компоненты';
 	cpAddPathway('Компоненты', 'index.php?view=components');

	$do = cmsCore::request('do', 'str', 'list');

	$id   = cmsCore::request('id', 'int');
	$link = cmsCore::request('link', 'str', '');
	if($link){
        $_REQUEST['id'] = $id = $inCore->getComponentId($link);
	}

    if ($do == 'show'){

		$com = cpComponentById($id);
		//check access for component
		if (!cmsUser::isAdminCan('admin/com_'.$com, $adminAccess)) { echo 0; exit; }

		dbShow('cms_components', $id);
		echo '1'; exit;

	}

	if ($do == 'hide'){

		$com = cpComponentById($id);
		//check access for component
		if (!cmsUser::isAdminCan('admin/com_'.$com, $adminAccess)) { echo 0; exit; }

		dbHide('cms_components', $id);
		echo '1'; exit;

	}

	if ($do == 'config'){

		$com = cpComponentById($id);
		//check access for component
		if (!cmsUser::isAdminCan('admin/com_'.$com, $adminAccess)) { cpAccessDenied(); }

		if ($com) {

			$file = PATH.'/admin/components/'.$com.'/backend.php';

			if (file_exists($file)){
				include $file; return;
			} else {
				$inCore->redirect('index.php?view=components');
			}

		} else {
			$inCore->redirect('index.php?view=components');
		}

	}

	if ($do == 'list'){
		$toolmenu = array();
		$toolmenu[0]['icon'] = 'install.gif';
		$toolmenu[0]['title'] = 'Установить компонент';
		$toolmenu[0]['link'] = '?view=install&do=component';

		$toolmenu[1]['icon'] = 'help.gif';
		$toolmenu[1]['title'] = 'Помощь';
		$toolmenu[1]['link'] = '?view=help&topic=components';

		cpToolMenu($toolmenu);

        $component = cmsCore::request('installed', 'str', '');

        if ($component){

            $task = cmsCore::request('task', 'str', 'install');

            if ($task == 'install' || $task == 'upgrade'){

                if (is_numeric($component)){ $component = $inCore->getComponentById($component); }

                $inCore->loadComponentInstaller($component);
                $_component = call_user_func('info_component_'.$component);

                $task_str   = ($task=='install') ? 'установлен' : 'обновлен';
                echo '<div style="color:green;margin-top:12px;margin-bottom:5px;">';
                echo '<p>Компонент <strong>"'.$_component['title'].'"</strong> успешно '.$task_str.'.</p>';
                if (isset($_component['modules']) && $task == 'install'){
                    if(is_array($_component['modules'])){
                        echo '<p>Дополнительно установлены модули:</p>';
                        echo '<ul>';
                            foreach($_component['modules'] as $module=>$title){
                                echo '<li>'.$title.'</li>';
                            }
                        echo '</ul>';
                    }
                }
                if (isset($_component['plugins']) && $task == 'install'){
                    if(is_array($_component['plugins'])){
                        echo '<p>Дополнительно установлены плагины:</p>';
                        echo '<ul>';
                            foreach($_component['plugins'] as $module=>$title){
                                echo '<li>'.$title.'</li>';
                            }
                        echo '</ul>';
                    }
                }
                echo '</div>';
            }

            if ($task == 'remove'){
                echo '<div style="color:green;margin-top:12px;margin-bottom:5px;">Компонент удален из системы.</div>';
            }

        }

		//TABLE COLUMNS
		$fields = array();

		$fields[0]['title'] = 'id';			$fields[0]['field'] = 'id';			$fields[0]['width'] = '30';

		$fields[1]['title'] = 'Название';	$fields[1]['field'] = 'title';		$fields[1]['width'] = '';		$fields[1]['link'] = '?view=components&do=config&id=%id%';

        $fields[2]['title'] = 'Версия';		$fields[2]['field'] = 'version';		$fields[2]['width'] = '60';

		$fields[3]['title'] = 'Включен';	$fields[3]['field'] = 'published';		$fields[3]['width'] = '65';

		$fields[4]['title'] = 'Автор';		$fields[4]['field'] = 'author';		$fields[4]['width'] = '200';

		$fields[5]['title'] = 'Ссылка';		$fields[5]['field'] = 'link';		$fields[5]['width'] = '100';

		//ACTIONS
		$actions = array();
		$actions[0]['title'] = 'Настроить';
		$actions[0]['icon']  = 'config.gif';
		$actions[0]['link']  = '?view=components&do=config&id=%id%';
		// Функция, которой передается ID объекта, и если она вернет TRUE то только тогда отобразится значок
		$actions[0]['condition'] = 'cpComponentHasConfig';

		$actions[1]['title'] = 'Удалить компонент';
		$actions[1]['icon']  = 'delete.gif';
		$actions[1]['link']  = '?view=install&do=remove_component&id=%id%';
        $actions[1]['confirm'] = 'Удалить компонент из системы?';
		// Функция, которой передается ID объекта, и если она вернет TRUE то только тогда отобразится значок
		$actions[1]['condition'] = 'cpComponentCanRemove';

		$where = '';

        if ($inUser->id > 1){
            foreach($adminAccess as $key=>$value){
                if (mb_strstr($value, 'admin/com_')){
                    if ($where) { $where .= ' OR '; }
                    $value = str_replace('admin/com_', '', $value);
                    $where .= "link='{$value}'";
                }
            }
        }

		if (!$where) { $where = 'id>0'; }

		//Print table
		cpListTable('cms_components', $fields, $actions, $where);
	}

}

?>