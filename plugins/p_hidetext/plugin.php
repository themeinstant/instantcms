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

class p_hidetext extends cmsPlugin {

// ==================================================================== //

    public function __construct(){

        parent::__construct();

        // Информация о плагине

        $this->info['plugin']           = 'p_hidetext';
        $this->info['title']            = 'Скрытый текст';
        $this->info['description']      = 'Скрывает содержимое тега [hide] от незарегистрированных';
        $this->info['author']           = 'InstantCMS Team';
        $this->info['version']          = '1.12';

        // События, которые будут отлавливаться плагином

        $this->events[]                 = 'GET_POSTS';
        $this->events[]                 = 'GET_POSTS_MODULE';
        $this->events[]                 = 'GET_POST';
        $this->events[]                 = 'GET_COMMENTS';
        $this->events[]                 = 'GET_COMMENTS_MODULE';
        $this->events[]                 = 'GET_COMMENT';
        $this->events[]                 = 'GET_FORUM_POST';
        $this->events[]                 = 'GET_FORUM_POSTS';
        $this->events[]                 = 'GET_FORUM_POSTS_MODULE';
        $this->events[]                 = 'GET_WALL_POSTS';

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
     * @param mixed $item
     * @return mixed
     */
    public function execute($event, $item){

        parent::execute();

        switch ($event){
            case 'GET_POST': $item = $this->eventGetPost($item); break;
            case 'GET_POSTS': $item = $this->eventGetPosts($item); break;
            case 'GET_POSTS_MODULE': $item = $this->eventGetPosts($item, true); break;
            case 'GET_COMMENT': $item = $this->eventGetComment($item); break;
            case 'GET_COMMENTS': $item = $this->eventGetComments($item); break;
            case 'GET_COMMENTS_MODULE': $item = $this->eventGetComments($item, true); break;
            case 'GET_FORUM_POST': $item = $this->eventGetPost($item); break;
            case 'GET_FORUM_POSTS': $item = $this->eventGetPosts($item); break;
            case 'GET_FORUM_POSTS_MODULE': $item = $this->eventGetPosts($item, true); break;
            case 'GET_WALL_POSTS': $item = $this->eventGetComments($item); break;
        }

        return $item;

    }

// ==================================================================== //

    private function parseHide($text, $hidden = false){

        $inUser = cmsUser::getInstance();

        $pattern = '/\[hide(?:=?)([0-9]*)\](.*?)\[\/hide\]/sui';

        preg_match($pattern, $text, $matches);

        if($hidden){
            $replacement = '';
        } else if (!$inUser->id){
            $replacement = '<div class="bb_tag_hide">Скрытый текст виден только <a href="/registration">зарегистрированным</a> пользователям</div>';
        } else {

            if(!$matches[1]){
                $replacement = '<div class="bb_tag_hide">${2}</div>';
            } elseif($inUser->rating > $matches[1] || $inUser->is_admin) {
                $replacement = '<div class="bb_tag_hide">${2}</div>';
        } else {
                $replacement = '<div class="bb_tag_hide">Пользователи с рейтингом более '.cmsCore::spellCount($matches[1], 'пункт', 'пункта', 'пунктов').' могут просмотреть скрытый текст.</div>';
            }

        }

        return preg_replace($pattern, $replacement, $text);

    }

    private function eventGetPost($item) {

        if (!is_array($item)){ return $item; }

        $item['content_html'] = $this->parseHide($item['content_html']);

        return $item;

    }

    private function eventGetPosts($items, $hidden = false){

        if (!is_array($items)){ return $items; }

        foreach($items as $i=>$item){
            $items[$i]['content_html'] = $this->parseHide($item['content_html'], $hidden);
        }

        return $items;
    }

    private function eventGetComments($items, $hidden = false){

        if (!is_array($items)){ return $items; }

        foreach($items as $i=>$item){
            $items[$i]['content'] = $this->parseHide($item['content'], $hidden);
        }

        return $items;
    }

    private function eventGetComment($item){

        if (!is_array($item)){ return $item; }

        $item['content'] = $this->parseHide($item['content']);

        return $item;

    }

// ==================================================================== //

}

?>
