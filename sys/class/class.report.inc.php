<?php

/**
 * Управляет формированием и отображением отчетов по проведенным 
 * дегустационным тестированиям
 */
class Report extends DB_Connect
{
	/**
	 * Создает объект базы
	 * 
	 * При создании экземпляра этого класса конструктор принимает
	 * объект базы данных в качестве параметра. Если значение этого 
	 * параметра отлично от null, оно сохраняется в закрытом 
	 * свойстве $_objDB. Если же это значение равно null, то вместо
	 * этого создается и сохраняется новый PDO-объект.
	 *
	 * @param int $type: определяем один из 4-х видов дегустационных листов 
	 * @param object $dbo: объект базы данных
	 * @return void
	 */
	public function __construct($dbo=NULL)
	{
		/**
		 * Вызвать конструктор родительского класса для проверки
		 * существования объекта базы данных
		 */
		parent::__construct($dbo);
	}
	
	/**
	 * Сохраняет в сеансе идентификатор опроса для которого необходимо 
	 * сформировать отчет
	 *
	 * @return mixed: TRUE в случае успешного завершения, иначе 
	 * сообщение об ошибке
	 */
	public function processChoiseInterview()
	{
		/*
		 * Аварийное завершение, если был отправлен недействительный
		 * атрибут ACTION
		 */
		if ($_POST['action'] != 'get_report' )
		{
			return "В processChoiseInterview передано недействительное значение
					атрибута ACTION";
		}
		
		/*
		 * Извлечь из формы идентификатор дегустационного опроса
		 */
		$interviewId = (int)$_POST['interview_id'];
		
		/*
		 * Сохранить идентификатор в сеансе
		 */
		$_SESSION['report']['interview_id'] = $interviewId;
		
		return TRUE;
	}
	
