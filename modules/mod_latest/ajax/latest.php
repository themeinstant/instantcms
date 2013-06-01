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

	cmsCore::loadLanguage('modules/mod_latest');

    $smarty = $inCore->initSmarty();

	$page	    = cmsCore::request('page', 'int', 1);	
	$module_id	= cmsCore::request('module_id', 'int', '');

	if(!$page || !$module_id) { cmsCore::halt(); }

	$cfg = $inCore->loadModuleConfig($module_id);
	if (!isset($cfg['showrss'])) { $cfg['showrss'] = 1; }
	if (!isset($cfg['subs'])) { $cfg['subs'] = 1; }
	if (!isset($cfg['cat_id'])) { $cfg['cat_id'] = 1; }
	if (!isset($cfg['newscount'])) { $cfg['newscount'] = 5; }

	// Если пагинация отключена, выходим
	if (!$cfg['is_pag']) { cmsCore::halt(); }

	$inCore->loadModel('content');
	$model = new cms_model_content();

	if($cfg['cat_id']){

		if (!$cfg['subs']){
	
			//выбираем из категории
			$model->whereCatIs($cfg['cat_id']);
	
		} else {
	
			//выбираем из категории и подкатегорий
			$rootcat = $inDB->getNsCategory('cms_category', $cfg['cat_id']);
			if(!$rootcat) { cmsCore::halt(); }
			$model->whereThisAndNestedCats($rootcat['NSLeft'], $rootcat['NSRight']);
	
		}

	}

	$inDB->where("con.showlatest = 1");

	$total = $model->getArticlesCount();

	$inDB->orderBy('con.pubdate', 'DESC');
	$inDB->limitPage($page, $cfg['newscount']);

	$content_list = $model->getArticlesList();
	if(!$content_list) { cmsCore::halt(); }

	$smarty = $inCore->initSmarty('modules', 'mod_latest.tpl');			
	$smarty->assign('articles', $content_list);
	$smarty->assign('pagebar_module', cmsPage::getPagebar($total, $page, $cfg['newscount'], 'javascript:conPage(%page%, '.$module_id.')'));
	$smarty->assign('is_ajax', true);
	$smarty->assign('module_id', $module_id);
	$smarty->assign('cfg', $cfg);
	$smarty->display('mod_latest.tpl');		

?>
