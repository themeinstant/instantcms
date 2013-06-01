<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

function smarty_function_csrf_token($params, &$smarty){
    return cmsUser::getCsrfToken();
}

?>
