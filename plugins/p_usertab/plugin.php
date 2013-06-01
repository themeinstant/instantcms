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

class p_usertab extends cmsPlugin {

// ==================================================================== //

    public function __construct(){

        parent::__construct();

        // Информация о плагине

        $this->info['plugin']           = 'p_usertab';
        $this->info['title']            = 'Demo Profile Plugin';
        $this->info['description']      = 'Пример плагина - Добавляет вкладку "Статьи" в профили всех пользователей';
        $this->info['author']           = 'InstantCMS Team';
        $this->info['version']          = '1.0';

        $this->info['tab']              = 'Статьи'; //-- Заголовок закладки в профиле

        // Настройки по-умолчанию
        $this->config['Количество статей'] = 10;

        // События, которые будут отлавливаться плагином

        $this->events[]                 = 'USER_PROFILE';

    }

// ==================================================================== //

    /**
     * Процедура установки плагина
     * @return bool
     */
    public function install(){

        return parent::install();

    }

// ==================================================================== //

    /**
     * Процедура обновления плагина
     * @return bool
     */
    public function upgrade(){

        return parent::upgrade();

    }

// ==================================================================== //

    /**
     * Обработка событий
     * @param string $event
     * @param array $user
     * @return html
     */
    public function execute($event, $user){

        parent::execute();

        $inCore = cmsCore::getInstance();
		$inDB   = cmsDatabase::getInstance();

		$inCore->loadModel('content');
		$model = new cms_model_content();

		// Условия
		$model->whereUserIs($user['id']);

		// Общее количество статей
		$total = $model->getArticlesCount();

		// Сортировка и разбивка на страницы
		$inDB->orderBy('con.pubdate', 'DESC');
		$inDB->limitPage(1, (int)$this->config['Количество статей']);

		// Получаем статьи
		$content_list = $total ?
						$model->getArticlesList() :
						array(); $inDB->resetConditions();

        ob_start();

        $smarty= $this->inCore->initSmarty('plugins', 'p_usertab.tpl');
        $smarty->assign('total', $total);
        $smarty->assign('articles', $content_list);
        $smarty->display('p_usertab.tpl');

        return ob_get_clean();

    }

// ==================================================================== //

}

?>
