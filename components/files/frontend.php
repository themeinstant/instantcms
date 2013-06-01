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

function files(){

    $inDB = cmsDatabase::getInstance();

    global $_LANG;

    $do = cmsCore::getInstance()->do;

//========================================================================================================================//
//========================================================================================================================//
    // Скачивание
    if ($do=='view'){

        $fileurl = cmsCore::request('fileurl', 'str', '');

        if(mb_strstr($fileurl, '..')){ cmsCore::halt(); }

        if (!$fileurl) { cmsCore::halt($_LANG['FILE_NOT_FOUND']); }

        if (mb_strstr($fileurl, 'http:/')){
            if (!mb_strstr($fileurl, 'http://')){ $fileurl = str_replace('http:/', 'http://', $fileurl); }
        }

        $downloads = cmsCore::fileDownloadCount($fileurl);

        if ($downloads == 0){
            $sql = "INSERT INTO cms_downloads (fileurl, hits) VALUES ('$fileurl', '1')";
            $inDB->query($sql);
        } else {
            $sql = "UPDATE cms_downloads SET hits = hits + 1 WHERE fileurl = '$fileurl'";
            $inDB->query($sql);
        }

        if (mb_strstr($fileurl, 'http:/')){
            cmsCore::redirect($fileurl);
        }

        if (file_exists(PATH.$fileurl)){

            header('Content-Disposition: attachment; filename='.basename($fileurl) . "\n");
            header('Content-Type: application/x-force-download; name="'.$fileurl.'"' . "\n");
            header('Location:'.$fileurl);
            cmsCore::halt();

        } else {
            cmsCore::halt($_LANG['FILE_NOT_FOUND']);
        }

    }

//========================================================================================================================//
//========================================================================================================================//

    if ($do=='redirect'){

    	$url = str_replace('--q--', '?', cmsCore::request('url', 'str', ''));

        if(mb_strstr($url, '..')){ cmsCore::halt(); }

        if (!$url) { cmsCore::halt(); }

        if (mb_strstr($url, 'http:/')){
            if (!mb_strstr($url, 'http://')){ $url = str_replace('http:/', 'http://', $url); }
        }
        if (mb_strstr($url, 'https:/')){
            if (!mb_strstr($url, 'https://')){ $url = str_replace('https:/', 'https://', $url); }
        }
        cmsCore::redirect($url);

    }

//========================================================================================================================//

}
?>
