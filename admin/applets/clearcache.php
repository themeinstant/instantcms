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

function applet_clearcache() {
  $inCore = cmsCore::getInstance();
  
  //check access
  global $adminAccess;
  if (!cmsUser::isAdminCan('admin/config', $adminAccess)) { cpAccessDenied(); }
  
  $GLOBALS['cp_page_title'] = 'Очистка системного кеша';
  
  cpAddPathway('Настройки сайта', 'index.php?view=config');	
  cpAddPathway('Очистка кеша', 'index.php?view=clearcache');
  
  $inCore->clearSmartyCache();
?>
<div>
  <h3>Очистка кеша (Smarty)</h3>
  <p>Кеш успешно очищен.</p>
</div>
<?php } ?>