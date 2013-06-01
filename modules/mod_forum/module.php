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

function mod_forum($module_id){

    $inCore = cmsCore::getInstance();
    $inDB   = cmsDatabase::getInstance();

	// конфигурация модуля
	$cfg = $inCore->loadModuleConfig($module_id);
	$default_cfg = array (
				  'shownum' => 4,
				  'cat_id' => 0,
				  'forum_id' => 0,
				  'subs' => 0,
                  'show_hidden' => 0,
				  'show_pinned' => 0,
				  'showtext' => 1,
				  'showforum' => 0,
				  'order' => 'pubdate'
				);
	$cfg = array_merge($default_cfg, $cfg);

    cmsCore::loadModel('forum');
    $model = new cms_model_forum();

    if(!$model->config['component_enabled']) { return false; }

    if($cfg['cat_id'] ||
            ($cfg['forum_id'] && $cfg['subs']) ||
            $cfg['showforum']){

        $inDB->addJoin('INNER JOIN cms_forums f ON f.id = t.forum_id');
        $inDB->addSelect('f.title as forum_title');

    }

    if($cfg['cat_id']){
        $model->whereForumCatIs($cfg['cat_id']);
    }

    if($cfg['forum_id']){

        if($cfg['subs']){

            $forum = $model->getForum($cfg['forum_id']);
            if(!$forum){ return false; }

            $model->whereThisAndNestedForum($forum['NSLeft'], $forum['NSRight']);

        } else {
            $model->whereForumIs($cfg['forum_id']);
        }

    }

    if(!$cfg['show_hidden']){
        $model->wherePublicThreads();
    }

    if($cfg['show_pinned']){
        $model->wherePinnedThreads();
    }

    $inDB->orderBy('t.'.$cfg['order'], 'DESC');
    $inDB->limit($cfg['shownum']);
    $threads = $model->getThreads();

    $smarty = $inCore->initSmarty('modules', 'mod_forum.tpl');
    $smarty->assign('threads', $threads);
    $smarty->assign('cfg', $cfg);
    $smarty->display('mod_forum.tpl');

    return true;

}
?>