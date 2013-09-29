<?php

include_once '../sys/core/init.inc.php';

/** Include PHPExcel */
require_once '../sys/class/PHPExcel/PHPExcel.php';

$objExportReport = new ExportReport($objDB);

$objPHPExcel = new PHPExcel();
$objSheet = $objPHPExcel->setActiveSheetIndex(0);

$arrResults = $objExportReport->getCurrentProducts();

$objSheet->setCellValue('A1', "Порядок следования образцов:");
	for ($i = 0; $i < count($arrResults); $i++)
		$objSheet->setCellValueByColumnAndRow(0, $i+2, $arrResults[$i]['product_name']);

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="report.xls"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
$objWriter->save('php://output');
exit;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 3.2//EN">

<html>
<head>
  <meta name="generator" content=
  "HTML Tidy for Windows (vers 14 February 2006), see www.w3.org">

  <title></title>
</head>

<body>
</body>
</html>
