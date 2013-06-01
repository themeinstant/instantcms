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

	function insertForm($form_title){

		cmsCore::loadClass('form');

		return cmsForm::displayForm(trim($form_title), array(), false);

	}

	function PriceLink($category_title){

        $category_title = cmsCore::strClear($category_title);

        $cat = cmsDatabase::getInstance()->get_fields('cms_price_cats', "title LIKE '{$category_title}'", 'id, title');

		if($cat){
			$link = '<a href="/price/'.$cat['id'].'" title="'.htmlspecialchars($cat['title']).'">'.$cat['title'].'</a>';
		} else { $link = ''; }

		return $link;
	}

	function PhotoLink($photo_title){

        $photo_title = cmsCore::strClear($photo_title);

        $photo = cmsDatabase::getInstance()->get_fields('cms_photo_files', "title LIKE '{$photo_title}'", 'id, title');

		if($photo){
			$link = '<a href="/photos/photo'.$photo['id'].'.html" title="'.htmlspecialchars($photo['title']).'">'.$photo['title'].'</a>';
		} else { $link = ''; }

		return $link;
	}

	function AlbumLink($album_title){

        $album_title = cmsCore::strClear($album_title);

        $album = cmsDatabase::getInstance()->get_fields('cms_photo_albums', "title LIKE '{$album_title}'", 'id, title');

		if($album){
			$link = '<a href="/photos/'.$album['id'].'" title="'.htmlspecialchars($album['title']).'">'.$album['title'].'</a>';
		} else { $link = ''; }

		return $link;
	}

	function ContentLink($content_title){

        $content_title = cmsCore::strClear($content_title);

        $content = cmsDatabase::getInstance()->get_fields('cms_content', "title LIKE '{$content_title}'", 'seolink, title');

		if($content){
			$link = '<a href="/'.$content['seolink'].'.html" title="'.htmlspecialchars($content['title']).'">'.$content['title'].'</a>';
		} else { $link = ''; }

		return $link;
	}


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	function f_replace(&$text){

        $inDB = cmsDatabase::getInstance();

		//REPLACE PRICE CATS LINKS
 		$regex = '/{(ПРАЙС=)\s*(.*?)}/i';
		$matches = array();
		preg_match_all( $regex, $text, $matches, PREG_SET_ORDER );
		foreach ($matches as $elm) {
			$elm[0] = str_replace('{', '', $elm[0]);
			$elm[0] = str_replace('}', '', $elm[0]);
			mb_parse_str( $elm[0], $args );
			$category=@$args['ПРАЙС'];
			if ($category){
				$output = PriceLink($category);
			} else { $output = ''; }
			$text = str_replace('{ПРАЙС='.$category.'}', $output, $text );
		}

		//REPLACE PHOTO LINK
 		$regex = '/{(ФОТО=)\s*(.*?)}/i';
		$matches = array();
		preg_match_all( $regex, $text, $matches, PREG_SET_ORDER );
		foreach ($matches as $elm) {
			$elm[0] = str_replace('{', '', $elm[0]);
			$elm[0] = str_replace('}', '', $elm[0]);
			mb_parse_str( $elm[0], $args );
			$photo=@$args['ФОТО'];
			if ($photo){
				$output = PhotoLink($photo);
			} else { $output = ''; }
			$text = str_replace('{ФОТО='.$photo.'}', $output, $text );
		}

		//REPLACE PHOTO ALBUM LINK
 		$regex = '/{(АЛЬБОМ=)\s*(.*?)}/i';
		$matches = array();
		preg_match_all( $regex, $text, $matches, PREG_SET_ORDER );
		foreach ($matches as $elm) {
			$elm[0] = str_replace('{', '', $elm[0]);
			$elm[0] = str_replace('}', '', $elm[0]);
			mb_parse_str( $elm[0], $args );
			$album=@$args['АЛЬБОМ'];
			if ($album){
				$output = AlbumLink($album);
			} else { $output = ''; }
			$text = str_replace('{АЛЬБОМ='.$album.'}', $output, $text );
		}

		//REPLACE CONTENT ITEM LINK
 		$regex = '/{(МАТЕРИАЛ=)\s*(.*?)}/i';
		$matches = array();
		preg_match_all( $regex, $text, $matches, PREG_SET_ORDER );
		foreach ($matches as $elm) {
			$elm[0] = str_replace('{', '', $elm[0]);
			$elm[0] = str_replace('}', '', $elm[0]);
			mb_parse_str( $elm[0], $args );
			$content=@$args['МАТЕРИАЛ'];
			if ($content){
				$output = ContentLink($content);
			} else { $output = ''; }
			$text = str_replace('{МАТЕРИАЛ='.$content.'}', $output, $text );
		}

		//INSERT USER FORM _WITH_ TITLE
 		$regex = '/{(ФОРМА=)\s*(.*?)}/i';
		$matches = array();
		preg_match_all( $regex, $text, $matches, PREG_SET_ORDER );
		foreach ($matches as $elm) {
			$elm[0] = str_replace('{', '', $elm[0]);
			$elm[0] = str_replace('}', '', $elm[0]);
			mb_parse_str( $elm[0], $args );
			$content=@$args['ФОРМА'];
			if ($content){
				$output = insertForm($content, true);
			} else { $output = ''; }
			$text = str_replace('{ФОРМА='.$content.'}', $output, $text );
		}

		//INSERT USER FORM _WITHOUT_ TITLE
 		$regex = '/{(БЛАНК=)\s*(.*?)}/i';
		$matches = array();
		preg_match_all( $regex, $text, $matches, PREG_SET_ORDER );
		foreach ($matches as $elm) {
			$elm[0] = str_replace('{', '', $elm[0]);
			$elm[0] = str_replace('}', '', $elm[0]);
			mb_parse_str( $elm[0], $args );
			$content=@$args['БЛАНК'];
			if ($content){
				$output = insertForm($content, false);
			} else { $output = ''; }
			$text = str_replace('{БЛАНК='.$content.'}', $output, $text );
		}

		//REPLACE BY USER RULES
		$sql = "SELECT * FROM cms_filter_rules";
		$result = $inDB->query($sql) ;
		if (mysql_num_rows($result)){
			while($rule = mysql_fetch_assoc($result)){
				$regex = '/{('.$rule['find'].')\s*(.*?)}/i';
				if($rule['published']){
					$text = preg_replace( $regex, $rule['replace'], $text );
				} else {
					$text = preg_replace( $regex, '', $text );
				}
			}
		}

		return true;
	}
?>