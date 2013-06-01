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

function pluginsList($new_plugins, $action_name, $action){

    $inCore = cmsCore::getInstance();

    echo '<table cellpadding="3" cellspacing="0" border="0" style="margin-left:40px">';
    foreach($new_plugins as $plugin){
        $plugin_obj = $inCore->loadPlugin($plugin);

        if ($action == 'install_plugin') { $version = $plugin_obj->info['version']; }
        if ($action == 'upgrade_plugin') { $version = $inCore->getPluginVersion($plugin) . ' &rarr; '. $plugin_obj->info['version']; }

        echo '<tr>';
            echo '<td width="16"><img src="/admin/images/icons/hmenu/plugins.png" /></td>';
            echo '<td><a style="font-weight:bold;font-size:14px" title="'.$action_name.' '.$plugin_obj->info['title'].'" href="index.php?view=install&do='.$action.'&id='.$plugin.'">'.$plugin_obj->info['title'].'</a> v'.$version.'</td>';
        echo '<tr>';
        echo '<tr>';
            echo '<td width="16">&nbsp;</td>';
            echo '<td>
                        <div style="margin-bottom:6px;">'.$plugin_obj->info['description'].'</div>
                        <div style="color:gray"><strong>Автор:</strong> '.$plugin_obj->info['author'].'</div>
                        <div style="color:gray"><strong>Папка:</strong> /plugins/'.$plugin_obj->info['plugin'].'</div>
                  </td>';
        echo '<tr>';
    }
    echo '</table>';

    return;

}

function componentsList($new_components, $action_name, $action){

    $inCore = cmsCore::getInstance();

    echo '<table cellpadding="3" cellspacing="0" border="0" style="margin-left:40px">';
    foreach($new_components as $component){
        if (cmsCore::loadComponentInstaller($component)) {

            $_component = call_user_func('info_component_'.$component);

            if ($action == 'install_component') { $version = $_component['version']; }
            if ($action == 'upgrade_component') { $version = $inCore->getComponentVersion($component) . ' &rarr; '. $_component['version']; }

            echo '<tr>';
                echo '<td width="16"><img src="/admin/images/icons/hmenu/plugins.png" /></td>';
                echo '<td><a style="font-weight:bold;font-size:14px" title="'.$action_name.' '.$_component['title'].'" href="index.php?view=install&do='.$action.'&id='.$component.'">'.$_component['title'].'</a> v'.$version.'</td>';
            echo '<tr>';
            echo '<tr>';
                echo '<td width="16">&nbsp;</td>';
                echo '<td>
                            <div style="margin-bottom:6px;">'.$_component['description'].'</div>
                            <div style="color:gray"><strong>Автор:</strong> '.$_component['author'].'</div>
                            <div style="color:gray"><strong>Папка:</strong> /components/'.$_component['link'].'</div>
                      </td>';
            echo '<tr>';

        }
    }
    echo '</table>';

    return;

}

function modulesList($new_modules, $action_name, $action){

    echo '<table cellpadding="3" cellspacing="0" border="0" style="margin-left:40px">';
    foreach($new_modules as $module){
        if (cmsCore::loadModuleInstaller($module)) {

            $_module = call_user_func('info_module_'.$module);

            if ($action == 'install_module') { $version = $_module['version']; }
            if ($action == 'upgrade_module') { $version = cmsCore::getModuleVersion($module) . ' &rarr; '. $_module['version']; }

            echo '<tr>';
                echo '<td width="16"><img src="/admin/images/icons/hmenu/plugins.png" /></td>';
                echo '<td><a style="font-weight:bold;font-size:14px" title="'.$action_name.' '.$_module['title'].'" href="index.php?view=install&do='.$action.'&id='.$module.'">'.$_module['title'].'</a> v'.$version.'</td>';
            echo '<tr>';
            echo '<tr>';
                echo '<td width="16">&nbsp;</td>';
                echo '<td>
                            <div style="margin-bottom:6px;">'.$_module['description'].'</div>
                            <div style="color:gray"><strong>Автор:</strong> '.$_module['author'].'</div>
                            <div style="color:gray"><strong>Папка:</strong> /modules/'.$_module['link'].'</div>
                      </td>';
            echo '<tr>';

        }
    }
    echo '</table>';

    return;

}

