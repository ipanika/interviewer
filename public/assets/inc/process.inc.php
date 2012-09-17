<?php

/*
 * Запуск сеанса
 */
session_start();

/*
 * Включить необходимые файлы
 */
include_once '../../../sys/config/db-cred.inc.php';

/*
 * Определить константы для конфигурационной информации
 */
foreach ( $C as $name => $val )
{
	define($name, $val);
}

/*
 * Создать поисковый массив для действий, выполняемых над формой
 */
$arrActions = array(
		'taster_edit' => array(
			'object'=> 'TasterManager',
			'method'=> 'processTasterForm',
			'header'=> 'Location: ../../'
		),
		'start_interview' => array(
			'object' => 'Interview',
			'method' => 'processStartInterview',
			'header' => 'Location: ../../interview.php'
		),
		'write_cluster' => array(
			'object' => 'Interview',
			'method' => 'processClusterForm',
			'header' => 'Location: ../../interview.php'
		)
	);

/*
 * Убедиться в том, что маркер защиты от CSRF был передан и что 
 * запрошенное действие существует в поисковом массиве
 */
if ( $_POST['token'] == $_SESSION['token'] 
		&& isset($arrActions[$_POST['action']]) )
{
	$useAction = $arrActions[$_POST['action']];
	$obj = new $useAction['object']();
	if ( TRUE === $msg=$obj->$useAction['method']() )
	{
		header($useAction['header']);
		exit;
	}
	else
	{
		//в случае ошибки вывести сообщение о ней и прекратить выполнение
		die ( $msg );
	}
}
else
{
	// В случае некорректности маркера/действия перенаправить
	// пользователя на основную страницу
	header("Location: ../../");
	exit;
}

function __autoLoad($strClassName)
{
	$strFileName = '../../../sys/class/class.'
			.strtolower($strClassName).'.inc.php';
	if ( file_exists($strFileName) )
	{
		include_once $strFileName;
	}
}

?>