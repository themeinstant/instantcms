<?php
if (!defined('VALID_CMS_ADMIN')) { die('ACCESS DENIED'); }
/* * **************************************************************************/
//                                                                            //
//                             InstantCMS v1.10                               //
//                        http://www.instantcms.ru/                           //
//                                                                            //
//                   written by InstantCMS Team, 2007-2012                    //
//                produced by InstantSoft, (www.instantsoft.ru)               //
//                                                                            //
//                        LICENSED BY GNU/GPL v2                              //
//                                                                            //
/* * **************************************************************************/
function autoOrder($form_id) {

    $inDB = cmsDatabase::getInstance();

    $sql = "SELECT * FROM cms_form_fields WHERE form_id = '$form_id' ORDER BY ordering";
    $rs  = $inDB->query($sql);

    if ($inDB->num_rows($rs)) {
        $ord = 1;
        while ($item = $inDB->fetch_assoc($rs)) {
            $inDB->query("UPDATE cms_form_fields SET ordering = $ord WHERE id= '{$item['id']}'");
            $ord += 1;
        }
    }
    return true;
}
function moveField($id, $form_id, $dir) {

    $inDB = cmsDatabase::getInstance();

    $sign = $dir>0 ? '+' : '-';

    $current = $inDB->get_field('cms_form_fields', "id='{$id}'", 'ordering');
    if($current === false){ return false; }

    if ($dir>0){

        $sql = "UPDATE cms_form_fields
                SET ordering = ordering-1
                WHERE form_id='{$form_id}' AND ordering = ({$current}+1)
                LIMIT 1";
        $inDB->query($sql);
    }
    if ($dir<0){

        if($current == 1) { return false; }

        $sql = "UPDATE cms_form_fields
                SET ordering = ordering+1
                WHERE form_id='{$form_id}' AND ordering = ({$current}-1)
                LIMIT 1";
        $inDB->query($sql);
    }

    $sql    = "UPDATE cms_form_fields
               SET ordering = ordering {$sign} 1
               WHERE id='{$id}'";
    $inDB->query($sql);

    return true;

}

require('../includes/jwtabs.php');

$GLOBALS['cp_page_head'][] = '<script language="JavaScript" type="text/javascript" src="js/forms.js"></script>';
$GLOBALS['cp_page_head'][] = jwHeader();

$id  = cmsCore::request('id', 'int');
$opt = cmsCore::request('opt', 'str', 'list');

cpAddPathway('Конструктор форм', '?view=components&do=config&id=' . $id);

$toolmenu = array();
$toolmenu[0]['icon'] = 'newform.gif';
$toolmenu[0]['title'] = 'Новая форма';
$toolmenu[0]['link'] = '?view=components&do=config&id=' . $id . '&opt=add';

$toolmenu[1]['icon'] = 'listforms.gif';
$toolmenu[1]['title'] = 'Формы';
$toolmenu[1]['link'] = '?view=components&do=config&id=' . $id . '&opt=list';

if ($opt != 'list') {
    $toolmenu[3]['icon'] = 'cancel.gif';
    $toolmenu[3]['title'] = 'Отмена';
    $toolmenu[3]['link'] = '?view=components&do=config&id=' . $id;
}

cpToolMenu($toolmenu);

cmsCore::loadClass('form');
$inDB = cmsDatabase::getInstance();

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if ($opt == 'up_field') {

    moveField(cmsCore::request('item_id', 'int'), cmsCore::request('form_id', 'int'), -1);

    cmsCore::redirectBack();

}

if ($opt == 'down_field') {

    moveField(cmsCore::request('item_id', 'int'), cmsCore::request('form_id', 'int'), 1);

    cmsCore::redirectBack();

}

if ($opt == 'del_field') {

    $item_id = cmsCore::request('item_id', 'int');
    $form_id = cmsCore::request('form_id', 'int');

    $inDB->delete('cms_form_fields', "id = '{$item_id}'", 1);

    autoOrder($form_id);

    cmsCore::addSessionMessage('Поле успешно удалено.');

    cmsCore::redirectBack();

}

