<?php

/**
 * Обеспечивает проведение опроса
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
			//генерируем разметку для метода треугольника
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
					break;
				case M_CONSUM:
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
		<input type="submit" name="cluster_submit" value="Далее" />
	</form>
FORM_MARKUP;
		}	
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
		
		echo "$strQuery";
		
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
		
		/*
		 * Получить номера вопросов блока
		 */
		$idInterview = (int)$_SESSION['interview_id'];
		$strQuery = "SELECT
						`questions`.`question_id`
					FROM `questions`
					LEFT JOIN `interviews` 
						ON `interviews`.`cluster_id` = `questions`.`cluster_id`
					WHERE `interviews`.`interview_id`= $idInterview
					ORDER BY 
						`questions`.`question_id`";
		
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
							 `comment`)
						VALUES
							(:tasterId, :prodId, :optionId, :ts, :comment)";
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
	 * @return array: массив с информацией о текущем опросе
	 */
	private function _getCurInterview()
	{
		$strQuery = "SELECT 
						`interviews`.`interview_type`,
						`interviews`.`interview_id`,
						`interviews`.`interview_name`,
						`current_interviews`.`current_interview_date` 
					FROM `interviews`
					LEFT JOIN `current_interviews` 
						ON `interviews`.`interview_id` = `current_interviews`.`interview_id`
					ORDER BY `current_interviews`.`current_interview_date` DESC
					LIMIT 1";
						
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			
			return $arrResults[0];
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
		//добавить шапку (название и вид дегустационного листа)
		$strHeader = $this->_getHeader();
		//добавить блок вопросов (новый или существующий)
		$strClusterType = $this->_getClusterType();
		//добавить список вопросов для существующего
		$strQuestionList = $this->_getQuestionList();
		//добавить список продуктов 
		$strProductList = $this->_getProductList();
		//добавить кнопку для сохранения дегустационного листа
		$strCmdSave = $this->_getCmdSave();
		print_r($_SESSION);
		
		$strCmdCancel = <<<CANCEL
	<form action="assets/inc/process.inc.php" method="post" >
		<input type="hidden" name="action" value="cancel_edit" />
		<input type="hidden" name="token" value="$_SESSION[token]" />
		<input type="submit" name="cancel_submit" value="Отмена" />
	</form>
CANCEL;
		return $strHeader . $strClusterType . $strQuestionList . $strProductList . $strCmdCancel . $strCmdSave;
		
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
				return <<<CMD_SAVE
	<form action="assets/inc/process.inc.php" method="post" >
		<input type="hidden" name="action" value="write_interview" />
		<input type="hidden" name="token" value="$_SESSION[token]" />
		<input type="submit" name="cancel_submit" value="Сохранить дегустационный лист" />
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
								`cluster_numQuestions`
							)
							VALUES
							(
								:name,
								:numQuest
							)";
			try
			{
				$stmt2 = $this->_objDB->prepare($strQuery);
				$stmt2->bindParam(":name", $strClusterName, PDO::PARAM_STR);
				$stmt2->bindParam(":numQuest", $numOfQuest, PDO::PARAM_INT);
				$stmt2->execute();
				$stmt2->closeCursor();
				//получаем идентификатор созданного блока вопросов
				$clusterId = $this->_objDB->lastInsertId();
				
				//сохраняем в базе вопросы
				$strQuery = "INSERT INTO `questions`
							(
								`cluster_id`,
								`question_text`,
								`question_rate`
							)
							VALUES
							(
								$clusterId,
								:qtext,
								:qrate
							)";
				try
				{
					$stmt3 = $this->_objDB->prepare($strQuery);
					foreach($arrQuestions as $question)
					{
						$stmt3->bindParam(":qtext", $question->text, PDO::PARAM_STR);
						$stmt3->bindParam(":qrate", $question->rate, PDO::PARAM_INT);
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
		//вид опросного листа
		$type = $arrEditedInterview['interview_type'];
		$strInterviewName = $arrEditedInterview['interview_name'];
		
		$strQuery = "INSERT INTO `interviews`
							(
								`interview_name`,
								`interview_type`,
								`cluster_id`
							)
							VALUES
							(
								:name,
								:type,
								:cluster_id
							)";
		
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->bindParam(":name", $strInterviewName, PDO::PARAM_STR);
			$stmt->bindParam(":type", $type, PDO::PARAM_INT);
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
				print_r($product->id);
				$stmt1->bindParam(":productId", $product->id, PDO::PARAM_INT);
				$stmt1->execute();
			}
			$stmt1->closeCursor();
		}
		catch (Exception $e)
		{
			return $e->getMessage();
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
<label>Вариант опроса:</label>
<input type="text" name="interview_type"
			id="interview_type" value="$strInterviewType" readonly/>
HEADER_FORM;
		}
		else
		{
			$strInterviewType = "<select name=\"interview_type\">
									<option value=\"".M_TRIANG."\">Метод треугольника</option>
									<option value=\"".M_PROFIL."\">Профильный метод</option>
									<option value=\"".M_COMPLX."\">Метод комплексной оценки</option>
									<option value=\"".M_CONSUM."\">Потребительское тестирование</option>
								</select>";
			//выводим форму для ввода данных
			return <<<HEADER_FORM
	<form action="assets/inc/process.inc.php" method="post" >
		<label for="interview_name">Название дегустационного листа</label>
		<input type="text" name="interview_name"
			id="interview_name" value="" />
		<label>Вариант опроса:</label>
		$strInterviewType
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
			//если в сеансе отмечено что создается новый блок вопросов
			//то выводим форму для задания названия блока вопросов
			if ( isset($_SESSION['edited_interview']['new_cluster']))
			{
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
		else
		{
			//в сеансе еще ничего нет о дегустационном листе, поэтому возвращаем 
			//пустую строку
			return "";
		}
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
	 * Метод возвращает разметку для отображения списка вопросов
	 *
	 * @return string: HTML-строка
	 */
	private function _getQuestionList()
	{
		//если в сеансе записан идентификатор блока вопросов, то 
		// выводим все вопросы блока с вариантами ответов
		if ( isset($_SESSION['edited_interview']) )
		{
			$arrEditedInterview = $_SESSION['edited_interview'];
			if ( isset($arrEditedInterview['cluster']) )
			{
				if ( isset($arrEditedInterview['cluster']['cluster_id'] ) )
				{
					$arrQuestions = $this->_getQuestionListObjByClusterId($arrEditedInterview['cluster']['cluster_id']);
					
					/*
					 * создать разметку для списка вопросов
					 */
					$strQuestionsList = "";
					$i = 1;
					foreach( $arrQuestions as $objQuestion )
					{
						$strOptionsList = "";
						foreach ( $objQuestion->arrResponseOptions as $option )
						{
							$strOptionsList .=<<<OPTION_LIST
							<label>$option->num</label>
							<label>$option->text</label>
OPTION_LIST;
						}
						$strQuestionsList .=<<<QUESTION_LIST
						<p>
						<form action="editQuestion.php" method="post">
							<legend>Вопрос №$i</legend>
							<fieldset>
								<label>$objQuestion->text</label>
								<label>Вес показателя:</label>
								<label>$objQuestion->rate</label>
								<label>Варианты ответа:</label>
								$strOptionsList
							</fieldset>
						</form>
						</p>
QUESTION_LIST;
						$i++;
					}
					return $strQuestionsList;
				}
				else
				{
					//блок вопросов еще только редактируется поэтому выводим вопросы,
					// которые сохранены в сеансе и кнопку для добавления нового вопроса
					//определяем количество вопросов уже сохраненных в сеансе
					$numQuestions =  $arrEditedInterview['cluster']['num_questions'];
					$strQuestionsList = "";
										
					for ($i = 0; $i < $numQuestions; $i++)
					{
						$strQuestionsList .= $this->displayQuestion($i);
					}
					//вставляем кнопку для добавления нового вопроса
					$strQuestionsList .=<<<NEW_QUESTION_BUTTON
					<a href="editQuestion.php" class="admin">Добавить вопрос в дегустационный лист</a>
NEW_QUESTION_BUTTON;
					
					return $strQuestionsList;
				}
			}
		}
	}
	
	/**
	 * Метод возвращает разметку для отображения вопроса с кнопкой для перехода
	 * к редактированию
	 *
	 * @param int: идентификатор вопрос в сеансе
	 * @return string: HTML-разметка
	 */
	public function displayQuestion($id)
	{
		//получаем вопрос из сеанса
		$objQuestion = $_SESSION['edited_interview']['cluster']['questions'][$id];
		
		//создаем разметку для вывода вариантов ответа
		$strOptionList = "";
		$arrOptions = $objQuestion->arrResponseOptions;
		
		print_r($arrOptions);
		for ($i = 0; $i < NUM_OF_OPTIONS; $i++)
		{
			$num = $i + 1;
			$option = $arrOptions[$i];
			$strOptionList .=<<<OPTION_LIST
			<label>$num</label>
			<label>$option->text</label>
OPTION_LIST;
		}
		
		$questionNumber = $id + 1;
		return <<<QUESTION_VIEW
	<p>
	<form action="editQuestion.php" method="post">
		<legend>Вопрос №$questionNumber</legend>
		<fieldset>
			<label>$objQuestion->text</label>
			<label>Вес показателя:</label>
			<label>$objQuestion->rate</label>
			<label>Варианты ответа:</label>
			$strOptionList
			<input type="hidden" name="question_id" value="$id"/>
			<input type="hidden" name="token" value="$_SESSION[token]" />
			<input type="submit" name="question_submit" value="Редактировать" />
		</fieldset>
	</form>
	</p>
QUESTION_VIEW;
	}

	
	/**
	 * Генерирует форму, позволяющую редактировать вопрос или создавать новый в сеансе.
	 *
	 * @return string: HTML-разметка формы для редактирования 
	 * вопроса
	 */
	public function displayQuestionForm()
	{
		/*
		 * Инициализировать переменную, хранящую текст надписи на 
		 * кнопке отправки формы
		 */
		$strSubmit = "Добавить вопрос в дегустационный лист";
		
		/*
		 * Проверить, был ли передан идентификатор вопроса
		 */
		if ( isset($_POST['question_id']) )
		{
			$id = (int) $_POST['question_id'];
			$questionNumber = $id + 1;
			/*
			 * Извлекаем объект вопроса из сеансовой переменной
			 * по переданному идентификатору
			 */
			$objQuestion = $_SESSION['edited_interview']['cluster']['questions'][$id];
			$strQuestionId = "<input type=\"hidden\" name=\"question_id\" value=\"$id\"/>\n\r";
			
			$strSubmit = "Сохранить изменения";
		}
		else
		{
			$id = NULL;
		}
		
		/*
		 * Создать разметку для вариантов ответа
		 */
		$strOptionList = "";
		$arrOptions = $objQuestion->arrResponseOptions;
		if (is_array($arrOptions) )
		{
			//если вопрос редактируется
			for ($i = 0; $i < NUM_OF_OPTIONS; $i++)
			{
				$number = $i + 1;
				$option = $arrOptions[$i];
				$strOptionsList .=<<<OPTION_FORM
				<label for="option$i">$number.</label>
				<input type="text" name="option$i"
					id="option$i" value="$option->text"/>
OPTION_FORM;
			}
		}
		else
		{
			//если создается новый вопрос
			for ($i = 0; $i < NUM_OF_OPTIONS; $i++)
			{
				$number = $i + 1;
				$strOptionsList .=<<<OPTION_FORM
				<label for="option$i">$number.</label>
				<input type="text" name="option$i"
					id="option$i" value=""/>
OPTION_FORM;
			}
		}
			
		return <<<QUESTION_FORM
		<form action="assets/inc/process.inc.php" method="post">
		<legend>Вопрос №$questionNumber</legend>
		<fieldset>
			<label for="question_text">Текст вопроса:</label>
			<input type="text" name="question_text" 
				id="question_rate" value="$objQuestion->text"/>
			<label for="question_rate">Вес показателя:</label>
			<input type="text" name="question_rate" 
				id="question_rate" value="$objQuestion->rate"/>
			<label>Варианты ответа:</label>
			$strOptionsList
			$strQuestionId
			<input type="hidden" name="action" value="question_edit" />
			<input type="hidden" name="token" value="$_SESSION[token]" />
			<input type="submit" name="question_submit" value="$strSubmit" />
			<a href="editInterview.php" class="admin">Отмена</a>
		</fieldset>
	</form>
QUESTION_FORM;
	}
	
	/**
	 * Метод осуществляет запись в сеанс информацию о новом или
	 * измененном вопросе
	 *
	 * @return mixed: TRUE в случае успешного завершения или 
	 * сообщение об ошибке в случае сбоя
	 */
	public function processQuestionForm()
	{
		/*
		 * Выход, если значение "action" задано неправильно
		 */
		if ($_POST['action'] !== 'question_edit' )
		{
			return "Некорректная попытка вызова метода processQuestionForm";
		}
		
		/*
		 * извлечь данные из формы
		 */
		$strQuestionText = htmlentities($_POST['question_text'], ENT_QUOTES);
		$strQuestionRate = htmlentities($_POST['question_rate'], ENT_QUOTES);
		
		/*
		 *извлечь варианты ответа на вопрос из формы
		 */
		$options = array();
		for ($i = 0; $i < NUM_OF_OPTIONS; $i++)
		{
			$options[$i] = array('responseOption_text' => htmlentities($_POST['option'.$i], ENT_QUOTES));
		}
		
		$arrQuestion = array(
			'text' => $strQuestionText,
			'rate' => $strQuestionRate,
			'options' => $options
		);
		
		/*
		 * Создать объект вопроса
		 */
		$objQuestion = Question::createQuestion($arrQuestion);
		
		/*
		 * Сохранить объект в сеансе, увеличивая счетчик на единицу
		 * если вопрос новый
		 */
		
		if ( !isset($_SESSION['edited_interview']['cluster']['questions']) )
		{
			$_SESSION['edited_interview']['cluster']['questions'] = array();
			$_SESSION['edited_interview']['cluster']['num_questions'] = 0;
		}
		
		if ( isset($_POST['question_id'] ) )
		{
			//вопрос редактировался пользователем, поэтому обновляем его в сеансе
			$id = $_POST['question_id'];
		}
		else
		{
			//добавлен еще один вопрос
			$id = (int)$_SESSION['edited_interview']['cluster']['num_questions']++;
		}
		
		$_SESSION['edited_interview']['cluster']['questions'][$id] = $objQuestion;
				
		return TRUE;
	}
	
	/**
	 * Метод возвращает массив объектов класса Question принадлежащих блоку 
	 * вопросов с заданным идентификатором
	 *
	 * @param int $clusterId
	 * @return array
	 */
	private function _getQuestionListObjByClusterId($clusterId)
	{
		/*
		 * Получить идентификаторы вопросов из базы даннных
		 */
		$strQuery = "SELECT
						`questions`.`question_id`
					FROM `questions`
					WHERE `questions`.`cluster_id` = $clusterId";
						
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
				
			$arrQuestions = array();
			$i = 0;
			foreach($arrResults as $elem )
			{
				try
				{
					$arrQuestions[$i++] = Question::getQuestionById($elem['question_id'], $this->_objDB);
				}
				catch ( Exception $e )
				{
					die ($e->getMessage() );
				}
			}			
			return $arrQuestions;
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
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
					WHERE `clusters`.`cluster_id`
					IN (
						SELECT 
							`interviews`.`cluster_id`
						FROM `interviews`
						WHERE `interviews`.`interview_type` = ".M_PROFIL
					.")";
						
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			
			$strClusterList = "<select name=\"cluster_id\">\n\t<option disabled selected>Выберите вид дегустационного листа</option>";
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
			$arrEditedInterview = $_SESSION['edited_interview'];
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
					<a href="choiseProduct.php" class="admin">Добавить образец в дегустационный лист</a>
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
		
		$arrEditedInterview = array(
					'interview_name' => $strInterviewName,
					'interview_type' => $intInterviewType
			);
		
		/*
		 * Сохраняем данные в сеансе
		 */
		$_SESSION['edited_interview'] = $arrEditedInterview;
		
		return TRUE;
	}
	
	/**
	 * Метод возвращает список всех дегустационных листов зарегистрированных в системе
	 * и кнопку для смены текущего дегустационного листа 
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
			
			$strCurInterview = $this->displayCurInterview();
									
			return <<<CHANGE_INTERVIEW
	<form action="assets/inc/process.inc.php" method="post">
		<fieldset>
			<label>Текущий дегустационный лист: $strCurInterview</label>
			$strInterviewList
			<input type="hidden" name="action" value="change_cur_interview" />
			<input type="hidden" name="token" value="$_SESSION[token]" />
			<input type="submit" name="taster_submit" value="Сделать текущим" />
			<a href="admin.php" class="admin">Отмена</a>
		</fieldset>
	</form>
CHANGE_INTERVIEW;
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
		
		return $arrInterview['interview_name'];
	}
}
 
?>