<?php

/**
 * Обеспечивает создание и проведение опроса
 */
class Interview extends DB_Connect
{
	/*
	 * Переменная для хранения вида дегустационного листа
	 */
	private $_type;
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
		
		/*
		 * Установить вид дегустационного листа
		 */
		if ( isset($_SESSION['interview_type']) )
		{
			$this->_type = $_SESSION['interview_type'];
		}
	}
	
	/**
	 * Возвращает HTML-разметку для отображения блока вопросов.
	 *
	 * @return string: HTML-разметка
	 */
	public function nextCluster()
	{
		/*
		 * Получаем имя и фамилию дегустатора и текущую дату
		 */
		$strTasterName = $this->_curTasterName();
		
		if ( $this->_type == M_TRIANG )
		{
			if ( isset($_SESSION['end']) )
			{
				//возвращаем разметку с благодарностью и кнопкой закончить опрос
				//завершаем сессию
				session_destroy();
				return <<<GOOD_BYE
				<form action="assets/inc/process.inc.php" method="post">
				
					<input type="submit" value="Закончить опрос" />
				</form>
GOOD_BYE;
			}
			$_SESSION['end'] = TRUE;
			//генерируем разметку для метода треугольника
			return $this->_getTriangle();
		}
		else
		{
			/*
			 * Определяем есть ли еще блоки вопросов в дегустационном листе
			 */
			//для этого определяем текущий продукт
			$idProd = isset($_SESSION['product_id']) ? $_SESSION['product_id'] : NULL;
			$arrProduct = $this->_nextProduct($idProd);
			
			if ( empty($arrProduct) )
			{
				//возвращаем разметку с благодарностью и кнопкой закончить опрос
				//завершаем сессию
				session_destroy();
				return <<<GOOD_BYE
				<form action="assets/inc/process.inc.php" method="post">
				
					<input type="submit" value="Закончить опрос" />
				</form>
GOOD_BYE;
			}
			//сохраняем следующий продукт как текущий
			$_SESSION['product_id'] = $arrProduct['product_id'];
			
			$strCluster = "";
			switch ($this->_type)
			{
				case M_PROFIL:
					$strCluster = $this->_getClusterProfil();
					break;
				case M_COMPLX:
					$strCluster = $this->_getClusterComplex();
					break;
				case M_CONSUM:
					$strCluster = $this->_getClusterConsum();
					break;
			} 
			
			return <<<FORM_MARKUP
	<form action="assets/inc/process.inc.php" method="post">
		<legend>$arrProduct[product_name]</legend>
		<input type="hidden" name="product_id" value="$arrProduct[product_id]" />
		$strCluster<br>
		<label>$strTasterName</label>
		<input type="hidden" name="action" value="write_cluster" />
		<input type="hidden" name="token" value="$_SESSION[token]" />
		<input type="submit" name="cluster_submit" value="Далее" class="nextCluster"/>
	</form>
FORM_MARKUP;
		}	
	}
	
	/**
	 * Возвращает разметку для блока вопросов составленных по потребительскому методу
	 *
	 * @return string: HTML-разметка
	 */
	private function _getClusterConsum()
	{
		/*
		 * Получаем идентификатор текущего опроса
		 */
		$idInterview = (int)$_SESSION['interview_id'];
		
		//получить идентификаторы вопросов для текущего опроса
		$strQuery = "SELECT 
						`question_ID` 
					FROM `questions`, `interviews` 
					WHERE `questions`.`cluster_id`= `interviews`.`cluster_id` 
							AND `interviews`.`interview_id` = $idInterview";
		
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrId = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
		}
		catch (Exception $e)
		{
			die ($e->getMessage() );
		}
		
		/*
		 * Формируем шапку таблицы
		 */
		$strTable = "<table border=\"1\">
			<tr>
				<td >№</td>
				<td>Показатели</td>
				<td>Шкала оценки</td>
				<td>Комментарии</td>
			</tr>\n";
		
		$arrQuestions = array();
		$count = 1;
		//получить объекты вопросов текущего опроса
		foreach($arrId as $ID)
		{
			$question = Question::getQuestionById($ID['question_ID'], $this->_objDB);
			//сформировать разметку для текущего вопроса
			$strTable .= "<tr>
							<td>$count</td>
							<td>$question->text</td>
							";
			if ($question->type == Q_CLOSE)
			{
				$strTable .= "<td>";
				for ($i = 0; $i<NUM_OF_OPTIONS; $i++)
				{
				$responseOption = $question->arrResponseOptions[$i];
					$strTable.= "<input type=\"radio\" name=\"quest$question->id\"
					value=\"$responseOption->id\">$responseOption->text<br>";
				}
				$strTable .= "</td>";
				$strTable .= "<td><textarea name=\"comment$question->id\"></textarea></td></tr>";
			}
			else
			{
				$strTable .= "<td colspan = \"2\"><textarea name=\"comment$question->id\"></textarea></td></tr>";
			}
			$count += 1;
		}
		
		$strTable .= "</table>";
		
		return $strTable;
	}
	
	/**
	 * Возвращает разметку для вопроса треугольника
	 *
	 * @return string: HTML-разметка
	 */
	private function _getTriangle()
	{
		/*
		 * Получаем идентификатор текущего опроса
		 */
		$interviewId = (int)$_SESSION['interview_id'];
		
		$strTasterName = $this->_curTasterName();
		
		// получить текст вопроса
		$strQuery = "SELECT  
						`questions`.`question_text`
					FROM `questions`
					LEFT JOIN `clusters`
						ON `clusters`.`cluster_id` = `questions`.`cluster_id`
					LEFT JOIN `interviews`
						ON `clusters`.`cluster_id` = `interviews`.`cluster_id`
					WHERE `interviews`.`interview_id` = $interviewId
					LIMIT 1";
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
		}
		catch (Exception $e)
		{
			die ($e->getMessage() );
		}
		$strQuestionText = $arrResult[0]['question_text'];
		
		// сгенерировать случайные 3-х значные номера для образцов
		$num1 = 1;
		$num2 = 1;
		$num3 = 1;
		while ( ($num1 == $num2) && ($num2 == $num3) && ($num1 == $num3) )
		{
			$num1 = mt_rand(1,999);
			$num2 = mt_rand(1,999);
			$num3 = mt_rand(1,999);
		}
		
		// получить идентификаторы образцов продукции
		$strQuery = "SELECT 
						`product_id` 
					FROM `interview_product` 
					WHERE `interview_id` = $interviewId";
		
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrIdProduct = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
		}
		catch (Exception $e)
		{
			die ($e->getMessage() );
		}
		// получить порядок следования образцов
		$strQuery = "SELECT 
						`pos1`,
						`pos2`,
						`pos3`
					FROM `productorders` 
					WHERE `interview_id` = $interviewId
					ORDER BY productorder_id DESC
					LIMIT 1";
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrProductOrder = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
		}
		catch (Exception $e)
		{
			die ($e->getMessage() );
		}
		
		// подготовить переменные для ввывода в таблицу
		// первый образец кодируется символом A, второй - B
		$prodId1 = ($arrProductOrder[0]['pos1'] == 'A') ? $arrIdProduct[0]['product_id'] : $arrIdProduct[1]['product_id'];
		$prodId2 = ($arrProductOrder[0]['pos2'] == 'A') ? $arrIdProduct[0]['product_id'] : $arrIdProduct[1]['product_id'];
		$prodId3 = ($arrProductOrder[0]['pos3'] == 'A') ? $arrIdProduct[0]['product_id'] : $arrIdProduct[1]['product_id'];
		
		return <<<TRIANGLE
	<form action="assets/inc/process.inc.php" method="post">
		<table width="100%" align="center" border="1">
			<tr align="center">
				<td colspan="3">$strQuestionText</td>
			</tr>
			<tr align="center">
				<td>Образец №$num1</td>
				<td>Образец №$num2</td>
				<td>Образец №$num3</td>
			</tr>
			<tr align="center">
				<td><input type="radio" name="product_id" value="$prodId1"></td>
				<td><input type="radio" name="product_id" value="$prodId2"></td>
				<td><input type="radio" name="product_id" value="$prodId3"></td>
			</tr>
			<tr>
				<td colspan="3">Замечания:</td>
			</tr>
			<tr align="center">
				<td colspan="3"><textarea name="comment"></textarea></td>
			</tr>
		</table>
		
		
		<label>$strTasterName</label>
		<input type="hidden" name="action" value="write_cluster" />
		<input type="hidden" name="token" value="$_SESSION[token]" />
		<input type="submit" name="cluster_submit" value="Далее" class="nextCluster"/>
	</form>
