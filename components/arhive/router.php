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

    function routes_arhive(){

        $routes[] = array(
                            '_uri'  => '/^arhive\/([0-9]+)\/([0-9]+)\/([0-9]+)$/i',
                            1       => 'y',
                            2       => 'm',
                            3       => 'd'
                         );

        $routes[] = array(
                            '_uri'  => '/^arhive\/([0-9]+)\/([0-9]+)$/i',
                            1       => 'y',
                            2       => 'm'
                         );

        $routes[] = array(
                            '_uri'  => '/^arhive\/([0-9]+)$/i',
                            1       => 'y'
                         );

        return $routes;

    }

?>
