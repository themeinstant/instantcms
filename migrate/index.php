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

	@set_time_limit(0);

    session_start();

    header('Content-Type: text/html; charset=utf-8');
    define('VALID_CMS', 1);

    define('PATH', $_SERVER['DOCUMENT_ROOT']);

    include(PATH.'/core/cms.php');
    $inCore = cmsCore::getInstance();

    define('HOST', 'http://' . $inCore->getHost());

    cmsCore::loadClass('user');
	cmsCore::loadClass('cron');
	cmsCore::loadClass('actions');
    cmsCore::loadClass('page');

    $inConf = cmsConfig::getInstance();
    $inDB   = cmsDatabase::getInstance();

    // принудительно включаем дебаг
    $inConf->debug = 1;

    $version_prev = '1.10.1';
    $version_next = '1.10.2';

// ========================================================================== //
// ========================================================================== //
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>InstantCMS - Миграция</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
<style type="text/css">
	body { font-family:Arial; font-size:14px; }

	a { color: #0099CC; }
	a:hover { color: #375E93; }
	h2 { color: #375E93; }

	#wrapper { padding:10px 30px; }
	#wrapper p{ line-height: 20px; }

	.migrate p {
				   line-height:16px;
				   padding-left:20px;
				   margin:2px;
				   margin-left:20px;
				   background:url(/admin/images/actions/on.gif) no-repeat;
			   }
	.migrate p.info {
                   font-size: 16px;
				   background: none;
                   color: #C00;
			   }
	.important {
				   margin:20px;
				   margin-left:0px;
				   border:solid 1px silver;
				   padding:15px;
				   padding-left:65px;
				   background:url(important.png) no-repeat 15px 15px;
			   }
	 .nextlink {
				   margin-top:15px;
				   font-size:18px;
	 }
  </style>
<div id="wrapper" class="migrate">
<?php
    echo "<h2>Миграция InstantCMS {$version_prev} &rarr; {$version_next}</h2>";

	if(!cmsCore::inRequest('go')){
		echo '<h3><a href="/migrate/index.php?go=1">начать миграцию...</a></h3>';
		exit;
	}

// ========================================================================== //
// ========================================================================== //
	$step = cmsCore::request('go', 'int', 0);

    echo '<h3>Шаг № '.$step.'</h3>';

// ========================================================================== //
// ========================================================================== //

	if($step == 1){

        // ========================================================================== //
        // ========================================================================== //

        $result = $inDB->query("SELECT id, fieldsdata FROM cms_uc_items");

        if ($inDB->num_rows($result)){

            while($item = $inDB->fetch_assoc($result)){

                $cfg = @unserialize($item['fieldsdata']);
                $config_yaml = ($cfg) ? cmsCore::arrayToYaml($cfg) : "---\n";
                $cfg_db = $inDB->escape_string($config_yaml);
                $inDB->query("UPDATE cms_uc_items SET fieldsdata='{$cfg_db}' WHERE id='{$item['id']}'");

            }

        }

        echo '<p>Значения полей категорий каталога переведены в формат YAML.</p>';

        // ========================================================================== //
        // ========================================================================== //

        $result = $inDB->query("SELECT id, fieldsstruct FROM cms_uc_cats");

        if ($inDB->num_rows($result)){

            while($item = $inDB->fetch_assoc($result)){

                $cfg = @unserialize($item['fieldsstruct']);
                $config_yaml = ($cfg) ? cmsCore::arrayToYaml($cfg) : "---\n";
                $cfg_db = $inDB->escape_string($config_yaml);
                $inDB->query("UPDATE cms_uc_cats SET fieldsstruct='{$cfg_db}' WHERE id='{$item['id']}'");

            }

        }

        echo '<p>Поля категорий каталога переведены в формат YAML.</p>';

        // ========================================================================== //
        // ========================================================================== //

        cmsDatabase::optimizeTables();

		echo '<div class="nextlink"><a href="/">Перейти на сайт</a></div>';

	}
// ========================================================================== //
// ========================================================================== //

    echo '</div></body></html>';

?>