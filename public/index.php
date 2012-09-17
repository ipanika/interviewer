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

$objTasterManager = new TasterManager();

/*
 * Отобразить стартовую страницу
 */
?>

<div id="content">
	<form action="assets/inc/process.inc.php" method="post">
		<fieldset>
		<label for="uname">Выберите себя из списка:</label>
		<?php echo $objTasterManager->buildTasterList()?>
		<input type="hidden" name="action"
			value="start_interview"/>
		<input type="hidden" name="token"
			value="<?php echo $_SESSION['token'];?>"/>
		<input type="submit" name="start_submit" 
			value="Начать опрос"/>
		<a href="">Войти в систему как администратор</a>
		</fieldset>
	</form>
	<form action="adminTaster.php" method="post">
		<fieldset>
		<label for="user_registry">Если вас нет в списке, заполните иформацию о себе:</label>
		<input type="submit" name="user_registry" value="О себе"/>
		</fieldset>
	</form>
</div><!-- end #content -->

<?php

/*
 * Включить завершающую часть страницы
 */
include_once 'assets/common/footer.inc.php';
?>