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
if(!defined('VALID_CMS')) { die('ACCESS DENIED'); }

function forum(){

    $inCore = cmsCore::getInstance();
    $inPage = cmsPage::getInstance();
    $inDB   = cmsDatabase::getInstance();
    $inUser = cmsUser::getInstance();

    cmsCore::loadModel('forum');
    $model = new cms_model_forum();

    define('IS_BILLING', $inCore->isComponentInstalled('billing'));
    if (IS_BILLING) { cmsCore::loadClass('billing'); }

    global $_LANG;

    $pagetitle = $inCore->menuTitle();
	$pagetitle = ($pagetitle && $inCore->isMenuIdStrict()) ? $pagetitle : $_LANG['FORUMS'];

	$inPage->addPathway($pagetitle, '/forum');
	$inPage->setTitle($pagetitle);
	$inPage->setDescription($pagetitle);

	$id	  = cmsCore::request('id', 'int', 0);
	$do   = $inCore->do;
	$page = cmsCore::request('page', 'int', 1);

    $inPage->addHeadJS('components/forum/js/common.js');

//============================================================================//
//=============================== Список Форумов  ============================//
//============================================================================//
if ($do=='view'){

    $inPage->addHead('<link rel="alternate" type="application/rss+xml" title="'.$_LANG['FORUMS'].'" href="'.HOST.'/rss/forum/all/feed.rss">');

    $forums = $model->getForums();

    $smarty = $inCore->initSmarty('components', 'com_forum_list.tpl');
    $smarty->assign('pagetitle', $pagetitle);
    $smarty->assign('forums', $forums);
    $smarty->assign('forum', array());
    $smarty->assign('user_id', $inUser->id);
    $smarty->assign('cfg', $model->config);
    $smarty->display('com_forum_list.tpl');

}
//============================================================================//
//================ Список тем форума + список подфорумов  ====================//
//============================================================================//
if ($do=='forum'){

    $forum = $model->getForum($id);
    if(!$forum){ cmsCore::error404(); }

    $moderators = $model->getForumModerators($forum['moder_list']);

    // опции просмотра
    $order_by  = cmsCore::getSearchVar('order_by', 'pubdate');
    $order_to  = cmsCore::getSearchVar('order_to', 'desc');
    $daysprune = (int)cmsCore::getSearchVar('daysprune');

	if(!cmsCore::checkContentAccess($forum['access_list'])) {
		cmsPage::includeTemplateFile('special/accessdenied.php');
		return;
	}

    $inPage->setTitle($forum['title']);
    $inPage->setDescription($forum['description'] ? $forum['description'] : $forum['title']);
	$inPage->addHead('<link rel="alternate" type="application/rss+xml" title="'.htmlspecialchars($forum['title']).'" href="'.HOST.'/rss/forum/'.$forum['id'].'/feed.rss">');

	// Получаем дерево форумов
    $path_list = $inDB->getNsCategoryPath('cms_forums', $forum['NSLeft'], $forum['NSRight'], 'id, title, access_list, moder_list');
    // Строим глубиномер
    if ($path_list){
        foreach($path_list as $pcat){
			if(!cmsCore::checkContentAccess($pcat['access_list'])){
                cmsPage::includeTemplateFile('special/accessdenied.php');
                return;
			}
            $inPage->addPathway($pcat['title'], '/forum/'.$pcat['id']);
        }
    }

    // Получим подфорумы
    $model->whereNestedForum($forum['NSLeft'], $forum['NSRight']);
    $sub_forums = $model->getForums();

    $smarty = $inCore->initSmarty('components', 'com_forum_list.tpl');
    $smarty->assign('pagetitle', $forum['title']);
    $smarty->assign('forums', $sub_forums);
    $smarty->assign('forum', $forum);
    $smarty->assign('cfg', $model->config);
    $smarty->assign('user_id', $inUser->id);
    $smarty->display('com_forum_list.tpl');

    // Получим темы
    if($daysprune){
        $model->whereDayIntervalIs($daysprune);
    }
    $model->whereForumIs($forum['id']);
    $inDB->orderBy('t.pinned', 'DESC, t.'.$order_by.' '.$order_to);
    $inDB->limitPage($page, $model->config['pp_forum']);
    $threads = $model->getThreads();

    $smarty = $inCore->initSmarty('components', 'com_forum_view.tpl');
    $smarty->assign('threads', $threads);
    $smarty->assign('show_panel', true);
    $smarty->assign('order_by', $order_by);
    $smarty->assign('order_to', $order_to);
    $smarty->assign('daysprune', $daysprune);
    $smarty->assign('moderators', $moderators);
    $smarty->assign('pagination', cmsPage::getPagebar($forum['thread_count'], $page, $model->config['pp_forum'], '/forum/'.$forum['id'].'-%page%'));
    $smarty->display('com_forum_view.tpl');

}
//============================================================================//
//======================== Просмотр темы форума  =============================//
//============================================================================//
if ($do=='thread'){

    $thread = $model->getThread($id);
    if(!$thread) { cmsCore::error404(); }

    // Строим глубиномер
    $path_list = $inDB->getNsCategoryPath('cms_forums', $thread['NSLeft'], $thread['NSRight'], 'id, title, access_list, moder_list');
    if ($path_list){
        foreach($path_list as $pcat){
			if(!cmsCore::checkContentAccess($pcat['access_list'])){
                cmsPage::includeTemplateFile('special/accessdenied.php');
                return;
			}
            $inPage->addPathway($pcat['title'], '/forum/'.$pcat['id']);
        }
        // Для последнего форума проверяем
        // не модератор ли текущий пользователь
        $is_forum_moder = $model->isForumModerator($pcat['moder_list']);
    }
    $inPage->addPathway($thread['title'], '/forum/thread'.$thread['id'].'.html');

	$inPage->setTitle($thread['title']);
	$inPage->setDescription($thread['description'] ? $thread['description'] : $thread['title']);

    if(!$thread['is_mythread']){
        $inDB->setFlag('cms_forum_threads', $thread['id'], 'hits', $thread['hits']+1);
    }

    // получаем посты
    $model->whereThreadIs($thread['id']);
    $inDB->orderBy('p.pinned', 'DESC, p.pubdate ASC');
    $inDB->limitPage($page, $model->config['pp_thread']);
    $posts = $model->getPosts();
    if(!$posts){ cmsCore::error404(); }

    $smarty = $inCore->initSmarty('components', 'com_forum_view_thread.tpl');
    $smarty->assign('forum', $pcat);
    $smarty->assign('forums', $model->getForums());
    $smarty->assign('is_subscribed', cmsUser::isSubscribed($inUser->id, 'forum', $thread['id']));
    $smarty->assign('thread', $thread);
    $smarty->assign('prev_thread', $inDB->get_fields('cms_forum_threads',
                                                     "id < '{$thread['id']}' AND forum_id = '{$thread['forum_id']}'",
                                                     'id, title', 'id DESC'));
    $smarty->assign('next_thread', $inDB->get_fields('cms_forum_threads',
                                                     "id > '{$thread['id']}' AND forum_id = '{$thread['forum_id']}'",
                                                     'id, title', 'id ASC'));
    $smarty->assign('posts', $posts);
    $smarty->assign('thread_poll', $model->getThreadPoll($thread['id']));
    $smarty->assign('page', $page);
    $smarty->assign('num', (($page-1)*$model->config['pp_thread'])+1);
    $smarty->assign('lastpage', ceil($thread['post_count'] / $model->config['pp_thread']));
    $smarty->assign('pagebar', cmsPage::getPagebar($thread['post_count'], $page, $model->config['pp_thread'], '/forum/thread'.$thread['id'].'-%page%.html'));
    $smarty->assign('user_id', $inUser->id);
    $smarty->assign('do', $do);
    $smarty->assign('is_moder', $is_forum_moder);
    $smarty->assign('is_admin', $inUser->is_admin);
    $smarty->assign('cfg', $model->config);
    $smarty->assign('bb_toolbar', ($inUser->id && $model->config['fast_on'] && $model->config['fast_bb']) ?
                                            cmsPage::getBBCodeToolbar('message', $model->config['img_on']) : '');
    $smarty->assign('smilies', ($inUser->id && $model->config['fast_on'] && $model->config['fast_bb']) ?
                                            cmsPage::getSmilesPanel('message') : '');
    $smarty->display('com_forum_view_thread.tpl');

}
//============================================================================//
//================ Новая тема, написать/редактировать пост ===================//
//============================================================================//
if (in_array($do, array('newthread','newpost','editpost'))){

    if (!$inUser->id){ cmsUser::goToLogin(); }

    // id первого поста в теме
    $first_post_id = false;
    // опросов по умолчанию нет
    $thread_poll   = array();
    // применяется при редактировании поста
    $is_allow_attach = true;

    // новая тема
    if ($do == 'newthread') {

        $forum = $model->getForum($id);
        if(!$forum) { cmsCore::error404(); }
        if(!cmsCore::checkContentAccess($forum['access_list'])) {
            cmsPage::includeTemplateFile('special/accessdenied.php');
            return;
        }

        $path_list = $inDB->getNsCategoryPath('cms_forums', $forum['NSLeft'], $forum['NSRight'], 'id, title, access_list, moder_list');
        if ($path_list){
            foreach($path_list as $pcat){
                if(!cmsCore::checkContentAccess($pcat['access_list'])){
                    cmsPage::includeTemplateFile('special/accessdenied.php');
                    return;
                }
                $inPage->addPathway($pcat['title'], '/forum/'.$pcat['id']);
            }
            $is_forum_moder = $model->isForumModerator($pcat['moder_list']);
        }

        if (IS_BILLING && $forum['topic_cost']){
            cmsBilling::checkBalance('forum', 'add_thread', false, $forum['topic_cost']);
        }

        $pagetitle = $_LANG['NEW_THREAD'];

        $thread = cmsUser::sessionGet('thread');
        if($thread) { cmsUser::sessionDel('thread'); }

        $last_post['content'] = cmsUser::sessionGet('post_content');
        if($last_post['content']) { cmsUser::sessionDel('post_content'); }

    }
    // новый пост
    if ($do == 'newpost'){

        $thread = $model->getThread($id);
        if(!$thread || $thread['closed']) { cmsCore::error404(); }

        $path_list = $inDB->getNsCategoryPath('cms_forums', $thread['NSLeft'], $thread['NSRight'], 'id, title, access_list, moder_list');
        if ($path_list){
            foreach($path_list as $pcat){
                if(!cmsCore::checkContentAccess($pcat['access_list'])){
                    cmsPage::includeTemplateFile('special/accessdenied.php');
                    return;
                }
                $inPage->addPathway($pcat['title'], '/forum/'.$pcat['id']);
            }
            $is_forum_moder = $model->isForumModerator($pcat['moder_list']);
        }
        $inPage->addPathway($thread['title'], '/forum/thread'.$thread['id'].'.html');

        $pagetitle = $_LANG['NEW_POST'];

        $last_post = $model->getPost(cmsCore::request('replyid', 'int', 0));
        if($last_post){
            $last_post['content'] =  preg_replace('/\[hide(.*?)\](.*?)\[\/hide\]/sui', '', $last_post['content']);
            $last_post['content'] =  preg_replace('/\[hide(.*?)\](.*?)$/sui', '', $last_post['content']);
            $quote_nickname = $inDB->get_field('cms_users', "id = '{$last_post['user_id']}'", 'nickname');
            $last_post['content'] = '[quote='.$quote_nickname.']'."\r\n".$last_post['content']."\r\n".'[/quote]'."\r\n\r\n";
            $pagetitle = $_LANG['REPLY_FULL_QUOTE'];
        }

    }
    // редактирование поста
    if ($do == 'editpost'){

        $last_post = $model->getPost($id);
        if(!$last_post) { cmsCore::error404(); }

        $is_allow_attach = $last_post['attach_count'] < $model->config['fa_max'];
        // уменьшаем значение настроек согласно загруженных файлов
        $model->config['fa_max'] = $model->config['fa_max'] - $last_post['attach_count'];

        $thread = $model->getThread($last_post['thread_id']);
        if(!$thread || $thread['closed']) { cmsCore::error404(); }

        $path_list = $inDB->getNsCategoryPath('cms_forums', $thread['NSLeft'], $thread['NSRight'], 'id, title, access_list, moder_list');
        if ($path_list){
            foreach($path_list as $pcat){
                if(!cmsCore::checkContentAccess($pcat['access_list'])){
                    cmsPage::includeTemplateFile('special/accessdenied.php');
                    return;
                }
                $inPage->addPathway($pcat['title'], '/forum/'.$pcat['id']);
            }
            $is_forum_moder = $model->isForumModerator($pcat['moder_list']);
        }
        $inPage->addPathway($thread['title'], '/forum/thread'.$thread['id'].'.html');

        $end_min = $model->checkEditTime($last_post['pubdate']);
        $is_author_can_edit = (is_bool($end_min) ? $end_min : $end_min > 0) &&
                                      ($last_post['user_id'] == $inUser->id);

        // редактировать могут только администраторы, модераторы или авторы,  если время есть
        if(!$inUser->is_admin && !$is_forum_moder && !$is_author_can_edit) { cmsCore::error404(); }

        if(!$inUser->is_admin && !$is_forum_moder && $model->config['edit_minutes']){

            $msg_minute = str_replace('{min}', cmsCore::spellCount($end_min, $_LANG['MINUTE1'], $_LANG['MINUTE2'], $_LANG['MINUTE10']), $_LANG['EDIT_INFO']);
            cmsCore::addSessionMessage($msg_minute, 'info');

        }

        $first_post_id = $inDB->get_field('cms_forum_posts', "thread_id = '{$thread['id']}' ORDER BY pubdate ASC", 'id');

        $thread_poll = $model->getThreadPoll($thread['id']);

        $pagetitle = $_LANG['EDIT_POST'];

    }

    /////////////////////////
    ///  Показываем форму ///
    /////////////////////////
    if(!cmsCore::inRequest('gosend')){

        $inPage->setTitle($pagetitle);
        $inPage->addPathway($pagetitle);

        cmsCore::initAutoGrowText('#message');

        $smarty = $inCore->initSmarty('components', 'com_forum_add.tpl');
        $smarty->assign('pagetitle', $pagetitle);
        $smarty->assign('is_first_post', $first_post_id == $last_post['id']);
        $smarty->assign('thread_poll', $thread_poll);
        $smarty->assign('cfg', $model->config);
        $smarty->assign('do', $do);
        $smarty->assign('forum', isset($forum)? $forum : $pcat);
        $smarty->assign('is_subscribed', cmsUser::isSubscribed($inUser->id, 'forum', @$thread['id']));
        $smarty->assign('thread', $thread);
        $smarty->assign('post_content', htmlspecialchars($last_post['content']));
        $smarty->assign('is_moder', $is_forum_moder);
        $smarty->assign('is_admin', $inUser->is_admin);
        $smarty->assign('is_allow_attach', cmsCore::checkContentAccess($model->config['group_access']) && $is_allow_attach);
        $smarty->assign('bb_toolbar', cmsPage::getBBCodeToolbar('message', $model->config['img_on'], 'forum', 'post', @$last_post['id']));
        $smarty->assign('smilies', cmsPage::getSmilesPanel('message'));
        $smarty->display('com_forum_add.tpl');

    } else {
    /////////////////////////
    // Выполняем действия ///
    /////////////////////////

        if(!cmsCore::validateForm()) { cmsCore::error404(); }

		$message_bb   = $inDB->escape_string(cmsCore::request('message', 'html', ''));
		$message_html = $inDB->escape_string(cmsCore::parseSmiles(cmsCore::request('message', 'html', ''), true));

		if (!$message_html) { cmsCore::addSessionMessage($_LANG['NEED_TEXT_POST'], 'error'); cmsCore::redirectBack(); }

        $message_post = strip_tags($message_html);
        $message_post = mb_strlen($message_post)>200 ? mb_substr($message_post, 0, 200) : $message_post;

        $post_pinned = 0;

        if(in_array($do, array('newthread','newpost'))){

            if ($do=='newthread'){

				$thread['title']       = cmsCore::request('title', 'str', '');
				$thread['description'] = cmsCore::request('description', 'str', '');

                $post_pinned = 1;

                if (!$thread['title']) {
                    cmsCore::addSessionMessage($_LANG['NEED_TITLE_THREAD_YOUR_POST'], 'error');
                    cmsUser::sessionPut('thread', $thread);
                    cmsUser::sessionPut('post_content', stripcslashes($message_bb));
                    cmsCore::redirectBack();
                }

                $thread['is_hidden'] = cmsCore::yamlToArray($forum['access_list']) ? 1 : 0;
                $thread['forum_id']  = $forum['id'];
                $thread['user_id']   = $inUser->id;
                $thread['pubdate']   = date("Y-m-d H:i:s");
                $thread['hits']      = 0;

                $thread['id'] = $model->addThread($thread);

                $thread['NSLeft']  = $forum['NSLeft'];
                $thread['NSRight'] = $forum['NSRight'];
                $thread['post_count'] = 0;

                if (IS_BILLING && $forum['topic_cost']){
                    cmsBilling::process('forum', 'add_thread', $forum['topic_cost']);
                }

            }

			$post_id = $model->addPost(array(
                            'thread_id' => $thread['id'],
                            'user_id' => $inUser->id,
                            'pinned' => $post_pinned,
                            'content' => $message_bb,
                            'content_html' => $message_html,
                            'pubdate' => date("Y-m-d H:i:s"),
                            'editdate' => date("Y-m-d H:i:s")
                        ));

            // Обновляем количество постов в теме
            $thread_post_count = $model->updateThreadPostCount($thread['id']);
            // Закрываем тему если нужно
            $is_fixed = cmsCore::request('fixed', 'int', 0);
            if($is_fixed && ($is_forum_moder || $inUser->is_admin || $thread['is_mythread'])){
                $model->closeThread($thread['id']);
            }
            // Загружаем аттачи
            if($model->config['fa_on'] && cmsCore::checkContentAccess($model->config['group_access'])){

                $file_error = $model->addUpdatePostAttachments($post_id);

                if($file_error === false){
                    cmsCore::addSessionMessage($_LANG['CHECK_SIZE_TYPE_FILE'].$model->config['fa_max'], 'error');
                }

            }
            // Проверяем награды
            cmsUser::checkAwards($inUser->id);
            // Рассылаем уведомления тем, кто подписан
            cmsUser::sendUpdateNotify('forum', $thread['id']);
            // Подписываемся сами если нужно
			if (cmsCore::inRequest('subscribe')){
				cmsUser::subscribe($inUser->id, 'forum', $thread['id']);
			}
            // Обновляем кеши
            $model->updateForumCache($thread['NSLeft'], $thread['NSRight'], true);

            $total_pages = ceil($thread_post_count / $model->config['pp_thread']);

            // Если пост не в скрытый форум и не в объедненный с предыдущим, добавляем в ленту
			if (!$thread['is_hidden'] && $thread_post_count > $thread['post_count']){

                if ($do=='newthread'){

                    cmsActions::log('add_thread', array(
                        'object' => $thread['title'],
                        'object_url' => '/forum/thread'.$thread['id'].'-1.html',
                        'object_id' => $thread['id'],
                        'target' => $forum['title'],
                        'target_url' => '/forum/'.$forum['id'],
                        'target_id' => $forum['id'],
                        'description' => $message_post
                    ));

                } else {

                    cmsActions::log('add_fpost', array(
                        'object' => $_LANG['MESSAGE'],
                        'object_url' => '/forum/thread'.$thread['id'].'-'.$total_pages.'.html#'.$post_id,
                        'object_id' => $post_id,
                        'target' => $thread['title'],
                        'target_url' => '/forum/thread'.$thread['id'].'.html',
                        'target_id' => $thread['id'],
                        'description' => $message_post
                    ));

                }

			}

            // Для новой темы прикрепляем опрос если нужно
            if ($do=='newthread'){
                $model->addPoll(cmsCore::request('poll', 'array', array()), $thread['id']);
                $last_poll_error = $model->getLastAddPollError();
                if($last_poll_error){
                    cmsCore::addSessionMessage($last_poll_error, 'error');
                    cmsCore::redirect('/forum/editpost'.$post_id.'-1.html');
                }
            }

            cmsUser::clearCsrfToken();

            cmsCore::redirect('/forum/thread'.$thread['id'].'-'.$total_pages.'.html#'.$post_id);

        } elseif ($do == 'editpost') {

            $model->updatePost(array(
                                    'content' => $message_bb,
                                    'content_html' => $message_html,
                                    'edittimes' => $last_post['edittimes']+1,
                                    'editdate' => date("Y-m-d H:i:s")
                                ), $last_post['id']);

            cmsActions::updateLog('add_fpost', array('description' => $message_post), $last_post['id']);

            if($model->config['fa_on'] && cmsCore::checkContentAccess($model->config['group_access'])){

                $file_error = $model->addUpdatePostAttachments($last_post['id']);

                if($file_error === false){
                    cmsCore::addSessionMessage($_LANG['CHECK_SIZE_TYPE_FILE'].$model->config['fa_max'], 'error');
                }

            }

            if($first_post_id == $last_post['id']){

                if($thread_poll){
                    $model->updatePoll(cmsCore::request('poll', 'array', array()), $thread_poll);
                } else {
                    $model->addPoll(cmsCore::request('poll', 'array', array()), $thread['id']);
                }

                $last_poll_error = $model->getLastAddPollError();
                if($last_poll_error){
                    cmsUser::sessionPut('thread', $thread);
                    cmsUser::sessionPut('post_content', stripcslashes($message_bb));
                    cmsCore::addSessionMessage($last_poll_error, 'error');
                    cmsCore::redirectBack();
                }

            }

            cmsUser::clearCsrfToken();

            cmsCore::redirect('/forum/thread'.$thread['id'].'-'.$page.'.html#'.$last_post['id']);

        }

    }

}
///////////////////////////// DELETE POST /////////////////////////////////////////////////////////////////////////////////////////////////
if ($do=='deletepost'){

    if(!cmsCore::validateForm()) { cmsCore::error404(); }

	if (!$inUser->id){ cmsCore::error404(); }

    $post = $model->getPost($id);
    if(!$post){ cmsCore::error404(); }

    $thread = $model->getThread($post['thread_id']);
    if(!$thread) { cmsCore::error404(); }

    $path_list = $inDB->getNsCategoryPath('cms_forums', $thread['NSLeft'], $thread['NSRight'], 'id, title, access_list, moder_list, NSLeft, NSRight');
    if ($path_list){
        foreach($path_list as $pcat){
            if(!cmsCore::checkContentAccess($pcat['access_list'])){
                cmsCore::error404();
            }
        }
        $is_forum_moder = $model->isForumModerator($pcat['moder_list']);
    }

    $end_min = $model->checkEditTime($post['pubdate']);
    $is_author_can_edit = (is_bool($end_min) ? $end_min : $end_min > 0) &&
                                  ($post['user_id'] == $inUser->id);

    if(!$inUser->is_admin && !($is_forum_moder && !cmsUser::userIsAdmin($post['user_id'])) && !$is_author_can_edit){ cmsCore::error404(); }

	$model->deletePost($post['id']);

    $model->updateThreadPostCount($post['thread_id']);
    $model->cacheThreadLastPost($post['thread_id']);

    if ($path_list){
        $path_list = array_reverse($path_list);
        foreach($path_list as $pcat){
            $model->updateForumCache($pcat['NSLeft'], $pcat['NSRight']);
        }
    }

	cmsCore::addSessionMessage($_LANG['MSG_IS_DELETED'], 'info');

    $total_pages = ceil(($thread['post_count']-1) / $model->config['pp_thread']);
    if($page > $total_pages) { $page = $total_pages; }

    cmsUser::clearCsrfToken();

    cmsCore::jsonOutput(array('error' => false,
                              'redirect' => '/forum/thread'.$thread['id'].'-'.$page.'.html'));

}
//============================================================================//
//========================== Операции с темами ===============================//
//============================================================================//
if(in_array($do, array('movethread','renamethread','deletethread','close','pin','pin_post', 'move_post'))){

    if (!$inUser->id){ cmsCore::halt(); }

    $thread = $model->getThread($id);
    if(!$thread) { cmsCore::halt(); }

    $path_list = $inDB->getNsCategoryPath('cms_forums', $thread['NSLeft'], $thread['NSRight'], 'id, title, access_list, moder_list, NSLeft, NSRight');
    if ($path_list){
        foreach($path_list as $pcat){
            if(!cmsCore::checkContentAccess($pcat['access_list'])){
                cmsCore::halt();
            }
        }
        $is_forum_moder = $model->isForumModerator($pcat['moder_list']);
    }

    //======================= Перемещение темы ===============================//
    if ($do=='movethread'){

        if(!$inUser->is_admin && !$is_forum_moder){ cmsCore::halt(); }

        if(!cmsCore::inRequest('gomove')){

            $smarty = $inCore->initSmarty('components', 'com_forum_move_thread.tpl');
            $smarty->assign('thread', $thread);
            $smarty->assign('forums', $model->getForums());
            $smarty->display('com_forum_move_thread.tpl');

            cmsCore::jsonOutput(array('error' => false,
                                      'html' => ob_get_clean()));

        } else {

            $new_forum = $model->getForum(cmsCore::request('forum_id', 'int', 0));
            if(!$new_forum) { cmsCore::error404(); }

            $is_hidden = 0;

            $path_list = $inDB->getNsCategoryPath('cms_forums', $new_forum['NSLeft'], $new_forum['NSRight'], 'id, title, access_list, moder_list');
            if ($path_list){
                foreach($path_list as $pcat){
                    if(!cmsCore::checkContentAccess($pcat['access_list'])){
                        cmsCore::halt();
                    }
                    if(cmsCore::yamlToArray($pcat['access_list'])){
                       $is_hidden = 1;
                    }
                }
                $is_forum_moder = $model->isForumModerator($pcat['moder_list']);
            }

            if(!$is_forum_moder && !$inUser->is_admin){
                cmsCore::addSessionMessage($_LANG['YOU_NO_THIS_FORUM_MODER'], 'error');
                cmsCore::redirect('/forum/thread'.$thread['id'].'.html');
            }

            $inDB->query("UPDATE cms_forum_threads SET forum_id = '{$new_forum['id']}', is_hidden = '{$is_hidden}' WHERE id = '{$thread['id']}'") ;

            cmsActions::updateLog('add_thread', array('target' => $new_forum['title'],
                                                       'target_url' => '/forum/'.$new_forum['id'],
                                                       'target_id' => $new_forum['id']), $thread['id']);

            // Обновляем кешированные значения
            // для старого форума
            $model->updateForumCache($thread['NSLeft'], $thread['NSRight'], true);
            // для нового форума
            $model->updateForumCache($new_forum['NSLeft'], $new_forum['NSRight'], true);

            cmsCore::addSessionMessage($_LANG['THREAD_IS_MOVE'].'"'.$new_forum['title'].'"', 'success');

            cmsCore::redirect('/forum/thread'.$thread['id'].'.html');

        }

    }

    //===================== Переименование темы ==============================//
    if ($do=='renamethread'){

        if(!$inUser->is_admin && !$is_forum_moder && !$thread['is_mythread']){ cmsCore::halt(); }

        if(!cmsCore::inRequest('gorename')){

            $smarty = $inCore->initSmarty('components', 'com_forum_rename_thread.tpl');
            $smarty->assign('thread', $thread);
            $smarty->display('com_forum_rename_thread.tpl');

            cmsCore::jsonOutput(array('error' => false,
                                      'html' => ob_get_clean()));

        } else {

			$new_thread['title']       = cmsCore::request('title', 'str', $thread['title']);
			$new_thread['description'] = cmsCore::request('description', 'str', '');

            $model->updateThread($new_thread, $thread['id']);

			cmsActions::updateLog('add_fpost', array('target' => $new_thread['title']), 0, $thread['id']);
			cmsActions::updateLog('add_thread', array('object' => $new_thread['title']), $thread['id']);

            $model->updateForumCache($thread['NSLeft'], $thread['NSRight'], true);

            cmsCore::jsonOutput(array('error' => false,
                                      'title' => stripslashes($new_thread['title']),
                                      'description' => stripslashes($new_thread['description'])));

        }

    }

    //======================= Удаление темы ==================================//
    if ($do=='deletethread'){

        if(!cmsCore::validateForm()) { cmsCore::error404(); }

        if(!$inUser->is_admin && !($is_forum_moder && !cmsUser::userIsAdmin($thread['user_id'])) && !$thread['is_mythread']){ cmsCore::halt(); }

        $model->deleteThread($thread['id']);

        // Обновляем кешированные значения
        $model->updateForumCache($thread['NSLeft'], $thread['NSRight'], true);

        cmsUser::clearCsrfToken();

		cmsCore::jsonOutput(array('error' => false,
								  'redirect' => '/forum/'.$thread['forum_id']));

    }

    //=============== Прикрепление/открепление темы ==========================//
    if ($do=='pin'){

        if(!$inUser->is_admin && !$is_forum_moder){ cmsCore::halt(); }

        $pinned = cmsCore::request('pinned', 'int', 0);

        $inDB->query("UPDATE cms_forum_threads SET pinned = '$pinned' WHERE id = '{$thread['id']}'");

        cmsCore::halt($pinned);

    }

    //========== Прикрепление/открепление сообщения темы =====================//
    if ($do=='pin_post'){

        if(!$inUser->is_admin && !$is_forum_moder){ cmsCore::halt(); }

        $pinned  = cmsCore::request('pinned', 'int', 0);
        $post_id = cmsCore::request('post_id', 'int', 0);

        // Проверяем, принадлежит ли сообщение теме
        if(!$model->isBelongsToPostTopic($post_id, $thread['id'])){
            cmsCore::halt();
        }

        $inDB->query("UPDATE cms_forum_posts SET pinned = '$pinned' WHERE id = '{$post_id}' AND thread_id = '{$thread['id']}'");

        // Ниже строки для тех, кто обновлялся с 1.9, если чистая установка, их можно удалить
        // Ставим принудительно для первого поста темы флаг pinned
        if($pinned){
            $first_post_id = $inDB->get_field('cms_forum_posts', "thread_id = '{$thread['id']}' ORDER BY pubdate ASC", 'id');
            $inDB->query("UPDATE cms_forum_posts SET pinned = 1 WHERE id = '{$first_post_id}' AND thread_id = '{$thread['id']}'");
        }

        cmsCore::redirect('/forum/thread'.$thread['id'].'-1.html#'.$post_id);

    }

    //=========================== Перенос сообщения темы =====================//
    if ($do=='move_post'){

        if(!$inUser->is_admin && !$is_forum_moder){ cmsCore::halt(); }

        $post_id = cmsCore::request('post_id', 'int', 0);
        // Проверяем, принадлежит ли сообщение теме
        if(!$model->isBelongsToPostTopic($post_id, $thread['id'])){
            cmsCore::halt();
        }

        cmsCore::callEvent('MOVE_FORUM_POST', array('thread'=>$thread, 'post_id'=>$post_id));

        if(!cmsCore::inRequest('gomove')){

            $smarty = $inCore->initSmarty('components', 'com_forum_move_post.tpl');
            $smarty->assign('thread', $thread);
            $smarty->assign('post_id', $post_id);
            $smarty->assign('threads', cmsCore::getListItems('cms_forum_threads', $thread['id'], 'title', 'ASC', "forum_id = '{$thread['forum_id']}'"));
            $smarty->display('com_forum_move_post.tpl');

            cmsCore::jsonOutput(array('error' => false,
                                      'html' => ob_get_clean()));

        } else {

            $new_thread = $model->getThread(cmsCore::request('new_thread_id', 'int', 0));
            if(!$new_thread) { cmsCore::error404(); }

            $n_path_list = $inDB->getNsCategoryPath('cms_forums', $new_thread['NSLeft'], $new_thread['NSRight'], 'id, title, access_list, moder_list, NSLeft, NSRight');
            if ($n_path_list){
                foreach($n_path_list as $n_pcat){
                    if(!cmsCore::checkContentAccess($n_pcat['access_list'])){
                        cmsCore::halt();
                    }
                }
                $is_forum_moder = $model->isForumModerator($n_pcat['moder_list']);
            }

            if(!$is_forum_moder && !$inUser->is_admin){
                cmsCore::error404();
            }

            $model->updatePost(array(
                                    'thread_id' => $new_thread['id'],
                                    'pubdate' => date("Y-m-d H:i:s")
                                ), $post_id);

            $model->updateThreadPostCount($thread['id']);
            $thread_post_count = $model->updateThreadPostCount($new_thread['id']);
            $total_pages = ceil($thread_post_count / $model->config['pp_thread']);

            cmsActions::updateLog('add_fpost', array('target' => $new_thread['title'],
                                                     'target_url' => '/forum/thread'.$new_thread['id'].'.html',
                                                     'target_id'=>$new_thread['id'],
                                                     'object_url'=>'/forum/thread'.$new_thread['id'].'-'.$total_pages.'.html#'.$post_id,
                                                     'pubdate'=>date("Y-m-d H:i:s")), $post_id);

            $model->cacheThreadLastPost($thread['id']);

            if ($path_list){
                $path_list = array_reverse($path_list);
                foreach($path_list as $pcat){
                    $model->cacheLastPost($pcat['NSLeft'], $pcat['NSRight']);
                }
            }

            if ($n_path_list){
                $n_path_list = array_reverse($n_path_list);
                foreach($n_path_list as $pcat){
                    $model->cacheLastPost($pcat['NSLeft'], $pcat['NSRight']);
                }
            }

            cmsCore::addSessionMessage($_LANG['POST_IS_MOVE'].'"'.$new_thread['title'].'"', 'success');

            cmsCore::redirect('/forum/thread'.$new_thread['id'].'-'.$total_pages.'.html#'.$post_id);

        }

    }

    //==================== Открытие/закрытие темы ============================//
    if ($do=='close'){

        if(!$inUser->is_admin && !$is_forum_moder && !$thread['is_mythread']){ cmsCore::halt(); }

        $closed = cmsCore::request('closed', 'int', 0);

        if($closed){
            $model->closeThread($thread['id']);
        } else {
            $model->openThread($thread['id']);
        }

        cmsCore::halt($closed);

    }

    cmsCore::halt();

}

//============================================================================//
//========================== Операции с файлами ==============================//
//============================================================================//
if(in_array($do, array('download','delfile','reloadfile'))){

    if(!$model->config['fa_on']) { cmsCore::error404(); }

    $file = $model->getPostAttachment($id);
    if(!$file){ cmsCore::error404(); }

    $post = $model->getPost($file['post_id']);
    if(!$post){ cmsCore::error404(); }

    $thread = $model->getThread($post['thread_id']);
    if(!$thread) { cmsCore::error404(); }

    $path_list = $inDB->getNsCategoryPath('cms_forums', $thread['NSLeft'], $thread['NSRight'], 'id, title, access_list, moder_list');
    if ($path_list){
        foreach($path_list as $pcat){
            if(!cmsCore::checkContentAccess($pcat['access_list'])){
                cmsCore::error404();
            }
        }
        $is_forum_moder = $model->isForumModerator($pcat['moder_list']);
    }

    //================= Скачивание прикрепленного файла ======================//
    if ($do=='download'){

        $location = PATH.'/upload/forum/post'.$file['post_id'].'/'.$file['filename'];

        if(!file_exists($location)) { cmsCore::error404(); }

        $inDB->query("UPDATE cms_forum_files SET hits = hits + 1 WHERE id = '{$file['id']}'") ;

        ob_clean();

        header('Content-Disposition: attachment; filename='.htmlspecialchars($file['filename']));
        header('Content-Type: application/x-force-download; name="'.htmlspecialchars($file['filename']).'"');
        header('Content-Length: ' . $file['filesize']);
        header('Accept-Ranges: bytes');

        cmsCore::halt(file_get_contents($location));

    }

    //=================== Удаление прикрепленного файла ======================//
    if ($do=='delfile'){

        if(!cmsCore::validateForm()) { cmsCore::error404(); }

        $end_min = $model->checkEditTime($post['pubdate']);
        $is_author_can_edit = (is_bool($end_min) ? $end_min : $end_min > 0) &&
                                      ($post['user_id'] == $inUser->id) &&
                                      cmsCore::checkContentAccess($model->config['group_access']);

        if(!$inUser->is_admin && !$is_forum_moder && !$is_author_can_edit){ cmsCore::halt(); }

        $model->deletePostAttachment($file);

        cmsUser::clearCsrfToken();

        cmsCore::halt(1);

    }

    //================== Перезакачка прикрепленного файла ====================//
    if ($do=='reloadfile'){

        $end_min = $model->checkEditTime($post['pubdate']);
        $is_author_can_edit = (is_bool($end_min) ? $end_min : $end_min > 0) &&
                                      ($post['user_id'] == $inUser->id) &&
                                      cmsCore::checkContentAccess($model->config['group_access']);

        if(!$inUser->is_admin && !$is_forum_moder && !$is_author_can_edit){ cmsCore::error404(); }

        if(!cmsCore::inRequest('goreload')){

            $smarty = $inCore->initSmarty('components', 'com_forum_file_reload.tpl');
            $smarty->assign('file', $file);
            $smarty->assign('cfg', $model->config);
            $smarty->display('com_forum_file_reload.tpl');

            cmsCore::jsonOutput(array('error' => false,
                                      'html' => ob_get_clean()));

        } else {

            $success = $model->addUpdatePostAttachments($post['id'], $file);

            if($success){

                $post['attached_files']     = $model->getPostAttachments($post['id']);
                $post['is_author_can_edit'] = $is_author_can_edit;

                $smarty = $inCore->initSmarty('components', 'com_forum_attached_files.tpl');
                $smarty->assign('post', $post);
                $smarty->assign('is_moder', $is_forum_moder);
                $smarty->assign('is_admin', $inUser->is_admin);
                $smarty->assign('cfg', $model->config);
                $smarty->display('com_forum_attached_files.tpl');

                cmsCore::jsonOutput(array('error' => false,
                                          'post_id' => $post['id'],
                                          'html' => ob_get_clean()));

            } else {

                cmsCore::jsonOutput(array('error' => true,
                                          'text' => $_LANG['CHECK_SIZE_TYPE_FILE'].$model->config['fa_max']));

            }

        }

    }

    cmsCore::halt();

}
//============================================================================//
//========================= Операции с опросами ==============================//
//============================================================================//
if ($do=='view_poll'){

    $thread = $model->getThread($id);
    if(!$thread) { cmsCore::halt(); }

    $path_list = $inDB->getNsCategoryPath('cms_forums', $thread['NSLeft'], $thread['NSRight'], 'id, title, access_list, moder_list');
    if ($path_list){
        foreach($path_list as $pcat){
			if(!cmsCore::checkContentAccess($pcat['access_list'])){
                cmsCore::halt();
			}
        }
        $is_forum_moder = $model->isForumModerator($pcat['moder_list']);
    }

    $thread_poll = $model->getThreadPoll($thread['id']);
    if(!$thread_poll) { cmsCore::halt(); }

    if($inUser->id && $thread_poll['is_user_vote'] && $thread_poll['options']['change'] && cmsCore::request('revote', 'int')){
        $model->deleteVote($thread_poll);
        $thread_poll['is_user_vote'] = 0;
        $thread_poll['vote_count'] -= 1;
    }

    if(!$thread_poll['is_user_vote'] && !$thread_poll['options']['result']){
        $thread_poll['show_result'] = cmsCore::request('show_result', 'int');
    }

    $smarty = $inCore->initSmarty('components', 'com_forum_thread_poll.tpl');
    $smarty->assign('thread', $thread);
    $smarty->assign('thread_poll', $thread_poll);
    $smarty->assign('user_id', $inUser->id);
    $smarty->assign('do', $thread_poll['show_result'] ? $do : 'thread');
    $smarty->assign('is_moder', $is_forum_moder);
    $smarty->assign('is_admin', $inUser->is_admin);
    $smarty->display('com_forum_thread_poll.tpl');

    cmsCore::halt(ob_get_clean());

}

if ($do=='delete_poll'){

    if (!$inUser->id){ cmsCore::halt(); }

    if(!cmsCore::validateForm()) { cmsCore::halt(); }

    $thread = $model->getThread($id);
    if(!$thread) { cmsCore::halt(); }

    $path_list = $inDB->getNsCategoryPath('cms_forums', $thread['NSLeft'], $thread['NSRight'], 'id, title, access_list, moder_list');
    if ($path_list){
        foreach($path_list as $pcat){
			if(!cmsCore::checkContentAccess($pcat['access_list'])){
                cmsCore::halt();
			}
        }
        $is_forum_moder = $model->isForumModerator($pcat['moder_list']);
    }

    $thread_poll = $model->getThreadPoll($thread['id']);
    if(!$thread_poll) { cmsCore::halt(); }

    if(!$is_forum_moder && !$inUser->is_admin) { cmsCore::halt(); }

    $model->deletePoll($thread_poll['id']);

    cmsUser::clearCsrfToken();

    cmsCore::halt(1);

}

if ($do=='vote_poll'){

    if (!$inUser->id){ cmsCore::halt(); }

    if(!cmsCore::validateForm()) { cmsCore::halt(); }

    $answer = cmsCore::request('answer', 'str', '');
    $poll   = $model->getPollById(cmsCore::request('poll_id', 'int'));

    if (!$answer || !$poll) { cmsCore::jsonOutput(array('error' => true, 'text'  => $_LANG['SELECT_THE_OPTION'])); }

    if($model->isUserVoted($poll['id'])){ cmsCore::jsonOutput(array('error' => true, 'text'  => '')); }

    $model->votePoll($poll, $answer);

    cmsUser::clearCsrfToken();

    cmsCore::jsonOutput(array('error' => false, 'text'  => ''));

}

//============================================================================//
//========================= Последние сообщения ==============================//
//============================================================================//
if ($do=='latest_posts'){

    $inActions = cmsActions::getInstance();

	$inPage->setTitle($_LANG['LATEST_POSTS_ON_FORUM']);
	$inPage->addPathway($_LANG['FORUMS'], '/forum');
	$inPage->addPathway($_LANG['LATEST_POSTS_ON_FORUM']);

	$inActions->showTargets(true);

	$action = $inActions->getAction('add_fpost');

	$inActions->onlySelectedTypes(array($action['id']));

	$total = $inActions->getCountActions();

	$inDB->limitPage($page, 15);

	$actions = $inActions->getActionsLog();

	$smarty = $inCore->initSmarty('components', 'com_forum_actions.tpl');
	$smarty->assign('actions', $actions);
	$smarty->assign('total', $total);
    $smarty->assign('do', $do);
    $smarty->assign('user_id', $inUser->id);
    $smarty->assign('pagetitle', $_LANG['LATEST_POSTS_ON_FORUM']);
	$smarty->assign('pagebar', cmsPage::getPagebar($total, $page, 15, '/forum/latest_posts/page-%page%'));
	$smarty->display('com_forum_actions.tpl');

}
//============================================================================//
//============================= Последние темы ===============================//
//============================================================================//
if ($do=='latest_thread'){

    $inActions = cmsActions::getInstance();

	$inPage->setTitle($_LANG['NEW_THREADS_ON_FORUM']);
	$inPage->addPathway($_LANG['FORUMS'], '/forum');
	$inPage->addPathway($_LANG['NEW_THREADS_ON_FORUM']);

	$inActions->showTargets(true);

	$action = $inActions->getAction('add_thread');

	$inActions->onlySelectedTypes(array($action['id']));

	$total = $inActions->getCountActions();

	$inDB->limitPage($page, 15);

	$actions = $inActions->getActionsLog();

	$smarty = $inCore->initSmarty('components', 'com_forum_actions.tpl');
	$smarty->assign('actions', $actions);
	$smarty->assign('total', $total);
    $smarty->assign('do', $do);
    $smarty->assign('user_id', $inUser->id);
    $smarty->assign('pagetitle', $_LANG['NEW_THREADS_ON_FORUM']);
	$smarty->assign('pagebar', cmsPage::getPagebar($total, $page, 15, '/forum/latest_thread/page-%page%'));
	$smarty->display('com_forum_actions.tpl');

}
//============================================================================//
//========================== Просмотр категории ==============================//
//============================================================================//
if ($do=='view_cat'){

    $cat = $model->getForumCat(cmsCore::request('seolink', 'str', ''));
    if(!$cat){ cmsCore::error404(); }

    $inPage->setTitle($cat['title']);
    $inPage->setDescription($cat['title']);
    $inPage->addPathway($cat['title']);

    $model->whereForumCatIs($cat['id']);
    $sub_forums = $model->getForums();

    $smarty = $inCore->initSmarty('components', 'com_forum_list.tpl');
    $smarty->assign('pagetitle', $cat['title']);
    $smarty->assign('forums', $sub_forums);
    $smarty->assign('forum', array());
    $smarty->assign('cfg', $model->config);
    $smarty->assign('user_id', false);
    $smarty->display('com_forum_list.tpl');

    $inDB->addJoin('INNER JOIN cms_forums f ON f.id = t.forum_id');
    $inDB->where("t.is_hidden = 0");
    $model->whereForumCatIs($cat['id']);
    $inDB->orderBy('t.pubdate', 'DESC, t.hits DESC');
    $inDB->limit(15);
    $threads = $model->getThreads();

    $smarty = $inCore->initSmarty('components', 'com_forum_view.tpl');
    $smarty->assign('threads', $threads);
    $smarty->display('com_forum_view.tpl');

}
//============================================================================//
//===================== Активность пользователя ==============================//
//============================================================================//
if ($do=='user_activity'){

    $login  = cmsCore::request('login', 'str', $inUser->login);
    $sub_do = cmsCore::request('sub_do', 'str', 'threads');

    $user = cmsUser::getShortUserData($login);
    if(!$user){ cmsCore::error404(); }

    $my_profile = $inUser->login == $login;

    $pagetitle = $my_profile ? $_LANG['MY_ACTIVITY'] : $user['nickname'].' - '.$_LANG['ACTIVITY_ON_FORUM'];

    $inPage->setTitle($pagetitle);
    $inPage->addPathway($pagetitle);

    $threads = array();
    $posts   = array();

    if(!$my_profile && !$inUser->is_admin){
        $model->wherePublicThreads();
    }

    $model->whereThreadUserIs($user['id']);
    $thread_count = $model->getThreadsCount();

    if($sub_do == 'threads' && $thread_count){
        $inDB->orderBy('t.pubdate', 'DESC, t.hits DESC');
        $inDB->limitPage($page, 15);
        $threads = $model->getThreads();

        $pagination = cmsPage::getPagebar($thread_count, $page, 15, "javascript:forum.getUserActivity('threads','/forum/{$user['login']}_activity.html','%page%');");

    }

    $inDB->resetConditions();

    // Если тем у пользователя нет, показываем вкладку сообщений
    if(!$thread_count){
        $sub_do = 'posts';
    }

    $inDB->addSelect('t.title as thread_title');
    $inDB->addJoin('INNER JOIN cms_forum_threads t ON t.id = p.thread_id');
    $model->wherePostUserIs($user['id']);
    if(!$my_profile && !$inUser->is_admin){
        $model->wherePublicThreads();
    }
    $post_count = $model->getPostsCount();

    // Если сообщений нет, 404
    if(!$post_count && !$my_profile){
        cmsCore::error404();
    }

    if($sub_do == 'posts' && $post_count){
        $inDB->orderBy('p.thread_id', 'DESC, p.pubdate DESC');
        $inDB->limitPage($page, 10);
        $posts = $model->getPosts();
        $pagination = cmsPage::getPagebar($post_count, $page, 10, "javascript:forum.getUserActivity('posts','/forum/{$user['login']}_activity.html','%page%');");
    }

    $inDB->resetConditions();

    $smarty = $inCore->initSmarty('components', 'com_forum_user_activity.tpl');
    $smarty->assign('threads', $threads);
    $smarty->assign('posts', $posts);
    $smarty->assign('post_count', $post_count);
    $smarty->assign('thread_count', $thread_count);
    $smarty->assign('pagetitle', $pagetitle);
    $smarty->assign('sub_do', $sub_do);
    $smarty->assign('page', $page);
    $smarty->assign('pagination', $pagination);
    $smarty->assign('link', '/forum/'.$user['login'].'_activity.html');
    $smarty->display('com_forum_user_activity.tpl');
    if (cmsCore::inRequest('of_ajax')) { cmsCore::halt(ob_get_clean()); }

}
////////////////////////////////////////////////////////////////////////////////

}
?>