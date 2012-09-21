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

$objInterview = new Interview($objDB)

?>

<div id="content">

<?php echo $objInterview->displayInterviewForm(); ?>

</div><!-- end #content -->

<?php

/*
 * Вывести завершающую часть страницы
 */
include_once 'assets/common/footer.inc.php';

?>