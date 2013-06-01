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

function mod_latest($module_id){

	$inCore = cmsCore::getInstance();
	$inDB   = cmsDatabase::getInstance();

	$inCore->loadModel('content');
	$model = new cms_model_content();

	$cfg = $inCore->loadModuleConfig($module_id);

	if (!isset($cfg['showrss'])) { $cfg['showrss'] = 1; }
	if (!isset($cfg['subs'])) { $cfg['subs'] = 1; }
	if (!isset($cfg['cat_id'])) { $cfg['cat_id'] = 1; }
	if (!isset($cfg['newscount'])) { $cfg['newscount'] = 5; }

	if($cfg['cat_id']){

		if (!$cfg['subs']){
	
			//выбираем из категории
			$model->whereCatIs($cfg['cat_id']);
	
		} else {
	
			//выбираем из категории и подкатегорий
			$rootcat = $inDB->getNsCategory('cms_category', $cfg['cat_id']);
			if(!$rootcat) { return false; }
			$model->whereThisAndNestedCats($rootcat['NSLeft'], $rootcat['NSRight']);
	
		}

	}

	$inDB->where("con.showlatest = 1");

	if ($cfg['is_pag']){
		$total = $model->getArticlesCount();
	}

	$inDB->orderBy('con.pubdate', 'DESC');
	$inDB->limitPage(1, $cfg['newscount']);

	$content_list = $model->getArticlesList();
	if(!$content_list) { return false; }

	$smarty = $inCore->initSmarty('modules', 'mod_latest.tpl');			
	$smarty->assign('articles', $content_list);
	if ($cfg['is_pag']) {
		$smarty->assign('pagebar_module', cmsPage::getPagebar($total, 1, $cfg['newscount'], 'javascript:conPage(%page%, '.$module_id.')'));
	}
	$smarty->assign('is_ajax', false);
	$smarty->assign('module_id', $module_id);
	$smarty->assign('cfg', $cfg);
	$smarty->display('mod_latest.tpl');			

	return true;

}
?>