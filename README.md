[ InstantCMS - Community Management System ]
-------------------------------------------------------------------------------

[ Версия:    ] 1.10
[ Дата:      ] 2012-10-29
[ Лицензия:  ] GNU/GPL v2

[ Язык:      ] русский (UTF-8)

[ сайт:      ] www.instantcms.ru

[ требуется: ] apache + mod_rewrite
[            ] php 5.x (+GD, iconv, mbstrings)
[            ] mysql 5

Система распространяется по принципу "КАК ЕСТЬ" и БЕЗ ГАРАНТИЙНЫХ ОБЯЗАТЕЛЬСТВ.
Вы можете свободно использовать и модифицировать систему, но исключительно на
свой страх и риск. Вы обязаны сохранять копирайты в исходном коде.
Подробнее см. в файле license.rus.txt

-------------------------------------------------------------------------------

УСТАНОВКА СИСТЕМЫ:

	1. Распакуйте архив с системой в папку сайта
	2. Создайте базу данных
	3. Запустите скрипт http://yoursite.ru/install
	4. После установки удалите папку "install" и "migrate"

ВНИМАНИЕ:

	При установке на реальный хостинг установите права для записи на директории:

        /cache
		/images
		/includes (только на время установки и только на саму /includes, без вложенных)
		/upload

	и все вложенные в них!
	На файлы .htaccess, находящиеся в этих директориях права должны стоять строго 644

-------------------------------------------------------------------------------


ОБНОВЛЕНИЕ С ВЕРСИИ 1.9:

	1. Сделайте резервную копию сайта и дамп базы данных - ОБЯЗАТЕЛЬНО!!!!
	2. Распакуйте архив в папку с сайтом, заменяя все имеющиеся файлы
	3. Запустите скрипт http://yoursite.ru/migrate
	4. После завершения миграции удалите папки "install" и "migrate"!
