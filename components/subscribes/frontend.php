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

function subscribes(){

    $inCore = cmsCore::getInstance();
    $inUser = cmsUser::getInstance();

    $do = $inCore->do;

//========================================================================================================================//
//========================================================================================================================//
    if ($do=='view'){

        $subscribe  = cmsCore::request('subscribe', 'int', 0);
        $target     = cmsCore::request('target', 'str', '');
        $target_id  = cmsCore::request('target_id', 'int', 0);

        if (!$target_id || !$target){
            cmsCore::redirectBack();
        }

        if ($inUser->id){
            cmsUser::subscribe($inUser->id,  $target, $target_id, $subscribe);
        }

        cmsCore::redirectBack();

    }

}
?>