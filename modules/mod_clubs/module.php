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

function mod_clubs($module_id){

	$inCore = cmsCore::getInstance();
	$inDB   = cmsDatabase::getInstance();
	$cfg    = $inCore->loadModuleConfig($module_id);

	if (!isset($cfg['count'])) { $cfg['count'] = 5; }
	if (!isset($cfg['type'])) { $cfg['type'] = 'id'; }
	if (!isset($cfg['vip_on_top'])) { $cfg['vip_on_top'] = 1; }

    cmsCore::loadModel('clubs');
    $model = new cms_model_clubs();

	if(!$model->config['component_enabled']) { return false; }

	if($cfg['vip_on_top']){
		$inDB->orderBy('is_vip', 'DESC, c.'.$cfg['type'].' DESC');
	} else {
		$inDB->orderBy('c.'.$cfg['type'], 'DESC');
	}
	$inDB->limit($cfg['count']);

	$total = $model->getClubsCount();

    $clubs = $model->getClubs();
	if(!$clubs){ return false; }

	$smarty = $inCore->initSmarty('modules', 'mod_clubs.tpl');			
	$smarty->assign('clubs', $clubs);
	$smarty->assign('is_clubs', $is_clubs);
	$smarty->display('mod_clubs.tpl');
				
	return true;
	
}
?>