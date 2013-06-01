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

if(!defined('VALID_CMS_ADMIN')) { die('ACCESS DENIED'); }

function applet_tree(){

    $inCore = cmsCore::getInstance();
    $inUser = cmsUser::getInstance();
	$inDB 	= cmsDatabase::getInstance();
	$inPage = cmsPage::getInstance();

	$inCore->loadLib('tags');

	//check access
	global $adminAccess;
	if (!cmsUser::isAdminCan('admin/content', $adminAccess)) { cpAccessDenied(); }

    $cfg = $inCore->loadComponentConfig('content');

    $inCore->loadModel('content');
    $model = new cms_model_content();

    $GLOBALS['cp_page_title'] = 'Контент сайта';
    cpAddPathway('Контент сайта', 'index.php?view=tree');

	$GLOBALS['cp_page_head'][] = '<script language="JavaScript" type="text/javascript" src="js/content.js"></script>';

    $do = $inCore->request('do', 'str', 'tree');

//============================================================================//
//============================================================================//

	if ($do == 'tree'){
		$toolmenu = array();

		$toolmenu[8]['icon'] = 'config.gif';
		$toolmenu[8]['title'] = 'Настроить каталог статей';
		$toolmenu[8]['link'] = "?view=components&do=config&link=content";

		$toolmenu[9]['icon'] = 'help.gif';
		$toolmenu[9]['title'] = 'Помощь';
		$toolmenu[9]['link'] = "?view=help&topic=content";

		cpToolMenu($toolmenu);

        $only_hidden    = $inCore->request('only_hidden', 'int', 0);
        $category_id    = $inCore->request('cat_id', 'int', 0);
        $base_uri       = 'index.php?view=tree';

        $title_part     = $inCore->request('title', 'str', '');

        $def_order  = $category_id ? 'con.ordering' : 'pubdate';
        $orderby    = $inCore->request('orderby', 'str', $def_order);
        $orderto    = $inCore->request('orderto', 'str', 'asc');
        $page       = $inCore->request('page', 'int', 1);
        $perpage    = 20;

        $hide_cats  = $inCore->request('hide_cats', 'int', 0);

        $cats       = $model->getCatsTree();

        if ($category_id) {
            $model->whereCatIs($category_id);
        }

        if ($title_part){
            $inDB->where('LOWER(con.title) LIKE \'%'.mb_strtolower($title_part).'%\'');
        }

        if ($only_hidden){
            $inDB->where('con.published = 0');
        }

        $inDB->orderBy($orderby, $orderto);

        $inDB->limitPage($page, $perpage);

        $total      = $model->getArticlesCount(false);

        $items      = $model->getArticlesList(false);

        $pages      = ceil($total / $perpage);


        $tpl_file   = 'admin/content.php';
        $tpl_dir    = file_exists(TEMPLATE_DIR.$tpl_file) ? TEMPLATE_DIR : DEFAULT_TEMPLATE_DIR;

        include($tpl_dir.$tpl_file);

	}

} ?>
