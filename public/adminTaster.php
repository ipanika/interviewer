<?php

/**
 * Включить необходимые файлы
 */
include_once '../sys/core/init.inc.php';

/**
 * Вывести начальную часть страницы
 */
$strPageTitle = "";
$arrCSSFiles = array('style.css', 'admin.css');
include_once 'assets/common/header.inc.php';

/**
 * Загрузить менеджер дегустаторов
 */
$objManager = new TasterManager($objDB);

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
    <?php echo $objManager->displayTasterForm(); ?>

  </div><!-- end #content -->
  <?php

/*
 * Вывести завершающую часть страницы
 */
include_once 'assets/common/footer.inc.php';

?>
</body>
</html>
