<?php

/*
 * Включить необходимые файлы, выполнить инициализацию приложения
 */
include_once '../sys/core/init.inc.php';

/*
 * Перенаправить незарегистрированного пользователя на основную страницу
 */
if (!isset($_SESSION['user']) )
{
	header("Location: ./");
	exit();
}

/*
 * Задать название страницы и файлы CSS
 */
$strPageTitle = "";
$arrCSSFiles = array('style.css', 'admin.css');

/*
 * Включить начальную часть страницы
 */
include_once 'assets/common/header.inc.php';

$objProductManager = new ProductManager();

?>

<div id="content">

<?php echo $objProductManager->displayProductForm(); ?>

</div><!-- end #content -->

<?php

/*
 * Включить завершающую часть страницы
 */
include_once 'assets/common/footer.inc.php';
?>