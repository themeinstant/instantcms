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

function mod_category($module_id){

	$inCore = cmsCore::getInstance();
	$inDB   = cmsDatabase::getInstance();

	$inCore->loadModel('content');
	$model = new cms_model_content();

	$cfg = $inCore->loadModuleConfig($module_id);

	if (!isset($cfg['category_id'])){ $cfg['category_id'] = 0; }
	if (!isset($cfg['show_subcats'])) { $cfg['show_subcats'] = 1; }
	if (!isset($cfg['expand_all'])) { $cfg['expand_all'] = 1; }

	$rootcat = $inDB->getNsCategory('cms_category', $cfg['category_id']);
	if(!$rootcat) { return false; }

	$subcats_list = $model->getSubCats($rootcat['id'], $cfg['show_subcats'], $rootcat['NSLeft'], $rootcat['NSRight']);
	if(!$subcats_list){ return false; }

	$current_seolink = urldecode($inCore->request('seolink', 'str', ''));

    $smarty = $inCore->initSmarty('modules', 'mod_content_cats.tpl');
    $smarty->assign('cfg', $cfg);
	$smarty->assign('current_seolink', $current_seolink);
    $smarty->assign('subcats_list', $subcats_list);
    $smarty->display('mod_content_cats.tpl');

	return true;

}
?>