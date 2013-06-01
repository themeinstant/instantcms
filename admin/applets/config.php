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

function applet_config(){

    $inConf = cmsConfig::getInstance();

	//check access
	global $adminAccess;
	if (!cmsUser::isAdminCan('admin/config', $adminAccess)) { cpAccessDenied(); }

	$GLOBALS['cp_page_title'] = 'Настройки сайта';

	cpAddPathway('Настройки сайта', 'index.php?view=config');

	$GLOBALS['cp_page_head'][] = '<script language="JavaScript" type="text/javascript" src="js/content.js"></script>';
	$GLOBALS['cp_page_head'][] = '<script type="text/javascript" src="/admin/js/config.js"></script>';
	$GLOBALS['cp_page_head'][] = '<script type="text/javascript" src="/includes/jquery/jquery.form.js"></script>';
	$GLOBALS['cp_page_head'][] = '<script type="text/javascript" src="/includes/jquery/tabs/jquery.ui.min.js"></script>';

	$GLOBALS['cp_page_head'][] = '<link href="/includes/jquery/tabs/tabs.css" rel="stylesheet" type="text/css" />';

	$do = cmsCore::request('do', 'str', 'list');

	if ($do == 'save'){

        if (!cmsCore::validateForm()) { cmsCore::error404(); }

		$newCFG = array();
		$newCFG['sitename'] 	= stripslashes(cmsCore::request('sitename', 'str'));
		$newCFG['title_and_sitename'] = cmsCore::request('title_and_sitename', 'int');
		$newCFG['title_and_page'] = cmsCore::request('title_and_page', 'int');

        $newCFG['hometitle'] 	= stripslashes(cmsCore::request('hometitle', 'str'));
        $newCFG['homecom']      = cmsCore::request('homecom', 'str');

		$newCFG['siteoff'] 		= cmsCore::request('siteoff', 'int');
		$newCFG['debug'] 		= cmsCore::request('debug', 'int');
		$newCFG['offtext'] 		= htmlspecialchars(cmsCore::request('offtext', 'str'), ENT_QUOTES);
		$newCFG['keywords'] 	= cmsCore::request('keywords', 'str');
		$newCFG['metadesc'] 	= cmsCore::request('metadesc', 'str');
		$newCFG['seourl']       = cmsCore::request('seourl', 'int');
		$newCFG['lang']         = cmsCore::request('lang', 'str', 'ru');

		$newCFG['sitemail'] 	= cmsCore::request('sitemail', 'str');
		$newCFG['wmark']        = cmsCore::request('wmark', 'str');
		$newCFG['stats'] 		= cmsCore::request('stats', 'int');
		$newCFG['template'] 	= cmsCore::request('template', 'str');
		$newCFG['splash'] 		= cmsCore::request('splash', 'int');
		$newCFG['slight'] 		= cmsCore::request('slight', 'int');
		$newCFG['db_host'] 		= $inConf->db_host;
		$newCFG['db_base'] 		= $inConf->db_base;
		$newCFG['db_user'] 		= $inConf->db_user;
		$newCFG['db_pass'] 		= $inConf->db_pass;
		$newCFG['db_prefix']	= $inConf->db_prefix;
		$newCFG['show_pw']		= cmsCore::request('show_pw', 'int');
		$newCFG['short_pw']		= cmsCore::request('short_pw', 'int');
		$newCFG['index_pw']		= cmsCore::request('index_pw', 'int');
		$newCFG['fastcfg']		= cmsCore::request('fastcfg', 'int');

		$newCFG['mailer'] 		= cmsCore::request('mailer', 'str');
		$newCFG['sendmail']		= cmsCore::request('sendmail', 'str');
		$newCFG['smtpauth']		= cmsCore::request('smtpauth', 'int');
		$newCFG['smtpuser']		= cmsCore::inRequest('smtpuser') ?
                                    cmsCore::request('smtpuser', 'str', '') :
                                    $inConf->smtpuser;
		$newCFG['smtppass']		= cmsCore::inRequest('smtppass') ?
                                    cmsCore::request('smtppass', 'str', '') :
                                    $inConf->smtppass;
		$newCFG['smtphost']		= cmsCore::request('smtphost', 'str');

        $newCFG['timezone']		= cmsCore::request('timezone', 'str');
        $newCFG['timediff']		= cmsCore::request('timediff', 'str');

		$newCFG['allow_ip']		= cmsCore::request('allow_ip', 'str', '');

		if (cmsConfig::saveToFile($newCFG)){
			cmsCore::addSessionMessage('Настройки сайта успешно сохранены', 'success');
        } else {
			cmsCore::addSessionMessage('Файл /includes/config.inc.php недоступен для записи', 'error');
        }

        cmsCore::getInstance()->clearSmartyCache();

        cmsUser::clearCsrfToken();

		cmsCore::redirect('index.php?view=config');
	}

?>
<div style="width:800px">

      <?php cpCheckWritable('/includes/config.inc.php'); ?>

<div id="config_tabs">

  <ul id="tabs">
	  	<li><a href="#basic"><span>Сайт</span></a></li>
	  	<li><a href="#home"><span>Главная страница</span></a></li>
		<li><a href="#design"><span>Дизайн</span></a></li>
		<li><a href="#time"><span>Время</span></a></li>
		<li><a href="#database"><span>База данных</span></a></li>
		<li><a href="#mail"><span>Почта</span></a></li>
		<li><a href="#other"><span>Глубиномер</span></a></li>
		<li><a href="#seq"><span>Безопасность</span></a></li>
  </ul>

	<form action="/admin/index.php?view=config" method="post" name="CFGform" target="_self" id="CFGform" style="margin-bottom:30px">
    <input type="hidden" name="csrf_token" value="<?php echo cmsUser::getCsrfToken(); ?>" />
        <div id="basic">
			<table width="720" border="0" cellpadding="5">
				<tr>
					<td>
                        <strong>Название сайта:</strong><br/>
						<span class="hinttext">Используется в заголовках страниц</span>
                    </td>
					<td width="350" valign="top">
                        <input name="sitename" type="text" id="sitename" value="<?php echo htmlspecialchars($inConf->sitename);?>" style="width:358px" />
                    </td>
				</tr>
				<tr>
					<td>
                        <strong>Добавлять в название страницы (тег title) название сайта:</strong>
                    </td>
					<td valign="top">
						<label><input name="title_and_sitename" type="radio" value="1" <?php if ($inConf->title_and_sitename) { echo 'checked="checked"'; } ?>/> Да</label>
						<label><input name="title_and_sitename" type="radio" value="0" <?php if (!$inConf->title_and_sitename) { echo 'checked="checked"'; } ?>/> Нет</label>
                    </td>
				</tr>
				<tr>
					<td>
                        <strong>Добавлять в название страницы (тег title) при пагинации номера страниц:</strong>
                    </td>
					<td valign="top">
						<label><input name="title_and_page" type="radio" value="1" <?php if ($inConf->title_and_page) { echo 'checked="checked"'; } ?>/> Да</label>
						<label><input name="title_and_page" type="radio" value="0" <?php if (!$inConf->title_and_page) { echo 'checked="checked"'; } ?>/> Нет</label>
                    </td>
				</tr>
				<tr>
					<td>
                        <strong>Язык сайта:</strong>
                    </td>
					<td width="350" valign="top">
                        <select name="lang" id="lang" style="width:364px">
                            <?php cmsCore::langList($inConf->lang); ?>
                        </select>
                    </td>
				</tr>
				<tr>
					<td>
                        <strong>Сайт работает:</strong><br/>
                        <span class="hinttext">Отключенный сайт виден только администраторам</span>
                    </td>
					<td valign="top">
                        <label><input name="siteoff" type="radio" value="0" <?php if (!$inConf->siteoff) { echo 'checked="checked"'; } ?>/> Да</label>
                        <label><input name="siteoff" type="radio" value="1" <?php if ($inConf->siteoff) { echo 'checked="checked"'; } ?>/> Нет</label>
                    </td>
                </tr>
				<tr>
					<td>
                        <strong>Включить режим отладки:</strong><br/>
						<span class="hinttext">Показывает ошибки базы данных и тексты запросов</span>
                    </td>
					<td valign="top">
						<label><input name="debug" type="radio" value="1" <?php if ($inConf->debug) { echo 'checked="checked"'; } ?>/> Да</label>
						<label><input name="debug" type="radio" value="0" <?php if (!$inConf->debug) { echo 'checked="checked"'; } ?>/> Нет</label>
                    </td>
				</tr>
				<tr>
					<td valign="middle">
                        <strong>Причина остановки работы:</strong><br />
						<span class="hinttext">Отображается на главной странице<br/>при отключении сайта</span>

                    </td>
					<td valign="top"><input name="offtext" type="text" id="offtext" value="<?php echo htmlspecialchars($inConf->offtext);?>" style="width:358px" /></td>
				</tr>
				<tr>
					<td>
                        <strong>Водяной знак для фотографий: </strong><br/>
						<span class="hinttext">Название картинки в папке /images/</span>
                    </td>
					<td>
						<input name="wmark" type="text" id="wmark" value="<?php echo $inConf->wmark;?>" style="width:358px" />
                    </td>
				</tr>
				<tr>
					<td>
                        <strong>Сбор статистики: </strong><br/>
						<span class="hinttext">Просматривать статистику можно через админку компонента <a href="index.php?view=components&do=config&link=statistics">Статистика</a> Внимание! Нагрузка на сервер увеличится с включением этой опции.</span>
                    </td>
					<td>
						<label><input name="stats" type="radio" value="1" <?php if ($inConf->stats) { echo 'checked="checked"'; } ?>/> Вкл</label>
						<label><input name="stats" type="radio" value="0" <?php if (!$inConf->stats) { echo 'checked="checked"'; } ?>/> Выкл</label>
                    </td>
				</tr>
				<tr>
					<td>
						<strong>Быстрая настройка:</strong> <br />
						<span class="hinttext">Если включено, на сайте заголовки модулей снабжаются ссылками &quot;Настроить&quot;. </span>
                    </td>
                    <td valign="top">
                        <label><input name="fastcfg" type="radio" value="1" <?php if ($inConf->fastcfg) { echo 'checked="checked"'; } ?>/> Вкл</label>
                        <label><input name="fastcfg" type="radio" value="0" <?php if (!$inConf->fastcfg) { echo 'checked="checked"'; } ?>/> Выкл</label>
                    </td>
				</tr>
			</table>
        </div>
        <div id="home">
			<table width="720" border="0" cellpadding="5">
                <tr>
    				<td>
                        <strong>Заголовок главной страницы:</strong><br />
						<span class="hinttext">Если не указан, будет совпадать с названием сайта</span><br/>
                        <span class="hinttext">Показывается в заголовке окна браузера</span>
                    </td>
                    <td width="350" valign="top">
                        <input name="hometitle" type="text" id="hometitle" value="<?php echo htmlspecialchars($inConf->hometitle);?>" style="width:358px" />
                    </td>
			    </tr>
				<tr>
					<td valign="top">
						<strong>Ключевые слова:</strong><br />
						<span class="hinttext">Через запятую, 10-15 слов.</span>
						<div class="hinttext" style="margin-top:4px"><a style="color:#09C" href="http://tutorial.semonitor.ru/#5" target="_blank">Как подобрать ключевые слова?</a></div>
                    </td>
					<td>
						<textarea name="keywords" style="width:350px" rows="3" id="keywords"><?php echo $inConf->keywords;?></textarea>					</td>
				</tr>
				<tr>
					<td valign="top">
						<strong>Описание:</strong><br />
						<span class="hinttext">Не более 250 символов.</span>
						<div class="hinttext" style="margin-top:4px"><a style="color:#09C" href="http://tutorial.semonitor.ru/#219" target="_blank">Как правильно составить описание?</a></div>
                    </td>
					<td>
						<textarea name="metadesc" style="width:350px" rows="3" id="metadesc"><?php echo $inConf->metadesc;?></textarea>
                    </td>
				</tr>
                <tr>
    				<td>
                        <strong>Компонент на главной странице:</strong>
                    </td>
                    <td width="350" valign="top">
                        <select name="homecom" style="width:358px">
                            <option value="" <?php if(!$inConf->homecom){ ?>selected="selected"<?php } ?>>-- Без компонента, только модули --</option>
                            <?php echo cmsCore::getListItems('cms_components', $inConf->homecom, 'title', 'ASC', 'internal=0', 'link'); ?>
                        </select>
                    </td>
			    </tr>
				<tr>
					<td>
						<strong>Входная страница:</strong> <br/>
						<span class="hinttext">Показывается при первом посещении сайта</span> <br/>
                        <span class="hinttext">Файл: <strong>/templates/&lt;ваш шаблон&gt;/splash/splash.php</strong></span>
					</td>
					<td valign="top">
						<label><input name="splash" type="radio" value="0" <?php if (!$inConf->splash) { echo 'checked="checked"'; } ?>/>	Скрыть</label>
						<label><input name="splash" type="radio" value="1" <?php if ($inConf->splash) { echo 'checked="checked"'; } ?>/> Показывать</label>
					</td>
				</tr>
			</table>
        </div>
		<div id="design">
			<table width="720" border="0" cellpadding="5">
				<tr>
					<td valign="top">
                        <div style="margin-top:2px">
                            <strong>Шаблон:</strong><br />
                            <span class="hinttext">Содержимое папки &quot;templates/&quot; </span>
                        </div>
					</td>
					<td>
                        <select name="template" id="template" style="width:350px" onchange="document.CFGform.submit();">
                            <?php cmsCore::templatesList($inConf->template); ?>
                        </select>
                        <div style="margin-top:5px" class="hinttext">
                            При смене шаблона необходимо очистить папку &laquo;<strong>cache</strong>&raquo; на сервере
                        </div>
					</td>
				</tr>
				<tr>
					<td><strong>Подсветка результатов поиска:</strong></td>
					<td valign="top">
						<label><input name="slight" type="radio" value="1" <?php if ($inConf->slight) { echo 'checked="checked"'; } ?>/> Да</label>
						<label><input name="slight" type="radio" value="0" <?php if (!$inConf->slight) { echo 'checked="checked"'; } ?>/> Нет</label>
					</td>
				</tr>
			</table>
		</div>
		<div id="time">
			<table width="720" border="0" cellpadding="5">
				<tr>
					<td valign="top" width="100">
                        <div style="margin-top:2px">
                            <strong>Временная зона:</strong>
                        </div>
					</td>
					<td>
                        <select name="timezone" id="timezone" style="width:350px">
                            <?php include(PATH.'/admin/includes/timezones.php'); ?>
                            <?php foreach($timezones as $tz) { ?>
                            <option value="<?php echo $tz; ?>" <?php if ($tz == $inConf->timezone) { ?>selected="selected"<?php } ?>><?php echo $tz; ?></option>
                            <?php } ?>
                        </select>
					</td>
				</tr>
				<tr>
					<td>
						<strong>Смещение в часах:</strong>
					</td>
					<td width="350">
                        <select name="timediff" id="timediff" style="width:60px">
                            <?php for($h=-12; $h<=12; $h++) { ?>
                                <option value="<?php echo $h; ?>" <?php if ($h == $inConf->timediff) { ?>selected="selected"<?php } ?>><?php echo ($h > 0 ? '+'.$h : $h); ?></option>
                            <?php } ?>
                        </select>
					</td>
				</tr>
			</table>
		</div>
		<div id="database">
			<table width="720" border="0" cellpadding="5" style="margin-top:15px;">
				<tr>
					<td colspan="2"><span class="hinttext">Все реквизиты MySQL настраиваются в файле /includes/config.inc.php</span></td>
				</tr>
			</table>
        </div>
		<div id="mail">
			<table width="720" border="0" cellpadding="5" style="margin-top:15px;">
				<tr>
					<td>
                        <strong>E-mail сайта: </strong><br/>
						<span class="hinttext">Адрес от имени которого будут рассылаться<br/>уведомления пользователям</span>
                    </td>
					<td>
						<input name="sitemail" type="text" id="sitemail" value="<?php echo $inConf->sitemail;?>" style="width:358px" />
                    </td>
				</tr>
				<tr>
					<td>
						<strong>Способ отправки:</strong>
					</td>
					<td width="250">
						<select name="mailer" style="width:354px">
							<option value="mail" <?php if ($inConf->mailer=='mail') { echo 'selected="selected"'; } ?>>Функция mail в PHP</option>
							<option value="sendmail" <?php if ($inConf->mailer=='sendmail') { echo 'selected="selected"'; } ?>>Sendmail</option>
							<option value="smtp" <?php if ($inConf->mailer=='smtp') { echo 'selected="selected"'; } ?>>SMTP-сервер</option>
						</select>
					</td>
				</tr>
				<tr>
					<td>
						<strong>Путь к Sendmail:</strong><br/>
						<span class="hinttext">Обычно это /usr/sbin/sendmail</span>
					</td>
					<td width="350">
						<input name="sendmail" type="text" id="sendmail" value="<?php echo $inConf->sendmail;?>" style="width:350px" />
					</td>
				</tr>
				<tr>
					<td>
						<strong>SMTP авторизация:</strong>
					</td>
					<td width="350">
						<label><input name="smtpauth" type="radio" value="1" <?php if ($inConf->smtpauth) { echo 'checked="checked"'; } ?>/> Да</label>
						<label><input name="smtpauth" type="radio" value="0" <?php if (!$inConf->smtpauth) { echo 'checked="checked"'; } ?>/> Нет</label>
					</td>
				</tr>
				<tr>
					<td>
						<strong>SMTP пользователь:</strong>
					</td>
					<td width="350">
                        <?php if(!$inConf->smtpuser){ ?>
                            <input name="smtpuser" type="text" id="smtpuser" value="<?php echo $inConf->smtpuser;?>" style="width:350px" />
                        <?php } else { ?>
                            <span class="hinttext">имя пользователя вы можете сменить в файле /includes/config.inc.php</span>
                        <?php } ?>
					</td>
				</tr>
				<tr>
					<td>
						<strong>SMTP пароль:</strong>
					</td>
					<td width="350">
                        <?php if(!$inConf->smtppass){ ?>
                            <input name="smtppass" type="password" id="smtppass" value="<?php echo $inConf->smtppass;?>" style="width:350px" />
                        <?php } else { ?>
                            <span class="hinttext">пароль вы можете сменить в файле /includes/config.inc.php</span>
                        <?php } ?>
					</td>
				</tr>
				<tr>
					<td>
						<strong>SMTP хост:</strong>
					</td>
					<td width="350">
						<input name="smtphost" type="text" id="smtphost" value="<?php echo $inConf->smtphost;?>" style="width:350px" />
					</td>
				</tr>
			</table>
		</div>
		<div id="other">
			<table width="720" border="0" cellpadding="5">
				<tr>
					<td>
						<strong>Показывать глубиномер?</strong><br />
						<span class="hinttext">
                            Отображает путь к разделу,<br/>
                            в котором находится посетитель
                        </span>
					</td>
					<td>
						<label><input name="show_pw" type="radio" value="1" <?php if ($inConf->show_pw) { echo 'checked="checked"'; } ?>/> Да</label>
						<label><input name="show_pw" type="radio" value="0" <?php if (!$inConf->show_pw) { echo 'checked="checked"'; } ?>/> Нет </label>
					</td>
				</tr>
				<tr>
					<td><strong>Глубиномер на главной странице:</strong></td>
					<td>
						<label><input name="index_pw" type="radio" value="1" <?php if ($inConf->index_pw) { echo 'checked="checked"'; } ?>/> Да</label>
						<label><input name="index_pw" type="radio" value="0" <?php if (!$inConf->index_pw) { echo 'checked="checked"'; } ?>/>	Нет </label>
					</td>
				</tr>
				<tr>
					<td><strong>Выводить текущую страницу в глубиномере:</strong></td>
					<td>
						<label><input name="short_pw" type="radio" value="0" <?php if (!$inConf->short_pw) { echo 'checked="checked"'; } ?>/> Да</label>
						<label><input name="short_pw" type="radio" value="1" <?php if ($inConf->short_pw) { echo 'checked="checked"'; } ?>/> Нет</label>
					</td>
				</tr>
			</table>
        </div>
        <div id="seq">
			<table width="720" border="0" cellpadding="5">
				<tr>
					<td>
						<strong>IP адреса, с которых разрешен доступ в админку:</strong> <br />
						<span class="hinttext">Введите ip адреса через запятую, с которых разрешен доступ в админку. Если не заданы, доступ разрешен всем. </span>					</td>
				<td valign="top">
					<input name="allow_ip" type="text" id="allow_ip" value="<?php echo htmlspecialchars($inConf->allow_ip);?>" style="width:358px" /></td>
				</tr>
			</table>
    <p style="color:#900"><strong>Внимание:</strong> после конфигурирования в целях безопасности необходимо сменить владельца файла /includes/config.inc.php и выставить права доступа на него 644.<br /> Так же обращаем Ваше внимание: после полной настройки сайта на сервере необходимо выставить права доступа <strong>644 для всех файлов</strong> и <strong>755 для всех каталогов,</strong> кроме директорий загрузки файлов. Кроме того, убедитесь, что владелец файлов сайта - пользователь, отличный от того, под которым работает web сервер и интерпретатор php.</p>
        </div>

	<script type="text/javascript">$('#config_tabs > ul#tabs').tabs();</script>
	<div align="left">
		<input name="do" type="hidden" id="do" value="save" />
		<input name="save" type="submit" id="save" value="Сохранить" />
        <input name="back" type="button" id="back" value="Отмена" onclick="window.history.back();" />
	</div>
</form>
</div></div>
<?php } ?>