<?php

/*
 * Включить необходимые файлы, выполнить инициализацию приложения
 */
include_once '../sys/core/init.inc.php';

/*
 * Задать название страницы и файлы CSS
 */
$strPageTitle = "Пожалуйста, зарегистрируйтесь";
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
		<legend>Пожалуйста, зарегистрируйтесь</legend>
		<label for="uname">Имя пользователя:</label>
		<input type="text" name="uname"
			id="uname" value=""/>
		<label for="pword">Пароль: </label>
		<input type="password" name="pword" 
			id="pword" value=""/>
		<input type="hidden" name="token"
			value="<?php echo $_SESSION['token'];?>" />
		<input type="hidden" name="action" 
			value="user_login" />
		<input type="submit" name="login_submit"
			value="Вход" />
		или <a href="./" class="admin">отменить</a>
		</fieldset>
	</form>
</div><!-- end #content -->

<?php

/*
 * Включить завершающую часть страницы
 */
include_once 'assets/common/footer.inc.php';
?>