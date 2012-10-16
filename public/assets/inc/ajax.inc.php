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
		'edit_question' => array(
			'object' => 'QuestionManager',
			'method' => 'displayQuestionForm'
		),
		'question_edit' => array(
			'object' => 'QuestionManager',
			'method' => 'processQuestionForm'
		),
		'view_question' => array(
			'object' => 'QuestionManager',
			'method' => 'displayQuestion'
		)
	);

/*
 * Убедиться в том, что маркер защиты от CSRF был передан и что 
 * запрошенное действие существует в поисковом массиве
 */
if ( isset($arrActions[$_POST['action']]) )
{
	$useAction = $arrActions[$_POST['action']];
	$obj = new $useAction['object']();
	
	echo $obj->$useAction['method']();
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