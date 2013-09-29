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

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">

<html>
<head>
  <meta name="generator" content=
  "HTML Tidy for Windows (vers 14 February 2006), see www.w3.org">

  <title></title>
</head>

<body>
  <div id="content">
    <?php echo $objProductManager->buildProductList($enterpriseId); ?>
<a href="editProduct.php"
    class="button">Создать новый образец продукции</a>
  </div><!-- end #content -->
  <?php

/*
 * Включить завершающую часть страницы
 */
include_once 'assets/common/footer.inc.php';
?>
</body>
</html>
