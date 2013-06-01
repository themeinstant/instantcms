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

function rssfeed(){

    $inCore = cmsCore::getInstance();
    $inConf = cmsConfig::getInstance();

	$cfg = $inCore->loadComponentConfig('rssfeed');

    global $_LANG;

    $do      = $inCore->do;
    $target  = cmsCore::request('target', 'str', 'rss');
    $item_id = cmsCore::request('item_id', 'str', 'all');

	if(!$inCore->isComponentInstalled($target)) { cmsCore::error404(); }

	if (!preg_match('/^([a-z0-9_\-]+)$/ui', $item_id)) { $item_id = 0; }

	if ($item_id == 'all') { $item_id = 0; }

////////////////////// RSS /////////////////////////////////////////////////////////////////////////////////////////////////
if ($do=='view'){

	if (!file_exists(PATH.'/components/'.$target.'/prss.php')){ cmsCore::halt($_LANG['NOT_RSS_GENERATOR']); }

	cmsCore::loadLanguage('components/'.$target);
	cmsCore::includeFile('components/'.$target.'/prss.php');

	$rssdata = call_user_func_array('rss_'.$target, array($item_id, $cfg));
	if(!$rssdata){ cmsCore::halt($_LANG['NOT_RSS_GENERATOR']); }

	$channel = $rssdata['channel'];
	$items   = $rssdata['items'];

	if ($cfg['addsite']) { $channel['title'] .= ' :: ' . $inConf->sitename; }
	$channel['title'] = trim(htmlspecialchars(strip_tags($channel['title'])));

	header('Content-Type: application/rss+xml; charset=utf-8');

	$rss  = '<?xml version="1.0" encoding="utf-8" ?>' ."\n";
	$rss .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' ."\n";
		$rss .= '<channel>' ."\n";
			// Канал
			$rss .= '<title>'.$channel['title'].'</title>' ."\n";
			$rss .= '<link>'.$channel['link'].'</link>' ."\n";
			$rss .= '<description><![CDATA['.trim(htmlspecialchars(strip_tags($channel['description']))).']]></description>' ."\n";

			if ($cfg['icon_on']){
				$rss .= '<image>'."\n";
					$rss .= '<title>'.$channel['title'].'</title>'."\n";
					$rss .= '<url>'.$cfg['icon_url'].'</url>'."\n";
					$rss .= '<link>'.$channel['link'].'</link>'."\n";
				$rss .= '</image>'."\n";
			}

			// Содержимое канала
			if (is_array($items) && $items){
				foreach ($items as $key=>$item){
					$rss .= '<item>' ."\n";
						$rss .= '<title>'.trim(htmlspecialchars(strip_tags($item['title']))).'</title>' ."\n";
						$rss .= '<pubDate>'.date('r', strtotime($item['pubdate'])+($inConf->timediff*3600)).'</pubDate>' ."\n";
						$rss .= '<guid>'.$item['link'].'</guid>' ."\n";
						$rss .= '<link>'.$item['link'].'</link>' ."\n";
						if (@$item['description']){
							$rss .= '<description><![CDATA['.$item['description'].']]></description>' ."\n";
						}
						$rss .= '<category>'.$item['category'].'</category>' ."\n";
						$rss .= '<comments>'.$item['comments'].'</comments>' ."\n";
						if (@$item['image']){
							  $rss .= '<enclosure url="'.$item['image'].'" length="'.$item['size'].'" type="image/jpeg" />' ."\n";
						}
					$rss .= '</item>' ."\n";
				}
			}
		$rss .= '</channel>' ."\n";
	$rss .= '</rss>';

	cmsCore::halt($rss);

}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

}

?>