if (in_array($opt, array('add_field', 'update_field'))) {

    $item['kind']     = cmsCore::request('kind', 'str', '');
    $item['title']    = cmsCore::request('f_title', 'str', 'Поле без названия');
    $item['description'] = cmsCore::request('f_description', 'str', '');
    $item['ordering'] = cmsCore::request('f_order', 'int');
    $item['form_id']  = cmsCore::request('form_id', 'int');
    $item['mustbe']   = cmsCore::request('mustbe', 'int');

    $item['config'] = array();

    $item['config']['text_is_link'] = cmsCore::request('text_is_link', 'int');
    $item['config']['text_link_prefix'] = cmsCore::request('text_link_prefix', 'str', '');
	$item['config']['max'] = cmsCore::request('text_max', 'int');

    switch ($item['kind']) {
        case 'text':

            $item['config']['size']    = cmsCore::request('f_text_size', 'int');
            break;

        case 'link':

            $item['config']['size']    = cmsCore::request('f_link_size', 'int');
            break;

        case 'textarea':

            $item['config']['size']    = cmsCore::request('f_ta_size', 'int');
            $item['config']['rows']    = cmsCore::request('f_ta_rows', 'int');
            $item['config']['default'] = cmsCore::request('f_ta_default', 'str', '');
            break;

        case 'checkbox':

            $item['config']['checked'] = cmsCore::request('f_checked', 'int');
            break;

        case 'radiogroup':

            $item['config']['items'] = cmsCore::request('f_rg_list', 'str', '');
            break;

        case 'list':

            $item['config']['items'] = cmsCore::request('f_list_list', 'str', '');
            $item['config']['size']  = cmsCore::request('f_list_size', 'int');
            break;

        case 'menu':

            $item['config']['items'] = cmsCore::request('f_menu_list', 'str', '');
            $item['config']['size']  = cmsCore::request('f_menu_size', 'int');
            break;
    }

    $item['config'] = cmsDatabase::escape_string(cmsCore::arrayToYaml($item['config']));

    if($opt == 'add_field'){

        $inDB->insert('cms_form_fields', cmsCore::callEvent('ADD_FORM_FIELD', $item));

        cmsCore::addSessionMessage('Поле успешно добавлено.');

    } else {

        $inDB->update('cms_form_fields', cmsCore::callEvent('UPDATE_FORM_FIELD', $item), cmsCore::request('field_id', 'int'));

        cmsCore::addSessionMessage('Поле успешно обновлено.');

    }

    cmsCore::redirect('?view=components&do=config&id=' . $id . '&opt=edit&item_id=' . $item['form_id']);
}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (in_array($opt, array('submit', 'update'))) {

    if (!cmsCore::validateForm()) { cmsCore::error404(); }

    $item['title']       = cmsCore::request('title', 'str', 'Форма без названия');
    $item['description'] = $inDB->escape_string(cmsCore::request('description', 'html', ''));

    $item['sendto']  = cmsCore::request('sendto', 'str', '');
    $item['email']   = cmsCore::request('email', 'email', '');
    $item['user_id'] = cmsCore::request('user_id', 'int');
    $item['form_action'] = cmsCore::request('form_action', 'str', '/forms/process');
    $item['only_fields'] = cmsCore::request('only_fields', 'int');
    $item['showtitle'] = cmsCore::request('showtitle', 'int');

    if($opt == 'submit'){

        $form_id = $inDB->insert('cms_forms', cmsCore::callEvent('ADD_FORM', $item));
        cmsCore::addSessionMessage('Форма успешно создана. Приступайте к ее наполнению прямо сейчас.');

    } else {

        $form_id = cmsCore::request('item_id', 'int');

        $inDB->update('cms_forms', cmsCore::callEvent('UPDATE_FORM', $item), $form_id);
        cmsCore::addSessionMessage('Данные формы обновлены.');

    }

    cmsUser::clearCsrfToken();

    cmsCore::redirect('?view=components&do=config&id=' . $id . '&opt=edit&item_id=' . $form_id);

}

