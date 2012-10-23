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
 * Перенаправить незарегистрированного пользователя на основную страницу
 */
if (!isset($_SESSION['user']) )
{
	header("Location: ./");
	exit();
}

/*
 * Создать объект для работы с группами кондитерских изделий
 */
$objGroupManager = new ProductGroupManager($objDB);

?>

<div id="content">
	<?php echo $objGroupManager->buildProductGroupList()?>

	<a href="editProductGroup.php" class="button">Добавить группу кондитерских изделий</a>
	<a href="admin.php" class="button">Назад</a>
</div><!-- end #content -->

<?php

/*
 * Включить завершающую часть страницы
 */
include_once 'assets/common/footer.inc.php';
?>