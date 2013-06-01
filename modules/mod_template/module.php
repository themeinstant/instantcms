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

function mod_template($module_id){

    global $_LANG;

    if (isset($_SESSION['template'])) { $template = $_SESSION['template']; } else { $template = ''; }

    echo '<form name="templform" action="/modules/mod_template/set.php" method="post">';
        echo '<select name="template" id="template" style="width:100%">
                <option value="0">'.$_LANG['TEMPLATE_DEFAULT'].'</option>';
                echo cmsCore::templatesList($template);
        echo '</select><br/>';
        echo '<input style="margin-top:5px" type="submit" value="'.$_LANG['TEMPLATE_CHOOSE'].'"/>';
    echo '</form>';

    return true;

}
?>