TRIANGLE;
	}
	
	
	/**
	 * Возвращает разметку для блока вопросов составленных по профильному методу
	 *
	 * @return string: HTML-разметка
	 */
	private function _getClusterComplex()
	{
		/*
		 * Получаем идентификатор текущего опроса
		 */
		$idInterview = (int)$_SESSION['interview_id'];
		
		$strQuery = "SELECT 
						`activequestions`.`question_id`,
						`questions`.`question_text`,
						`responseoptions`.`responseoption_id`,
						`responseoptions`.`responseoption_num`
					FROM `activequestions` 
					LEFT JOIN `questions`
							ON `questions`.`question_id` = `activequestions`.`question_id`
					LEFT JOIN `responseoptions` 
						ON `questions`.`question_id` = `responseoptions`.`question_id`
					WHERE `activequestions`.`interview_id` = $idInterview
					ORDER BY 
						`questions`.`question_id`, 
						`responseoptions`.`responseoption_id`";
		
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
		}
		catch (Exception $e)
		{
			die ($e->getMessage() );
		}
		
		/*
		 * Формируем шапку таблицы
		 */
		$strTable = "<table border=\"1\">
			<tr>
				<td >№</td>
				<td>Показатели</td>
				<td>Шкала оценки</td>
				<td>Комментарии</td>
			</tr>\n";
		/*
		 * Формируем строки таблицы с вопросами и вариантами ответов
		 */
		$curQuest = NULL;
		$i = 0;
		foreach ($results as $quest )
		{
		 	if ( $curQuest !== $quest['question_id'] )
			{
				//закрываем строку таблицы
				if ($curQuest != NULL )
				{
					$strTable .= "\n\t\t</nobr></td>\n\t\t<td><textarea name=\"comment$curQuest\"></textarea></td>\n\t</tr>";
				}
				$i++;
				$curQuest = $quest['question_id'];
				$strTable .= "\n\t<tr>\n\t\t<td>$i</td>\n\t\t<td>$quest[question_text]</td>\n\t\t<td><nobr>";
			}
			$strTable .= "\n\t\t\t<input type=\"radio\" name=\"quest$quest[question_id]\" 
					value=\"$quest[responseoption_id]\">$quest[responseoption_num]";
		}
		/*
		 * Закрываем таблицу
		 */
		$strTable .= "\n\t\t</td>\n\t\t</nobr><td><textarea name=\"comment$curQuest\"></textarea></td>\n\t</tr>\n</table>\n";
		return $strTable;
	}
	 
	/**
	 * Возвращает разметку для блока вопросов составленных по профильному методу
	 *
	 * @return string: HTML-разметка
	 */
	private function _getClusterProfil()
	{
		/*
		 * Получаем идентификатор текущего опроса
		 */
		$idInterview = (int)$_SESSION['interview_id'];
		
		$strQuery = "SELECT
						`questions`.`question_id`,
						`questions`.`question_text`,
						`responseoptions`.`responseoption_id`,
						`responseoptions`.`responseoption_text`
					FROM `questions`
					LEFT JOIN `interviews` 
						ON `interviews`.`cluster_id` = `questions`.`cluster_id`
					LEFT JOIN `responseoptions` 
						ON `questions`.`question_id` = `responseoptions`.`question_id`
					WHERE `interviews`.`interview_id`= $idInterview
					ORDER BY 
						`questions`.`question_id`, 
						`responseoptions`.`responseoption_id`";
		
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
		}
		catch (Exception $e)
		{
			die ($e->getMessage() );
		}
		
		/*
		 * Формируем шапку таблицы
		 */
		$strTable = "<table border=\"1\">
			<tr>
				<td >№</td>
				<td>Показатели</td>
				<td>Шкала оценки</td>
				<td>Комментарии</td>
			</tr>\n";
		/*
		 * Формируем строки таблицы с вопросами и вариантами ответов
		 */
		$curQuest = NULL;
		$i = 0;
		foreach ($results as $quest )
		{
		 	if ( $curQuest !== $quest['question_id'] )
			{
				//закрываем строку таблицы
				if ($curQuest != NULL )
				{
					$strTable .= "\n\t\t</td>\n\t\t<td><textarea name=\"comment$curQuest\"></textarea></td>\n\t</tr>";
				}
				$i++;
				$curQuest = $quest['question_id'];
				$strTable .= "\n\t<tr>\n\t\t<td>$i</td>\n\t\t<td>$quest[question_text]</td>\n\t\t<td>";
			}
			$strTable .= "\n\t\t\t<input type=\"radio\" name=\"quest$quest[question_id]\" 
					value=\"$quest[responseoption_id]\">$quest[responseoption_text]<br>";
		}
		/*
		 * Закрываем таблицу
		 */
		$strTable .= "\n\t\t</td>\n\t\t<td><textarea name=\"comment$curQuest\"></textarea></td>\n\t</tr>\n</table>\n";
		return $strTable;
	}
	
	/**
	 * Возвращает ассоциативный массив содержащий все данные о продукте с 
	 * идентификатором больше чем переданный
	 *
	 * @param int $id: идентификатор последнего продукта
	 * @return mixed: NULL - в случае отсутствия следующего образца продукции 
	 * для данной анкеты и array ассоциативный массив если образец существует
	 */
	private function _nextProduct($id=NULL)
	{
		$idInterview = $_SESSION['interview_id'];
		$strQuery = "SELECT 
						`interview_product`.`interview_product_id` AS `product_id`,
						`products`.`product_name`
					FROM `products`
					LEFT JOIN `interview_product` ON `products`.`product_id` = `interview_product`.`product_id`";
		//если передан идентификатор добавляем условие 
		if ( !empty($id))
		{
			$strQuery .= " WHERE `interview_product`.`interview_product_id` >$id
									AND `interview_product`.`interview_id` = $idInterview"; 
		}
		else
		{
			$strQuery .= " WHERE `interview_product`.`interview_id` = $idInterview";
		}
		
		$strQuery .= " ORDER BY `interview_product`.`interview_product_id` 
					LIMIT 1";
		
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			if ( !empty($id) )
			{
				$stmt->bindParam(":id", $id, PDO::PARAM_INT);
			}
			$stmt->execute();
			$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			
			return empty($result) ? NULL : $result[0];
		}
		catch (Exception $e)
		{
			die ($e->getMessage() );
		}
	}
	
	/**
	 * Возвращает строку с именем и фамилией текущего дегустатора
	 *
	 * @return string
	 */
	private function _curTasterName()
	{
		/*
		 * Получаем имя и фамилию текущего администратора из сессии
		 */
		$strSurname = $_SESSION['taster_surname'];
		$strName = $_SESSION['taster_name'];
		
		return $strSurname . " " . $strName;
	}
	
	/**
	 * Сохраняет в базе данных ответы которые дал дегустатор на последний 
	 * предложенный блок вопросов
	 *
	 * @return mixed: TRUE в случае успешного завершения или 
	 * сообщение об ошибке в случае сбоя
	 */
	public function processClusterForm()
	{
		//в зависимость от типа текущего опроса обрабатываем форму
		
		/*
		 * Получить идентификатор дегустатора
		 */
		$idTaster = (int)$_SESSION['taster_id'];
		
		/*
		 * Получить идентификатор продукта
		 */
		$idProd = (int)$_POST['product_id'];
		
		$idInterview = (int)$_SESSION['interview_id']; 
		
		if ( $this->_type == M_TRIANG )
		{
			$strComment = $_POST['comment'];
			
			$strQuery = "INSERT INTO `trianganswers`
							(
							`interview_id`, 
							`taster_id`, 
							`product_id`, 
							`ts`, 
							`comment`
							) 
						VALUES 
							(
							$idInterview,
							$idTaster,
							$idProd,
							:ts,
							:comment
							)";
			try
			{
				$stmt = $this->_objDB->prepare($strQuery);
				$ts = date('Y-m-d h:i:s');
				$stmt->bindParam(":ts", $ts, PDO::PARAM_STR);
				$stmt->bindParam(":comment", $strComment, PDO::PARAM_STR);
				$stmt->execute();
				$stmt->closeCursor();
			}
			catch (Exception $e)
			{
				die ($e->getMessage() );
			}
			
			return TRUE;
		}
		
		/*
		 * Получить номера вопросов блока
		 */
		if ( $this->_type == M_PROFIL)
		{
			$strQuery = "SELECT
							`questions`.`question_id`
						FROM `questions`
						LEFT JOIN `interviews` 
							ON `interviews`.`cluster_id` = `questions`.`cluster_id`
						WHERE `interviews`.`interview_id`= $idInterview
						ORDER BY 
							`questions`.`question_id`";
		}
		if ( $this->_type == M_COMPLX)
		{
			$strQuery = "SELECT
							`activequestions`.`question_id`
						FROM `activequestions`
						WHERE `activequestions`.`interview_id`= $idInterview
						ORDER BY 
							`activequestions`.`question_id`";
		}
		if ( $this->_type == M_CONSUM)
		{
			$strQuery = "SELECT
							`questions`.`question_id`
						FROM `questions`
						LEFT JOIN `interviews` 
							ON `interviews`.`cluster_id` = `questions`.`cluster_id`
						WHERE `interviews`.`interview_id`= $idInterview
						ORDER BY 
							`questions`.`question_id`";
		}
		
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrQuestNums = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
		}
		catch (Exception $e)
		{
			die ($e->getMessage() );
		}
		
		/*
		 * Получить номера ответов которые дал дегустатор и записать их в базу 
		 */
		$strQuery = "INSERT INTO `answers`
							(`taster_id`,
							 `interview_product_id`,
							 `responseOption_id`,
							 `ts`,
							 `comment`, `question_id`)
						VALUES
							(:tasterId, :prodId, :optionId, :ts, :comment, :question_id)";
		$stmt = $this->_objDB->prepare($strQuery);
		
		foreach ($arrQuestNums as $questNum)
		{
			//получить ответ на текущий вопрос
			$num = $questNum['question_id'];
			$ansNum = (int)$_POST['quest'.$num];
			$comment = htmlentities($_POST['comment'.$num], ENT_QUOTES);
			try
			{
				//подставляем параметры в запрос
				$stmt->bindParam(":tasterId", $idTaster, PDO::PARAM_INT);
				$stmt->bindParam(":prodId", $idProd, PDO::PARAM_INT);
				$stmt->bindParam(":optionId", $ansNum, PDO::PARAM_INT);
				$stmt->bindParam(":ts", date('Y-m-d h:i:s'), PDO::PARAM_STR);
				$stmt->bindParam(":comment", $comment, PDO::PARAM_STR);
				$stmt->bindParam(":question_id", $num, PDO::PARAM_INT);
				$stmt->execute();
				$stmt->closeCursor();
			}
			catch (Exception $e)
			{
				die ($e->getMessage() );
			}
		}
		return TRUE;
	}
	
	/**
	 * Сохраняет в сеансе данные о виде текущего опроса,
	 * идентификатор, фамилию, имя дегустатора
	 *
	 * @return mixed: TRUE в случае успешного завершения или 
	 * сообщение об ошибке в случае сбоя
	 */
	public function processStartInterview()
	{
		/*
		 * Получаем идентификатор дегустатора из формы
		 */
		if ( !isset($_POST['taster_id']) )
		{
			// пользователь не выбрал себя из списка
			// отправляем его на главную страницу и завершаем сеанс
			session_destroy();
			header('Location: ../../');
			exit;
		}
		
		$id = (int)$_POST['taster_id'];
		/*
		 * Получаем все данные о дегустаторе из базы
		 */
		$objTasterManager = new TasterManager($this->_objDB);
		$objCurTaster = $objTasterManager->getTasterById($id);
		
		/*
		 * Получаем вид текущего опроса, его идентификатор
		 */
		$arrInterview = $this->_getCurInterview();	
		
		/*
		 * Сохраняем данные в сеансе
		 */
		$_SESSION['taster_id'] = $objCurTaster->id;
		$_SESSION['taster_name'] = $objCurTaster->name;
		$_SESSION['taster_surname'] = $objCurTaster->surname;
		$_SESSION['interview_id'] = $arrInterview['interview_id'];
		$_SESSION['interview_type'] = $arrInterview['interview_type'];
		
		return TRUE;
	}
	
	/**
	 * Загружает информацию о текущем опросе в массив
	 *
	 * @return array: массив с информацией о текущем опросе или NULL
	 */
	private function _getCurInterview()
	{
		$strQuery = "SELECT 
						`interviews`.`interview_type`,
						`interviews`.`interview_id`,
						`interviews`.`interview_name`,
						`current_interviews`.`current_interview_date` 
					FROM `current_interviews`
					LEFT JOIN `interviews` 
						ON `interviews`.`interview_id` = `current_interviews`.`interview_id`
					ORDER BY `current_interviews`.`current_interview_date` DESC
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
			return NULL;
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
		}
	}
	
	/**
	 * Генерирует форму, позволяющую редактировать дегустационные листы
	 *
	 * @return string: HTML-разметка формы для редактирования дегустационного
	 * листа
	 */
	public function displayInterviewForm()
	{
		print_r($_SESSION);
		//добавить шапку (название и вид дегустационного листа)
		$strHeader = $this->_getHeader();
		//добавить блок вопросов (новый или существующий)
		$strClusterType = $this->_getClusterType();
		//добавить список вопросов для существующего
		$objQuestionManager = new QuestionManager($this->_objDB);
		$strQuestionList = $objQuestionManager->getQuestionList();
		//добавить список продуктов 
		$strProductList = $this->_getProductList();
		//добавить кнопку для сохранения дегустационного листа
		$strCmdSave = $this->_getCmdSave();
		
		$strCmdCancel = <<<CANCEL
	<form action="assets/inc/process.inc.php" method="post" >
		<input type="hidden" name="action" value="cancel_edit" />
		<input type="hidden" name="token" value="$_SESSION[token]" />
		<input type="submit" name="cancel_submit" value="Отмена" />
	</form>
CANCEL;
		return $strHeader . $strClusterType . $strQuestionList . $strProductList . $strCmdSave . $strCmdCancel;
		
	}
	
	/** 
	 * Метод возвращает HTML-разметку формы для сохранения дегустационного листа
	 * или пустую строку, если в дегустационный лист еще не добавлен хотя бы один образец
	 * продукции
	 *
	 * @return string
	 */
	private function _getCmdSave()
	{
		if ( isset($_SESSION['edited_interview']) )
		{
			$arrEditedInterview = $_SESSION['edited_interview'];
			if ( isset($arrEditedInterview['num_products'] ) )
			{
				//если опрос по комплексной методике - то выводим меню какие вопросы включать в лист
				if ($arrEditedInterview['interview_type'] == M_COMPLX )
				{
					$strCheckQuestion = "<label>Вопросы которые включены к дегустационный лист:</label>";
					$questNum = $arrEditedInterview['cluster']['num_questions'];
					for ($i = 0; $i < $questNum; $i++)
					{
						$num = $i + 1;
						$strCheckQuestion .= <<<CHECK_QUEST
					<input type="checkbox" name="quest_num[]" value="$i" checked="checked" />$num</label>
CHECK_QUEST;
					}
				}
				// если добавлено меньше 2-х образцов не даем сохранить опросный лист
				if ( $arrEditedInterview['interview_type'] == M_TRIANG && $arrEditedInterview['num_products'] < 2)
				{
					return;
				}
				// если опрос по методу треугольника - выводим меню для определения порядка следования образцов
				if ($arrEditedInterview['interview_type'] == M_TRIANG && $arrEditedInterview['num_products'] == 2 )
				{
					$strOrderProduct =<<<ORDER_PRODUCT
						<br>
						<label>Порядок следования образцов:</label>
						1:<select name="pos1" id="pos1">
							<option value="A" >A</option>
							<option value="B">B</option>
						</select><br>
						2:<select name="pos2" id="pos2">
							<option value="A">A</option>
							<option value="B">B</option>
						</select><br>
						3:<select name="pos3" id="pos3">
							<option value="A">A</option>
							<option value="B">B</option>
						</select><br>
ORDER_PRODUCT;
					$strButtonClass = " class=\"check_order\" ";
				}
				return <<<CMD_SAVE
	<form action="assets/inc/process.inc.php" method="post" >
		$strCheckQuestion$strOrderProduct<br>
		<input type="hidden" name="action" value="write_interview" />
		<input type="hidden" name="token" value="$_SESSION[token]" />
		<input $strButtonClass type="submit" name="cancel_submit" value="Сохранить дегустационный лист" />
	</form>
CMD_SAVE;
			}
			else
			{
				return "";
			}
		}
		return "";
	}
	
	/**
	 * Метод осуществляет запись в базу данных информации о созданном дегустационном листе
	 *
	 * @return mixed: TRUE в случае успешного завершения или 
	 * сообщение об ошибке в случае сбоя
	 */
	public function processInterviewForm()
	{
		$arrEditedInterview = $_SESSION['edited_interview'];
		// определить тип создаваемого опросного листа
		if ( $arrEditedInterview['interview_type'] == M_TRIANG )
		{
			// сохранить опрос созданный по методу треугольника
			// 1. сохранить блок вопросов
			$strQuery = "INSERT INTO `clusters`
							(
								`cluster_name`,
								`cluster_numQuestions`,
								`cluster_type`
							)
							VALUES
							(
								'Triang',
								1,
								".M_TRIANG."
							)";
			try
			{
				$stmtC = $this->_objDB->prepare($strQuery);
				$stmtC->execute();
				$stmtC->closeCursor();
				//получаем идентификатор созданного блока вопросов
				$clusterId = $this->_objDB->lastInsertId();
			}
			catch(Exception $e)
			{
				return $e->getMessage();
			}
			// 2. сохранить вопрос
			$strQuery = "INSERT INTO `questions`
							(
								`question_text`,
								`cluster_id`,
								`question_type`
							)
							VALUES
							(
								:text,
								$clusterId,
								".Q_TRIANG."
							)";
			try
			{
				$strQuestionText = $arrEditedInterview['question_text'];
				$stmtQ = $this->_objDB->prepare($strQuery);
				$stmtQ->bindParam(":text", $strQuestionText, PDO::PARAM_STR);
				$stmtQ->execute();
				$stmtQ->closeCursor();
			}
			catch(Exception $e)
			{
				return $e->getMessage();
			}
			// 3. сохранить данные о самом опросе ( таблица interviews)
			$enterpriseId = $arrEditedInterview['enterprise']['enterprise_id'];
			$strQuery = "INSERT INTO `interviews`
							(
								`interview_name`,
								`cluster_id`,
								`interview_type`,
								`enterprise_id`,
								`interview_date`
							)
							VALUES
							(
								:name,
								$clusterId,
								".M_TRIANG.",
								$enterpriseId,
								:date
							)";
			try
			{
				$strInterviewName = $arrEditedInterview['interview_name'];
				$strInterviewDate = $arrEditedInterview['interview_date'];
				$stmtQ = $this->_objDB->prepare($strQuery);
				$stmtQ->bindParam(":name", $strInterviewName, PDO::PARAM_STR);
				$stmtQ->bindParam(":date", $strInterviewDate, PDO::PARAM_STR);
				$stmtQ->execute();
				$stmtQ->closeCursor();
				// получить идентификатор опроса
				$interviewId = $this->_objDB->lastInsertId();
			}
			catch(Exception $e)
			{
				return $e->getMessage();
			}
			// 4. сохранить порядок следования образцов
			$strQuery = "INSERT INTO `productorders`
							(
								`interview_id`,
								`pos1`,
								`pos2`,
								`pos3`
							)
							VALUES
							(
								$interviewId,
								:pos1,
								:pos2,
								:pos3
							)";
			try
			{
				$stmtO = $this->_objDB->prepare($strQuery);
				$stmtO->bindParam(":pos1", $_POST['pos1'], PDO::PARAM_STR);
				$stmtO->bindParam(":pos2", $_POST['pos2'], PDO::PARAM_STR);
				$stmtO->bindParam(":pos3", $_POST['pos3'], PDO::PARAM_STR);
				$stmtO->execute();
				$stmtO->closeCursor();
			}
			catch(Exception $e)
			{
				return $e->getMessage();
			}
			// 5. сохранить образцы для данного опроса
			// первый образец - кодируется литерой A, второй - B
			$strQuery = "INSERT INTO `interview_product`
							(
								`interview_id`,
								`product_id`
							)
							VALUES
							(
								$interviewId,
								:productId
							)";
			try
			{
				$stmtP = $this->_objDB->prepare($strQuery);
				$arrProducts = $arrEditedInterview['products'];
				foreach($arrProducts as $product)
				{
					$stmtP->bindParam(":productId", $product->id, PDO::PARAM_INT);
					$stmtP->execute();
				}
				$stmtP->closeCursor();
			}
			catch(Exception $e)
			{
				return $e->getMessage();
			}
			return TRUE;
		}
		//проверяем создавался новый блок вопросов или использовался существующий
		if (!isset($arrEditedInterview['cluster']['cluster_id']))
		{
			//был создан новый блок вопросов - записываем его в базу данных
			// и получаем его идентификатор
			$strClusterName = $arrEditedInterview['cluster']['cluster_name'];
			$numOfQuest = $arrEditedInterview['cluster']['num_questions'];
			$arrQuestions = $arrEditedInterview['cluster']['questions'];
			
			$strQuery = "INSERT INTO `clusters`
							(
								`cluster_name`,
								`cluster_numQuestions`,
								`cluster_type`
							)
							VALUES
							(
								:name,
								:numQuest,
								:type
							)";
			try
			{
				$stmt2 = $this->_objDB->prepare($strQuery);
				$stmt2->bindParam(":name", $strClusterName, PDO::PARAM_STR);
				$stmt2->bindParam(":numQuest", $numOfQuest, PDO::PARAM_INT);
				$stmt2->bindParam(":type", $arrEditedInterview['interview_type'], PDO::PARAM_INT);
				$stmt2->execute();
				$stmt2->closeCursor();
				//получаем идентификатор созданного блока вопросов
				$clusterId = $this->_objDB->lastInsertId();
				
				//сохраняем в базе вопросы
				$strQuery = "INSERT INTO `questions`
							(
								`cluster_id`,
								`question_text`,
								`question_rate`,
								`question_type`
							)
							VALUES
							(
								$clusterId,
								:qtext,
								:qrate,
								:qtype
							)";
				try
				{
					$stmt3 = $this->_objDB->prepare($strQuery);
					foreach($arrQuestions as $question)
					{
						$stmt3->bindParam(":qtext", $question->text, PDO::PARAM_STR);
						$stmt3->bindParam(":qrate", $question->rate, PDO::PARAM_INT);
						$stmt3->bindParam(":qtype", $question->type, PDO::PARAM_INT);
						$stmt3->execute();
						//получаем идентификатор сохраненного вопроса
						$questionId = $this->_objDB->lastInsertId();
						
						//сохраняем варианты ответа на данный вопрос
						$strQuery = "INSERT INTO `responseoptions`
											(
												`question_id`,
												`responseOption_text`,
												`responseOption_num`
											)
											VALUES
											(
												$questionId,
												:otext,
												:onum
											)";
						$k = 1;//номер варианта ответа
						$stmt4 = $this->_objDB->prepare($strQuery);
						foreach($question->arrResponseOptions as $option)
						{
							$stmt4->bindParam(":otext", $option->text, PDO::PARAM_STR);
							$stmt4->bindParam(":onum", $k, PDO::PARAM_INT);
							$stmt4->execute();
							$k++;
						}
						$stmt4->closeCursor();
					}
					$stmt3->closeCursor();
				}
				catch (Exception $e)
				{
					return $e->getMessage();
				}
			}
			catch (Exception $e)
			{
				return $e->getMessage();
			}
			
		}
		else
		{
			//использовался существующий - получаем его идентификатор
			$clusterId = $arrEditedInterview['cluster']['cluster_id'];
		}
		
		//записываем название дегустационного листа в базу данных
		//вид опросного листа и идентификатор предприятия для которого проводится опрос
		$type = $arrEditedInterview['interview_type'];
		$strInterviewName = $arrEditedInterview['interview_name'];
		$strInterviewDate = $arrEditedInterview['interview_date'];
		$enterpriseId = $arrEditedInterview['enterprise']['enterprise_id'];
		
		$strQuery = "INSERT INTO `interviews`
							(
								`interview_name`,
								`interview_type`,
								`cluster_id`,
								`enterprise_id`,
								`interview_date`
							)
							VALUES
							(
								:name,
								:type,
								:cluster_id,
								$enterpriseId,
								:date
								
							)";
		
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->bindParam(":name", $strInterviewName, PDO::PARAM_STR);
			$stmt->bindParam(":type", $type, PDO::PARAM_INT);
			$stmt->bindParam(":date", $strInterviewDate, PDO::PARAM_STR);
			$stmt->bindParam(":cluster_id", $clusterId, PDO::PARAM_INT);
			$stmt->execute();
			$stmt->closeCursor();
			
			//получаем идентификатор созданного опроса
			$interviewId = $this->_objDB->lastInsertId();
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}
		
		//связываем образцы продукции и опросный лист
		$strQuery = "INSERT INTO `interview_product`
							(
								`interview_id`,
								`product_id`
							)
							VALUES
							(
								$interviewId,
								:productId
							)";
		try
		{
			$stmt1 = $this->_objDB->prepare($strQuery);
			
			foreach($arrEditedInterview['products'] as $product)
			{
				$stmt1->bindParam(":productId", $product->id, PDO::PARAM_INT);
				$stmt1->execute();
			}
			$stmt1->closeCursor();
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}
		
		
		//если сохраняемый опросный лист создан по комплексной методике - сохраняем вопросы которые участвуют в 
		// данном опросе
		if ( $arrEditedInterview['interview_type'] == M_COMPLX )
		{
			$objQuestionManager = new QuestionManager($this->_objDB);
			$arrQuestion = $objQuestionManager->getQuestionListObjByClusterId($clusterId);
			$strQuery = "INSERT INTO `activequestions`
							(
								`interview_id`,
								`question_id`
							)
							VALUES
							(
								$interviewId,
								:questId
							)";
			try
			{
				$stmt6 = $this->_objDB->prepare($strQuery);
				print_r($_POST['quest_num']);
				// получаем из формы, какие вопросы выбрал пользователь
				foreach($_POST['quest_num'] as $questNum)
				{
					// и записываем в базу данных
					$questId = $arrQuestion[$questNum]->id;
					$stmt6->bindParam(":questId", $questId, PDO::PARAM_INT);
					$stmt6->execute();
				}
				$stmt6->closeCursor();
			}
			catch(Exception $e)
			{
				return $e->getMessage();
			}
		}
		
		/*
		 * Удалить все данные о созданном опросе из сеанса
		 */
		$this->processEndEdit(); 
		
		return TRUE;
	}
	
	/**
	 * Метод возвращает разметку для ввода названия нового дегустационного листа
	 * и выбора вида опроса
	 *
	 * @return string: HTML-строка
	 */
	private function _getHeader()
	{
		/*
		 * Если в сеансе уже хранятся данные по дегустационному листу
		 * выводим их без возможности редактировать
		 */
		if ( isset($_SESSION['edited_interview']) )
		{
			$arrEditedInterview = $_SESSION['edited_interview'];
			$strInterviewName = $arrEditedInterview['interview_name'];
			$strEnterpriseName = $arrEditedInterview['enterprise']['enterprise_name'];
			$strInterviewDate = $arrEditedInterview['interview_date'];
			switch ($arrEditedInterview['interview_type'] )
			{
				case M_TRIANG:
					$strInterviewType = "Метод треугольника";
					break;
				case M_PROFIL:
					$strInterviewType = "Профильный метод";
					break;
				case M_COMPLX:
					$strInterviewType = "Метод комплексной оценки";
					break;
				case M_CONSUM:
					$strInterviewType = "Потребительское тестирование";
					break;
			}
			
			return <<<HEADER_FORM
<label>Название дегустационного листа:</label>
<input type="text" name="interview_name"
			id="interview_name" value="$strInterviewName" readonly/>
<label>Дата</label>
<input type="text" name="interview_date"
			id="interview_date" value="$strInterviewDate" readonly/>
<label>Выпускающее предприятие:</label>
<input type="text" name="enterprise_name"
			id="enterprise_name" value="$strEnterpriseName" readonly/>
<label>Вариант опроса:</label>
<input type="text" name="interview_type"
			id="interview_type" value="$strInterviewType" readonly/>
HEADER_FORM;
		}
		else
		{
			// список для выбора выпускающего предприятия, для которого проводится опрос
			$objEnterpriseManager = new EnterpriseManager($this->_objDB);
			$strEnterpriseList = $objEnterpriseManager->getDropDownList();
			// список для выбора типа опросного листа
			$strInterviewType = "<select name=\"interview_type\">
									<option value=\"".M_PROFIL."\">Профильный метод</option>
									<option value=\"".M_COMPLX."\">Метод комплексной оценки</option>
									<option value=\"".M_TRIANG."\">Метод треугольника</option>
									<option value=\"".M_CONSUM."\">Потребительское тестирование</option>
								</select>";
			$strInterviewDate = date('Y-m-d');
			//выводим форму для ввода данных
			return <<<HEADER_FORM
	<form action="assets/inc/process.inc.php" method="post" >
		<label for="interview_name">Название дегустационного листа</label>
		<input type="text" name="interview_name"
			id="interview_name" value="" />
		<label for="interview_date">Дата</label>
		<input type="text" name="interview_date"
			id="interview_date" value="$strInterviewDate" />
		<label>Вариант опроса:</label>
		$strInterviewType
		<label>Выпускающее предприятие</label>
		$strEnterpriseList
		<input type="hidden" name="action" value="new_interview" />
		<input type="hidden" name="token" value="$_SESSION[token]" />
		<input type="submit" name="next_submit" value="Продолжить" />
	</form>
HEADER_FORM;
		}
	}
	
	/**
	 * Метод возвращает либо разметку для выбора из существующих блоков вопросов или 
	 * создания нового блока вопросов, либо название блока выбранного для дегустационного листа
	 *
	 * @return string: HTML
	 */
	private function _getClusterType()
	{
		/*
		 * Если в сеансе уже хранятся данные по блоку вопросов
		 * выводим их без возможности редактировать
		 */
		if ( isset($_SESSION['edited_interview']) )
		{
			// если создается опрос по методу треугольника
			if ( $_SESSION['edited_interview']['interview_type'] == M_TRIANG )
			{
				// вывести разметку для ввода текста вопроса и определения порядка
				// следования образцов
				if (isset($_SESSION['edited_interview']['question_text']) )
				{
					$strQuestionText = $_SESSION['edited_interview']['question_text'];
					return <<<TRIANGq
					<label>Текст вопроса:</label>
					<textarea name="question_text" 
							id="question_text" readonly>$strQuestionText</textarea>
TRIANGq;
				}
				return <<<TRIANG
	<form action="assets/inc/process.inc.php" method="post">
		<label for="question_text">Текст вопроса:</label>
		<textarea name="question_text" 
				id="question_text"></textarea>
		
		<input type="hidden" name="action" value="new_triang_quest" />
		<input type="hidden" name="token" value="$_SESSION[token]" />
		<input type="submit" name="next_submit" value="Далее" />
	</form>
TRIANG;
			}
			//если создается опрос по профильной схеме
			if ( $_SESSION['edited_interview']['interview_type'] == M_PROFIL )
			{
				//если в сеансе отмечено что создается новый блок вопросов
				//то выводим форму для задания названия блока вопросов
				if ( isset($_SESSION['edited_interview']['new_cluster']))
				{
					//вернуть разметку для задания названия блока вопросов
					return <<<NEW_CLUSTER_FORM
	<form action="assets/inc/process.inc.php" method="post">
		<label>Блок вопросов</label>
		<input type="text" name="cluster_name"
			id="cluster_name" value="" />
		<input type="hidden" name="action" value="new_cluster_name" />
		<input type="hidden" name="token" value="$_SESSION[token]" />
		<input type="submit" name="next_submit" value="Далее" />
	</form>
NEW_CLUSTER_FORM;
				}
				$arrEditedInterview = $_SESSION['edited_interview'];
				if ( isset($arrEditedInterview['cluster']) )
				{
					$strClusterName = $arrEditedInterview['cluster']['cluster_name'];
					return <<<CLUSTER_FORM
	<label>Блок вопросов:</label>
	<input type="text" name="cluster_name"
			id="cluster_name" value="$strClusterName" readonly/>
CLUSTER_FORM;
				}
				else
				{
					//блок не задан, поэтому выводим приглашение для выбора
					$strClusterList = $this->_getClusterList();
					$strClusterForm = <<<CLUSTER_FORM
<form action="assets/inc/process.inc.php" method="post">
	<label>Блок вопросов:</label>
	$strClusterList\n\t
	<input type="hidden" name="action" value="choice_cluster" />
	<input type="hidden" name="token" value="$_SESSION[token]" />
	<input type="submit" name="next_submit" value="Продолжить" />
</form>
<form action="assets/inc/process.inc.php" method="post">
	<input type="hidden" name="action" value="new_cluster" />
	<input type="hidden" name="token" value="$_SESSION[token]" />
	<input type="submit" name="new_cluster_submit" value="Новый блок вопросов" />
</form>
CLUSTER_FORM;
					return $strClusterForm;
				}
			}
		}
		else
		{
			//в сеансе еще ничего нет о дегустационном листе, поэтому возвращаем 
			//пустую строку
			return "";
		}
	}
	
	/**
	 * Метод записывает в сеансе текст вопроса для метода треугольника
	 *
	 * @return mixed: TRUE в случае успеха или сообщение об ошибке 
	 */
	public function processTriangQuestForm()
	{
		$_SESSION['edited_interview']['question_text'] = $_POST['question_text'];
		$_SESSION['edited_interview']['num_product'] = 0;
		return TRUE;
	}
	
	/**
	 * Метод сохраняет информацию о выбранном типе опроса (название и идентификатор)
	 *
	 * @return mixed: TRUE в случае успешного завершения или 
	 * сообщение об ошибке в случае сбоя
	 */
	public function processChoiceCluster()
	{
		$id = (int)$_POST['cluster_id'];
		$strQuery = "SELECT 
						`clusters`.`cluster_name`,
						`clusters`.`cluster_numQuestions`
					FROM `clusters`
					WHERE `clusters`.`cluster_id` = $id";
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			
			$arrCluster = array(
						'cluster_id'=>$id, 
						'cluster_name' => $arrResults[0]['cluster_name'],
						'num_questions'=> $arrResults[0]['cluster_numQuestions']);
			$_SESSION['edited_interview']['cluster'] = $arrCluster;
			
			return TRUE;
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
		}
	}
	
	/** 
	 * Метод сохраняет информацию в сеансе о том что пользователь выбрал создание нового
	 * блока вопросов
	 *
	 * @return mixed: TRUE в случае успешного завершения или 
	 * сообщение об ошибке в случае сбоя
	 */
	public function processNewClusterForm()
	{
		/*
		 * Выход, если значение "action" задано неправильно
		 */
		if ($_POST['action'] !== 'new_cluster' )
		{
			return "Некорректная попытка вызова метода processNewClusterForm";
		}
		
		$_SESSION['edited_interview']['new_cluster'] = TRUE;
		
		return TRUE;
	}
	
	/** 
	 * Метод сохраняет в сеансе имя нового блока вопросов
	 *
	  * @return mixed: TRUE в случае успешного завершения или 
	 * сообщение об ошибке в случае сбоя
	 */
	public function processNewClusterNameForm()
	{
		/*
		 * Выход, если значение "action" задано неправильно
		 */
		if ($_POST['action'] !== 'new_cluster_name' )
		{
			return "Некорректная попытка вызова метода processNewClusterNameForm";
		}
		if ( isset($_SESSION['edited_interview']['new_cluster']) )
		{
			unset($_SESSION['edited_interview']['new_cluster']);
			
			$cluster = array(
				'cluster_name' => htmlentities($_POST['cluster_name'], ENT_QUOTES)
			);
			
			$_SESSION['edited_interview']['cluster'] = $cluster;
			
			return TRUE;
		}
	}
	
	/**
	 * Метод возвращает выпадающий список зарегистрированных в системе блоков 
	 * вопросов для профильного метода.
	 * 
	 * @return string: HTML-разметка выпадающего списка
	 */
	private function _getClusterList()
	{
		$strQuery = "SELECT DISTINCT 
						`clusters`.`cluster_id`, 
						`clusters`.`cluster_name`
					FROM `clusters`
					WHERE `clusters`.`cluster_type` = ".M_PROFIL;
						
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			
			$strClusterList = "<select name=\"cluster_id\">";
			foreach($arrResults as $elem )
			{
				$strClusterList .= "\n\t<option value=\"$elem[cluster_id]\">$elem[cluster_name]</option>";
			}
			$strClusterList .= "\n</select>";
									
			return $strClusterList;
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
		}
	}
	/**
	 * Метод удаляет из сеанса информацию о редактируемом дегустационном листе
	 *
	 * @return mixed: TRUE в случае успешного завершения или 
	 * сообщение об ошибке в случае сбоя
	 */
	public function processEndEdit()
	{
		unset($_SESSION['edited_interview']);
		return TRUE;
	}
	
	/**
	 * Метод возвращает список продуктов уже добавленных в дегустационный лист
	 * и форму для добавления нового образца 
	 */
	private function _getProductList()
	{
		//если в сеансе записан хотя бы один вопрос, то выводим 
		// список уже добавленных продуктов и форму для добавления еще одного.
		if ( isset($_SESSION['edited_interview']) )
		{
			// если редактируется опрос по методу треугольника
			$arrEditedInterview = $_SESSION['edited_interview'];
			if($arrEditedInterview['interview_type'] == M_TRIANG && isset($arrEditedInterview['question_text']) )
			{
				$strProductList = "";
				$arrProducts = $arrEditedInterview['products'];
				$i = 1;
				foreach($arrProducts as $product)
				{
					$strProductCode = $i == 1 ? "A" : "B";
					$strProductList .= "<label>Образец $strProductCode: $product->name</label>";
					$i++;
				}
				// Проверить сколько образцов уже добавлено в дегустационный лист
				if ( $arrEditedInterview['num_products'] < 2)
				{
					// если количество образцов меньше двух вывести кнопку добавления 
					// образца в дегустационный лист
					$strProductList .=<<<NEW_PRODUCT_BUTTON
					<a href="choiseProduct.php" class="admin add_product">Добавить образец в дегустационный лист</a>
NEW_PRODUCT_BUTTON;
				}
				return  $strProductList;
			}
			if ( isset($arrEditedInterview['cluster']) )
			{
				if ( isset($arrEditedInterview['cluster']['num_questions'] ) 
						&& $arrEditedInterview['cluster']['num_questions'] > 0 )
				{
					//выводим список уже сохраненных в сеансе продуктов
					$strProductList = "<p><label>Образцы продукции:</label>";
					$arrProducts = $arrEditedInterview['products'];
					$i = 1;
					foreach($arrProducts as $product)
					{
						$strProductList .= "<label>$i. $product->name</label>";
						$i++;
					}
					
					//выводим кнопку для добавления нового продукта
					$strProductList .=<<<NEW_PRODUCT_BUTTON
					<a href="choiseProduct.php" class="admin add_product">Добавить образец в дегустационный лист</a>
NEW_PRODUCT_BUTTON;
					return $strProductList;
				}
			}
		}
	}
	
	/** 
	 * Предназначен для сохранения в сеансе информации о выбранном образце продукции
	 *
	 * @return mixed: TRUE в случае успешного завершения или 
	 * сообщение об ошибке в случае сбоя
	 */
	public function processChoiseProduct()
	{
		/*
		 * Выход, если значение "action" задано неправильно
		 */
		if ($_POST['action'] !== 'product_choise' )
		{
			return "Некорректная попытка вызова метода processChoiseProduct";
		}
		
		/*
		 * извлечь данные из формы
		 */
		$productId = $_POST['product_id'];
		
		/*
		 * Получить объект образца и сохранить в сеансе
		 */
		$objProductManager = new ProductManager($this->_objDB);
		$objProduct = $objProductManager->getProductById($productId);
		
		if ( !isset($_SESSION['edited_interview']['products']) )
		{
			$_SESSION['edited_interview']['products'] = array();
			$_SESSION['edited_interview']['num_products'] = 0;
		}
		$_SESSION['edited_interview']['products'][] = $objProduct;
		$_SESSION['edited_interview']['num_products']++;
		
		return TRUE;
	}
	
	/**
	 * Метод запоминает название и тип вновь создаваемого дегустационного листа
	 * 
	 * @return mixed: TRUE в случае успешного завершения или 
	 * сообщение об ошибке в случае сбоя
	 */
	public function processInterviewName()
	{
		/*
		 * Получаем название дегустационного листа и его тип из формы
		 */
		$strInterviewName =  htmlentities($_POST['interview_name'], ENT_QUOTES);
		$intInterviewType = (int) $_POST['interview_type'];
		$enterpriseId = (int)$_POST['enterprise_id'];
		$strInterviewDate = htmlentities($_POST['interview_date'], ENT_QUOTES);
		
		$objEnterpriseManager = new EnterpriseManager($this->_objDB);
		$strEnterpriseName = $objEnterpriseManager->getEnterpriseById($enterpriseId)->name;
		$arrEnterprise = array(
				'enterprise_id' => $enterpriseId,
				'enterprise_name'=> $strEnterpriseName
				);
		
		$arrEditedInterview = array(
					'interview_name' => $strInterviewName,
					'interview_type' => $intInterviewType,
					'enterprise' => $arrEnterprise,
					'interview_date' => $strInterviewDate
			);
		
		if ( $intInterviewType == M_COMPLX)
		{
			/*
			 * Ищем в базе данных блок вопросов составленный по методу комплексной оценки
			 */
			$strQuery = "SELECT 
							`cluster_id`,
							`cluster_numQuestions`
						FROM `clusters`
						WHERE `cluster_type` = " . M_COMPLX;
			try
			{
				$stmt = $this->_objDB->prepare($strQuery);
				$stmt->execute();
				$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$stmt->closeCursor();
				
				if ( isset($arrResults[0]) )
				{
					$clusterId = $arrResults[0]['cluster_id'];
					$numQuest = $arrResults[0]['cluster_numQuestions'];
				}
				else
				{
					$clusterId = NULL;
				}
			}
			catch ( Exception $e )
			{
				die ( $e->getMessage() );
			}
			if ( $clusterId !== NULL)
			{
				$cluster = array(
					'cluster_id' => $clusterId,
					'num_questions' => $numQuest
				);
			}
			else
			{
				$cluster = array(
					'num_questions' => 0
				);
			}
			
			/*
			 * Сохраняем данные в сеансе
			 */
			$arrEditedInterview['cluster'] = $cluster;
		}
		
		if ( $intInterviewType == M_CONSUM)
		{
			//создаем новый блок вопросов
			$cluster = array(
				'num_questions' => 0
				);
			//Сохраняем данные в сеансе
			$arrEditedInterview['cluster'] = $cluster;	
		}
		
		$_SESSION['edited_interview'] = $arrEditedInterview;
		
		
		return TRUE;
	}
	
	/**
	 * Метод возвращает список всех дегустационных листов зарегистрированных в системе
	 */
	public function displayInterviewList()
	{
		/*
		 * Получить список дегустационных листов
		 */
		$strQuery = "SELECT 
						`interview_id` , 
						`interview_name`
					FROM `interviews`";
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			
			$strInterviewList = "<select name=\"interview_id\">\n\t";
			foreach($arrResults as $elem )
			{
				$strInterviewList .= "\n\t<option value=\"$elem[interview_id]\">$elem[interview_name]</option>";
			}
			$strInterviewList .= "\n</select>";
									
			return $strInterviewList;
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
		}
	}
	
	
	/**
	 * Метод записывает в базу данных 
	 * 
	 * @return mixed: TRUE в случае успешного завершения или 
	 * сообщение об ошибке в случае сбоя
	 */
	public function processChangeCurInterview()
	{
		/*
		 * Выход, если значение "action" задано неправильно
		 */
		if ($_POST['action'] !== 'change_cur_interview' )
		{
			return "Некорректная попытка вызова метода processChangeCurInterview";
		}
		
		/*
		 * извлечь данные из формы
		 */
		$interviewId = (int)$_POST['interview_id'];
		
		/*
		 * Получить текущую дату и время
		 */
		$strCurDateTime = date("Y-m-d H:i:s");
		
		/*
		 * Записать изменения в базу данных
		 */
		$strQuery = "INSERT INTO `current_interviews`
						(
						`current_interview_date`, 
						`interview_id`
						) 
						VALUES 
						(
						'$strCurDateTime',
						$interviewId
						)";
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$stmt->closeCursor();
			
			return  TRUE;
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
		}
	}
	
	/**
	 * Возвращает название текущего дегустационного листа
	 *
	 * @return string
	 */
	public function displayCurInterview()
	{
		$arrInterview = $this->_getCurInterview();
		
		/*
		 * Для метода треугольника выводим порядок следования образцов
		 */
		if ( $arrInterview['interview_type'] == M_TRIANG )
		{
			$interviewId = $arrInterview['interview_id'];
			// получить идентификаторы образцов продукции
			$strQuery = "SELECT 
							`product_id` 
						FROM `interview_product` 
						WHERE `interview_id` = $interviewId";
			try
			{
				$stmt = $this->_objDB->prepare($strQuery);
				$stmt->execute();
				$arrIdProduct = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$stmt->closeCursor();
			}
			catch (Exception $e)
			{
				die ($e->getMessage() );
			}
			// получить порядок следования образцов
			$strQuery = "SELECT 
							`pos1`,
							`pos2`,
							`pos3`
						FROM `productorders` 
						WHERE `interview_id` = $interviewId
						ORDER BY productorder_id DESC
						LIMIT 1";
			try
			{
				$stmt = $this->_objDB->prepare($strQuery);
				$stmt->execute();
				$arrProductOrder = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$stmt->closeCursor();
			}
			catch (Exception $e)
			{
				die ($e->getMessage() );
			}
			
			$objProductManager = new ProductManager($this->_objDB);
			$arrProducts[0] = $objProductManager->getProductById($arrIdProduct[0]['product_id']);
			$arrProducts[1] = $objProductManager->getProductById($arrIdProduct[1]['product_id']);
			
			// подготовить переменные для ввывода в таблицу
			// первый образец кодируется символом A, второй - B
			$selectedB1 = ($arrProductOrder[0]['pos1'] == 'B') ? "selected" : "";
			$selectedB2 = ($arrProductOrder[0]['pos2'] == 'B') ? "selected" : "";
			$selectedB3 = ($arrProductOrder[0]['pos3'] == 'B') ? "selected" : "";
			
			$strProductA = "A: ".$arrProducts[0]->name;
			$strProductB = "B: ".$arrProducts[1]->name;
			$arrOptions = array();
			
			$strOrderProduct =<<<ORDER_PRODUCT
				<br>
				<form action="assets/inc/process.inc.php" method="post">
						<label>Порядок следования образцов:</label>
						1:<select name="pos1" id="pos1">
							<option value="A">$strProductA</option>
							<option $selectedB1 value="B">$strProductB</option>
						</select><br>
						2:<select name="pos2" id="pos2">
							<option value="A">$strProductA</option>
							<option $selectedB2 value="B">$strProductB</option>
						</select><br>
						3:<select name="pos3" id="pos3">
							<option value="A">$strProductA</option>
							<option $selectedB3 value="B">$strProductB</option>
						</select><br>
						<input type="hidden" name="token" value="$_SESSION[token]" />
						<input type="hidden" name="action" value="edit_productorder" />
						<input type="submit" name="question_submit" class="check_order" value="Сделать текущим выбранный порядок образцов" />
				</form>
ORDER_PRODUCT;
			
		}
		
		return "<label>".$arrInterview['interview_name']."</label>" . $strOrderProduct;
	}
	
	public function processOrderForm()
	{
		$interviewId = $this->_getCurInterview()['interview_id'];
		// сохранить порядок следования образцов
		$strQuery = "INSERT INTO `productorders`
						(
							`interview_id`,
							`pos1`,
							`pos2`,
							`pos3`
						)
						VALUES
						(
							$interviewId,
							:pos1,
							:pos2,
							:pos3
						)";
		try
		{
			$stmtO = $this->_objDB->prepare($strQuery);
			$stmtO->bindParam(":pos1", $_POST['pos1'], PDO::PARAM_STR);
			$stmtO->bindParam(":pos2", $_POST['pos2'], PDO::PARAM_STR);
			$stmtO->bindParam(":pos3", $_POST['pos3'], PDO::PARAM_STR);
			$stmtO->execute();
			$stmtO->closeCursor();
		}
		catch(Exception $e)
		{
			return $e->getMessage();
		}
		
		return TRUE;
	}
}
 
?>