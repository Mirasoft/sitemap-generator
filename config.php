<?php
$config['cookie'] = true; // использовать куки
$config['useragents'] = array(
	'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2236.0 Safari/537.36 OPR/28.0.1719.0 (Edition developer)',
	'Opera/9.80 (X11; Linux x86_64) Presto/2.12.388 Version/12.16',
	'Mozilla/5.0 (X11; Linux x86_64; rv:25.0) Gecko/20100101 Firefox/25.0 FirePHP/0.7.4'
);
$config['timeout'] = 2; // время ожидания

$config['nesting'] = 3; //максимальный уровень вложености, 0 - не ограничено
$config['limit'] = 0; // максимальное количество ссылок, 0 - не ограничено

$config['sort'] = false; // сортировать по уровню вложености
$config['gzip'] = false; // Gzip сжатие
$config['formatOutput'] = false; // Форматирование разметки выходного файла

$config['changefreq'] = false; // устанавливать частоту обновления
$config['modification'] = false; // устанавливать время последней модификации
$config['priority'] = false; // устанавливать приоритет страниц
?>
