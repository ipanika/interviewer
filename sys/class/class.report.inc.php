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
		$arrInterview = $this->getInterview($interviewId);
		
		$interviewType = $arrInterview['interview_type'];
		$strInterviewName = $arrInterview['interview_name'];
		$strInterviewDate = $arrInterview['interview_date'];
		$EnterpriseId = $arrInterview['enterprise_id'];
		$objEnterpriseManager = new EnterpriseManager($this->_objDB);
		$strEnterpriseName = $objEnterpriseManager->getEnterpriseById($EnterpriseId)->name;
		/*
		 * Задать шапку отчета
		 */
		$strHeader ="<label>Отчет по дегустационному листу: $strInterviewName от $strInterviewDate</label>
					<label>Выпускающее предприятие: $strEnterpriseName</label><br>";
		
		/*
		 * Получить список участников дегустации
		 */
		$arrTasterList = $this->_getTasterList($interviewId);
		// построить HTML список участников
		$strTasterList = "<label>Участники дегустации:</label><ol>";
		foreach ($arrTasterList as $arrTaster)
		{
			$strTasterList .= "<li>$arrTaster[taster_surname] $arrTaster[taster_name]</li>";
		}
		$strTasterList .= "</ol>";
		
		/*
		 * Добавить форму для экспорта отчета в формате Excel
		 */
		$strExportForm =<<<EXPORTFORM
			<a href="download.php" class="button">Экспортировать отчет в Excel</a>
</form>
EXPORTFORM;
		
		switch($interviewType)
		{
			case M_PROFIL:
				$strReport = $this->buildProfilReport($interviewId);
				break;
			case M_COMPLX:
				$strReport = $this->buildComplxReport($interviewId);
				break;
			case M_CONSUM:
				$strReport = $this->buildConsumReport($interviewId);
				break;
			case M_TRIANG:
				$strReport = $this->buildTriangReport($interviewId);
				break;
		}
		
		if ($strReport == "По данному опросу нет данных")
		{
			return $strHeader . $strReport ;
		}
		
		return $strHeader . $strReport . $strTasterList . $strExportForm;
	}
	
	
	/**
	 * Формирует таблицу отчет по потребительскому опросу
	 *
	 * @param int: идентификатор опроса в базе данных
	 * @return string: HTML-разметка таблицы отчета
	 */
	private function buildConsumReport($interviewId)
	{
		$strQuery = "SELECT
							`answers`.`question_id`,
							`question_type`,
							`question_text`,
							`answers`.`responseOption_id`,
							`answers`.`interview_product_id` AS `product_id`,
							`responseOption_num`,
							`product_name`,
							GROUP_CONCAT(
							(CASE 
								WHEN NOT `answers`.`comment` = '' THEN CONCAT(`answers`.`comment`, '-',
									`tasters`.`taster_surname`, ' ',
									`tasters`.`taster_name`)
								END
							)
							SEPARATOR '<br>') AS `comments`,
							COUNT(*) AS `amount_taster`
					FROM `answers`
						LEFT JOIN `questions` 
							ON `answers`.`question_id` = `questions`.`question_id`
						LEFT JOIN `interview_product` 
							ON `interview_product`.`interview_product_id` = `answers`.`interview_product_id`
						LEFT JOIN `products` 
							ON `products`.`product_id` = `interview_product`.`product_id`
						LEFT JOIN `responseoptions` 
							ON `responseoptions`.`responseoption_id` = `answers`.`responseoption_id`
						LEFT JOIN `tasters`
							ON `tasters`.`taster_id` = `answers`.`taster_id`
					WHERE 
							`interview_product`.`interview_id` = $interviewId
					GROUP BY 
							`answers`.`interview_product_id`, `answers`.`question_id`, `answers`.`responseoption_id`";
		
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
						
			if ( isset($arrResults[0]) )
			{
				$strReport = $this->_getConsumReport($arrResults);
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
	private function _getConsumReport($arrRes)
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
				//вариант ответа для данного продукта согласно типу вопроса
				if ($elem['question_type'] == Q_CLOSE)
				{
					$arrProducts[$curProductId]['product_name'] = $elem['product_name'];
					$responseOption = array(
						'responseOption_id' => $elem['responseOption_id'],
						'amount_taster' => $elem['amount_taster'],
						'responseOption_num' => $elem['responseOption_num'],
						'question_id' => $elem['question_id'],
						'question_text' => $elem['question_text'],
						'comment' => $elem['comments']
					);
					$arrProducts[$curProductId]['responseOptions'] = array();
					$arrProducts[$curProductId]['responseOptions'][] = $responseOption;
				}
				else
				{
					$arrProducts[$curProductId]['product_name'] = $elem['product_name'];
					$responseOption = array(
						'responseOption_id' => $elem['responseOption_id'],
						'amount_taster' => $elem['amount_taster'],
						'question_id' => $elem['question_id'],
						'question_text' => $elem['question_text'],
						'answers' => $elem['comments']
					);
					$arrProducts[$curProductId]['responseOptions'] = array();
					$arrProducts[$curProductId]['responseOptions'][] = $responseOption;
				}
			}
			else
			{
				//вариант ответа для данного продукта
				if ($elem['question_type'] == 0)
				{
					$responseOption = array(
						'responseOption_id' => $elem['responseOption_id'],
						'amount_taster' => $elem['amount_taster'],
						'responseOption_num' => $elem['responseOption_num'],
						'question_id' => $elem['question_id'],
						'question_text' => $elem['question_text'],
						'comment' => $elem['comments']
					);
					$arrProducts[$curProductId]['responseOptions'][] = $responseOption;
				}
				else
				{
					$responseOption = array(
						'responseOption_id' => $elem['responseOption_id'],
						'amount_taster' => $elem['amount_taster'],
						'question_id' => $elem['question_id'],
						'question_text' => $elem['question_text'],
						'answers' => $elem['comments']
					);
					$arrProducts[$curProductId]['responseOptions'][] = $responseOption;
				}
			}
		}
		
		
		/*
		 * Добавить строки таблицы для каждого образца
		 */
		$strTab = "";
		foreach($arrProducts as $arrProduct)
		{
			$strTab .= $this->_printConsumRowForProduct($arrProduct);
			
		}
		
		return <<<REP
		<table width="100%" border="1" cellpadding="4" cellspacing="0">
			<tr>
				<td rowspan="2">Наименование продукта</td>
				<td rowspan="2">Номер вопроса</td>
				<td rowspan="2">Текст вопроса</td>
				<th colspan="7">Количество участников, поставивших оценки:</th>
				<th colspan="7">Доля участников, поставивших оценки:</th>
				<td rowspan="2">Итого, участников</td>
				<td rowspan="2">Средний балл</td>
				<td rowspan="2">Все ответы, которые дали участники</td>
				<td rowspan="2">Комментарии</td>
			</tr>
			<tr>
				<th>1.0</th><th>2.0</th><th>3.0</th><th>4.0</th><th>5.0</th><th>6.0</th><th>7.0</th>
				<th>1.0</th><th>2.0</th><th>3.0</th><th>4.0</th><th>5.0</th><th>6.0</th><th>7.0</th>
			</tr>
			$strTab
		</table>
