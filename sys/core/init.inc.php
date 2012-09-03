<?php

/*
 * Отвечает за загрузку и определение всех необходимых констант,
 * создание объекта базы данных и настройку функции автозагрузки для классов.
 */

/*
 * Включить необходимую конфигурационную информацию
 */ 
include_once '../sys/config/db-cred.inc.php';

/*
 * Определить константы для конфигурационной информации
 */
foreach ( $C as $name => $val )
{
	define($name, $val);
}

/*
 * Создать PDO-объект
 */
$strDSN = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
$objDB = new PDO($strDSN, DB_USER, DB_PASS);

/*
 * Определить для классов функцию автозагрузки
 *
 * Функция автозагрузки вызывается в тех случаях, когда в сценарии 
 * делается попытка создания экземпляра класса, но сам класс к этому 
 * времени еще не был загружен.
 */
function __autoload($strClassName)
{
	$strFileName = "../sys/class/class." . strtolower($strClassName) . ".inc.php";
	if ( file_exists($strFileName) )
	{
		include_once $strFileName;
	}
}
?>