if ($opt == 'delete') {

    $item_id = cmsCore::request('item_id', 'int');

    cmsCore::callEvent('DELETE_FORM', $item_id);

    $inDB->delete('cms_forms', "id = '{$item_id}'", 1);

    $inDB->delete('cms_form_fields', "form_id = '{$item_id}'");

    cmsCore::addSessionMessage('Форма успешно удалена.');

    cmsCore::redirect('?view=components&do=config&id=' . $id . '&opt=list');

}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if ($opt == 'list') {

    $fields = array();

    $fields[0]['title'] = 'id';
    $fields[0]['field'] = 'id';
    $fields[0]['width'] = '30';

    $fields[1]['title'] = 'Название';
    $fields[1]['field'] = 'title';
    $fields[1]['width'] = '';
    $fields[1]['link'] = '?view=components&do=config&id=' . $id . '&opt=edit&item_id=%id%';

    $fields[2]['title'] = 'E-Mail';
    $fields[2]['field'] = 'email';
    $fields[2]['width'] = '150';

    $actions = array();
    $actions[0]['title'] = 'Редактировать';
    $actions[0]['icon'] = 'edit.gif';
    $actions[0]['link'] = '?view=components&do=config&id=' . $id . '&opt=edit&item_id=%id%';

    $actions[1]['title'] = 'Удалить';
    $actions[1]['icon'] = 'delete.gif';
    $actions[1]['confirm'] = 'Удалить форму?';
    $actions[1]['link'] = '?view=components&do=config&id=' . $id . '&opt=delete&item_id=%id%';

    cpListTable('cms_forms', $fields, $actions);

}

