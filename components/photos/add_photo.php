<?php
if(!defined('VALID_CMS')) { die('ACCESS DENIED'); }

/////////////////////////////// форма загрузки фотографий 1 шаг ////////////////////////////////////
if ($do_photo == 'addphoto'){ 

	$inPage->addPathway($_LANG['ADD_PHOTO'].': '.$_LANG['STEP_1']);
	$inPage->setTitle($_LANG['ADD_PHOTO'].': '.$_LANG['STEP_1']);

	if (!cmsCore::inRequest('submit')){

		$inPage->initAutocomplete();
		$autocomplete_js = $inPage->getAutocompleteJS('tagsearch', 'tags');

		$smarty = $inCore->initSmarty('components', 'com_photos_add1.tpl');			
		$smarty->assign('no_tags', false);
		$smarty->assign('is_admin', $inUser->is_admin);
		$smarty->assign('autocomplete_js', $autocomplete_js);
		$smarty->display('com_photos_add1.tpl');

	}

	if (cmsCore::inRequest('submit')){

		$mod = array();
		
		$mod['title']       = cmsCore::request('title', 'str', '');
		$mod['description'] = cmsCore::request('description', 'str');
		$mod['is_multi']    = cmsCore::request('only_mod', 'int', 0);
		$mod['tags']        = cmsCore::request('tags', 'str');
		$mod['comments']    = $inUser->is_admin ? cmsCore::request('comments', 'int') : 1;

		cmsUser::sessionPut('mod', $mod);
		
		cmsCore::redirect('/photos/'.$album['id'].'/submit_photo.html');

	}

}
	
/////////////////////////////// форма загрузки фотографий 2 шаг /////////////////////////////////////////////////////////////////////////////
if ($do_photo == 'submit_photo'){ 

	$mod = cmsUser::sessionGet('mod');
	if (!$mod) { cmsCore::error404(); }

	$inPage->addPathway($_LANG['ADD_PHOTO'].': '.$_LANG['STEP_2']);
	$inPage->setTitle($_LANG['ADD_PHOTO'].': '.$_LANG['STEP_2']);

    if($album['uplimit'] && !$inUser->is_admin) {

        $max_limit  = true;
        $max_files  = (int)$album['uplimit'] - $today_uploaded;
		$stop_photo = $today_uploaded >= (int)$album['uplimit'];

    } else {

        $max_limit  = false;
        $max_files  = 0;
		$stop_photo = false;

    }

    $smarty = $inCore->initSmarty('components', 'com_photos_add2.tpl');
	$smarty->assign('upload_url', '/components/photos/ajax/upload_photo.php');
	$smarty->assign('upload_complete_url', '/photos/'.$album['id'].'/uploaded.html');
    $smarty->assign('sess_id', session_id());
    $smarty->assign('max_limit', $max_limit);
	$smarty->assign('album', $album);
    $smarty->assign('max_files', $max_files);
	$smarty->assign('uload_type', $mod['is_multi'] ? 'multi' : 'single');
	$smarty->assign('stop_photo', $stop_photo);
    $smarty->display('com_photos_add2.tpl');

}
	
///////////////////////////////// фотографии загружены ///////////////////////////////////////////////////////////////////////////////////////
if ($do_photo == 'uploaded'){ 

	cmsUser::sessionDel('mod');
	cmsCore::redirect('/photos/'.$album['id']);

}

?>
