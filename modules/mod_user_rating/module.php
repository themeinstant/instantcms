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

	function mod_user_rating($module_id){

        $inCore = cmsCore::getInstance();
		$inDB   = cmsDatabase::getInstance();
		$inCore->loadModel('users');
		$model = new cms_model_users();
		
		if(!$model->config['component_enabled']) { return false; }	
	
		$cfg = $inCore->loadModuleConfig($module_id);

        if (!isset($cfg['count'])) { $cfg['count'] = 20; }
		if (!isset($cfg['view_type'])) { $cfg['view_type'] = 'rating'; }

		if(!in_array($cfg['view_type'], array('karma', 'rating'))) { $cfg['view_type'] = 'rating'; }

		$inDB->orderBy($cfg['view_type'], 'DESC');

		$inDB->limitPage(1, $cfg['count']);

		$users = $model->getUsers();
	
		$smarty = $inCore->initSmarty('modules', 'mod_user_rating.tpl');			
		$smarty->assign('users', $users);
		$smarty->assign('cfg', $cfg);
		$smarty->display('mod_user_rating.tpl');
				
		return true;
	
	}
?>