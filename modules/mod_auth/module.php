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

	function mod_auth($module_id){

        $inCore = cmsCore::getInstance();
        $inUser = cmsUser::getInstance();

		$cfg = $inCore->loadModuleConfig($module_id);

		if ($inUser->id){ return false; }

		cmsUser::sessionPut('auth_back_url', $inCore->getBackURL());

        $smarty = $inCore->initSmarty('modules', 'mod_auth.tpl');
        $smarty->assign('cfg', $cfg);
        $smarty->display('mod_auth.tpl');

		return true;

	}
?>