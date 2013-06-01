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

function mod_polls($module_id){

    $inCore = cmsCore::getInstance();
    $cfg    = $inCore->loadModuleConfig($module_id);

    cmsCore::loadModel('polls');
    $model = new cms_model_polls();

    if ($cfg['poll_id']>0){

        $poll = $model->getPoll($cfg['poll_id']);

    } else {

        $poll = $model->getPoll(0, 'RAND()');

    }

    if (!$poll) { return false; }

	$smarty = $inCore->initSmarty('modules', 'mod_polls.tpl');
	$smarty->assign('poll', $poll);
	$smarty->assign('is_ajax', cmsCore::request('is_ajax', 'int', 0));
	$smarty->assign('is_voted', $model->isUserVoted($poll['id']));
	$smarty->assign('module_id', $module_id);
	$smarty->assign('cfg', $cfg);
	$smarty->display('mod_polls.tpl');

    return true;

}
?>