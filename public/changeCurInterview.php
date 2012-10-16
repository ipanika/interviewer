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

$objInterview = new Interview($objDB);

?>

<div id="content">

<form action="assets/inc/process.inc.php" method="post">
		<fieldset>
			<label>Текущий дегустационный лист: <?php echo $objInterview->displayCurInterview();?></label>
			<?php echo $objInterview->displayInterviewList();?>
			<input type="hidden" name="action" value="change_cur_interview" />
			<input type="hidden" name="token" value="<?php echo $_SESSION['token'];?>" />
			<input type="submit" name="taster_submit" value="Сделать текущим" />
			<a href="admin.php" class="button">Отмена</a>
		</fieldset>
	</form>
	
</div><!-- end #content -->

<?php

/*
 * Включить завершающую часть страницы
 */
include_once 'assets/common/footer.inc.php';
?>