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

class cmsConfig {

    private static $instance = null;
    private $config = array();

    private function __construct(){

		mb_internal_encoding("UTF-8");

        $cfg_file = PATH.'/includes/config.inc.php';

        if (file_exists($cfg_file)){ include($cfg_file); } else { $_CFG = array(); }

        $d_cfg = self::getDefaultConfig();
		$this->config = array_merge($d_cfg, $_CFG);

		date_default_timezone_set($this->config['timezone']);

		setlocale(LC_ALL, "ru_RU.UTF-8");

		foreach ($this->config as $id=>$value) {
			$this->{$id} = $value;
		}

        return true;

	}

    private function __clone() {}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self;
        }
        return self::$instance;
    }

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public static function getDefaultConfig() {

		$cfg['sitename']  = 'Моя социальная сеть';
		$cfg['title_and_sitename'] = 1;
		$cfg['title_and_page'] = 1;
		$cfg['hometitle'] = '';
		$cfg['homecom']   = '';
		$cfg['siteoff']   = 0;
		$cfg['debug'] 	  = 0;
		$cfg['offtext']   = 'Производится обновление сайта';
		$cfg['keywords']  = 'InstantCMS, система управления сайтом, бесплатная CMS, движок сайта, CMS, движок социальной сети';
		$cfg['metadesc']  = 'InstantCMS - бесплатная система управления сайтом с социальными функциями';
		$cfg['lang'] 	  = 'ru';
		$cfg['sitemail']  = '';
		$cfg['wmark'] 	  = 'watermark.png';
		$cfg['stats'] 	  = 0;
		$cfg['template']  = '_default_';
		$cfg['com_without_name_in_url'] = 'content';
		$cfg['splash'] 	  = 0;
		$cfg['slight'] 	  = 1;
		$cfg['db_host']   = '';
		$cfg['db_base']   = '';
		$cfg['db_user']   = '';
		$cfg['db_pass']   = '';
		$cfg['db_prefix'] = 'cms';
		$cfg['show_pw']   = 1;
		$cfg['short_pw']  = 0;
		$cfg['index_pw']  = 0;
		$cfg['fastcfg']   = 1;
		$cfg['mailer'] 	  = 'mail';
		$cfg['sendmail']  = '/usr/sbin/sendmail';
		$cfg['smtpauth']  = 0;
		$cfg['smtpuser']  = '';
		$cfg['smtppass']  = '';
		$cfg['smtphost']  = 'localhost';
		$cfg['timezone']  = 'Europe/Moscow';
		$cfg['timediff']  = '';
		$cfg['allow_ip']  = '';

        return $cfg;

    }

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Возвращает значение опции конфигурации
     * или полный массив значений
     * @param str $value
     */
    public static function getConfig($value = '') {

		if($value && isset(self::getInstance()->{$value})){
			return self::getInstance()->{$value};
		} else {
			return self::getInstance()->config;
		}

    }

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * Сохраняет массив в файл конфигурации
     * @param array $_CFG
     */
    public static function saveToFile($_CFG, $file='config.inc.php'){

        $filepath = PATH.'/includes/'.$file;

        if (file_exists($filepath)){
            if (!@is_writable($filepath)){ die('Файл <strong>'.$filepath.'</strong> недоступен для записи!'); }
        } else {
            if (!@is_writable(dirname($filepath))){ die('Папка <strong>'.dirname($filepath).'</strong> недоступна для записи!'); }
        }

        $cfg_file = fopen($filepath, 'w+');

        fputs($cfg_file, "<?php \n");
        fputs($cfg_file, "if(!defined('VALID_CMS')) { die('ACCESS DENIED'); } \n");
        fputs($cfg_file, '$_CFG = array();'."\n");

        foreach($_CFG as $key=>$value){
            if (is_int($value)){
                $s = '$_CFG' . "['$key'] \t= $value;\n";
            } else {
                $s = '$_CFG' . "['$key'] \t= '$value';\n";
            }
            fwrite($cfg_file, $s);
        }

        fwrite($cfg_file, "?>");
        fclose($cfg_file);

        return true;

    }

}

?>