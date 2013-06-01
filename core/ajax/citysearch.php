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

    $q = cmsCore::request('q', 'str', '');
    if (!$q) { cmsCore::halt(); }

	$q = mb_strtolower($q);

	$sql = "SELECT city FROM cms_user_profiles WHERE LOWER(city) LIKE '{$q}%' GROUP BY city LIMIT 10";
	$rs  = $inDB->query($sql);

	while ($item = $inDB->fetch_assoc($rs)){
        echo $item['city']."\n";
	}

    cmsCore::halt();

?>