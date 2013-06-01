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
	function f_banners(&$text){

 		$regex   = '/{(БАННЕР=)\s*(.*?)}/i';
		$matches = array();

		preg_match_all( $regex, $text, $matches, PREG_SET_ORDER );

        if (!$matches){ return true; }

		cmsCore::loadModel('banners');
		$model = new cms_model_banners();
		if(!$model->config['component_enabled']) { return true; }

		foreach ($matches as $elm) {

            $elm[0] = str_replace('{', '', $elm[0]);
			$elm[0] = str_replace('}', '', $elm[0]);

			mb_parse_str( $elm[0], $args );

			$position = @$args['БАННЕР'];

			if ($position){
				$output = $model->getBannerHTML($position);
			} else {
                $output = '';
            }

			$text = str_replace('{БАННЕР='.$position.'}', $output, $text );

		}

		return true;

	}
?>