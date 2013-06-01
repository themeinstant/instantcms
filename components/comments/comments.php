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

	define('PATH', $_SERVER['DOCUMENT_ROOT']);
	include(PATH.'/core/ajax/ajax_core.php');

    cmsCore::loadModel('comments');
    $model = new cms_model_comments();
    // Проверяем включен ли компонент
    if(!$inCore->isComponentEnable('comments')) { cmsCore::halt(); }
	// Инициализируем права доступа для группы текущего пользователя
	$model->initAccess();

    $smarty = $inCore->initSmarty();

    $target    = cmsCore::request('target', 'str');
    $target_id = cmsCore::request('target_id', 'int');

	if(!$target || !$target_id) { cmsCore::halt(); }

	$model->whereTargetIs($target, $target_id);

	$inDB->orderBy('c.pubdate', 'ASC');

	$comments = $model->getComments(!($inUser->is_admin || $model->is_can_moderate), true);

    ob_start();

	$smarty = $inCore->initSmarty('components', 'com_comments_list.tpl');
	$smarty->assign('comments_count', count($comments));
	$smarty->assign('comments', $comments);
	$smarty->assign('user_can_moderate', $model->is_can_moderate);
	$smarty->assign('user_can_delete', $model->is_can_delete);
	$smarty->assign('user_can_add', $model->is_can_add);
	$smarty->assign('is_admin', $inUser->is_admin);
	$smarty->assign('is_user', $inUser->id);
	$smarty->assign('cfg', $model->config);
	$smarty->assign('labels', $model->labels);
	$smarty->assign('target', $target);
	$smarty->assign('target_id', $target_id);
	$smarty->display('com_comments_list.tpl');

    cmsCore::halt(ob_get_clean());

?>