	/**
	 * Отображает форму отчета по проведенному дегустационному опросу,
	 * если идентификатор опроса был сохранен в сеансе
	 *
	 * @return string: HTML-разметка
	 */
	public function displayReport()
	{
		/*
		 * Вернуть пустую строку, если идентификатор опроса не передан
		 */
		if (!isset($_SESSION['report']['interview_id']) )
		{
			return "";
		}
		
		/*
		 * Получить идентификатор опроса
		 */
		$interviewId = $_SESSION['report']['interview_id'];
		
		$strQuery = "SELECT 
						`answers`.`interview_product_id`,
						`products`.`product_name`,
						`answers`.`responseOption_id`,
						COUNT(*) AS amount_taster,
						`responseoptions`.`responseOption_text`,
						`responseoptions`.`responseOption_num`,
						`questions`.`question_id`,
						`questions`.`question_text`,
						`questions`.`question_rate`
					FROM `answers`
					LEFT JOIN `interview_product` 
						ON `interview_product`.`interview_product_id` = `answers`.`interview_product_id`
					LEFT JOIN `products`
						ON `products`.`product_id` = `interview_product`.`product_id`
					LEFT JOIN `responseoptions` 
						ON `responseoptions`.`responseOption_id` = `answers`.`responseOption_id`
					LEFT JOIN `questions`
						ON `questions`.`question_id` = `responseoptions`.`question_id`
					WHERE `interview_product`.`interview_id` = $interviewId
					GROUP BY 
						`answers`.`interview_product_id`, 
						`answers`.`responseOption_id`
					ORDER BY 
						`answers`.`interview_product_id`, 
						`questions`.`question_id`, 
						`responseoptions`.`responseOption_num`";
		
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			
			if ( isset($arrResults[0]) )
			{
				echo "lskdjfalksdjflka";
				$strReport = $this->_getReport($arrResults);
			}
									
			return $strReport;
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
		}
	}
	
	public function getHeader()
	{
		//количество показателей для продукта
		$numOfQuest = NUM_OF_QUESTIONS;
		$strProductResF =<<<PRODUCT_RES_FIRST
			<tr align="center">
				<td rowspan="$numOfQuest">$product_name</td>
				<td>$question_num</td>
				<td>$question_text</td>
				<td>$question_rate</td>
				<td>$scores[1]</td>
				<td>$scores[2]</td>
				<td>$scores[3]</td>
				<td>$scores[4]</td>
				<td>$scores[5]</td>
				<td>$scores[6]</td>
				<td>$scores[7]</td>
				<td>$taster_num</td>
				<td>$average</td>
				<td>$averToRate</td>
				<td  rowspan="$numOfQuest">$overallRating</td>
				<td>$percentOfMin</td>
				<td>$percentOfMax</td>
				<td>$percentOfOver5</td>
			</tr>
PRODUCT_RES_FIRST;

		$strProductRes =<<<PRODUCT_RES
			<tr align="center">
				<td>$question_num</td>
				<td>$question_text</td>
				<td>$question_rate</td>
				<td>$scores[1]</td>
				<td>$scores[2]</td>
				<td>$scores[3]</td>
				<td>$scores[4]</td>
				<td>$scores[5]</td>
				<td>$scores[6]</td>
				<td>$scores[7]</td>
				<td>$taster_num</td>
				<td>$average</td>
				<td>$averToRate</td>
				<td>$percentOfMin</td>
				<td>$percentOfMax</td>
				<td>$percentOfOver5</td>
			</tr>
PRODUCT_RES;
	
	
		$strReport =<<<REP
		<table  width="100%" border="1" cellpadding="4" cellspacing="0">
			<tr>
				<td rowspan="2">Наименование образца</td>
				<td rowspan="2">Номер показателя</td>
				<td rowspan="2">Наименование показателя </td>
				<td rowspan="2">Вес показателя</td>
				<th colspan="7">Количество участников дегустации, поставивших оценки:</th>
				<td rowspan="2">Итого, участников</td>
				<td rowspan="2">Средний балл</td>
				<td rowspan="2">Оценка с учетом весомости</td>
				<td rowspan="2">Общая оценка</td>
				<td rowspan="2">Доля участников, давших минимальные оценки, %(1, 2, 3)</td>
				<td rowspan="2">Доля участников, давших максимальные оценки, % (5, 6, 7)</td>
				<td rowspan="2">Доля участников, давших оценки свыше 5 баллов, % (6, 7)</td>
			</tr>
			<tr>
				<th>1.0</th><th>2.0</th><th>3.0</th><th>4.0</th><th>5.0</th><th>6.0</th><th>7.0</th>
			</tr>
			$strTab
		</table>
REP;
	}
	
	/**
	 * Возвращает HTML-таблицу отчета о проведенном опросе
	 *
	 * @param array: массив содержащий результат запроса к базе данных
	 * @return string: HTML-таблица
	 */
	private function _getReport($arrRes)
	{
		return <<<REP
		<table width="100%" border="1" cellpadding="4" cellspacing="0">
			<tr>
				<td rowspan="2">Наименование образца</td>
				<td rowspan="2">Номер показателя</td>
				<td rowspan="2">Наименование показателя </td>
				<td rowspan="2">Вес показателя</td>
				<th colspan="7">Количество участников дегустации, поставивших оценки:</th>
				<td rowspan="2">Итого, участников</td>
				<td rowspan="2">Средний балл</td>
				<td rowspan="2">Оценка с учетом весомости</td>
				<td rowspan="2">Общая оценка</td>
				<td rowspan="2">Доля участников, давших минимальные оценки, %(1, 2, 3)</td>
				<td rowspan="2">Доля участников, давших максимальные оценки, % (5, 6, 7)</td>
				<td rowspan="2">Доля участников, давших оценки свыше 5 баллов, % (6, 7)</td>
			</tr>
			<tr>
				<th>1.0</th><th>2.0</th><th>3.0</th><th>4.0</th><th>5.0</th><th>6.0</th><th>7.0</th>
			</tr>
			$strTab
		</table>
REP;
	}
}
 
?>