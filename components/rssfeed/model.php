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

class cms_model_rssfeed{

	public function __construct(){}

/* ==================================================================================================== */
/* ==================================================================================================== */

    public static function getDefaultConfig() {

        $cfg = array (
				  'addsite' => 1,
				  'maxitems' => 50,
				  'icon_on' => 1,
				  'icon_url' => HOST.'/images/rss.png'
				);

        return $cfg;

    }

}