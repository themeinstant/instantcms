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

    $opt = cmsCore::request('opt', 'str', 'list');
    $id  = cmsCore::request('id', 'int', 0);

    $inDB = cmsDatabase::getInstance();

    cmsCore::loadModel('polls');
    $model = new cms_model_polls();

	cpAddPathway('Голосования', '?view=modules&do=edit&id='.$id);

	$toolmenu = array();
	$toolmenu[0]['icon'] = 'new.gif';
	$toolmenu[0]['title'] = 'Новое голосование';
	$toolmenu[0]['link'] = '?view=modules&do=config&id='.$id.'&opt=add';

	$toolmenu[1]['icon'] = 'list.gif';
	$toolmenu[1]['title'] = 'Все голосования';
	$toolmenu[1]['link'] = '?view=modules&do=config&id='.$id.'&opt=list';

	$toolmenu[2]['icon'] = 'config.gif';
	$toolmenu[2]['title'] = 'Настройки модуля';
	$toolmenu[2]['link'] = '?view=modules&do=config&id='.$id.'&opt=config';

	$toolmenu[3]['icon'] = 'cancel.gif';
	$toolmenu[3]['title'] = 'Отмена';
	$toolmenu[3]['link'] = '?view=modules';

	cpToolMenu($toolmenu);

    $cfg = $inCore->loadModuleConfig($id);

	if($opt=='saveconfig'){

		$cfg['shownum'] = cmsCore::request('shownum', 'int', 0);
		$cfg['poll_id'] = cmsCore::request('poll_id', 'int', 0);

        $inCore->saveModuleConfig($id, $cfg);

        cmsCore::addSessionMessage('Настройки сохранены.', 'success');
        cmsCore::redirectBack();

	}

	if ($opt == 'list'){

		cpAddPathway('Все голосования', '?view=modules&do=config&id='.$id.'&opt=list');
		echo '<h3>Все голосования</h3>';

		//TABLE COLUMNS
		$fields = array();

		$fields[0]['title'] = 'id'; $fields[0]['field'] = 'id'; $fields[0]['width'] = '30';

		$fields[1]['title'] = 'Название'; $fields[1]['field'] = 'title'; $fields[1]['width'] = ''; $fields[1]['link'] = '?view=modules&do=config&id='.$id.'&opt=edit&poll_id=%id%';
		$fields[1]['filter'] = 15;

		//ACTIONS
		$actions = array();
		$actions[0]['title'] = 'Редактировать';
		$actions[0]['icon']  = 'edit.gif';
		$actions[0]['link']  = '?view=modules&do=config&id='.$id.'&opt=edit&poll_id=%id%';

		$actions[1]['title'] = 'Удалить';
		$actions[1]['icon']  = 'delete.gif';
		$actions[1]['confirm'] = 'Удалить голосование?';
		$actions[1]['link']  = '?view=modules&do=config&id='.$id.'&opt=delete&poll_id=%id%';

		//Print table
		cpListTable('cms_polls', $fields, $actions);

	}

	if ($opt == 'submit'){

		$title         = cmsCore::request('title', 'str', '');
		$answers_title = cmsCore::request('answers', 'array_str', '');

		$answers = array();

		foreach($answers_title as $answer){
			if ($answer) { $answers[$answer] = 0; }
		}

		$str_answers = cmsCore::arrayToYaml($answers);

		$sql = "INSERT INTO cms_polls (title, pubdate, answers)
				VALUES ('$title', NOW(), '$str_answers')";
		$inDB->query($sql);

        cmsCore::addSessionMessage('Голосование успешно создано.', 'success');

        cmsCore::redirect('?view=modules&do=config&id='.$id.'&opt=list');

	}

	if($opt == 'delete'){

        $model->deletePoll(cmsCore::request('poll_id', 'int'));

        cmsCore::addSessionMessage('Голосование успешно удалено.', 'success');

		cmsCore::redirect('?view=modules&do=config&id='.$id.'&opt=list');

	}

	if ($opt == 'update'){

        $poll_id = cmsCore::request('poll_id', 'int');

		$title         = cmsCore::request('title', 'str', '');
		$answers_title = cmsCore::request('answers', 'array_str');
        $nums          = cmsCore::request('num', 'array_int');

        $is_clear = cmsCore::request('is_clear', 'int');

        if($is_clear){
            $sql = "DELETE FROM cms_polls_log WHERE poll_id = '$poll_id'";
            $inDB->query($sql);
        }

		$answers = array();

		foreach($answers_title as $key=>$answer){
			if ($answer) {
                if (isset($nums[$key]) && !$is_clear) {
                    $answers[$answer] = $nums[$key];
                }
                else {
                    $answers[$answer] = 0;
                }
            }
		}

		$str_answers = cmsCore::arrayToYaml($answers);

        $sql = "UPDATE cms_polls
                SET title='$title',
                    answers='$str_answers'
                WHERE id = '$poll_id' LIMIT 1";

        $inDB->query($sql);

        cmsCore::addSessionMessage('Данные голосования сохранены.', 'success');

		cmsCore::redirect('?view=modules&do=config&id='.$id.'&opt=list');

	}

	if($opt=='add' || $opt=='edit'){

		if ($opt=='add'){

            cpAddPathway('Новое голосование', '?view=modules&do=config&id='.$id.'&opt=add');
			echo '<h3>Добавить голосование</h3>';

		} else {

			$mod = $model->getPoll(cmsCore::request('poll_id', 'int'));
			cpAddPathway($mod['title'], '?view=modules&do=config&id='.$id.'&opt=edit&poll_id='.$mod['id']);
			echo '<h3>Редактировать голосование</h3>';

            $answers_title = array();
            $answers_num   = array();
            $item = 1;
            foreach ($mod['answers'] as $answer=>$num){

                $answers_title[$item] = htmlspecialchars($answer);
                $answers_num[$item]   = $num;
                $item++;

            }

		}

?>
      <form id="addform" name="addform" method="post" action="index.php?view=modules&do=config&id=<?php echo $id; ?>">
        <table width="600" border="0" cellspacing="5" class="proptable">
          <tr>
            <td width="200">Вопрос: </td>
            <td width="213"><input name="title" type="text" id="title" size="30" value="<?php echo htmlspecialchars(@$mod['title']); ?>" /></td>
            <td width="173">&nbsp;</td>
          </tr>
          <?php for ($v=1; $v<=12; $v++) { ?>

          <tr>
            <td>Вариант ответа №<?php echo $v ?>:</td>
            <td><input name="answers[<?php echo $v ?>]" type="text" size="30" value="<?php echo @$answers_title[$v]; ?>" /></td>
            <td><?php if (isset($answers_num[$v])) { echo 'Голосов: '.$answers_num[$v]; echo '<input type="hidden" name="num['.$v.']" value="'.$answers_num[$v].'" />';  } else { echo '&nbsp;'; } ?></td>
          </tr>

          <?php } ?>
        </table>

        <input name="add_mod" type="submit" id="add_mod" <?php if ($opt=='add') { echo 'value="Создать голосование"'; } else { echo 'value="Сохранить голосование"'; } ?> />
        <input name="opt" type="hidden" id="opt" <?php if ($opt=='add') { echo 'value="submit"'; } else { echo 'value="update"'; } ?> />
        <?php
		  	if ($opt=='edit'){
			 echo '<input name="poll_id" type="hidden" value="'.$mod['id'].'" /> ';
             echo ' <label><input name="is_clear" type="checkbox" value="1" /> Очистить данные голосований</label>';
			}
		  ?>
      </form>

<?php }

	if($opt=='config'){

	cpAddPathway('Настройки', '?view=modules&do=config&id='.$id.'&opt=config');
	echo '<h3>Настройки модуля</h3>';

	?>

      <form action="index.php?view=modules&do=config&id=<?php echo $id;?>" method="post" name="optform" target="_self" id="form1">
        <table border="0" cellpadding="10" cellspacing="0" class="proptable">
          <tr>
            <td width="215"><strong>Показывать результаты до голосования: </strong></td>
            <td width="126">
                <label><input name="shownum" type="radio" value="1" <?php if (@$cfg['shownum']) { echo 'checked="checked"'; } ?> /> Да</label>
                <label><input name="shownum" type="radio" value="0" <?php if (@!$cfg['shownum']) { echo 'checked="checked"'; } ?> /> Нет </label>
            </td>
          </tr>
          <tr>
            <td><strong>Активное голосование : </strong></td>
            <td>
                <select name="poll_id" id="poll_id">
                    <option value="0">-- Случайное голосование --</option>
                    <?php
                        if (isset($cfg['poll_id'])) {
                            echo $inCore->getListItems('cms_polls', $cfg['poll_id']);
                        } else {
                            echo $inCore->getListItems('cms_polls');
                        }
                    ?>
                </select>
            </td>
          </tr>
        </table>
        <p>
          <input name="opt" type="hidden" id="opt" value="saveconfig" />
          <input name="save" type="submit" id="save" value="Сохранить" />
          <input name="back" type="button" id="back" value="Отмена" onclick="window.location.href='/admin/index.php?view=modules&do=config&id=<?php echo $id; ?>&opt=list';"/>
        </p>
      </form>
    <?php

	}

?>