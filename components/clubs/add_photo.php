<?php
if(!defined('VALID_CMS')) { die('ACCESS DENIED'); }

/////////////////////////////// форма загрузки фотографий 1 шаг ////////////////////////////////////
if ($do_photo == 'addphoto'){ 

	$inPage->addPathway($_LANG['ADD_PHOTO'].': '.$_LANG['STEP_1']);
	$inPage->setTitle($_LANG['ADD_PHOTO'].': '.$_LANG['STEP_1']);

	if (!cmsCore::inRequest('submit')){

		$smarty = $inCore->initSmarty('components', 'com_photos_add1.tpl');			
		$smarty->assign('no_tags', true);
		$smarty->assign('is_admin', ($is_admin || $is_moder));
		$smarty->display('com_photos_add1.tpl');

	}

	if (cmsCore::inRequest('submit')){

		$mod = array();
		
		$mod['title']       = cmsCore::request('title', 'str', '');
		$mod['description'] = cmsCore::request('description', 'str');
		$mod['is_multi']    = cmsCore::request('only_mod', 'int', 0);
		$mod['comments']    = ($is_admin || $is_moder) ? cmsCore::request('comments', 'int') : 1;

		cmsUser::sessionPut('mod', $mod);
		
		cmsCore::redirect('/clubs/photoalbum'.$album['id'].'/submit_photo.html');

	}

}
	
/////////////////////////////// форма загрузки фотографий 2 шаг /////////////////////////////////////////////////////////////////////////////
if ($do_photo == 'submit_photo'){ 

	$mod = cmsUser::sessionGet('mod');
	if (!$mod) { cmsCore::error404(); }
	
	$inPage->addPathway($_LANG['ADD_PHOTO'].': '.$_LANG['STEP_2']);
	$inPage->setTitle($_LANG['ADD_PHOTO'].': '.$_LANG['STEP_2']);

    $smarty = $inCore->initSmarty('components', 'com_photos_add2.tpl');
	$smarty->assign('upload_url', '/components/clubs/ajax/upload_photo.php');
	$smarty->assign('upload_complete_url', '/clubs/uploaded'.$album['id'].'.html');
    $smarty->assign('sess_id', session_id());
    $smarty->assign('max_limit', false);
	$smarty->assign('album', $album);
    $smarty->assign('max_files', 0);
	$smarty->assign('uload_type', $mod['is_multi'] ? 'multi' : 'single');
	$smarty->assign('stop_photo', false);
    $smarty->display('com_photos_add2.tpl');

}
	
///////////////////////////////// фотографии загружены ///////////////////////////////////////////////////////////////////////////////////////
if ($do_photo == 'uploaded'){ 

	cmsUser::sessionDel('mod');

	cmsCore::redirect('/clubs/photoalbum'.$album['id']);

}

?>
