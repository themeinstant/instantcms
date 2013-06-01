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

function mod_usermenu($module_id){

	$inCore = cmsCore::getInstance();
	$inUser = cmsUser::getInstance();

	if (!$inUser->id){ return false; }

	$is_billing = $inCore->isComponentInstalled('billing');

	$smarty = $inCore->initSmarty('modules', 'mod_usermenu.tpl');
	$smarty->assign('avatar', $inUser->imageurl);
	$smarty->assign('nickname', $inUser->nickname);
	$smarty->assign('login', $inUser->login);
	$smarty->assign('id', $inUser->id);
	$smarty->assign('newmsg', cmsUser::getNewMessages($inUser->id));
	$smarty->assign('is_can_add', cmsUser::isUserCan('content/add'));
	$smarty->assign('is_admin', $inUser->is_admin);
	$smarty->assign('is_editor', cmsUser::userIsEditor());
	$smarty->assign('cfg', $inCore->loadModuleConfig($module_id));
	$smarty->assign('users_cfg', $inCore->loadComponentConfig('users'));
	$smarty->assign('is_billing', $is_billing);
	$smarty->assign('balance', $is_billing ? $inUser->balance : 0);
	$smarty->display('mod_usermenu.tpl');

	return true;

}
?>