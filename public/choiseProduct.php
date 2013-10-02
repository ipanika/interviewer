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

/*
 * Получаем идентификатор выпускающего предприятия
 * для наложения фильтра на список образцов продукции
 */
$enterpriseId = $_SESSION['edited_interview']['enterprise']['enterprise_id']

?>

<div id="content">

<?php echo $objProductManager->buildProductList($enterpriseId); ?>

<a href="editProduct.php" class="button">Создать новый образец продукции</a>

</div><!-- end #content -->

<?php

/*
 * Включить завершающую часть страницы
 */
include_once 'assets/common/footer.inc.php';
?>