if (in_array($opt, array('add', 'edit'))) {

    if ($opt == 'add') {

        cpAddPathway('Добавить форму', '?view=components&do=config&id=' . $id . '&opt=add');
        echo '<h3>Добавить форму</h3>';

        $mod['showtitle'] = 1;
        $mod['form_action'] = '/forms/process';
        $mod['only_fields'] = 0;

    } else {

        $item_id  = cmsCore::request('item_id', 'int');
        $field_id = cmsCore::request('field_id', 'int');

        $mod = $inDB->get_fields('cms_forms', "id = '{$item_id}'", '*');

        $field = $inDB->get_fields('cms_form_fields', "id='{$field_id}'", '*');
        if($field){
            $field['config'] = cmsCore::yamlToArray($field['config']);
        }

        echo '<h3>Форма: ' . $mod['title'] . '</h3>';
        cpAddPathway($mod['title'], '?view=components&do=config&id=' . $id . '&opt=edit&item_id=' . $item_id);

        ob_start();

        echo '{tab=Свойства формы}';

    } ?>

    <form id="addform" name="addform" method="post" action="index.php?view=components&do=config&id=<?php echo $id; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
        <table width="605" border="0" cellspacing="5" class="proptable">
            <tr>
                <td width="200"><strong>Название формы: </strong></td>
                <td width=""><input name="title" type="text" id="title" size="30" value="<?php echo htmlspecialchars(@$mod['title']); ?>" style="width:220px;"/></td>
            </tr>
            <tr>
                <td><strong>Куда отправлять форму: </strong></td>
                <td>
                    <select name="sendto" id="sendto" style="width:220px;" onChange="toggleSendTo()">
                        <option value="mail" <?php if (@$mod['sendto'] == 'mail' || !isset($mod['sendto'])) { echo 'selected'; } ?>>На адрес e-mail</option>
                        <option value="user" <?php if (@$mod['sendto'] == 'user') { echo 'selected'; } ?>>Личным сообщением на сайте</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td width="200"><strong>Показывать заголовок: </strong></td>
                <td width="">
                    <label><input name="showtitle" type="radio" value="1" <?php if ($mod['showtitle']) { echo 'checked="checked"'; } ?>/> Да</label>
                    <label><input name="showtitle" type="radio" value="0" <?php if (!$mod['showtitle']) { echo 'checked="checked"'; } ?>/> Нет</label>
                </td>
            </tr>
            <tr>
                <td width="200"><strong>Атрибут action формы: </strong></td>
                <td width="">
                    <input name="form_action" type="text" size="30" value="<?php echo htmlspecialchars(@$mod['form_action']); ?>" style="width:220px;"/>
                </td>
            </tr>
            <tr>
                <td width="200"><strong>Выводить только поля формы: </strong></td>
                <td width="">
                    <label><input name="only_fields" type="radio" value="1" <?php if ($mod['only_fields']) { echo 'checked="checked"'; } ?>/> Да</label>
                    <label><input name="only_fields" type="radio" value="0" <?php if (!$mod['only_fields']) { echo 'checked="checked"'; } ?>/> Нет</label>
                </td>
            </tr>


        </table>
        <div id="sendto_mail" <?php if (@$mod['sendto'] == 'mail' || !isset($mod['sendto'])) {
                            echo 'style="display:block"';
                        } else {
                            echo 'style="display:none"';
                        } ?>>
            <table width="605" border="0" cellspacing="5" class="proptable">
                <tr>
                    <td width="16"><img src="/admin/components/forms/email.gif" width="16" height="16"></td>
                    <td width="178"><strong>Адрес e-mail: </strong></td>
                    <td><input name="email" type="text" id="email" size="30" value="<?php echo @$mod['email']; ?>" style="width:220px;"/></td>
                </tr>
            </table>
        </div>
        <div id="sendto_user" <?php if (@$mod['sendto'] == 'user') {
                            echo 'style="display:block"';
                        } else {
                            echo 'style="display:none"';
                        } ?>>
            <table width="605" border="0" cellspacing="5" class="proptable">
                <tr>
                    <td width="16"><img src="/admin/components/forms/user.gif" width="16" height="16"></td>
                    <td width="178"><strong>Получатель: </strong></td>
                    <td>
                        <select name="user_id" id="user_id" style="width:220px">
                            <?php
                            if (isset($mod['user_id'])) {
                                echo $inCore->getListItems('cms_users', $mod['user_id'], 'nickname', 'ASC', 'is_deleted=0 AND is_locked=0', 'id', 'nickname');
                            } else {
                                echo $inCore->getListItems('cms_users', 0, 'nickname', 'ASC', 'is_deleted=0 AND is_locked=0', 'id', 'nickname');
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        <table width="100%" border="0">
            <tr>
                <td width="52%" valign="top">
                    <p><strong>Пояснения к форме:</strong></p>
                    <?php $inCore->insertEditor('description', $mod['description'], '280', '100%'); ?>
                </td>
           </tr>
        </table>
        <?php if ($opt == 'add') {
            echo '<p><b>Примечание: </b>После создания формы вернитесь в режим ее редактирования, чтобы добавить поля. </p>';
        } else {
            echo '<p><b>Примечание: </b> Чтобы вставить форму в материал (статью/новость), укажите в нужном<br/> месте статьи выражение {ФОРМА=Название формы}, либо воспользуйтесь панелью вставки,<br/> расположенной над окном редактора материала.';
        }
        ?>
        <p>
            <input name="add_mod" type="submit" id="add_mod" <?php if ($opt == 'add') { echo 'value="Создать форму"'; } else { echo 'value="Сохранить изменения"'; } ?> />
            <input name="opt" type="hidden" id="do" <?php if ($opt == 'add') { echo 'value="submit"'; } else { echo 'value="update"'; } ?> />
    <?php
    if ($opt == 'edit') {
        echo '<input name="item_id" type="hidden" value="' . $mod['id'] . '" />';
    } ?>

        </p>
    </form>
    <?php if ($opt == 'edit') {
        $last_order = 1 + $inDB->get_field('cms_form_fields', "form_id='{$mod['id']}' ORDER BY ordering DESC", 'ordering'); ?>

        {tab=Поля формы}
        <table width="761" cellpadding="8" cellspacing="5">
            <tr>
                <td width="300" valign="top" class="proptable">
                    <h4 style="border-bottom:solid 1px black; font-size: 14px; margin-bottom: 10px"><b><?php if(!@$field){ ?>Добавить поле<?php } else { ?>Редактировать поле<?php } ?></b></h4>
                    <form id="fieldform" name="fieldform" method="post" action="index.php?view=components&do=config&id=<?php echo $id; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
                        <input type="hidden" name="opt" value="<?php if(!@$field){ ?>add_field<?php } else { ?>update_field<?php } ?>"/>
                        <input name="form_id" type="hidden" id="form_id" value="<?php echo @$mod['id'] ?>"/>
                        <input name="field_id" type="hidden" value="<?php echo @$field['id'] ?>"/>
                        <table width="100%" border="0" cellspacing="2" cellpadding="2">
                            <tr>
                                <td width="100">Тип поля:</td>
                                <td>
                                    <select name="kind" id="kind" onchange="show()">
                                        <option value="text" <?php if (@$field['kind'] == 'text' || !@$field['kind']) { echo 'selected="selected"'; } ?>>Текстовое</option>
                                        <option value="link" <?php if (@$field['kind'] == 'link') { echo 'selected="selected"'; } ?>>Ссылка</option>
                                        <option value="textarea" <?php if (@$field['kind'] == 'textarea') { echo 'selected="selected"'; } ?>>Многострочное</option>
                                        <option value="checkbox" <?php if (@$field['kind'] == 'checkbox') { echo 'selected="selected"'; } ?>>Опция да/нет</option>
                                        <option value="radiogroup" <?php if (@$field['kind'] == 'radiogroup') { echo 'selected="selected"'; } ?>>Группа опций</option>
                                        <option value="list" <?php if (@$field['kind'] == 'list') { echo 'selected="selected"'; } ?>>Выпадающий список</option>
                                        <option value="menu" <?php if (@$field['kind'] == 'menu') { echo 'selected="selected"'; } ?>>Видимый список</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Заголовок:</td>
                                <td><input name="f_title" type="text" id="f_title" size="25" value="<?php echo htmlspecialchars(@$field['title']) ?>" /></td>
                            </tr>
                            <tr>
                                <td>Описание:</td>
                                <td><input name="f_description" type="text" id="f_description" size="25" value="<?php echo htmlspecialchars(@$field['description']) ?>" /></td>
                            </tr>
                            <tr>
                                <td>Порядок:</td>
                                <td><input name="f_order" type="text" id="f_order" value="<?php if(!@$field) { echo $last_order; } else { echo @$field['ordering']; } ?>" size="6" /></td>
                            </tr>
                            <tr>
                                <td>Заполнение:</td>
                                <td><select name="mustbe" id="mustbe">
                                        <option value="1" <?php if (@$field['mustbe']) { echo 'selected="selected"'; } ?>>Обязательно</option>
                                        <option value="0" <?php if (!@$field['mustbe']) { echo 'selected="selected"'; } ?>>Не обязательно</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <td>Значение поля ссылкой?</td>
                                <td>
                                    <label><input name="text_is_link"
                                                  onclick="$('#text_link_prefix').show();"
                                                  type="radio" value="1" <?php if (@$field['config']['text_is_link']) { echo 'checked="checked"'; } ?>/> Да</label>
                                    <label><input name="text_is_link"
                                                  onclick="$('#text_link_prefix').hide();"
                                                  type="radio" value="0" <?php if (!@$field['config']['text_is_link']) { echo 'checked="checked"'; } ?>/> Нет</label>
                                </td>
                            </tr>
                            <tr id="text_link_prefix" <?php if(!@$field['config']['text_is_link']) { echo 'style="display:none"'; } ?>>
                                <td>Префикс ссылки:</td>
                                <td><input name="text_link_prefix" type="text" size="25" value="<?php echo (@$field['config']['text_link_prefix'] ? $field['config']['text_link_prefix'] : '/users/hobby/'); ?>" /></td>
                            </tr>
                            <tr>
                                <td>Макс. длина:</td>
                                <td><input name="text_max" type="text" size="6" value="<?php echo (isset($field['config']['max']) ? $field['config']['max'] : 300) ?>" /> символов </td>
                            </tr>
                        </table>

                        <div id="kind_text">
                            <table width="100%" border="0" cellspacing="2" cellpadding="2">
                                <tr>
                                    <td width="100">Ширина:</td>
                                    <td><input name="f_text_size" type="text" id="f_text_size" value="<?php echo (@$field['config']['size'] ? $field['config']['size'] : 160) ?>" size="6" />  px </td>
                                </tr>
                            </table>
                        </div>
                        <div id="kind_link" style="display:none">
                            <table width="100%" border="0" cellspacing="2" cellpadding="2">
                                <tr>
                                    <td width="100">Ширина:</td>
                                    <td><input name="f_link_size" type="text" id="f_text_size" value="<?php echo (@$field['config']['size'] ? $field['config']['size'] : 160) ?>" size="6" />  px </td>
                                </tr>
                            </table>
                        </div>
                        <div id="kind_textarea" style="display:none">
                            <table width="100%" border="0" cellspacing="2" cellpadding="2">
                                <tr>
                                    <td width="100">Ширина:</td>
                                    <td><input name="f_ta_size" type="text" id="f_ta_size" value="<?php echo (@$field['config']['size'] ? $field['config']['size'] : 160) ?>" size="6" /> px </td>
                                </tr>
                                <tr>
                                    <td>Строк:</td>
                                    <td><input name="f_ta_rows" type="text" id="f_ta_rows" value="<?php echo (@$field['config']['rows'] ? $field['config']['rows'] : 5) ?>" size="6" /></td>
                                </tr>
                            </table>
                        </div>
                        <div id="kind_checkbox" style="display:none">
                            <div id="div" >
                                <table width="100%" border="0" cellspacing="2" cellpadding="2">
                                    <tr>
                                        <td width="100">Отметка:</td>
                                        <td><select name="f_checked" id="f_checked">
                                                <option value="1" <?php if (@$field['config']['checked']) { echo 'selected="selected"'; } ?>>Отмечена</option>
                                                <option value="0" <?php if (!@$field['config']['checked']) { echo 'selected="selected"'; } ?>>Не отмечена</option>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div id="kind_radiogroup" style="display:none">
                            <table width="100%" border="0" cellspacing="2" cellpadding="2">
                                <tr>
                                    <td width="100">Элементы:<br />
                                        <small>через "<b>/</b>"</small> </td>
                                    <td><textarea name="f_rg_list" cols="20" rows="5" id="f_rg_list"><?php echo htmlspecialchars(@$field['config']['items']) ?></textarea></td>
                                </tr>
                            </table>
                        </div>
                        <div id="kind_list" style="display:none">
                            <table width="100%" border="0" cellspacing="2" cellpadding="2">
                                <tr>
                                    <td width="100">Элементы:<br />
                                        <small>через "<b>/</b>"</small> </td>
                                    <td><textarea name="f_list_list" cols="20" rows="5" id="f_list_list"><?php echo htmlspecialchars(@$field['config']['items']) ?></textarea></td>
                                </tr>
                                <tr>
                                    <td>Ширина:</td>
                                    <td><input name="f_list_size" type="text" id="f_ta_size" value="<?php echo (@$field['config']['size'] ? $field['config']['size'] : 160) ?>" size="6" /> px </td>
                                </tr>
                            </table>
                        </div>
                        <div id="kind_menu" style="display:none">
                            <table width="100%" border="0" cellspacing="2" cellpadding="2">
                                <tr>
                                    <td width="100">Элементы:<br />
                                        <small>через "<b>/</b>"</small> </td>
                                    <td><textarea name="f_menu_list" cols="20" rows="5" id="f_menu_list"><?php echo htmlspecialchars(@$field['config']['items']) ?></textarea></td>
                                </tr>
                                <tr>
                                    <td>Ширина:</td>
                                    <td><input name="f_menu_size" type="text" id="f_ta_size" value="<?php echo (@$field['config']['size'] ? $field['config']['size'] : 160) ?>" size="6" /> px </td>
                                </tr>
                            </table>
                        </div>

                        <p>
                            <input type="submit" name="Submit" value="<?php if(!@$field){ ?>Добавить поле<?php } else { ?>Сохранить поле<?php } ?>" />
                        </p>
                    </form>

                </td>
                <td width="440" valign="top" class="proptable"><h4 style="border-bottom:solid 1px black;font-size: 14px; margin-bottom: 5px"><b>Предварительный просмотр </b></h4>
                    <?php echo cmsForm::displayForm($item_id, array(), true); ?>
                </td>
            </tr>
        </table>
        <script type="text/javascript">
            $(document).ready(function(){
                show();
            });
        </script>

        {/tabs}
        <?php
        echo jwTabs(ob_get_clean());
        ?>
        <?php
    }
}
?>