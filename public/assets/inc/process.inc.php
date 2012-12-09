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
		'user_login' => array(
			'object'=> 'Admin',
			'method'=> 'processLoginForm',
			'header'=> 'Location: ../../admin.php'
		),
		'user_logout' => array(
			'object'=> 'Admin',
			'method'=> 'processLogout',
			'header'=> 'Location: ../../'
		),
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
		),
		'new_cluster' => array(
			'object' => 'Interview',
			'method' => 'processNewClusterForm',
			'header' => 'Location: ../../editInterview.php'
		), 
		'new_cluster_name' => array(
			'object' => 'Interview',
			'method' => 'processNewClusterNameForm',
			'header' => 'Location: ../../editInterview.php'
		), 
		'question_edit' => array(
			'object' => 'QuestionManager',
			'method' => 'processQuestionForm',
			'header' => 'Location: ../../editInterview.php'
		), 
		'new_interview' => array(
			'object' => 'Interview',
			'method' => 'processInterviewName',
			'header' => 'Location: ../../editInterview.php'
		),
		'product_edit' => array(
			'object'=> 'ProductManager',
			'method'=> 'processProductForm',
			'header'=> 'Location: ../../choiseProduct.php'
		),
		'product_choise' => array(
			'object'=> 'Interview',
			'method'=> 'processChoiseProduct',
			'header'=> 'Location: ../../editInterview.php'
		),
		'choice_cluster' => array(
			'object' => 'Interview',
			'method' => 'processChoiceCluster',
			'header' => 'Location: ../../editInterview.php'
		),
		'cancel_edit' => array(
			'object' => 'Interview',
			'method' => 'processEndEdit',
			'header' => 'Location: ../../admin.php'
		),
		'write_interview' => array(
			'object' => 'Interview',
			'method' => 'processInterviewForm',
			'header' => 'Location: ../../admin.php'
		),
		'change_cur_interview' => array(
			'object' => 'Interview',
			'method' => 'processChangeCurInterview',
			'header' => 'Location: ../../admin.php'
		),
		'get_report' => array(
			'object' => 'Report',
			'method' => 'processChoiseInterview',
			'header' => 'Location: ../../report.php'
		),
		'productgroup_edit' => array(
			'object' => 'ProductGroupManager',
			'method' => 'processProductGroupForm',
			'header' => 'Location: ../../groupList.php'
		),
		'enterprise_edit' => array(
			'object' => 'EnterpriseManager',
			'method' => 'processEnterpriseForm',
			'header' => 'Location: ../../enterpriseList.php'
		),
		'new_triang_quest' => array(
			'object' => 'Interview',
			'method' => 'processTriangQuestForm',
			'header' => 'Location: ../../editInterview.php'
		),
		'edit_productorder' => array(
			'object' => 'Interview',
			'method' => 'processOrderForm',
			'header' => 'Location: ../../admin.php'
		),
		'interview_delete' => array(
			'object' => 'InterviewManager',
			'method' => 'delInterviews',
			'header' => 'Location: ../../interviewList.php'
		),
		'product_delete' => array(
			'object' => 'ProductManager',
			'method' => 'delProducts',
			'header' => 'Location: ../../productList.php'
		),
		'productgroup_delete' => array(
			'object' => 'ProductGroupManager',
			'method' => 'delProductGroups',
			'header' => 'Location: ../../groupList.php'
		),
		'enterprise_delete' => array(
			'object' => 'EnterpriseManager',
			'method' => 'delEnterprise',
			'header' => 'Location: ../../enterpriseList.php'
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