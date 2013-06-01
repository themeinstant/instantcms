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

function mod_actions($module_id){

        $inCore = cmsCore::getInstance();
        $inDB   = cmsDatabase::getInstance();
		$inActions = cmsActions::getInstance();

        global $_LANG;

        $cfg = $inCore->loadModuleConfig($module_id);

		if (!isset($cfg['show_target'])) { $cfg['show_target'] = 1; }
		if (!isset($cfg['limit'])) { $cfg['limit'] = 15; }
		if (!isset($cfg['show_link'])) { $cfg['show_link'] = 1; }
        if (!isset($cfg['action_types'])) { echo $_LANG['MODULE_NOT_CONFIGURED']; return true; }

        if (!$cfg['show_target']){ $inActions->showTargets(false); }

        $inActions->onlySelectedTypes($cfg['action_types']);
        $inDB->limitIs($cfg['limit']);

        $actions = $inActions->getActionsLog();

        $smarty = $inCore->initSmarty('modules', 'mod_actions.tpl');
        $smarty->assign('actions', $actions);
		$smarty->assign('cfg', $cfg);
		$smarty->assign('user_id', cmsUser::getInstance()->id);
        $smarty->display('mod_actions.tpl');

		return true;

}
?>
