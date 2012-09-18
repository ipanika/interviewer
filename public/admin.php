<?php

/*
 * Включить необходимые файлы, выполнить инициализацию приложения
 */
include_once '../sys/core/init.inc.php';

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
?>

<div id="content">
	<form action="assets/inc/process.inc.php" method="post">
		<fieldset>
		<label>Текущий опрос: <?php?></label>
		<?php /*если текущий опрос - треугольник*/?>
		<input type="submit" name="admin_logout" 
			value="Выход"/>
		</fieldset>
	</form>
	<form action="editInterview.php" method="post">
		<input type="submit" name="edit_interview" 
			value="Создать/изменить дегустационный лист"/>
	</form>	
	<form action="changeInterview.php" method="post">
		<input type="submit" name="change_current_interview" 
			value="Сменить текущий дегустационный лист"/>
	</form>
	<form action="report.php" method="post">
		<input type="submit" name="report_submit" 
			value="Отчеты по проведенным опросам"/>
	</form>
</div><!-- end #content -->

<?php

/*
 * Включить завершающую часть страницы
 */
include_once 'assets/common/footer.inc.php';
?>