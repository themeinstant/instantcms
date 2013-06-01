<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

function smarty_function_captcha($params, &$smarty){
    return cmsPage::getCaptcha();
}

?>
