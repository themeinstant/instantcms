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

	function mod_lastreg($module_id){

        $inCore = cmsCore::getInstance();
        $inDB   = cmsDatabase::getInstance();

		$cfg = $inCore->loadModuleConfig($module_id);

		$inCore->loadModel('users');
		$model = new cms_model_users();

		if(!$model->config['component_enabled']) { return false; }

		$inDB->orderBy('regdate', 'DESC');

		$inDB->limitPage(1, $cfg['newscount']);

		$users = $model->getUsers();

		if ($cfg['view_type']=='list'){
			$total_all = cmsUser::getCountAllUsers();
		}

		$smarty = $inCore->initSmarty('modules', 'mod_lastreg.tpl');
		$smarty->assign('usrs', $users);
		$smarty->assign('cfg', $cfg);
		if ($cfg['view_type']=='list'){
			$smarty->assign('total_all', $total_all);
			$smarty->assign('total', sizeof($users));
		}
		$smarty->display('mod_lastreg.tpl');

		return true;

	}
?>