REP;
	}
	
	/**
	 * Выводит строку таблицы полностью описывающюю данный продукт
	 */
	private function _printConsumRowForProduct($arrProduct)
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
				$arrQuestions[$i]['question_num'] = $j++;
				$arrQuestions[$i]['scores'] = array(1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0);
				//количество участников 
				$arrQuestions[$i]['numOfTasters'] = $option['amount_taster'];
				//ответы на открытые вопросы
				if ( isset($option['answers']) )
				{
					$arrQuestions[$i]['answers'] = $option['answers'];
				}
				//количество участников давших текущую оценку
				else
				{
					$arrQuestions[$i]['scores'][$option['responseOption_num']] = $option['amount_taster'];
					$arrQuestions[$i]['comment'] .= $option['comment'];
				}
			}
			else
			{
				//количество участников 
				$arrQuestions[$i]['numOfTasters'] += $option['amount_taster'];
				//ответы на открытые вопросы
				if ( isset($option['answers']) )
				{
					$arrQuestions[$i]['answers'] = $option['answers'];
				}
				//количество участников давших текущую оценку
				else
				{
					$arrQuestions[$i]['scores'][$option['responseOption_num']] = $option['amount_taster'];
					$arrQuestions[$i]['comment'] .= '<br>' . $option['comment'];
				}
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
						//Подсчитать долю участников
						$question['proportion'][($j+1)] = 100*round($question['scores'][($j+1)] / $question['numOfTasters'], 2);
					}
					$average = round($average / $question['numOfTasters'], 2);
					$question['average'] = $average;
					//выводим данный вопрос
					$strLastRows .= $this->_printConsumRow($question);
					//echo htmlentities($this->_printConsumRow($question));
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
			//Подсчитать долю участников
			$question['proportion'][($j+1)] = 100*round($question['scores'][($j+1)] / $question['numOfTasters'], 2);
		}
		$average = round($average / $question['numOfTasters'], 2);
		$question['average'] = $average;
		
		//выводим 1 вопрос, название образца и суммарный балл
		$strFirstRow = $this->_printConsumFirstRow($arrProduct['product_name'], $question, $i, $average);
		
		return $strFirstRow . $strLastRows;
	}
	
	/**
	 * Возвращает строку HTML-таблицы
	 *
	 * @param array: массив данных для заполнения полей строки
	 * @return string: HTML-строка
	 */
	private function _printConsumRow($arrRow)
	{
	
		$scores = $arrRow['scores'];
		$proportion = $arrRow['proportion'];
		return <<<PRODUCT_RES
			<tr align="center">
				<td>$arrRow[question_num]</td>
				<td>$arrRow[question_text]</td>
				<td>$scores[1]</td>
				<td>$scores[2]</td>
				<td>$scores[3]</td>
				<td>$scores[4]</td>
				<td>$scores[5]</td>
				<td>$scores[6]</td>
				<td>$scores[7]</td>
				<td>$proportion[1]%</td>
				<td>$proportion[2]%</td>
				<td>$proportion[3]%</td>
				<td>$proportion[4]%</td>
				<td>$proportion[5]%</td>
				<td>$proportion[6]%</td>
				<td>$proportion[7]%</td>
				<td>$arrRow[numOfTasters]</td>
				<td>$arrRow[average]</td>
				<td>$arrRow[answers]</td>
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
	private function _printConsumFirstRow($strProductName, $arrRow, $numQuest, $overallRating)
	{
		$scores = $arrRow['scores'];
		$proportion = $arrRow['proportion'];
		return <<<PRODUCT_RES
			<tr align="center">
				<td rowspan="$numQuest">$strProductName</td>
				<td>$arrRow[question_num]</td>
				<td>$arrRow[question_text]</td>
				<td>$scores[1]</td>
				<td>$scores[2]</td>
				<td>$scores[3]</td>
				<td>$scores[4]</td>
				<td>$scores[5]</td>
				<td>$scores[6]</td>
				<td>$scores[7]</td>
				<td>$proportion[1]%</td>
				<td>$proportion[2]%</td>
				<td>$proportion[3]%</td>
				<td>$proportion[4]%</td>
				<td>$proportion[5]%</td>
				<td>$proportion[6]%</td>
				<td>$proportion[7]%</td>
				<td>$arrRow[numOfTasters]</td>
				<td>$arrRow[average]</td>
				<td>$arrRow[answers]</td>
				<td>$arrRow[comment]</td>
			</tr>
PRODUCT_RES;
	}

	
	
	/**
	 * Формирует таблицу отчет по опросу сформированному по методу треугольника
	 *
	 * @param int: идентификатор опроса в базе данных
	 * @return string: HTML-разметка таблицы отчета
	 */
	private function buildTriangReport($interviewId)
	{
		$strQuery = "SELECT
						COUNT(*) AS `count_value`,
						`t_prod`.`product_id`,
						`products`.`product_name`,
						GROUP_CONCAT(
							(CASE
								WHEN `trianganswers`.`comment` IS NOT NULL
									THEN CONCAT(`trianganswers`.`comment`, '-',`tasters`.`taster_surname`, ' ',`tasters`.`taster_name`)
								END
							)
							SEPARATOR '<br>') AS `comment`
					FROM
						(SELECT
							`product_id`,
							`interview_product_id` AS `t_prod_id`
						FROM `interview_product`
						WHERE `interview_id` = $interviewId
						) AS `t_prod`
					LEFT JOIN `products`
						ON `products`.`product_id` = `t_prod`.`product_id`
					LEFT JOIN `trianganswers`
						ON `trianganswers`.`product_id` = `t_prod`.`product_id`
					LEFT JOIN `tasters`
						ON `tasters`.`taster_id` = `trianganswers`.`taster_id`
					WHERE `trianganswers`.`interview_id` = $interviewId

					GROUP BY `t_prod`.`product_id`
					ORDER BY `t_prod`.`t_prod_id`";
	
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			
			if ( !isset($arrResults[0]) )
			{
				return "По данному опросу нет данных";
			}
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
		}
		
		$strProductNameA = $arrResults[0]['product_name'];
		$strProductNameB = $arrResults[1]['product_name'];
		$productAValue = $arrResults[0]['count_value'];
		$productBValue = $arrResults[1]['count_value'];
		$numTasters = $arrResults[0]['count_value'] + $arrResults[1]['count_value'];
		$comments = $arrResults[0]['comment'] . '<br>' . $arrResults[1]['comment'];
		
		$numCorrectAnswersA = "";
		$numCorrectAnswersB = "";
		
		/*
		 * Вычислить минимальное количество правильных ответов, для принятия решения \
		 * об идентичности образцов
		 */
		/* 
		 * Формула для вычисления взята из стандарта BS ISO 4120-2004
		 */
		$z = 3.09;
		$numCorrectAnswersB = (int)($numTasters/3 + $z * sqrt(2*$numTasters/9) + 0.5 );
		
		return <<<TRIANG
		<table width="100%" border="1" cellpadding="4" cellspacing="0">
			<tr align="center">
				<td>Количество участников</td>
				<td>Наименование образца</td>
				<td>Количество оценок образца</td>
				<td>Число требуемых правильных ответов при вероятности 0.001</td>
				<td>Комментарии участников</td>
			</tr>
			<tr  align="center">
				<td rowspan="2">$numTasters</td>
				<td>Образец A: $strProductNameA</td>
				<td>$productAValue</td>
				<td>$numCorrectAnswersA</td>
				<td rowspan="2">$comments</td>
			</tr>
			<tr  align="center">
				<td>Образец B: $strProductNameB</td>
				<td>$productBValue</td>
				<td>$numCorrectAnswersB</td>
			</tr>
		</table>
TRIANG;
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
						GROUP_CONCAT(
							(CASE 
								WHEN `answers`.`comment` IS NOT NULL THEN CONCAT(`answers`.`comment`, '-',
									`tasters`.`taster_surname`, ' ',
									`tasters`.`taster_name`)
								END
							)
							SEPARATOR '<br>') AS `comment`
					FROM `answers`
					LEFT JOIN `interview_product` 
						ON `interview_product`.`interview_product_id` = `answers`.`interview_product_id`
					LEFT JOIN `products`
						ON `products`.`product_id` = `interview_product`.`product_id`
					LEFT JOIN `responseoptions` 
						ON `responseoptions`.`responseOption_id` = `answers`.`responseOption_id`
					LEFT JOIN `questions`
						ON `questions`.`question_id` = `responseoptions`.`question_id`
					LEFT JOIN `tasters`
						ON `tasters`.`taster_id` = `answers`.`taster_id`
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
	private function getInterview($interviewId)
	{
		$strQuery = "SELECT
						`interviews`.`interview_type`,
						`interviews`.`interview_name`,
						`interviews`.`interview_date`,
						`interviews`.`enterprise_id`
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
				return $arrResults[0];
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
						
						GROUP_CONCAT(
							(CASE 
								WHEN `answers`.`comment` IS NOT NULL
									THEN CONCAT(`answers`.`comment`, '-',
									`tasters`.`taster_surname`, ' ',
									`tasters`.`taster_name`)
								END
							)
							SEPARATOR '<br>') AS `comment`
					FROM `answers`
					LEFT JOIN `interview_product` 
						ON `interview_product`.`interview_product_id` = `answers`.`interview_product_id`
					LEFT JOIN `products`
						ON `products`.`product_id` = `interview_product`.`product_id`
					LEFT JOIN `responseoptions` 
						ON `responseoptions`.`responseOption_id` = `answers`.`responseOption_id`
					LEFT JOIN `questions`
						ON `questions`.`question_id` = `responseoptions`.`question_id`
					LEFT JOIN `tasters`
						ON `tasters`.`taster_id` = `answers`.`taster_id`
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
	
	/**
	 * Возвращает список учасников дегустации
	 *
	 * @param int: идентификатор опроса
	 * @return array: HTML-строка
	 */
	private function _getTasterList($interviewId)
	{
		$arrInterview = $this->getInterview($interviewId);
		
		/*
		 * Подсчитать число участников дегустации
		 */
		// в записимости от типа опроса искать в разных таблицах
		if ($arrInterview['interview_type'] == M_TRIANG )
		{
			$strQuery = "SELECT
							`tasters`.`taster_name`,
							`tasters`.`taster_surname`
						FROM `trianganswers` 
						LEFT JOIN `tasters`
							ON `tasters`.`taster_id` = `trianganswers`.`taster_id`
						WHERE `interview_id` = $interviewId
						GROUP BY `trianganswers`.`taster_id`"; 
			
		}
		else
		{
			$strQuery = "SELECT
							`tasters`.`taster_name`,
							`tasters`.`taster_surname`
						FROM `answers`
						LEFT JOIN `tasters` 
							ON `tasters`.`taster_id` = `answers`.`taster_id`
						LEFT JOIN `interview_product`
							ON `answers`.`interview_product_id` = `interview_product`.`interview_product_id`
						WHERE `interview_product`.`interview_id` = $interviewId
						GROUP BY `answers`.`taster_id`";
		}
		
		
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			
			return $arrResults;
			
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
		}
		
	}
}
 
?>