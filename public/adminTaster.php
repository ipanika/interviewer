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

<div id="content">

<?php echo $objManager->displayTasterForm(); ?>

</div><!-- end #content -->

<?php

/*
 * Вывести завершающую часть страницы
 */
include_once 'assets/common/footer.inc.php';

?>