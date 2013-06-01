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

class cms_model_banners{

	public function __construct(){
		$this->config = cmsCore::getInstance()->loadComponentConfig('banners');
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

	public static function getBanner($id){

		$banner = cmsDatabase::getInstance()->get_fields('cms_banners', "id = '$id'", '*');

        if ($banner){
            $banner = cmsCore::callEvent('GET_BANNER', $banner);
            return $banner;
        } else {
            return false;
        }

    }

/* ==================================================================================================== */
/* ==================================================================================================== */

	public static function getImageBanner($banner){

		return '<a href="/gobanner'.$banner['id'].'" title="'.$banner['title'].'" target="_blank"><img src="/images/banners/'.$banner['fileurl'].'" border="0" alt="'.$banner['title'].'"/></a>';

    }

	public static function getSwfBanner($banner){

		return '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0" width="468" height="60">'."\n".
						'<param name="movie" value="/images/banners/'.$banner['fileurl'].'?banner_id='.$banner['id'].'" />'."\n".
						'<param name="quality" value="high" />'."\n".
						'<param name="FlashVars" value="banner_id='.$banner['id'].'" />'."\n".
						'<embed src="/images/banners/'.$banner['fileurl'].'?banner_id='.$banner['id'].'" quality="high" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" width="468" height="60">'."\n".
						'</embed>'."\n".
					'</object>';

    }

/* ==================================================================================================== */
/* ==================================================================================================== */
    /**
     * Возвращает код баннера по названию позиции
     * Считаются просмотры
     * @param int $id
     * @return html
     */
    public static function getBannerHTML($position) {

        $position = cmsDatabase::escape_string($position);

		$html = '';

		$banner = cmsDatabase::getInstance()->get_fields('cms_banners', "position = '$position' AND published = 1 AND ((maxhits > hits) OR (maxhits = 0))", '*', 'RAND()');
		if(!$banner) { return $html; }

		if ($banner['typeimg']=='image'){
			$html = self::getImageBanner($banner);
		}

		if ($banner['typeimg']=='swf'){
			$html = self::getSwfBanner($banner);
		}

		if ($html) { cmsDatabase::getInstance()->query("UPDATE cms_banners SET hits = hits + 1 WHERE id= '{$banner['id']}'");	}

        return $html;

    }

/* ==================================================================================================== */
/* ==================================================================================================== */
    /**
     * Возвращает код баннера по ID
     * @param int $id
     * @return html
     */
    public static function getBannerById($id){

        $html = '';

        $banner = self::getBanner($id);
		if(!$banner) { return $html; }

		if ($banner['typeimg']=='image'){
			$html = self::getImageBanner($banner);
		}
		if ($banner['typeimg']=='swf'){
			$html = self::getSwfBanner($banner);
		}

        return $html;
    }

/* ==================================================================================================== */
/* ==================================================================================================== */
    /**
     * Возвращает элементы <option> для списка баннерных позиций
     * @param int $selected
     * @return html
     */
    public static function getBannersListHTML($selected=0){
        $html = '';
        for($bp=1; $bp<=30; $bp++){
            if (@$selected==$bp){
                $s = 'selected';
            } else {
                $s = '';
            }
            $html .= '<option value="banner'.$bp.'" '.$s.'>banner'.$bp.'</option>'."\n";
        }
        return $html;
    }

/* ==================================================================================================== */
/* ==================================================================================================== */

	public static function clickBanner($id){

        $update_sql = "UPDATE cms_banners SET clicks = clicks + 1 WHERE id = '$id'";
        cmsDatabase::getInstance()->query($update_sql);
        cmsCore::callEvent('CLICK_BANNER', $id);

        return true;

    }

}