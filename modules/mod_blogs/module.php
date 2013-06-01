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

function mod_blogs($module_id){

	$inCore = cmsCore::getInstance();
	$inDB   = cmsDatabase::getInstance();

	// конфигурация модуля
	$cfg = $inCore->loadModuleConfig($module_id);
	$default_cfg = array (
				  'sort' => 'pubdate',
				  'owner' => 'user',
				  'shownum' => 5,
				  'minrate' => 0,
                  'blog_id' => 0,
				  'showrss' => 1
				);
	$cfg = array_merge($default_cfg, $cfg);

	cmsCore::loadClass('blog');
	$inBlog = cmsBlogs::getInstance();
	$inBlog->owner = $cfg['owner'];

	if($cfg['owner'] == 'club'){
		cmsCore::loadModel('clubs');
		$model = new cms_model_clubs();
		$inDB->addSelect('b.user_id as bloglink');
	} else {
		cmsCore::loadModel('blogs');
		$model = new cms_model_blogs();
	}

	// получаем аватары владельцев
	$inDB->addSelect('up.imageurl');
	$inDB->addJoin('LEFT JOIN cms_user_profiles up ON up.user_id = u.id');

	$inBlog->whereOnlyPublic();

	if($cfg['minrate']){
		$inBlog->ratingGreaterThan($cfg['minrate']);
	}

	if($cfg['blog_id']){
		$inBlog->whereBlogIs($cfg['blog_id']);
	}

    $inDB->orderBy('p.'.$cfg['sort'], 'DESC');

    $inDB->limit($cfg['shownum']);

	$posts = $inBlog->getPosts(false, $model);
	if(!$posts){ return false; }

	$smarty = $inCore->initSmarty('modules', 'mod_blogs.tpl');
	$smarty->assign('posts', $posts);
	$smarty->assign('cfg', $cfg);
	$smarty->display('mod_blogs.tpl');

	return true;

}
?>