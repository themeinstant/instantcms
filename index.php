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

    Error_Reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

    header('Content-Type: text/html; charset=utf-8');
    define('PATH', $_SERVER['DOCUMENT_ROOT']);

////////////////////////////// Проверяем что система установлена /////////////////////////////

    if(is_dir('install')|| is_dir('migrate')) {
        if (!file_exists(PATH.'/includes/config.inc.php')){
            header('location:/install/');
			die();
        } else {
            include(PATH.'/core/messages/installation.html');
            die();
        }
    }

/////////////////////////////////// Подготовка //////////////////////////////////////////////

	define("VALID_CMS", 1);
	session_start();

	include('core/cms.php');
    $inCore = cmsCore::getInstance();

    define('HOST', 'http://' . cmsCore::getHost());

/////////////////////////////////// Включаем таймер /////////////////////////////////////////

    $inCore->startGenTimer();

////////////////////////// Загружаем нужные классы //////////////////////////////////////////

    cmsCore::loadClass('page');    //страница
    cmsCore::loadClass('user');    //пользователь
    cmsCore::loadClass('actions'); //лента активности

    cmsCore::callEvent('GET_INDEX', '');

    $inDB   = cmsDatabase::getInstance();
    $inPage = cmsPage::getInstance();
    $inConf = cmsConfig::getInstance();
    $inUser = cmsUser::getInstance();

	$inUser->autoLogin(); //автоматически авторизуем пользователя, если найден кукис

    // проверяем что пользователь не удален и не забанен и загружаем его данные
    if (!$inUser->update() && !$_SERVER['REQUEST_URI']!=='/logout') { cmsCore::halt(); }

    //устанавливаем заголовок браузера в название сайта
    $inPage->setTitle( $inConf->sitename );

////////////////////////// Проверяем, включен ли сайт //////////////////////////

    //Если сайт выключен и пользователь не администратор,
    //то показываем шаблон сообщения о том что сайт отключен
	if ($inConf->siteoff &&
        !$inUser->is_admin &&
        $_SERVER['REQUEST_URI']!='/login' &&
        $_SERVER['REQUEST_URI']!='/logout'
       ){
            cmsPage::includeTemplateFile('special/siteoff.php');
            cmsCore::halt();
	}

//////////////////////////// Мониторинг пользователей //////////////////////////

	$inCore->onlineStats();   //обновляем статистику посещений сайта

////////////////////////////// Генерация страницы //////////////////////////////

	//Строим глубиномер
	$inPage->addPathway($_LANG['PATH_HOME'], '/');
    $inPage->setTitle( $inCore->menuTitle() );

	//Проверяем доступ пользователя
    //При положительном результате
	//Строим тело страницы (запускаем текущий компонент)
    if ($inCore->checkMenuAccess()) $inCore->proceedBody();

//////////////////////////////////// Вывод шаблона /////////////////////////////

    //Проверяем нужно ли показать входную страницу (splash)
	if($inCore->isSplash()){
        //Показываем входную страницу
		if (!$inPage->showSplash()){
            //Если шаблон входной страницы не был найден,
            //показываем обычный шаблон сайта
            $inPage->showTemplate();
        }
	} else {
        //показываем шаблон сайта
		$inPage->showTemplate();
	}

////////////// Вычисляем и выводим время генерации, запросы к базе /////////////

	if ($inDB->q_count && $inConf->debug && $inUser->is_admin){

        $time = $inCore->getGenTime();
		echo $_LANG['DEBUG_TIME_GEN_PAGE'].' '.number_format($time, 4).' '.$_LANG['DEBUG_SEC'];
		echo '<br />'.$_LANG['DEBUG_QUERY_DB'].' '.$inDB->q_count.'<br />';
		echo $inDB->q_dump;

    }

?>
