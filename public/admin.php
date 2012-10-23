﻿<?php

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

/*
 * Панель администратора
 */
$objInterview = new Interview($objDB);
?>

<div id="content">
	<form action="assets/inc/process.inc.php" method="post">
		<fieldset>
		<label>Текущий опрос: <?php echo $objInterview->displayCurInterview()?></label>
		<?php /*если текущий опрос - треугольник*/?>
		<div>
		<input type="submit" name="admin_logout" 
			value="Выход"/>
		<input type="hidden" name="token" value="<?php echo $_SESSION['token'];?>"/>
		<input type="hidden" name="action" value="user_logout" />
		<div>
		</fieldset>
	</form>
	<a href="editInterview.php" class="button">Создать новый дегустационный лист</a>
	<a href="changeCurInterview.php" class="button">Сменить текущий дегустационный лист</a>
	<a href="report.php" class="button">Отчеты по проведенным опросам</a>
	<a href="groupList.php" class="button">Список групп кондитерских изделий</a>
</div><!-- end #content -->

<?php

/*
 * Включить завершающую часть страницы
 */
include_once 'assets/common/footer.inc.php';
?>