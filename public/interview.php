<?php

/*
 * Включить необходимые файлы, выполнить инициализацию приложения
 */
include_once '../sys/core/init.inc.php';

/*
 * Задать название страницы и файлы CSS
 */
$strPageTitle = "";
$arrCSSFiles = array('style.css', 'admin.css', 'ajax.css', 'jquery-ui-1.9.0.custom.css');

/*
 * Включить начальную часть страницы
 */
include_once 'assets/common/header.inc.php';

/*
 * Проверяем авторизовался ли пользователь
 */
if ( !isset($_SESSION['taster_id']) )
{	
	//отправляем пользователя на главную страницу
	header("Location: ./");
	exit;
}

/*
 * Создать объект для работы с опросом
 */
$objInterview = new Interview($objDB);

?>

<div id="content">
<?php 
//print_r($_SESSION['product_id']);
//print_r($_SESSION['temp_results']);

//переход в опросе к следующему или предыдущему образцу
if ($_SESSION['button'] == "next" or $_SESSION['button'] == "startInterview")
	echo $objInterview->nextCluster();
elseif ($_SESSION['button'] == "previous")
	echo $objInterview->previousCluster();
elseif ($_SESSION['button'] == "finish")
	echo $objInterview->finishInterview();
?>
</div><!-- end #content -->

<?php

/*
 * Включить завершающую часть страницы
 */
include_once 'assets/common/footer.inc.php';
?>