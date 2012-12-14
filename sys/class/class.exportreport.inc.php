<?php

/** Include PHPExcel */
require_once 'PHPExcel/PHPExcel.php';

define("HEADERH_HEIGHT", 2);

/**
 * Управляет формированием и отображением отчетов по проведенным 
 * дегустационным тестированиям
 */
class ExportReport extends DB_Connect
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
	 * Формирует отчет в формате Excel и отправляет файл пользователю,
	 * если идентификатор опроса был сохранен в сеансе
	 *
	 * @return object: Объект класс PHPExcel содержащий сформированный отчет
	 */
	public function getReport()
	{
		/*
		 * Вернуть пустую строку, если идентификатор опроса не передан
		 */
		if (!isset($_SESSION['report']['interview_id']) )
		{
			return TRUE;
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
		 * Получить список участников дегустации
		 */
		$arrTasterList = $this->_getTasterList($interviewId);
		
		switch($interviewType)
		{
			case M_PROFIL:
				$objPHPExcel = $this->exportProfilReport($interviewId, $arrTasterList);
				break;
			case M_COMPLX:
				$objPHPExcel = $this->buildComplxReport($interviewId, $arrTasterList);
				break;
			case M_CONSUM:
				return $strHeader . $this->buildConsumReport($interviewId). $strTasterList;
				break;
			case M_TRIANG:
				$objPHPExcel = $this->buildTriangReport($interviewId, $arrTasterList);
				break;
		}
		
		return $objPHPExcel;
	}
	
	
	/**
	 * Формирует таблицу-отчет по потребительскому опросу
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
							GROUP_CONCAT(`comment`) AS `comments`,
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
	private function buildTriangReport($interviewId, $arrTasterList)
	{
		$strQuery = "SELECT
						COUNT(`trianganswers`.`product_id`) AS `count_value`,
						`products`.`product_name`,
						GROUP_CONCAT(`trianganswers`.`comment` SEPARATOR '\n\r') AS `comment`
					FROM `interview_product`
					LEFT JOIN `products`
						ON `products`.`product_id` = `interview_product`.`product_id`
					LEFT JOIN `trianganswers`
						ON `trianganswers`.`product_id` = `interview_product`.`product_id` AND 
								`trianganswers`.`interview_id` = `interview_product`.`interview_id`
					WHERE `interview_product`.`interview_id` = $interviewId 
					GROUP BY `trianganswers`.`product_id`";
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
		$comments = $arrResults[0]['comment'] . $arrResults[1]['comment'];
		
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
		
		
		/*
		 * Создать объект класса PHPExcel
		 */
		$objPHPExcel = new PHPExcel();
		
		/*
		 * Напечатать шапку отчета
		 * Шапка занимает первые две строки 
		 */
		$this->_printHeader($interviewId, $objPHPExcel);
		
		
		$objSheet = $objPHPExcel->setActiveSheetIndex(0);
		
		// Установить ширину столбцов
		$objSheet->getColumnDimension('A')->setWidth(12);
		$objSheet->getColumnDimension('B')->setWidth(22);
		$objSheet->getColumnDimension('C')->setWidth(21);
		$objSheet->getColumnDimension('D')->setWidth(20);
		$objSheet->getColumnDimension('E')->setWidth(30);
		
		// Добавить заколовки столбцов
		$objSheet->setCellValueByColumnAndRow(0, 4, 'Количество участников');
		$objSheet->getStyleByColumnAndRow(0, 4)->getAlignment()->setWrapText(true);
		$objSheet->setCellValueByColumnAndRow(1, 4, 'Наименование образца');
		$objSheet->getStyleByColumnAndRow(1, 4)->getAlignment()->setWrapText(true);
		$objSheet->setCellValueByColumnAndRow(2, 4, 'Количество оценок образца');
		$objSheet->getStyleByColumnAndRow(2, 4)->getAlignment()->setWrapText(true);
		$objSheet->setCellValueByColumnAndRow(3, 4, 'Число требуемых правильных ответов при вероятности 0.001');
		$objSheet->getStyleByColumnAndRow(3, 4)->getAlignment()->setWrapText(true);
		$objSheet->setCellValueByColumnAndRow(4, 4, 'Комментарии участников');
		$objSheet->getStyleByColumnAndRow(4, 4)->getAlignment()->setWrapText(true);
		
		// Добавить данные
		// Количество участников
		$objSheet->setCellValueByColumnAndRow(0, 5, $numTasters);
		// Объединить первую и вторую строки столбца
		$objSheet->mergeCellsByColumnAndRow( 0, 5, 0, 6);
		// Наименование образцов
		$objSheet->setCellValueByColumnAndRow(1, 5, 'Образец A: '.$strProductNameA);
		$objSheet->getStyleByColumnAndRow(1, 5)->getAlignment()->setWrapText(true);
		$objSheet->setCellValueByColumnAndRow(1, 6, 'Образец B: '.$strProductNameB);
		$objSheet->getStyleByColumnAndRow(1, 6)->getAlignment()->setWrapText(true);
		// Количество оценок образца
		$objSheet->getCellByColumnAndRow(2, 5)->setValueExplicit($productAValue, PHPExcel_Cell_DataType::TYPE_NUMERIC);
		$objSheet->getCellByColumnAndRow(2, 6)->setValueExplicit($productBValue, PHPExcel_Cell_DataType::TYPE_NUMERIC);
		// Число требуемых правильных ответов
		$objSheet->setCellValueByColumnAndRow(3, 6, $numCorrectAnswersB);
		// Комментарии участников
		$objSheet->setCellValueByColumnAndRow(4, 5, $comments);
		$objSheet->mergeCellsByColumnAndRow( 4, 5, 4, 6);
		
		/*
		 * Применить стиль ко всем ячейкам документа
		 */
		$styleArray = array(
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
			),
			'borders' => array(
				'allborders' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN,
				),
			),
		);
		$objPHPExcel->setActiveSheetIndex(0)->getStyle('A4:E6')->applyFromArray($styleArray);
		
		// добавить список участников дегустации
		$lastRow = 5;
		$this->_printTasterList($objPHPExcel, $arrTasterList, $lastRow);
		
		return $objPHPExcel;
	}
	
	/**
	 *
	 */
	private function _printHeader($interviewId, $objPHPExcel)
	{
		$arrInterview = $this->getInterview($interviewId);
		
		$strInterviewName = $arrInterview['interview_name'];
		$strInterviewDate = $arrInterview['interview_date'];
		$EnterpriseId = $arrInterview['enterprise_id'];
		$objEnterpriseManager = new EnterpriseManager($this->_objDB);
		$strEnterpriseName = $objEnterpriseManager->getEnterpriseById($EnterpriseId)->name;
		
		$objSheet = $objPHPExcel->setActiveSheetIndex(0);
		// Напечатать заголовок
		$objSheet->setCellValue('A1', "Отчет по дегустационному листу: $strInterviewName от $strInterviewDate");
		$objSheet->mergeCells( 'A1:E1');
		$objSheet->setCellValue('A2', "Выпускающее предприятие: $strEnterpriseName");
		$objSheet->mergeCells( 'A2:E2');
	}
	
	/**
	 * Добавляет список участников дегустации начиная со строки, номер которой
	 * передан 3 параметром
	 */
	private function _printTasterList($objPHPExcel, $arrTasterList, $lastRow)
	{
		$lastRow += 2;
		
		$objSheet = $objPHPExcel->setActiveSheetIndex(0);
		// Напечатать заголовок
		$objSheet->setCellValueByColumnAndRow(1, $lastRow, 'Участники дегустации:');
		$objSheet->mergeCellsByColumnAndRow( 1, $lastRow, 2, $lastRow);
		$num = 1;
		foreach($arrTasterList as $arrTaster)
		{
			$lastRow++;
			$objSheet->setCellValueByColumnAndRow(1, $lastRow, $num . '. ' . $arrTaster['taster_surname'].' '.$arrTaster['taster_name']);
			$objSheet->mergeCellsByColumnAndRow( 1, $lastRow, 2, $lastRow);
			$num++;
		}
	}
	
	/**
	 * Формирует таблицу отчет по опросу сформированному по комплексному методу
	 *
	 * @param int: идентификатор опроса в базе данных
	 * @return mixed: FALSE в случае отсутствия данных по переданному опросу, объект 
	 *	PHPExcel содержащий таблицу отчет.
	 */
	private function buildComplxReport($interviewId, $arrTasterList)
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
						CASE 
							WHEN `answers`.`comment` IS NOT NULL THEN CONCAT(`answers`.`comment`, '-',
									`tasters`.`taster_surname`, ' ',
									`tasters`.`taster_name`)
						END AS cm,
						
						GROUP_CONCAT(
							(CASE 
								WHEN NOT `answers`.`comment` = '' THEN CONCAT(`answers`.`comment`, '-',
									`tasters`.`taster_surname`, ' ',
									`tasters`.`taster_name`)
								END
							)
							SEPARATOR '\n\r') AS `comment`
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
				$objPHPExcel = $this->_getComplxReport($arrResults, $arrTasterList, $interviewId);
			}
			else 
			{
				return FALSE;
			}
									
			return $objPHPExcel;
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
	 * Возвращает таблицу отчета о проведенном опросе
	 *
	 * @param array: массив содержащий результат запроса к базе данных
	 * @return object: объект PHPExcel содержащий таблицу отчета
	 */
	private function _getComplxReport($arrRes, $arrTasterList, $interviewId)
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
		 * Создать объект класса PHPExcel
		 */
		$objPHPExcel = new PHPExcel();
		
		
		/*
		 * Напечатать шапку отчета
		 */
		$this->_printHeader($interviewId, $objPHPExcel);
		
		/*
		 * Вывести шапку таблицы отчета
		 */
		$this->_printComplxHeader($objPHPExcel);
		
		/*
		 * Последняя заполненая строка - равна 2 + 3
		 */
		$lastRow = 5;
		
		/*
		 * Вывести строки по каждому образцу 
		 */
		$productNum = 0;
		foreach ($arrProducts as $arrProduct)
		{
			$lastRow += $this->_printComplxRowForProduct($objPHPExcel, $arrProduct, $productNum);
			$productNum++;
		}
		
		/*
		 * Применить стиль ко всем ячейкам таблицы
		 */
		$styleArray = array(
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
			),
			'borders' => array(
				'allborders' => array(
					'style' => PHPExcel_Style_Border::BORDER_THIN,
				),
			),
		);
		$objPHPExcel->setActiveSheetIndex(0)->getStyle('A4:S'.$lastRow)->applyFromArray($styleArray);
		
		/*
		 * Распечатать список участников дегустации
		 */
		$this->_printTasterList($objPHPExcel, $arrTasterList, $lastRow);
		
		/*
		 * Вернуть сформированный документ
		 */ 
		return $objPHPExcel;
	}
	
	/**
	 * Выводит шапку таблицы для отчета по комплексному опросу
	 *
	 * @param object: объект класса PHPExcel
	 */
	private function _printComplxHeader($objPHPExcel)
	{
		// Установить свойства формируемого документа
		$objPHPExcel->getProperties()->setCreator("АС ДКИ")
									 ->setLastModifiedBy("АС ДКИ");
									 
		$objSheet = $objPHPExcel->setActiveSheetIndex(0);
		// Задать ширину столбцов
		$objSheet->getColumnDimension('A')->setWidth(15);
		$objSheet->getStyle('A')->getAlignment()->setWrapText(true);
		$objSheet->getColumnDimension('B')->setWidth(11);
		$objSheet->getStyle('B')->getAlignment()->setWrapText(true);
		$objSheet->getColumnDimension('C')->setWidth(30);
		$objSheet->getStyle('C')->getAlignment()->setWrapText(true);
		$objSheet->getColumnDimension('D')->setWidth(11);
		$objSheet->getStyle('D')->getAlignment()->setWrapText(true);
		$objSheet->getColumnDimension('L')->setWidth(15);
		$objSheet->getStyle('L')->getAlignment()->setWrapText(true);
		$objSheet->getColumnDimension('M')->setWidth(15);
		$objSheet->getStyle('M')->getAlignment()->setWrapText(true);
		$objSheet->getColumnDimension('N')->setWidth(15);
		$objSheet->getStyle('N')->getAlignment()->setWrapText(true);
		$objSheet->getColumnDimension('O')->setWidth(15);
		$objSheet->getStyle('O')->getAlignment()->setWrapText(true);
		$objSheet->getColumnDimension('P')->setWidth(20);
		$objSheet->getStyle('P')->getAlignment()->setWrapText(true);
		$objSheet->getColumnDimension('Q')->setWidth(20);
		$objSheet->getStyle('Q')->getAlignment()->setWrapText(true);
		$objSheet->getColumnDimension('R')->setWidth(20);
		$objSheet->getStyle('R')->getAlignment()->setWrapText(true);
		$objSheet->getColumnDimension('S')->setWidth(30);
		$objSheet->getStyle('S')->getAlignment()->setWrapText(true);
		 
		// Добавить заколовки столбцов
		$objPHPExcel->setActiveSheetIndex(0)
           ->setCellValueByColumnAndRow(0, 4, 'Наименование образца')
			->setCellValueByColumnAndRow(1, 4, 'Номер показателя')
			->setCellValueByColumnAndRow(2, 4, 'Наименование показателя')
			->setCellValueByColumnAndRow(3, 4, 'Вес показателя')
			// Объединить следующие 7 ячеек
			->setCellValueByColumnAndRow(4, 4, 'Количество участников дегустации, поставивших оценки:')
			->mergeCellsByColumnAndRow( 4, 4, 10, 4)
			->setCellValueByColumnAndRow(4, 5, '1.0')
			->setCellValueByColumnAndRow(5, 5, '2.0')
			->setCellValueByColumnAndRow(6, 5, '3.0')
			->setCellValueByColumnAndRow(7, 5, '4.0')
			->setCellValueByColumnAndRow(8, 5, '5.0')
			->setCellValueByColumnAndRow(9, 5, '6.0')
			->setCellValueByColumnAndRow(10, 5, '7.0')
			->setCellValueByColumnAndRow(11, 4, 'Итого, участников')
			->setCellValueByColumnAndRow(12, 4, 'Средний бал')
			->setCellValueByColumnAndRow(13, 4, 'Оценка с учетом весомости')
			->setCellValueByColumnAndRow(14, 4, 'Общая оценка')
			->setCellValueByColumnAndRow(15, 4, 'Доля участников давших минимальные оценки, %(1, 2, 3)')
			->setCellValueByColumnAndRow(16, 4, 'Доля участников давших максимальные оценки, %(5, 6, 7)')
			->setCellValueByColumnAndRow(17, 4, 'Доля участников давших оценки свыше 5 баллов, %(6, 7)')
			->setCellValueByColumnAndRow(18, 4, 'Комментарии участников');
		
		// объединить ячейки первой и второй строки
		for ($i = 0; $i < 19; $i++)
		{
			if ( $i>=4 && $i<=10 )
			{
				continue;
			}
			$objPHPExcel->setActiveSheetIndex(0)->mergeCellsByColumnAndRow( $i, 4, $i, 5);
		}
	}
	
	/**
	 * Выводит строку таблицы полностью описывающюю данный продукт
	 */
	private function _printComplxRowForProduct($objPHPExcel, $arrProduct, $productNum)
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
		// количество строк - 1
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
				$this->_printComplxRow($objPHPExcel, $question, $productNum, $i+1, count($arrQuestions));
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
		$this->_printComplxFirstRow($objPHPExcel,$arrProduct['product_name'], $question, $productNum, $i, $overallRating);
		
		// вернуть количество добавленых строк
		return $i;
	}
	
	/**
	 * Возвращает строку HTML-таблицы
	 *
	 * @param object: объект PHPExcel
	 * @param array: массив данных для заполнения полей строки
	 * @param int: номер образца
	 * @param int: номер вопроса
	 * @return 
	 */
	private function _printComplxRow($objPHPExcel, $arrRow, $productNum, $questNum, $amountQuest)
	{
		$scores = $arrRow['scores'];
		$percentOfMin = round(100 * ($scores[1] + $scores[2] + $scores[3]) / $arrRow['numOfTasters'], 2);
		$percentOfMax = round(100 * ($scores[5] + $scores[6] + $scores[7]) / $arrRow['numOfTasters'], 2);
		$percentOfOver5 = round(100 * ($scores[6] + $scores[7]) / $arrRow['numOfTasters'], 2);
		
		/*
		 * Вычислить номер строки в которую необходимо записать данные
		 */
		$rowNum = $amountQuest * $productNum + $questNum + HEADERH_HEIGHT + 3;
		
		$objPHPExcel->setActiveSheetIndex(0)
			// номер показателя (вопроса)
			->setCellValueByColumnAndRow(1, $rowNum, $arrRow['question_num'])
			// наименование показателя
			->setCellValueByColumnAndRow(2, $rowNum, $arrRow['question_text'])
			// вес показателя
			->setCellValueByColumnAndRow(3, $rowNum, $arrRow['question_rate'])
			// количество участников поставивших оценки
			->setCellValueByColumnAndRow(4, $rowNum, $scores[1])
			->setCellValueByColumnAndRow(5, $rowNum, $scores[2])
			->setCellValueByColumnAndRow(6, $rowNum, $scores[3])
			->setCellValueByColumnAndRow(7, $rowNum, $scores[4])
			->setCellValueByColumnAndRow(8, $rowNum, $scores[5])
			->setCellValueByColumnAndRow(9, $rowNum, $scores[6])
			->setCellValueByColumnAndRow(10, $rowNum, $scores[7])
			// итого, участников
			->setCellValueByColumnAndRow(11, $rowNum, $arrRow['numOfTasters'])
			// средний бал
			->setCellValueByColumnAndRow(12, $rowNum, $arrRow['average'])
			// оценка с учетом весомости
			->setCellValueByColumnAndRow(13, $rowNum, $arrRow['averToRate'])
			// доля участников давших минимальные оценки
			->setCellValueByColumnAndRow(15, $rowNum, $percentOfMin)
			// доля участников давших максимальные оценки
			->setCellValueByColumnAndRow(16, $rowNum, $percentOfMax)
			// доля участников давших оценки свыше 5 баллов
			->setCellValueByColumnAndRow(17, $rowNum, $percentOfOver5)
			// коментарии участников
			->setCellValueByColumnAndRow(18, $rowNum, $arrRow['comment']);
			
	}
	
	/**
	 * Возвращает строку HTML-таблицы
	 *
	 * @param array: массив данных для заполнения полей строки
	 * @return string: HTML-строка
	 */
	private function _printComplxFirstRow($objPHPExcel, $strProductName, $arrRow, $productNum, $amountQuest, $overallRating)
	{
		$scores = $arrRow['scores'];
		$percentOfMin = round(100 * ($scores[1] + $scores[2] + $scores[3]) / $arrRow['numOfTasters'], 2);
		$percentOfMax = round(100 * ($scores[5] + $scores[6] + $scores[7]) / $arrRow['numOfTasters'], 2);
		$percentOfOver5 = round(100 * ($scores[6] + $scores[7]) / $arrRow['numOfTasters'], 2);
		
		/*
		 * Вычислить номер строки в которую необходимо записать данные
		 */
		$rowNum = $amountQuest * $productNum + 1 + HEADERH_HEIGHT + 3;
		
		$objPHPExcel->setActiveSheetIndex(0)
			// название образца
			->setCellValueByColumnAndRow(0, $rowNum, $strProductName)
			// объединить ячейки для названия образца
			->mergeCellsByColumnAndRow( 0, $rowNum, 0, $rowNum+$amountQuest-1)
			// номер показателя (вопроса)
			->setCellValueByColumnAndRow(1, $rowNum, $arrRow['question_num'])
			// наименование показателя
			->setCellValueByColumnAndRow(2, $rowNum, $arrRow['question_text'])
			// вес показателя
			->setCellValueByColumnAndRow(3, $rowNum, $arrRow['question_rate'])
			// количество участников поставивших оценки
			->setCellValueByColumnAndRow(4, $rowNum, $scores[1])
			->setCellValueByColumnAndRow(5, $rowNum, $scores[2])
			->setCellValueByColumnAndRow(6, $rowNum, $scores[3])
			->setCellValueByColumnAndRow(7, $rowNum, $scores[4])
			->setCellValueByColumnAndRow(8, $rowNum, $scores[5])
			->setCellValueByColumnAndRow(9, $rowNum, $scores[6])
			->setCellValueByColumnAndRow(10, $rowNum, $scores[7])
			// итого, участников
			->setCellValueByColumnAndRow(11, $rowNum, $arrRow['numOfTasters'])
			// средний бал
			->setCellValueByColumnAndRow(12, $rowNum, $arrRow['average'])
			// оценка с учетом весомости
			->setCellValueByColumnAndRow(13, $rowNum, $arrRow['averToRate'])
			// общий балл
			->setCellValueByColumnAndRow(14, $rowNum, $overallRating)
			->mergeCellsByColumnAndRow( 14, $rowNum, 14, $rowNum+$amountQuest-1)
			// доля участников давших минимальные оценки
			->setCellValueByColumnAndRow(15, $rowNum, $percentOfMin)
			// доля участников давших максимальные оценки
			->setCellValueByColumnAndRow(16, $rowNum, $percentOfMax)
			// доля участников давших оценки свыше 5 баллов
			->setCellValueByColumnAndRow(17, $rowNum, $percentOfOver5)
			// коментарии участников
			->setCellValueByColumnAndRow(18, $rowNum, $arrRow['comment']);
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
	/* private function _printProfilRowForProduct($arrProduct)
	{
		/*
		 * Группируем данные по вопросам к данному образцу
		 */
	/* 	$arrQuestions = array();
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
 */		
		/*
		 * Формируем строки начиная со второй, чтобы подсчитать суммарную оценку образца 
		 */
/* 		$overallRating = 0;
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
	} */ 
	
	/**
	 * Возвращает строку HTML-таблицы
	 *
	 * @param array: массив данных для заполнения полей строки
	 * @return string: HTML-строка
	 */
	/* private function _printProfilRow($arrRow)
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
	} */
	
	/**
	 * Возвращает строку HTML-таблицы
	 *
	 * @param array: массив данных для заполнения полей строки
	 * @return string: HTML-строка
	 */
	/* private function _printProfilFirstRow($strProductName, $arrRow, $numQuest, $overallRating)
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
	} */
	
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
	
	/**
	 * Формирует excel-отчет по опросу, сформированному по профильному методу
	 *
	 * @param int: идентификатор опроса в базе данных
	 * @return object: объект PHPExcel с сформированной таблицей-отчетом
	 */
	public function exportProfilReport($interviewId, $arrTasterList)
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
				$objPHPExcel = $this->_getProfilExcelReport($arrResults, $arrTasterList, $interviewId);
				
				return $objPHPExcel;
			}
			else 
			{
				echo "По данному опросу нет данных";
			}
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
		}
	}
	
	/**
	 * Возвращает excel-файл отчета о проведенном опросе
	 *
	 * @param array: массив содержащий результат запроса к базе данных
	 * @return obj: объект PHPExcel со сформированной таблицей
	 */
	private function _getProfilExcelReport($arrRes, $arrTasterList, $interviewId)
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
		
		$objPHPExcel = new PHPExcel();
		
		/*
		 * Напечатать шапку отчета
		 * Шапка занимает первые две строки 
		 */
		$this->_printHeader($interviewId, $objPHPExcel);
		
		$activeSheet = $objPHPExcel->getActiveSheet();
		
		//установка высоты шапки таблицы
		//$activeSheet->getRowDimension(1)->setRowHeight(90);
		//горизонтальная ориентация
		$activeSheet->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);
		//локализация
		$validLocale = PHPExcel_Settings::setLocale('ru_ru');
		//объединение ячеек
		$activeSheet->mergeCells('A4:A5')
					->mergeCells('B4:B5')
					->mergeCells('C4:C5')
					->mergeCells('D4:D5')
					->mergeCells('E4:K5')
					->mergeCells('D4:D5')
					->mergeCells('L4:L5')
					->mergeCells('M4:M5')
					->mergeCells('N4:N5')
					;
		//заполнение ячеек
		$activeSheet->setCellValue('A4', 'Наименование продукции')
					->setCellValue('B4', '№ показателя')
					->setCellValue('C4', 'Наименование показателя')
					->setCellValue('D4', 'Вес показателя')
					->setCellValue('E4', 'Количество участников, поставивших оценки')
					->setCellValue('L4', 'Итого, участников')
					->setCellValue('M4', 'Средний балл')
					->setCellValue('N4', 'Комментарии участников')
					->setCellValue('E5', '1')
					->setCellValue('F5', '2')
					->setCellValue('G5', '3')
					->setCellValue('H5', '4')
					->setCellValue('I5', '5')
					->setCellValue('J5', '6')
					->setCellValue('K5', '7')
					;
				
		$activeSheet->getColumnDimension('A')->setWidth(15.57);
		$activeSheet->getColumnDimension('B')->setWidth(15);
		$activeSheet->getColumnDimension('C')->setWidth(15.5);
		$activeSheet->getColumnDimension('D')->setWidth(15.5);
		$activeSheet->getColumnDimension('E')->setWidth(3.57);
		$activeSheet->getColumnDimension('F')->setWidth(3.57);
		$activeSheet->getColumnDimension('G')->setWidth(3.57);
		$activeSheet->getColumnDimension('H')->setWidth(3.57);
		$activeSheet->getColumnDimension('I')->setWidth(3.57);
		$activeSheet->getColumnDimension('J')->setWidth(3.57);
		$activeSheet->getColumnDimension('K')->setWidth(3.57);
		$activeSheet->getColumnDimension('L')->setWidth(12);
		$activeSheet->getColumnDimension('M')->setWidth(10.57);
		$activeSheet->getColumnDimension('N')->setWidth(24.29); 
		
		//номер последней используемой строки
		$previousRow = 5;
		
		/*
		 * Добавить строки таблицы для каждого образца
		 */
		foreach($arrProducts as $arrProduct)
		{
			$this->_printProfilRowForProduct($arrProduct, $previousRow, $objPHPExcel);
		} 
		
		//рамка вокруг ячеек
		$styleArray = array(
		  'borders' => array(
			'allborders' => array(
			  'style' => PHPExcel_Style_Border::BORDER_THIN
			)
		  )
		);
	
		$activeSheet->getStyle("A4:N".$previousRow)->applyFromArray($styleArray);
		unset($styleArray);
		//перенос текста со словами
		$activeSheet->getStyle("A4:N".$previousRow)->getAlignment()->setWrapText(true);
				
		$activeSheet->getStyle("A4:N".$previousRow)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
		$activeSheet->getStyle("A4:N".$previousRow)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
		
		/*
		 * Напечатать список участников
		 */
		$this->_printTasterList($objPHPExcel, $arrTasterList, $previousRow);

		return $objPHPExcel;
	}
	
	/**
	 * Выводит строку таблицы полностью описывающюю данный продукт
	 */
	private function _printProfilRowForProduct($arrProduct, &$previousRow, $objPHPExcel)
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
		$this->_printProfilFirstRow($arrProduct['product_name'], $question, $i, $overallRating, $previousRow, $objPHPExcel);
				
		$overallRating = 0;
		$i = 0;
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
				$this->_printProfilRow($question, $previousRow, $objPHPExcel);
				//$previousRow++;
			}
			$i++;
		}
	}
	
	/**
	 * Возвращает строку HTML-таблицы
	 *
	 * @param array: массив данных для заполнения полей строки
	 * @return string: HTML-строка
	 */
	private function _printProfilRow($arrRow, &$previousRow, $objPHPExcel)
	{
		//если название продукции уже напечатано, то печатаем с 1 столбца
		$i = 1;
		$previousRow += 1;
		
		$scores = $arrRow['scores'];
		$activeSheet = $objPHPExcel->getActiveSheet();
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$arrRow['question_num']); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$arrRow['question_text']); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$arrRow['question_rate']); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$scores[1]); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$scores[2]); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$scores[3]); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$scores[4]); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$scores[5]); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$scores[6]); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$scores[7]); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$arrRow['numOfTasters']); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$arrRow['average']); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$arrRow['comment']); $i += 1;
	}
	
	/**
	 * Возвращает строку HTML-таблицы
	 *
	 * @param array: массив данных для заполнения полей строки
	 * @return string: HTML-строка
	 */
	private function _printProfilFirstRow($strProductName, $arrRow, $numQuest, $overallRating, &$previousRow, $objPHPExcel)
	{
		//если необходимо напечатать название продукции, то печатаем с 0 столбца
		$i = 0;
		$previousRow += 1;
		$scores = $arrRow['scores'];
		//объединение ячеек под название продукции
		$merge_from = $previousRow;
		$merge_to = $merge_from + $numQuest - 1;
		$activeSheet = $objPHPExcel->getActiveSheet();
		
		$activeSheet->mergeCells("A$merge_from:A$merge_to");
		
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$strProductName); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$arrRow['question_num']); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$arrRow['question_text']); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$arrRow['question_rate']); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$scores[1]); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$scores[2]); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$scores[3]); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$scores[4]); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$scores[5]); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$scores[6]); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$scores[7]); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$arrRow['numOfTasters']); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$arrRow['average']); $i += 1;
		$activeSheet->setCellValueByColumnAndRow($i,$previousRow,$arrRow['comment']); $i += 1;
	}
}
?>