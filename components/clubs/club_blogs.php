<?php
if(!defined('VALID_CMS')) { die('ACCESS DENIED'); }

/////////////////////////////// ДОБАВЛЕНИЕ ПОСТА ////////////////////////////////////
if (in_array($bdo, array('newpost', 'editpost'))){

	if($bdo=='editpost'){

        $post = $inBlog->getPost($post_id);
        if (!$post){ cmsCore::error404(); }
        $post['tags'] = cmsTagLine($inBlog->getTarget('tags'), $post['id'], false);

		$blog = $inBlog->getBlog($post['blog_id']);
		if (!$blog) { cmsCore::error404(); }

		$club = $model->getClub($blog['user_id']);
		if(!$club) { cmsCore::error404(); }

	}

	if($bdo=='newpost'){

		$club = $model->getClub($id);
		if(!$club) { cmsCore::error404(); }

		$blog = $inBlog->getBlogByUserId($club['id']);
		if (!$blog) { cmsCore::error404(); }

	}

	// если блоги запрещены
	if(!$club['enabled_blogs']){ cmsCore::error404(); }

	// Инициализируем участников клуба
	$model->initClubMembers($club['id']);
	// права доступа
    $is_admin  = $inUser->is_admin || ($inUser->id == $club['admin_id']);
    $is_moder  = $model->checkUserRightsInClub('moderator');
    $is_member = $model->checkUserRightsInClub('member');

    $is_karma_enabled = (($inUser->karma >= $club['blog_min_karma']) && $is_member) ? true : false;

	if(!$is_admin && !$is_moder && !$is_karma_enabled) { cmsCore::error404();}

    $inPage->addPathway($club['title'], '/clubs/'.$club['id']);

	// проверяем является ли пользователь автором, если редактируем пост
	if (($bdo=='editpost') && !$inUser->is_admin && $post['user_id'] != $inUser->id) { cmsCore::error404(); }

    //Если еще не было запроса на сохранение
    if (!cmsCore::inRequest('goadd')){

		//для нового поста
		if ($bdo=='newpost'){

			if (IS_BILLING){ cmsBilling::checkBalance('blogs', 'add_post'); }

			$inPage->addPathway($_LANG['NEW_POST']);
			$inPage->setTitle($_LANG['NEW_POST']);

			$post = cmsUser::sessionGet('mod');
			if ($post){
				cmsUser::sessionDel('mod');
			} else {
				$post['cat_id'] = $cat_id;
				$post['comments'] = 1;

			}

		}

		//для редактирования поста
		if ($bdo=='editpost'){

			$inPage->addPathway($post['title'], $model->getPostURL($club['id'], $post['seolink']));
			$inPage->addPathway($_LANG['EDIT_POST']);
			$inPage->setTitle($_LANG['EDIT_POST']);

		}

		$inPage->initAutocomplete();
		$autocomplete_js = $inPage->getAutocompleteJS('tagsearch', 'tags');

        //получаем рубрики блога
        $cat_list = cmsCore::getListItems('cms_blog_cats', $post['cat_id'], 'id', 'ASC', "blog_id = '{$blog['id']}'");

        //получаем код панелей bbcode и смайлов
        $bb_toolbar = cmsPage::getBBCodeToolbar('message', true, 'clubs', 'post', $post_id);
        $smilies    = cmsPage::getSmilesPanel('message');

        $inCore->initAutoGrowText('#message');

        //показываем форму
        $smarty = $inCore->initSmarty('components', 'com_blog_edit_post.tpl');
		$smarty->assign('blog', $blog);
		$smarty->assign('pagetitle', ($do=='editpost' ? $_LANG['EDIT_POST'] : $_LANG['NEW_POST']));
		$smarty->assign('mod', $post);
		$smarty->assign('cat_list', $cat_list);
		$smarty->assign('bb_toolbar', $bb_toolbar);
		$smarty->assign('smilies', $smilies);
		$smarty->assign('is_admin', $inUser->is_admin);
		$smarty->assign('myblog', ($is_admin || $is_moder));
		$smarty->assign('user_can_iscomments', cmsUser::isUserCan('comments/iscomments'));
		$smarty->assign('autocomplete_js', $autocomplete_js);
        $smarty->display('com_blog_edit_post.tpl');

    }

    //Если есть запрос на сохранение
    if (cmsCore::inRequest('goadd')) {

        $error = false;

        //Получаем параметры
        $mod['title'] 	  = cmsCore::request('title', 'str');
        $mod['content']   = cmsCore::request('content', 'html');
        $mod['feel'] 	  = cmsCore::request('feel', 'str', '');
        $mod['music'] 	  = cmsCore::request('music', 'str', '');
        $mod['cat_id'] 	  = cmsCore::request('cat_id', 'int');
        $mod['allow_who'] = cmsCore::request('allow_who', 'str', $blog['allow_who']);
        $mod['tags'] 	  = cmsCore::request('tags', 'str', '');
		$mod['comments']  = cmsCore::request('comments', 'int', 1);

		$mod['published'] = ($is_admin || $is_moder || !$club['blog_premod']) ? 1 : 0;
		$mod['blog_id']   = $blog['id'];

        //Проверяем их
        if (mb_strlen($mod['title'])<2) {  cmsCore::addSessionMessage($_LANG['POST_ERR_TITLE'], 'error'); $errors = true; }
        if (mb_strlen($mod['content'])<5) { cmsCore::addSessionMessage($_LANG['POST_ERR_TEXT'], 'error'); $errors = true; }

		// Если есть ошибки, возвращаемся назад
		if($errors){
			cmsUser::sessionPut('mod', $mod);
			cmsCore::redirectBack();
        }

        //Если нет ошибок
		//добавляем новый пост...
		if ($bdo=='newpost'){

			if (IS_BILLING){ cmsBilling::process('blogs', 'add_post'); }

			$mod['pubdate'] = date( 'Y-m-d H:i:s');
			$mod['user_id'] = $inUser->id;

			// добавляем пост, получая его id и seolink
			$added = $inBlog->addPost($mod);
            $mod = array_merge($mod, $added);

			if ($mod['published']) {

				if ($club['clubtype']!='private' && $mod['allow_who'] != 'nobody'){

                    $mod['seolink'] = $model->getPostURL($club['id'], $mod['seolink']);

                    cmsCore::callEvent('ADD_POST_DONE', $mod);

					cmsActions::log($inBlog->getTarget('actions_post'), array(
						'object' => $mod['title'],
						'object_url' => $mod['seolink'],
						'object_id' => $mod['id'],
						'target' => $club['title'],
						'target_url' => '/clubs/'.$club['id'],
						'target_id' => $club['id'],
						'description' => '',
						'is_friends_only' => (int)($mod['allow_who'] == 'friends')
					));

				}

				cmsCore::addSessionMessage($_LANG['POST_CREATED'], 'success');

				cmsCore::redirect($mod['seolink']);

			}

			if (!$mod['published']) {

				$message = str_replace('%user%', cmsUser::getProfileLink($inUser->login, $inUser->nickname), $_LANG['MSG_CLUB_POST_SUBMIT']);
				$message = str_replace('%post%', '<a href="'.$model->getPostURL($club['id'], $added['seolink']).'">'.$mod['title'].'</a>', $message);
				$message = str_replace('%club%', '<a href="/clubs/'.$club['id'].'">'.$club['title'].'</a>', $message);

				cmsUser::sendMessage(USER_UPDATER, $club['admin_id'], $message);

				cmsCore::addSessionMessage($_LANG['POST_PREMODER_TEXT'], 'info');

				cmsCore::redirect('/clubs/'.$club['id']);

			}

		}

		//...или сохраняем пост после редактирования
		if ($bdo=='editpost') {

			$mod['edit_times'] = (int)$post['edit_times']+1;

			$inBlog->updatePost($post['id'], $mod);

			cmsActions::updateLog($inBlog->getTarget('actions_post'), array('object' => $mod['title']), $post['id']);

			if (!$mod['published']) {

				$message = str_replace('%user%', cmsUser::getProfileLink($inUser->login, $inUser->nickname), $_LANG['MSG_CLUB_POST_UPDATE']);
				$message = str_replace('%post%', '<a href="'.$model->getPostURL($club['id'], $post['seolink']).'">'.$mod['title'].'</a>', $message);
				$message = str_replace('%club%', '<a href="/clubs/'.$club['id'].'">'.$club['title'].'</a>', $message);

				cmsUser::sendMessage(USER_UPDATER, $club['admin_id'], $message);

				cmsCore::addSessionMessage($_LANG['POST_PREMODER_TEXT'], 'info');

			} else {
				cmsCore::addSessionMessage($_LANG['POST_UPDATED'], 'success');
			}

			cmsCore::redirect($model->getPostURL($club['id'], $post['seolink']));

		}

    }

}
////////////////////////// ПРОСМОТР ПОСТА /////////////////////////////////////////////////////////////////////////
if($bdo=='post'){

	$post = $inBlog->getPost($seolink);
	if (!$post){ cmsCore::error404(); }

	$blog = $inBlog->getBlog($post['blog_id']);
	if (!$blog) { cmsCore::error404(); }

	$club = $model->getClub($blog['user_id']);
	if(!$club) { cmsCore::error404(); }

	// если блоги запрещены
	if(!$club['enabled_blogs']){ cmsCore::error404(); }

	// право просмотра самого поста
	if (!cmsUser::checkUserContentAccess($post['allow_who'], $post['user_id'])){

		cmsCore::addSessionMessage($_LANG['CLOSED_POST'].'<br>'.$_LANG['CLOSED_POST_TEXT'], 'error');
		cmsCore::redirect('/clubs/'.$club['id']);

    }

	// Инициализируем участников клуба
	$model->initClubMembers($club['id']);
	// права доступа
    $is_admin  = $inUser->is_admin || ($inUser->id == $club['admin_id']);
    $is_moder  = $model->checkUserRightsInClub('moderator');
    $is_member = $model->checkUserRightsInClub();

	// пост приватного клуба показываем только участникам
    if ($club['clubtype']=='private' && !$is_member && !$is_admin){ cmsCore::error404(); }

    $inPage->addPathway($club['title'], '/clubs/'.$club['id']);
	$inPage->addPathway($blog['title'], $model->getBlogURL($club['id']));
    $inPage->setTitle($post['title']);
    $inPage->addPathway($post['title']);
	$inPage->setDescription($post['title']);

    if ($post['cat_id']){
        $cat = $inBlog->getBlogCategory($post['cat_id']);
    }

	$post['tags'] = cmsTagBar($inBlog->getTarget('tags'), $post['id']);

	$is_author = ($inUser->id && $inUser->id == $post['user_id']);

	// меняем сеолинк
	$blog['seolink'] = $club['id'].'_blog';

    $smarty = $inCore->initSmarty('components', 'com_blog_view_post.tpl');
	$smarty->assign('post', $post);
	$smarty->assign('blog', $blog);
	$smarty->assign('cat', $cat);
	$smarty->assign('is_author', $is_author);
	$smarty->assign('myblog', ($inUser->id && $inUser->id == $blog['user_id']));
	$smarty->assign('is_admin', $inUser->is_admin);
	$smarty->assign('karma_form', cmsKarmaForm($inBlog->getTarget('rating'), $post['id'], $post['rating'], $is_author));
    $smarty->assign('navigation', $inBlog->getPostNavigation($post['id'], $blog['id'], $model, $club['id']));
    $smarty->display('com_blog_view_post.tpl');

    if($inCore->isComponentInstalled('comments') && $post['comments']){
        cmsCore::includeComments();
        comments($inBlog->getTarget('comments'), $post['id']);
    }

}
///////////////////////// УДАЛЕНИЕ ПОСТА /////////////////////////////////////////////////////////////////////////////
if ($bdo == 'delpost'){

	if(!cmsCore::validateForm()) { cmsCore::halt(); }

	if($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') { cmsCore::halt(); }

	if (!$inUser->id) { cmsCore::halt(); }

	$post = $inBlog->getPost($post_id);
	if (!$post){ cmsCore::halt(); }

	$blog = $inBlog->getBlog($post['blog_id']);
	if (!$blog) { cmsCore::halt(); }

	$club = $model->getClub($blog['user_id']);
	if(!$club) { cmsCore::halt(); }

	// если блоги запрещены
	if(!$club['enabled_blogs']){ cmsCore::error404(); }

	// Инициализируем участников клуба
	$model->initClubMembers($club['id']);
	// права доступа
    $is_admin  = $inUser->is_admin || ($inUser->id == $club['admin_id']);
    $is_moder  = $model->checkUserRightsInClub('moderator');
    $is_member = $model->checkUserRightsInClub();
	$is_author = ($inUser->id == $post['user_id']);

	// удалять могут автор, модераторы, админы
    if (!$is_admin && !$is_moder && !($is_author && $is_member)) { cmsCore::halt(); }

	$inBlog->deletePost($post['id']);

	if ($inUser->id != $post['user_id']){

		cmsUser::sendMessage(USER_UPDATER, $post['user_id'], $_LANG['YOUR_POST'].' <b>&laquo;'.$post['title'].'&raquo;</b> '.$_LANG['WAS_DELETED_FROM_BLOG'].' <b>&laquo;<a href="'.$model->getBlogURL($club['id']).'">'.$blog['title'].'</a>&raquo;</b>');

	}

	cmsCore::addSessionMessage($_LANG['POST_IS_DELETED'], 'success');

    // Очищаем токен
    cmsUser::clearCsrfToken();

	cmsCore::jsonOutput(array('error' => false, 'redirect'  => '/clubs/'.$club['id']));

}
////////// ПРОСМОТР БЛОГА ////////////////////////////////////////////////////////////////////////////////////////
if ($bdo=='blog'){

	$club = $model->getClub($id);
	if(!$club) { cmsCore::error404(); }

	$blog = $inBlog->getBlogByUserId($club['id']);
	if (!$blog) { cmsCore::error404(); }

	// если блоги запрещены
	if(!$club['enabled_blogs']){ cmsCore::error404(); }

	// Инициализируем участников клуба
	$model->initClubMembers($club['id']);
	// права доступа
    $is_admin  = $inUser->is_admin || ($inUser->id == $club['admin_id']);
    $is_moder  = $model->checkUserRightsInClub('moderator');
    $is_member = $model->checkUserRightsInClub();

	// блог приватного клуба показываем только участникам
    if ($club['clubtype']=='private' && !$is_member && !$is_admin){ cmsCore::error404(); }

    $inPage->addPathway($club['title'], '/clubs/'.$club['id']);
	$inPage->addPathway($blog['title'], $model->getBlogURL($club['id']));
    $inPage->setTitle($blog['title']);
	$inPage->setDescription($blog['title']);

	$inDB->addSelect('b.user_id as bloglink');

	// Если показываем посты на модерации, если запрашиваем их
	if($on_moderate){

		if(!$is_admin && !$is_moder){
			cmsCore::error404();
		}

		$inBlog->whereNotPublished();

		$inPage->setTitle($_LANG['POSTS_ON_MODERATE']);
		$inPage->addPathway($_LANG['POSTS_ON_MODERATE']);

		$blog['title'] .= ' - '.$_LANG['POSTS_ON_MODERATE'];

	}

	//Получаем html-код ссылки на автора с иконкой его пола
	$blog['author'] = cmsUser::getGenderLink($club['admin_id']);

	// посты данного блога
	$inBlog->whereBlogIs($blog['id']);

	// кроме админов автора в списке только с доступом для всех
	if(!$is_admin && !$is_moder){
		$inBlog->whereOnlyPublic();
	}

	// если пришла категория
	if($cat_id){
		$all_total = $inBlog->getPostsCount($is_admin || $is_moder);
		$inBlog->whereCatIs($cat_id);
	}

	// всего постов
	$total = $inBlog->getPostsCount($is_admin || $is_moder);

    //устанавливаем сортировку
    $inDB->orderBy('p.pubdate', 'DESC');

    $inDB->limitPage($page, $model->config['posts_perpage']);

	// сами посты
	$posts = $inBlog->getPosts(($is_admin || $is_moder), $model);
	if(!$posts && $page > 1){ cmsCore::error404(); }

    //Если нужно, получаем список рубрик (категорий) этого блога
    $blogcats = $blog['showcats'] ? $inBlog->getBlogCats($blog['id']) : false;

    //Считаем количество постов, ожидающих модерации
    $on_moderate = ($is_admin || $is_moder) && !$on_moderate ? $inBlog->getModerationCount($blog['id']) : false;

	// админлинки
	$blog['moderate_link'] = $model->getBlogURL($club['id']).'/moderate.html';
	$blog['blog_link']     = $model->getBlogURL($club['id']);
	$blog['add_post_link'] = '/clubs/'.$club['id'].'/newpost'.($cat_id ? $cat_id : '').'.html';

	//Генерируем панель со страницами
	if ($cat_id){
		$pagination = cmsPage::getPagebar($total, $page, $model->config['posts_perpage'], $blog['blog_link'].'/page-%page%/cat-'.$cat_id);
	} else {
		$pagination = cmsPage::getPagebar($total, $page, $model->config['posts_perpage'], $blog['blog_link'].'/page-%page%');
	}

	$smarty = $inCore->initSmarty('components', 'com_blog_view.tpl');
    $smarty->assign('myblog', ($is_admin || $is_moder));
    $smarty->assign('is_admin', $inUser->is_admin);
	$smarty->assign('is_config', false);
    $smarty->assign('is_writer', (($inUser->karma >= $club['blog_min_karma']) && $is_member) ? true : false);
    $smarty->assign('on_moderate', $on_moderate);
    $smarty->assign('cat_id', $cat_id);
    $smarty->assign('blogcats', $blogcats);
    $smarty->assign('total', $total);
	$smarty->assign('all_total', (isset($all_total) ? $all_total : 0));
    $smarty->assign('blog', $blog);
    $smarty->assign('posts', $posts);
    $smarty->assign('pagination', $pagination);
    $smarty->display('com_blog_view.tpl');

}
////////// НОВАЯ РУБРИКА / РЕДАКТИРОВАНИЕ РУБРИКИ //////////////////////////////////////////////////////
if (in_array($bdo, array('newcat','editcat'))){

	if($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') { cmsCore::halt(); }

	if (!$inUser->id) { cmsCore::halt(); }

	if($bdo=='editcat'){

        $cat = $inBlog->getBlogCategory($cat_id);
        if (!$cat) { cmsCore::halt(); }

		$blog = $inBlog->getBlog($cat['blog_id']);
		if (!$blog) { cmsCore::halt(); }

		$club = $model->getClub($blog['user_id']);
		if(!$club) { cmsCore::halt(); }

	} else {

		$blog = $inBlog->getBlog($id);
		if (!$blog) { cmsCore::halt(); }

		$club = $model->getClub($blog['user_id']);
		if(!$club) { cmsCore::halt(); }

	}

	if(!$club['enabled_blogs']){ cmsCore::halt(); }

	$model->initClubMembers($club['id']);

    $is_admin  = $inUser->is_admin || ($inUser->id == $club['admin_id']);
    $is_moder  = $model->checkUserRightsInClub('moderator');

    if (!$is_admin && !$is_moder) { cmsCore::halt(); }

    if (!cmsCore::inRequest('goadd')){

        $smarty = $inCore->initSmarty('components', 'com_blog_edit_cat.tpl');
        $smarty->assign('mod', $cat);
		$smarty->assign('form_action', ($bdo=='newcat' ? '/clubs/'.$blog['id'].'/newcat.html' : '/clubs/editcat'.$cat['id'].'.html'));
        $smarty->display('com_blog_edit_cat.tpl');
		cmsCore::jsonOutput(array('error' => false, 'html' => ob_get_clean()));

    }

    if (cmsCore::inRequest('goadd')){

		if(!cmsCore::validateForm()) { cmsCore::halt(); }

        $new_cat['title']       = cmsCore::request('title', 'str', '');
		$new_cat['description'] = cmsCore::request('description', 'str', '');
		$new_cat['blog_id']     = $blog['id'];
        if (mb_strlen($new_cat['title'])<3) { cmsCore::jsonOutput(array('error' => true, 'text' => $_LANG['CAT_ERR_TITLE'])); }

		if ($bdo=='newcat'){
			$cat['id'] = $inBlog->addBlogCategory($new_cat);
			cmsCore::addSessionMessage($_LANG['CAT_IS_ADDED'], 'success');
		}

		if ($bdo=='editcat'){
			$inBlog->updateBlogCategory($cat['id'], $new_cat);
			cmsCore::addSessionMessage($_LANG['CAT_IS_UPDATED'], 'success');
		}

        cmsUser::clearCsrfToken();

		cmsCore::jsonOutput(array('error' => false, 'redirect'  => $model->getBlogURL($club['id'], 1, $cat['id'])));

    }

}
///////////////////////// УДАЛЕНИЕ РУБРИКИ /////////////////////////////////////////////////////////////////////////
if ($bdo == 'delcat'){

	if($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') { cmsCore::halt(); }

	if (!$inUser->id) { cmsCore::halt(); }

	$cat = $inBlog->getBlogCategory($cat_id);
	if (!$cat) { cmsCore::halt(); }

	$blog = $inBlog->getBlog($cat['blog_id']);
	if (!$blog) { cmsCore::halt(); }

	$club = $model->getClub($blog['user_id']);
	if(!$club) { cmsCore::halt(); }

	if(!$club['enabled_blogs']){ cmsCore::halt(); }

	$model->initClubMembers($club['id']);

    $is_admin  = $inUser->is_admin || ($inUser->id == $club['admin_id']);
    $is_moder  = $model->checkUserRightsInClub('moderator');

    if (!$is_admin && !$is_moder) { cmsCore::halt(); }

	if(!cmsCore::validateForm()) { cmsCore::halt(); }

	$inBlog->deleteBlogCategory($cat['id']);

	cmsCore::addSessionMessage($_LANG['CAT_IS_DELETED'], 'success');

    cmsUser::clearCsrfToken();

	cmsCore::jsonOutput(array('error' => false, 'redirect'  => $model->getBlogURL($club['id'])));

}
///////////////////////// ПУБЛИКАЦИЯ ПОСТА /////////////////////////////////////////////////////////////////////////
if ($bdo == 'publishpost'){

	if($_SERVER['HTTP_X_REQUESTED_WITH'] != 'XMLHttpRequest') { cmsCore::halt(); }

	if (!$inUser->id) { cmsCore::halt(); }

	$post = $inBlog->getPost($post_id);
	if (!$post){ cmsCore::halt(); }

	$blog = $inBlog->getBlog($post['blog_id']);
	if (!$blog) { cmsCore::halt(); }

	$club = $model->getClub($blog['user_id']);
	if(!$club) { cmsCore::halt(); }

	if(!$club['enabled_blogs']){ cmsCore::halt(); }

	$model->initClubMembers($club['id']);

    $is_admin  = $inUser->is_admin || ($inUser->id == $club['admin_id']);
    $is_moder  = $model->checkUserRightsInClub('moderator');

    if (!$is_admin && !$is_moder) { cmsCore::halt(); }

	$inBlog->publishPost($post_id);

	$post['seolink'] = $model->getPostURL($club['id'], $post['seolink']);

    if ($club['clubtype'] != 'private' && $post['allow_who'] == 'all') { cmsCore::callEvent('ADD_POST_DONE', $post); }

	if ($club['clubtype'] != 'private' && $post['allow_who'] != 'nobody'){

		cmsActions::log($inBlog->getTarget('actions_post'), array(
			'object' => $post['title'],
			'user_id' => $post['user_id'],
			'object_url' => $post['seolink'],
			'object_id' => $post['id'],
			'target' => $club['title'],
			'target_url' => '/clubs/'.$club['id'],
			'target_id' => $club['id'],
			'description' => '',
			'is_friends_only' => (int)($post['allow_who'] == 'friends')
		));

	}

    cmsUser::sendMessage(USER_UPDATER, $post['user_id'], $_LANG['YOUR_POST'].' <b>&laquo;<a href="'.$post['seolink'].'">'.$post['title'].'</a>&raquo;</b> '.$_LANG['PUBLISHED_IN_BLOG'].' <b>&laquo;<a href="'.$model->getBlogURL($club['id']).'">'.$blog['title'].'</a>&raquo;</b>');

    cmsCore::halt('ok');

}
?>
