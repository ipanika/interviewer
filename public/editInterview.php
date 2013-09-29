<?php

/**
 * Включить необходимые файлы
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

/**
 * Вывести начальную часть страницы
 */
$strPageTitle = "";
$arrCSSFiles = array('style.css', 'admin.css', 'ajax.css', 'jquery-ui-1.9.0.custom.css');
include_once 'assets/common/header.inc.php';

$objInterview = new Interview($objDB)

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
    <?php echo $objInterview->displayInterviewForm(); ?>

  </div><!-- end #content -->
  <?php

/*
 * Вывести завершающую часть страницы
 */
include_once 'assets/common/footer.inc.php';

?>
</body>
</html>
