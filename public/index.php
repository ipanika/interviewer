<?php

/*
 * Включить необходимые файлы, выполнить инициализацию приложения
 */
include_once '../sys/core/init.inc.php';

/*
 * Загрузить объект стартовой страницы
 */
$objNav = new Navigator($objDB);

if ( is_object($objNav) )
{
	echo "<pre>", var_dump($objNav), "</pre>";
}


/*
 * Задать название страницы и файлы CSS
 */
$strPageTitle = "";
$arrCSSFiles = array();


/*
 * Включить начальную часть страницы
 */
include_once 'assets/common/header.inc.php';
?>

<div id="content">
<?php

/*
 * Отобразить объекты
 */ 
 
?>

</div><!-- end #content -->
<?php

/*
 * Включить завершающую часть страницы
 */
include_once 'assets/common/footer.inc.php';
