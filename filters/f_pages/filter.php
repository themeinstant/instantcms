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

	function f_pages(&$text){

        $seolink = urldecode(cmsCore::request('seolink', 'str', ''));
        $seolink = preg_replace ('/[^a-zа-я-яёіїєґА-ЯЁІЇЄҐ0-9_\/\-]/ui', '', $seolink);

        $article = cmsDatabase::getInstance()->get_fields('cms_content', "seolink='{$seolink}'", 'id, title, seolink');
		if (!$article) return;

		if (mb_strpos($text, 'pagebreak') === false){
			return true;
		}

		$regex = '/{(pagebreak)\s*(.*?)}/iu';

		$matches = array();
		preg_match_all($regex, $text, $matches, PREG_SET_ORDER);

		$pages = preg_split($regex, $text);

		$n = count($pages);

		if ($n<=1){
			return true;
		} else {

			$page = cmsCore::request('page', 'int', 1);
			$text = $pages[$page-1];

            if(!$text){ cmsCore::error404(); }

			$text .= getPageBar($article, $n, $page);
			return true;

		}

	}

    /**
     * Возвращает панель с выбором страниц для статьи
     * @param int $pages
     * @param int $current
     * @return html
     */
    function getPageBar($article, $pages, $current){

        $html = '';

        cmsCore::loadModel('content');

        if($pages>1){
            $html .= '<div class="pagebar">';
            $html .= '<span class="pagebar_title"><strong>Страницы: </strong></span>';
            for ($p=1; $p<=$pages; $p++){
                if ($p != $current) {
                    $link  = cms_model_content::getArticleURL(null, $article['seolink'], $p);
                    $html .= ' <a href="'.$link.'" class="pagebar_page">'.$p.'</a> ';
                } else {
                    $html .= '<span class="pagebar_current">'.$p.'</span>';
                }
            }
            $html .= '</div>';
        }
        return $html;
    }

?>