function applet_install(){

    $inCore = cmsCore::getInstance();

	$GLOBALS['cp_page_title'] = 'Установка расширений';

    $do = cmsCore::request('do', 'str', 'list');
	$id = cmsCore::request('id', 'int', -1);

	global $adminAccess;

// ============================================================================== //

    if ($do == 'module'){

		if (!cmsUser::isAdminCan('admin/modules', $adminAccess)) { cpAccessDenied(); }

      	cpAddPathway('Установка модулей', 'index.php?view=install&do=module');

        $new_modules = $inCore->getNewModules();
        $upd_modules = $inCore->getUpdatedModules();

        echo '<h3>Установка модулей</h3>';

        if (!$new_modules && !$upd_modules){

            echo '<p>В системе не найдены модули, которые еще не установлены или требуют обновления.</p>';
            echo '<p>Если вы скачали архив с модулем и хотите установить или обновить, то распакуйте его в корень сайта и перезагрузите страницу.</p>';
            echo '<p><a href="javascript:window.history.go(-1);">Вернуться назад</a></p>';
            return;

        }

        if ($new_modules){

            echo '<p><strong>Найдены модули, доступные для установки:</strong></p>';
            modulesList($new_modules, 'Установить', 'install_module');

        }

        if ($upd_modules){

            echo '<p><strong>Найдены модули, доступные для обновления:</strong></p>';
            modulesList($upd_modules, 'Обновить', 'upgrade_module');

        }

        echo '<p>Щелкните по названию модуля, чтобы продолжить.</p>';

        echo '<p><a href="javascript:window.history.go(-1);">Назад</a></p>';

    }

// ============================================================================== //

    if ($do == 'install_module'){

		if (!cmsUser::isAdminCan('admin/modules', $adminAccess)) { cpAccessDenied(); }

        cpAddPathway('Установка модулей', 'index.php?view=install&do=module');

        $error = '';

        $module_id = cmsCore::request('id', 'str', '');

        if(!$module_id){ cmsCore::redirectBack(); }

        if (cmsCore::loadModuleInstaller($module_id)){
            $_module = call_user_func('info_module_'.$module_id);
            //////////////////////////////////////
            $error   = call_user_func('install_module_'.$module_id);
        } else {
            $error = 'Не удалось загрузить установщик модуля.';
        }

        if ($error === true) {
            $inCore->installModule($_module, $_module['config']);
            cmsCore::redirect('/admin/index.php?view=install&do=finish_module&id='.$module_id.'&task=install');
        } else {

            echo '<p style="color:red">'.$error.'</p>';

        }

        echo '<p><a href="index.php?view=install&do=module">Назад</a></p>';

    }

// ============================================================================== //

    if ($do == 'upgrade_module'){

		if (!cmsUser::isAdminCan('admin/modules', $adminAccess)) { cpAccessDenied(); }

        cpAddPathway('Обновление модуля', 'index.php?view=install&do=module');

        $error = '';

        $module_id = cmsCore::request('id', 'str', '');

        if(!$module_id){ cmsCore::redirectBack(); }

        if (cmsCore::loadModuleInstaller($module_id)) {
            $_module = call_user_func('info_module_'.$module_id);
            if (isset($_module['link'])) {
                $_module['content'] = $_module['link'];
            }
            $error   = call_user_func('upgrade_module_'.$module_id);
        } else {
            $error = 'Не удалось загрузить установщик модуля.';
        }

        if ($error === true) {
            $inCore->upgradeModule($_module, $_module['config']);
            cmsCore::redirect('/admin/index.php?view=install&do=finish_module&id='.$module_id.'&task=upgrade');
        } else {

            echo '<p style="color:red">'.$error.'</p>';

        }

        echo '<p><a href="index.php?view=install&do=module">Назад</a></p>';

    }

// ============================================================================== //

    if ($do == 'remove_module'){

		if (!cmsUser::isAdminCan('admin/modules', $adminAccess)) { cpAccessDenied(); }

        $module_id = cmsCore::request('id', 'int', '');
        if(!$module_id){ cmsCore::redirectBack(); }

		$module = cmsCore::getModuleById($module_id);

        if (cmsCore::loadModuleInstaller($module)){
			if(function_exists('remove_module_'.$module)){
            	call_user_func('remove_module_'.$module);
			}
        }

        cmsCore::removeModule($module_id);

        cmsCore::redirect('/admin/index.php?view=install&do=finish_module&id='.$module_id.'&task=remove');

    }

// ============================================================================== //

    if ($do == 'finish_module'){

        $module_id      = $inCore->request('id', 'str', '');
        $task           = $inCore->request('task', 'str', 'install');

        $inCore->redirect('/admin/index.php?view=modules&installed='.$module_id.'&task='.$task);

    }

// ============================================================================== //

    if ($do == 'component'){

		if (!cmsUser::isAdminCan('admin/components', $adminAccess)) { cpAccessDenied(); }

      	cpAddPathway('Установка компонентов', 'index.php?view=install&do=component');

        $new_components = $inCore->getNewComponents();
        $upd_components = $inCore->getUpdatedComponents();

        echo '<h3>Установка компонентов</h3>';

        if (!$new_components && !$upd_components){

            echo '<p>В системе не найдены компоненты, которые еще не установлены или требуют обновления.</p>';
            echo '<p>Если вы скачали архив с компонентом и хотите установить или обновить, то распакуйте его в корень сайта и перезагрузите страницу.</p>';
            echo '<p><a href="javascript:window.history.go(-1);">Вернуться назад</a></p>';
            return;

        }

        if ($new_components){

            echo '<p><strong>Найдены компоненты, доступные для установки:</strong></p>';
            componentsList($new_components, 'Установить', 'install_component');

        }

        if ($upd_components){

            echo '<p><strong>Найдены компоненты, доступные для обновления:</strong></p>';
            componentsList($upd_components, 'Обновить', 'upgrade_component');

        }

        echo '<p>Щелкните по названию компонента, чтобы продолжить.</p>';

        echo '<p><a href="javascript:window.history.go(-1);">Назад</a></p>';

    }

// ============================================================================== //

    if ($do == 'install_component'){

        cpAddPathway('Установка компонента', 'index.php?view=install&do=component');

        $error = '';

        $component = $inCore->request('id', 'str', '');
        if(!$component){ $inCore->redirectBack(); }

		if (!cmsUser::isAdminCan('admin_components', $adminAccess)) { cpAccessDenied(); }

        if ($inCore->loadComponentInstaller($component)){
            $_component = call_user_func('info_component_'.$component);
            $error      = call_user_func('install_component_'.$component);
        } else {
            $error = 'Не удалось загрузить установщик компонента.';
        }

        if ($error === true) {
            $inCore->installComponent($_component, $_component['config']);
            $inCore->redirect('/admin/index.php?view=install&do=finish_component&id='.$component.'&task=install');
        } else {

            echo '<p style="color:red">'.$error.'</p>';

        }

        echo '<p><a href="index.php?view=install&do=component">Назад</a></p>';

    }

// ============================================================================== //

    if ($do == 'upgrade_component'){

        cpAddPathway('Обновление компонентов', 'index.php?view=install&do=component');

        $error = '';

        $component = $inCore->request('id', 'str', '');
        if(!$component){ $inCore->redirectBack(); }

		if (!cmsUser::isAdminCan('admin/components', $adminAccess)) { cpAccessDenied(); }
		if (!cmsUser::isAdminCan('admin/com_'.$component, $adminAccess)) { cpAccessDenied(); }

        if ($inCore->loadComponentInstaller($component)) {
            $_component = call_user_func('info_component_'.$component);
            $error      = call_user_func('upgrade_component_'.$component);
        } else {
            $error = 'Не удалось загрузить установщик компонента.';
        }

        if ($error === true) {
            $inCore->upgradeComponent($_component, $_component['config']);
            $inCore->redirect('/admin/index.php?view=install&do=finish_component&id='.$component.'&task=upgrade');
        } else {

            echo '<p style="color:red">'.$error.'</p>';

        }

        echo '<p><a href="index.php?view=install&do=component">Назад</a></p>';

    }

// ============================================================================== //

    if ($do == 'remove_component'){

        $component_id = $inCore->request('id', 'int', '');

        if(!$component_id){ $inCore->redirectBack(); }

		$com = cpComponentById($component_id);
		if (!cmsUser::isAdminCan('admin/components', $adminAccess)) { cpAccessDenied(); }
		if (!cmsUser::isAdminCan('admin/com_'.$com, $adminAccess)) { cpAccessDenied(); }

        if ($inCore->loadComponentInstaller($com)){
			if(function_exists('remove_component_'.$com)){
            	call_user_func('remove_component_'.$com);
			}
        }

        $inCore->removeComponent($component_id);

        $inCore->redirect('/admin/index.php?view=install&do=finish_component&id='.$component_id.'&task=remove');

    }

// ============================================================================== //

    if ($do == 'finish_component'){

        $component_id   = $inCore->request('id', 'str', '');
        $task           = $inCore->request('task', 'str', 'install');

        $inCore->redirect('/admin/index.php?view=components&installed='.$component_id.'&task='.$task);

    }


// ============================================================================== //

    if ($do == 'plugin'){

		if (!cmsUser::isAdminCan('admin/plugins', $adminAccess)) { cpAccessDenied(); }

      	cpAddPathway('Установка плагинов', 'index.php?view=install&do=plugin');

        $new_plugins = $inCore->getNewPlugins();
        $upd_plugins = $inCore->getUpdatedPlugins();

        echo '<h3>Установка плагинов</h3>';

        if (!$new_plugins && !$upd_plugins){

            echo '<p>В системе не найдены плагины, которые еще не установлены или требуют обновления.</p>';
            echo '<p>Если вы скачали архив с плагином и хотите установить или обновить его, то распакуйте его в папку <strong>/plugins</strong> и перезагрузите страницу.</p>';
            echo '<p><a href="javascript:window.history.go(-1);">Вернуться назад</a></p>';
            return;

        }

        if ($new_plugins){

            echo '<p><strong>Найдены плагины, доступные для установки:</strong></p>';
            pluginsList($new_plugins, 'Установить', 'install_plugin');

        }

        if ($upd_plugins){

            echo '<p><strong>Найдены плагины, доступные для обновления:</strong></p>';
            pluginsList($upd_plugins, 'Обновить', 'upgrade_plugin');

        }

        echo '<p>Щелкните по названию плагина, чтобы продолжить.</p>';

        echo '<p><a href="javascript:window.history.go(-1);">Назад</a></p>';

    }

// ============================================================================== //

    if ($do == 'install_plugin'){

		if (!cmsUser::isAdminCan('admin/plugins', $adminAccess)) { cpAccessDenied(); }

        cpAddPathway('Установка плагина', 'index.php?view=install&do=plugin');

        $error = '';

        $plugin_id = $inCore->request('id', 'str', '');

        if(!$plugin_id){
            $inCore->redirectBack();
        }

        $plugin = $inCore->loadPlugin($plugin_id);

        if (!$plugin) { $error = 'Не удалось загрузить файл плагина.'; }

        if (!$error && $plugin->install()) {
            $inCore->redirect('/admin/index.php?view=install&do=finish_plugin&id='.$plugin_id.'&task=install');
        }

        if ($error){
            echo '<p style="color:red">'.$error.'</p>';
        }

        echo '<p><a href="index.php?view=install&do=plugin">Назад</a></p>';

    }

// ============================================================================== //

    if ($do == 'upgrade_plugin'){

		if (!cmsUser::isAdminCan('admin/plugins', $adminAccess)) { cpAccessDenied(); }

        cpAddPathway('Обновление плагина', 'index.php?view=install&do=plugin');

        $error = '';

        $plugin_id = $inCore->request('id', 'str', '');

        if(!$plugin_id){
            $inCore->redirectBack();
        }

        $plugin = $inCore->loadPlugin($plugin_id);

        if (!$plugin) { $error = 'Не удалось загрузить файл плагина.'; }

        if (!$error && $plugin->upgrade()) {
            $inCore->redirect('/admin/index.php?view=install&do=finish_plugin&id='.$plugin_id.'&task=upgrade');
        }

        if ($error){
            echo '<p style="color:red">'.$error.'</p>';
        }

        echo '<p><a href="index.php?view=install&do=plugin">Назад</a></p>';

    }

// ============================================================================== //

    if ($do == 'remove_plugin'){

		if (!cmsUser::isAdminCan('admin/plugins', $adminAccess)) { cpAccessDenied(); }

        $plugin_id = $inCore->request('id', 'str', '');

        if(!$plugin_id){
            $inCore->redirectBack();
        }

        $inCore->removePlugin($plugin_id);

        $inCore->redirect('/admin/index.php?view=install&do=finish_plugin&id='.$plugin_id.'&task=remove');

    }

// ============================================================================== //

    if ($do == 'finish_plugin'){

        $plugin_id  = $inCore->request('id', 'str', '');
        $task       = $inCore->request('task', 'str', 'install');

        $inCore->redirect('/admin/index.php?view=plugins&installed='.$plugin_id.'&task='.$task);

    }

}

?>