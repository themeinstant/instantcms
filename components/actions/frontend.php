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

function actions(){

    $inCore = cmsCore::getInstance();
    $inUser = cmsUser::getInstance();
	$inPage = cmsPage::getInstance();
	$inDB   = cmsDatabase::getInstance();
	$inActions = cmsActions::getInstance();

    cmsCore::loadModel('actions');
    $model = new cms_model_actions();

    global $_LANG;

    $do      = $inCore->do;
	$page    = cmsCore::request('page', 'int', 1);
	$user_id = cmsCore::request('user_id', 'int', 0);
	$perpage = 6;

//======================================================================================================================//

    if ($do=='delete'){

        if (!$inUser->is_admin) { cmsCore::error404(); }

        $id = cmsCore::request('id', 'int', 0);
        if (!$id) { cmsCore::error404(); }

        $model->deleteAction($id);
        cmsCore::redirectBack();

    }

//======================================================================================================================//

    if ($do=='view'){

		$inPage->setTitle($_LANG['FEED_EVENTS']);
		$inPage->addPathway($_LANG['FEED_EVENTS']);

        $inActions->showTargets($model->config['show_target']);

		if($model->config['act_type'] && !$model->config['is_all']){
        	$inActions->onlySelectedTypes($model->config['act_type']);
		}

		$total = $inActions->getCountActions();

        $inDB->limitPage($page, $model->config['perpage']);

        $actions = $inActions->getActionsLog();
		if(!$actions && $page > 1){ cmsCore::error404(); }

        $smarty = $inCore->initSmarty('components', 'com_actions_view.tpl');
        $smarty->assign('actions', $actions);
		$smarty->assign('total', $total);
		$smarty->assign('user_id', $inUser->id);
		$smarty->assign('pagebar', cmsPage::getPagebar($total, $page, $model->config['perpage'], '/actions/page-%page%'));
        $smarty->display('com_actions_view.tpl');


    }

//======================================================================================================================//

    if ($do=='view_user_feed'){

		if(!$inUser->id) { exit; }

		if($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') { exit; }

		// Получаем друзей
		$friends = cmsUser::getFriends($inUser->id);

		$friends_total = count($friends);

		// нам нужно только определенное количество друзей
		$friends = array_slice($friends, ($page-1)*$perpage, $perpage, true);

		if($friends){

			$inActions->onlyMyFriends();

			$inActions->showTargets($model->config['show_target']);
			$inDB->limitIs($model->config['perpage_tab']);
        	$actions = $inActions->getActionsLog();
			// получаем первый элемент массива для выборки оттуда имя пользователя и ссылку на профиль.
		}

        $smarty = $inCore->initSmarty('components', 'com_actions_view_tab.tpl');
        $smarty->assign('actions', $actions);
		$smarty->assign('friends', $friends);
		$smarty->assign('user_id', $user_id);
		$smarty->assign('page', $page);
		$smarty->assign('cfg', $model->config);
		$smarty->assign('total_pages', ceil($friends_total / $perpage));
		$smarty->assign('friends_total', $friends_total);
        $smarty->display('com_actions_view_tab.tpl');
		echo ob_get_clean(); exit;

    }
//======================================================================================================================//
    if ($do=='view_user_feed_only'){

		if(!$inUser->id) { exit; }

		if($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') { exit; }

		if($user_id){
			if(!cmsUser::isFriend($user_id)) { exit; }
			$inActions->whereUserIs($user_id);
		} else {
			$inActions->onlyMyFriends();
		}

		$inActions->showTargets($model->config['show_target']);
		$inDB->limitIs($model->config['perpage_tab']);
		$actions = $inActions->getActionsLog();
		// получаем последний элемент массива для выборки имя пользователя и ссылки на профиль.
		$user = end($actions);

        $smarty = $inCore->initSmarty('components', 'com_actions_tab.tpl');
        $smarty->assign('actions', $actions);
		$smarty->assign('user_id', $user_id);
		$smarty->assign('user', $user);
		$smarty->assign('cfg', $model->config);
        $smarty->display('com_actions_tab.tpl');
		echo ob_get_clean(); exit;

    }
//======================================================================================================================//
    if ($do=='view_user_friends_only'){

		if(!$inUser->id) { exit; }

		if($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') { exit; }

		// Получаем друзей
		$friends = cmsUser::getFriends($inUser->id);

		$friends_total = count($friends);

		// нам нужно только определенное количество друзей
		$friends = array_slice($friends, ($page-1)*$perpage, $perpage, true);

        $smarty = $inCore->initSmarty('components', 'com_actions_friends.tpl');
		$smarty->assign('friends', $friends);
		$smarty->assign('page', $page);
		$smarty->assign('user_id', $user_id);
		$smarty->assign('total_pages', ceil($friends_total / $perpage));
		$smarty->assign('friends_total', $friends_total);
        $smarty->display('com_actions_friends.tpl');
		echo ob_get_clean(); exit;

    }

}
?>