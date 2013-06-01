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

function forms(){

    $inCore = cmsCore::getInstance();

    cmsCore::loadClass('form');

    $do = $inCore->do;

    global $_LANG;
//========================================================================================================================//
//========================================================================================================================//
    if ($do=='view'){

        if (!cmsCore::validateForm()) { cmsCore::error404(); }

        // Получаем форму
        $form = cmsForm::getFormData(cmsCore::request('form_id', 'int'));
        if(!$form) { cmsCore::error404(); }

        // Получаем данные полей формы
        $form_fields = cmsForm::getFormFields($form['id']);
        // Если полей нет, 404
        if(!$form_fields) { cmsCore::error404(); }

        // Получаем данные формы
        // Если не переданы, назад
		$form_input = cmsForm::getFieldsInputValues($form['id']);
		if(!$form_input) { cmsCore::addSessionMessage($_LANG['FORM_ERROR'], 'error'); cmsCore::redirectBack(); }

		$errors = false;
		// Проверяем значения формы
		foreach ($form_input['errors'] as $field_error) {
			if($field_error){ cmsCore::addSessionMessage($field_error, 'error'); $errors = true; }
		}
		// проверяем каптчу
		if(!cmsCore::checkCaptchaCode(cmsCore::request('code', 'str'))) { cmsCore::addSessionMessage($_LANG['ERR_CAPTCHA'], 'error'); $errors = true; }

		if($errors){ cmsCore::redirectBack(); }

        // Подготовим начало письма
        if($form['sendto']=='mail'){
             $mail_message  = $_LANG['FORM'].': ' . $form['title'];
             $mail_message .=  "\n----------------------------------------------\n\n";
        } else {
             $mail_message  = '<h3>'.$_LANG['FORM'].': ' . $form['title'] . '</h3>';
        }
		// Добавляем заполненные поля в письмо
        foreach ($form_fields as $field) {

			if($form_input['values'][$field['id']]){
				if($form['sendto'] == 'mail'){
					$mail_message .= $field['title'] . ":\n" . $form_input['values'][$field['id']] . "\n\n";
				} else {
					$mail_message .= '<h5>'.$field['title'] . '</h5><p>'.$form_input['values'][$field['id']].'</p>';
				}
			}

        }

        // Отправляем форму
		if ($form['sendto']=='mail'){
			$inCore->mailText($form['email'], cmsConfig::getConfig('sitename').': '.$form['title'], $mail_message);
		} else {
			cmsUser::sendMessage(-2, $form['user_id'], $mail_message);
		}

		cmsUser::sessionClearAll();

		cmsCore::addSessionMessage($_LANG['FORM_IS_SEND'], 'info');

        // Очищаем токен
        cmsUser::clearCsrfToken();

		cmsCore::redirectBack();

    }

//========================================================================================================================//

}
?>