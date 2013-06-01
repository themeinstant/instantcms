<?php

    header('Content-Type: text/html; charset=utf-8');

    session_start();

	define("VALID_CMS", 1);
    define('PATH', $_SERVER['DOCUMENT_ROOT']);

	include(PATH.'/core/cms.php');

	cmsCore::getInstance();

    define('HOST', 'http://' . cmsCore::getHost());

    cmsCore::loadClass('page');
    cmsCore::loadClass('user');

    cmsCore::callEvent('LOGINZA_AUTH', array());

    exit;

?>