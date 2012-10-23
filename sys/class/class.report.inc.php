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
		
		/*
		 * Определить тип опроса
		 */
		$interviewType = $this->getInterviewType($interviewId);
		
		switch($interviewType)
		{
			case M_PROFIL:
				return $this->buildProfilReport($interviewId);
				break;
			case M_COMPLX:
				return $this->buildComplxReport($interviewId);
				break;
			case M_CONSUM:
				break;
			case M_TRIANG:
				break;
		}
	}
	
	/**
	 * Формирует таблицу отчет по опросу сформированному по комплексному методу
	 *
	 * @param int: идентификатор опроса в базе данных
	 * @return string: HTML-разметка таблицы отчета
	 */
	private function buildComplxReport($interviewId)
	{
		$strQuery = "SELECT 
						`answers`.`interview_product_id` AS `product_id`,
						`products`.`product_name`,
						`answers`.`responseOption_id`,
						COUNT(*) AS amount_taster,
						`responseoptions`.`responseOption_num`,
						`questions`.`question_id`,
						`questions`.`question_text`,
						`questions`.`question_rate`,
						GROUP_CONCAT(`answers`.`comment` SEPARATOR '<br>') AS `comment`
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
				$strReport = $this->_getComplxReport($arrResults);
			}
			else 
			{
				$strReport = "По данному опросу нет данных";
			}
									
			return $strReport;
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
		}
	}
	
	
	/**
	 * Возвращает тип опроса идентификатор которого передан
	 * в качестве параметра
	 *
	 * @param int: уникальный идентификатор опроса
	 * @return mixed: тип опроса или NULL если в базе данных
	 * 				не найден опрос с переданным идентификотором
	 */
	private function getInterviewType($interviewId)
	{
		$strQuery = "SELECT
						`interviews`.`interview_type`
					FROM `interviews`
					WHERE `interviews`.`interview_id` = $interviewId
					LIMIT 1";
		
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			
			if ( isset($arrResults[0]) )
			{
				return $arrResults[0]['interview_type'];
			}
			else 
			{
				return NULL;
			}
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
		}
	}
	
	/**
	 * Возвращает HTML-таблицу отчета о проведенном опросе
	 *
	 * @param array: массив содержащий результат запроса к базе данных
	 * @return string: HTML-таблица
	 */
	private function _getComplxReport($arrRes)
	{
		/*
		 * Разбить исходный массив результатов на строки, содержащие 
		 * результаты по конкретному показателю для конкретного образца
		 */
		
		/*
		 * Сгруппировать все данные по образцам продукции
		 */
		$arrProducts = array();
		$curProductId = 0;
		foreach($arrRes as $elem)
		{
			if ($curProductId != $elem['product_id'])
			{
				$curProductId = $elem['product_id'];
				$arrProducts[$curProductId] = array();
				$arrProducts[$curProductId]['product_name'] = $elem['product_name'];
				//вариант ответа для данного продукта
				$responseOption = array(
					'responseOption_id' => $elem['responseOption_id'],
					'amount_taster' => $elem['amount_taster'],
					'responseOption_num' => $elem['responseOption_num'],
					'question_id' => $elem['question_id'],
					'question_text' => $elem['question_text'],
					'question_rate' => $elem['question_rate'],
					'comment' => $elem['comment']
				);
				$arrProducts[$curProductId]['responseOptions'] = array();
				$arrProducts[$curProductId]['responseOptions'][] = $responseOption;
			}
			else
			{
				//вариант ответа для данного продукта
				$responseOption = array(
					'responseOption_id' => $elem['responseOption_id'],
					'amount_taster' => $elem['amount_taster'],
					'responseOption_num' => $elem['responseOption_num'],
					'question_id' => $elem['question_id'],
					'question_text' => $elem['question_text'],
					'question_rate' => $elem['question_rate'],
					'comment' => $elem['comment']
				);
				$arrProducts[$curProductId]['responseOptions'][] = $responseOption;
			}
		}
		
		/*
		 * Добавить строки таблицы для каждого образца
		 */
		$strTab = "";
		foreach($arrProducts as $arrProduct)
		{
			$strTab .= $this->_printComplxRowForProduct($arrProduct);
		}
		
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
				<td rowspan="2">Комментарии участников</td>
			</tr>
			<tr>
				<th>1.0</th><th>2.0</th><th>3.0</th><th>4.0</th><th>5.0</th><th>6.0</th><th>7.0</th>
			</tr>
			$strTab
		</table>
REP;
	}
	
	/**
	 * Выводит строку таблицы полностью описывающюю данный продукт
	 */
	private function _printComplxRowForProduct($arrProduct)
	{
		/*
		 * Группируем данные по вопросам к данному образцу
		 */
		$arrQuestions = array();
		$curQuestionId = 0;
		$j = 1;
		$i = 0;
		foreach($arrProduct['responseOptions'] as $option)
		{
			if ( $curQuestionId != $option['question_id'] )
			{
				$i++;
				$curQuestionId = $option['question_id'];
				$arrQuestions[$i] = array();
				$arrQuestions[$i]['question_text'] = $option['question_text'];
				$arrQuestions[$i]['question_rate'] = $option['question_rate'];
				$arrQuestions[$i]['question_num'] = $j++;
				$arrQuestions[$i]['scores'] = array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0);
				$arrQuestions[$i]['comment'] = $option['comment'];
				//количество участников 
				$arrQuestions[$i]['numOfTasters'] = $option['amount_taster'];
				//количество участников давших текущую оценку
				$arrQuestions[$i]['scores'][$option['responseOption_num']] = $option['amount_taster'];
			}
			else
			{
				//количество участников 
				$arrQuestions[$i]['numOfTasters'] += $option['amount_taster'];
				//количество участников давших текущую оценку
				$arrQuestions[$i]['scores'][$option['responseOption_num']] = $option['amount_taster'];
				$arrQuestions[$i]['comment'] .= '<br>' . $option['comment'];
			}
		}
		
		/*
		 * Формируем строки начиная со второй, чтобы подсчитать суммарную оценку образца 
		 */
		$overallRating = 0;
		$i = 0;
		$strLastRows = "";
		foreach ($arrQuestions as $question)
		{
			if ( $i != 0 )
			{
				// Подсчитать среднюю оценку по показателю
				$average = 0;
				for ($j = 0; $j < NUM_OF_OPTIONS; $j++)
				{
					$average += ($j+1) * $question['scores'][($j+1)];
				}
				$average = round($average / $question['numOfTasters'], 2);
				$question['average'] = $average;
				//подсчитываем оценку с учетом веса
				$question['averToRate'] = $average * $question['question_rate'];
				$overallRating += $average * $question['question_rate'];
				//выводим данный вопрос
				$strLastRows .= $this->_printComplxRow($question);
			}
			$i++;
		}
		
		//выводим первую строку
		$question = $arrQuestions[1];
		$question['question_num'] = 1;
		// Подсчитать среднюю оценку по показателю
		$average = 0;
		for ($j = 0; $j < NUM_OF_OPTIONS; $j++)
		{
			$average += ($j+1) * $question['scores'][($j+1)];
		}
		$average = round($average / $question['numOfTasters'], 2);
		$question['average'] = $average;
		//подсчитываем оценку с учетом веса
		$question['averToRate'] = $average * $question['question_rate'];
		$overallRating += $average * $question['question_rate'];
		//выводим 1 вопрос, название образца и суммарный балл
		$strFirstRow = $this->_printComplxFirstRow($arrProduct['product_name'], $question, $i, $overallRating);
		
		return $strFirstRow . $strLastRows;
	}
	
	/**
	 * Возвращает строку HTML-таблицы
	 *
	 * @param array: массив данных для заполнения полей строки
	 * @return string: HTML-строка
	 */
	private function _printComplxRow($arrRow)
	{
		$scores = $arrRow['scores'];
		$percentOfMin = round(100 * ($scores[1] + $scores[2] + $scores[3]) / $arrRow['numOfTasters'], 2);
		$percentOfMax = round(100 * ($scores[5] + $scores[6] + $scores[7]) / $arrRow['numOfTasters'], 2);
		$percentOfOver5 = round(100 * ($scores[6] + $scores[7]) / $arrRow['numOfTasters'], 2);
		return <<<PRODUCT_RES
			<tr align="center">
				<td>$arrRow[question_num]</td>
				<td>$arrRow[question_text]</td>
				<td>$arrRow[question_rate]</td>
				<td>$scores[1]</td>
				<td>$scores[2]</td>
				<td>$scores[3]</td>
				<td>$scores[4]</td>
				<td>$scores[5]</td>
				<td>$scores[6]</td>
				<td>$scores[7]</td>
				<td>$arrRow[numOfTasters]</td>
				<td>$arrRow[average]</td>
				<td>$arrRow[averToRate]</td>
				<td>$percentOfMin</td>
				<td>$percentOfMax</td>
				<td>$percentOfOver5</td>
				<td>$arrRow[comment]</td>
			</tr>
PRODUCT_RES;
	}
	
	/**
	 * Возвращает строку HTML-таблицы
	 *
	 * @param array: массив данных для заполнения полей строки
	 * @return string: HTML-строка
	 */
	private function _printComplxFirstRow($strProductName, $arrRow, $numQuest, $overallRating)
	{
		$scores = $arrRow['scores'];
		$percentOfMin = round(100 * ($scores[1] + $scores[2] + $scores[3]) / $arrRow['numOfTasters'], 2);
		$percentOfMax = round(100 * ($scores[5] + $scores[6] + $scores[7]) / $arrRow['numOfTasters'], 2);
		$percentOfOver5 = round(100 * ($scores[6] + $scores[7]) / $arrRow['numOfTasters'], 2);
		return <<<PRODUCT_RES
			<tr align="center">
				<td rowspan="$numQuest">$strProductName</td>
				<td>$arrRow[question_num]</td>
				<td>$arrRow[question_text]</td>
				<td>$arrRow[question_rate]</td>
				<td>$scores[1]</td>
				<td>$scores[2]</td>
				<td>$scores[3]</td>
				<td>$scores[4]</td>
				<td>$scores[5]</td>
				<td>$scores[6]</td>
				<td>$scores[7]</td>
				<td>$arrRow[numOfTasters]</td>
				<td>$arrRow[average]</td>
				<td>$arrRow[averToRate]</td>
				<td rowspan="$numQuest">$overallRating</td>
				<td>$percentOfMin</td>
				<td>$percentOfMax</td>
				<td>$percentOfOver5</td>
				<td>$arrRow[comment]</td>
			</tr>
PRODUCT_RES;
	}

	/**
	 * Формирует таблицу отчет по опросу сформированному по комплексному методу
	 *
	 * @param int: идентификатор опроса в базе данных
	 * @return string: HTML-разметка таблицы отчета
	 */
	private function buildProfilReport($interviewId)
	{
		$strQuery = "SELECT 
						`answers`.`interview_product_id` AS `product_id`,
						`products`.`product_name`,
						`answers`.`responseOption_id`,
						COUNT(*) AS amount_taster,
						`responseoptions`.`responseOption_num`,
						`questions`.`question_id`,
						`questions`.`question_text`,
						`questions`.`question_rate`,
						GROUP_CONCAT(`answers`.`comment` SEPARATOR '<br>') AS `comment`
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
				$strReport = $this->_getProfilReport($arrResults);
			}
			else 
			{
				$strReport = "По данному опросу нет данных";
			}
									
			return $strReport;
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
		}
	}
	
	/**
	 * Возвращает HTML-таблицу отчета о проведенном опросе
	 *
	 * @param array: массив содержащий результат запроса к базе данных
	 * @return string: HTML-таблица
	 */
	private function _getProfilReport($arrRes)
	{
		/*
		 * Разбить исходный массив результатов на строки, содержащие 
		 * результаты по конкретному показателю для конкретного образца
		 */
		
		/*
		 * Сгруппировать все данные по образцам продукции
		 */
		$arrProducts = array();
		$curProductId = 0;
		foreach($arrRes as $elem)
		{
			if ($curProductId != $elem['product_id'])
			{
				$curProductId = $elem['product_id'];
				$arrProducts[$curProductId] = array();
				$arrProducts[$curProductId]['product_name'] = $elem['product_name'];
				//вариант ответа для данного продукта
				$responseOption = array(
					'responseOption_id' => $elem['responseOption_id'],
					'amount_taster' => $elem['amount_taster'],
					'responseOption_num' => $elem['responseOption_num'],
					'question_id' => $elem['question_id'],
					'question_text' => $elem['question_text'],
					'question_rate' => $elem['question_rate'],
					'comment' => $elem['comment']
				);
				$arrProducts[$curProductId]['responseOptions'] = array();
				$arrProducts[$curProductId]['responseOptions'][] = $responseOption;
			}
			else
			{
				//вариант ответа для данного продукта
				$responseOption = array(
					'responseOption_id' => $elem['responseOption_id'],
					'amount_taster' => $elem['amount_taster'],
					'responseOption_num' => $elem['responseOption_num'],
					'question_id' => $elem['question_id'],
					'question_text' => $elem['question_text'],
					'question_rate' => $elem['question_rate'],
					'comment' => $elem['comment']
				);
				$arrProducts[$curProductId]['responseOptions'][] = $responseOption;
			}
		}
		
		/*
		 * Добавить строки таблицы для каждого образца
		 */
		$strTab = "";
		foreach($arrProducts as $arrProduct)
		{
			$strTab .= $this->_printProfilRowForProduct($arrProduct);
		}
		
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
				<td rowspan="2">Комментарии участников</td>
			</tr>
			<tr>
				<th>1.0</th><th>2.0</th><th>3.0</th><th>4.0</th><th>5.0</th><th>6.0</th><th>7.0</th>
			</tr>
			$strTab
		</table>
REP;
	}
	
	/**
	 * Выводит строку таблицы полностью описывающюю данный продукт
	 */
	private function _printProfilRowForProduct($arrProduct)
	{
		/*
		 * Группируем данные по вопросам к данному образцу
		 */
		$arrQuestions = array();
		$curQuestionId = 0;
		$j = 1;
		$i = 0;
		foreach($arrProduct['responseOptions'] as $option)
		{
			if ( $curQuestionId != $option['question_id'] )
			{
				$i++;
				$curQuestionId = $option['question_id'];
				$arrQuestions[$i] = array();
				$arrQuestions[$i]['question_text'] = $option['question_text'];
				$arrQuestions[$i]['question_rate'] = $option['question_rate'];
				$arrQuestions[$i]['question_num'] = $j++;
				$arrQuestions[$i]['scores'] = array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0);
				$arrQuestions[$i]['comment'] = $option['comment'];
				//количество участников 
				$arrQuestions[$i]['numOfTasters'] = $option['amount_taster'];
				//количество участников давших текущую оценку
				$arrQuestions[$i]['scores'][$option['responseOption_num']] = $option['amount_taster'];
			}
			else
			{
				//количество участников 
				$arrQuestions[$i]['numOfTasters'] += $option['amount_taster'];
				//количество участников давших текущую оценку
				$arrQuestions[$i]['scores'][$option['responseOption_num']] = $option['amount_taster'];
				$arrQuestions[$i]['comment'] .= '<br>' . $option['comment'];
			}
		}
		
		/*
		 * Формируем строки начиная со второй, чтобы подсчитать суммарную оценку образца 
		 */
		$overallRating = 0;
		$i = 0;
		$strLastRows = "";
		foreach ($arrQuestions as $question)
		{
			if ( $i != 0 )
			{
				// Подсчитать среднюю оценку по показателю
				$average = 0;
				for ($j = 0; $j < NUM_OF_OPTIONS; $j++)
				{
					$average += ($j+1) * $question['scores'][($j+1)];
				}
				$average = round($average / $question['numOfTasters'], 2);
				$question['average'] = $average;
				//подсчитываем оценку с учетом веса
				$question['averToRate'] = $average * $question['question_rate'];
				$overallRating += $average * $question['question_rate'];
				//выводим данный вопрос
				$strLastRows .= $this->_printProfilRow($question);
			}
			$i++;
		}
		
		//выводим первую строку
		$question = $arrQuestions[1];
		$question['question_num'] = 1;
		// Подсчитать среднюю оценку по показателю
		$average = 0;
		for ($j = 0; $j < NUM_OF_OPTIONS; $j++)
		{
			$average += ($j+1) * $question['scores'][($j+1)];
		}
		$average = round($average / $question['numOfTasters'], 2);
		$question['average'] = $average;
		//подсчитываем оценку с учетом веса
		$question['averToRate'] = $average * $question['question_rate'];
		$overallRating += $average * $question['question_rate'];
		//выводим 1 вопрос, название образца и суммарный балл
		$strFirstRow = $this->_printProfilFirstRow($arrProduct['product_name'], $question, $i, $overallRating);
		
		return $strFirstRow . $strLastRows;
	}
	
	/**
	 * Возвращает строку HTML-таблицы
	 *
	 * @param array: массив данных для заполнения полей строки
	 * @return string: HTML-строка
	 */
	private function _printProfilRow($arrRow)
	{
		$scores = $arrRow['scores'];
		return <<<PRODUCT_RES
			<tr align="center">
				<td>$arrRow[question_num]</td>
				<td>$arrRow[question_text]</td>
				<td>$arrRow[question_rate]</td>
				<td>$scores[1]</td>
				<td>$scores[2]</td>
				<td>$scores[3]</td>
				<td>$scores[4]</td>
				<td>$scores[5]</td>
				<td>$scores[6]</td>
				<td>$scores[7]</td>
				<td>$arrRow[numOfTasters]</td>
				<td>$arrRow[average]</td>
				<td>$arrRow[comment]</td>
			</tr>
PRODUCT_RES;
	}
	
	/**
	 * Возвращает строку HTML-таблицы
	 *
	 * @param array: массив данных для заполнения полей строки
	 * @return string: HTML-строка
	 */
	private function _printProfilFirstRow($strProductName, $arrRow, $numQuest, $overallRating)
	{
		$scores = $arrRow['scores'];
		return <<<PRODUCT_RES
			<tr align="center">
				<td rowspan="$numQuest">$strProductName</td>
				<td>$arrRow[question_num]</td>
				<td>$arrRow[question_text]</td>
				<td>$arrRow[question_rate]</td>
				<td>$scores[1]</td>
				<td>$scores[2]</td>
				<td>$scores[3]</td>
				<td>$scores[4]</td>
				<td>$scores[5]</td>
				<td>$scores[6]</td>
				<td>$scores[7]</td>
				<td>$arrRow[numOfTasters]</td>
				<td>$arrRow[average]</td>
				<td>$arrRow[comment]</td>
			</tr>
PRODUCT_RES;
	}
}
 
?>