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
		
		$strQuery .= " LIMIT 1";
		
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
						`interviews`.`interview_id`
					FROM `interviews`
					WHERE `interviews`.`interview_id`
					IN (
						SELECT `current_interviews`.`interview_id`
						FROM `current_interviews`
						ORDER BY `current_interviews`.`current_interview_date` DESC
					)
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
		return $strHeader . $strClusterType . $strQuestionList . $strProductList . $strCmdCancel;
		
		if ( isset($_SESSION['edited_interview']) )
		{
			$arrEditedInterview = $_SESSION['edited_interview'];
			$strInterviewName = $arrEditedInterview['interview_name'];
			switch ($arrEditedInterview['interview_type'] )
			{
				case M_TRIANG:
					$strInterviewType = "<label>Метод треугольника</label>";
					break;
				case M_PROFIL:
					$strInterviewType = "<label>Профильный метод</label>";
					break;
				case M_COMPLX:
					$strInterviewType = "<label>Метод комплексной оценки</label>";
					break;
				case M_CONSUM:
					$strInterviewType = "<label>Потребительское тестирование</label>";
					break;
			}
			
			// для профильного метода
			/*
			 * Определяем задан ли блок вопросов
			 */
			if ( isset($arrEditedInterview['cluster']) )
			{
				
			}
			else
			{
				//блок не задан, поэтому выводим приглашение для выбора
				$strClusterList = $this->_getClusterList();
				$strClusterForm = <<<CLUSTER_FORM
				<form action="assets/inc/process.inc.php" method="post">
					$strClusterList\n\t
					<input type="hidden" name="action" value="new_interview" />
					<input type="hidden" name="token" value="$_SESSION[token]" />
					<input type="submit" name="next_submit" value="Продолжить" />
				</form>
CLUSTER_FORM;
			}
			/*
			 * Получаем данные был ли выбран существующий блок вопросов 
			 */
			 
		}
		else
		{
			$strInterviewName = "";
			$strInterviewType = "<select name=\"interview_type\">
									<option value=\"".M_TRIANG."\">Метод треугольника</option>
									<option value=\"".M_PROFIL."\">Профильный метод</option>
									<option value=\"".M_COMPLX."\">Метод комплексной оценки</option>
									<option value=\"".M_CONSUM."\">Потребительское тестирование</option>
								</select>";
		}
		return <<<FORM_MARKUP
	<form action="assets/inc/process.inc.php" method="post" >
		<label for="interview_name">Название дегустационного листа</label>
		<input type="text" name="interview_name"
			id="interview_name" value="$strInterviewName"
		<label>Вариант опроса:</label>
		$strInterviewType
		<input type="hidden" name="action" value="new_interview" />
		<input type="hidden" name="token" value="$_SESSION[token]" />
		<input type="submit" name="next_submit" value="Продолжить" />
	</form>
	$strClusterForm
	<form action="assets/inc/process.inc.php" method="post" >
		<input type="hidden" name="action" value="cancel_edit" />
		<input type="hidden" name="token" value="$_SESSION[token]" />
		<input type="submit" name="cancel_submit" value="Отмена" />
	</form>
FORM_MARKUP;
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
			if ( isset($arrEditedInterview['num_products'] )
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
	<input type="submit" name="next_submit" value="Новый блок вопросов" />
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
						'cluster_numQuestions'=> $arrResults[0]['cluster_numQuestions']);
			$_SESSION['edited_interview']['cluster'] = $arrCluster;
			
			return TRUE;
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
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
					foreach( $arrQuestions as $question )
					{
						$strQuestionsList .=<<<QUESTION_LIST
							<p>
							<label>Вопрос №$i</label>
							<label>Текст вопроса:</label>
							<input type="text" name="question_text"
								id="question_text" value="$question->text" readonly/>
							<label>Вес показателя:</label>
							<input type="text" name="question_rate"
								id="question_rate" value="$question->rate" readonly/>
							<label>Варианты ответа</label>
QUESTION_LIST;
						$i++;
												
						foreach ( $question->arrResponseOptions as $option )
						{
							
							$strQuestionsList .=<<<OPTION_LIST
							<label>$option->num. $option->text</label>
OPTION_LIST;
						}
						$strQuestionsList .= "</p>";
					}
					return $strQuestionsList;
				}
				else
				{
					//блок вопросов еще только редактируется поэтому выводим вопросы,
					// которые сохранены в сеансе и форму для добавления нового вопроса
				}
			}
		}
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
		$strQuery = "SELECT
						`questions`.`question_id`,
						`questions`.`question_text`,
						`questions`.`question_rate`,
						`questions`.`question_numAns`
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
					$arrQuestions[$i++] = new Question($elem, $this->_objDB);
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
				if ( isset($arrEditedInterview['cluster']['cluster_numQuestions'] ) 
						&& $arrEditedInterview['cluster']['cluster_numQuestions'] > 0 )
				{
					//выводим список уже сохраненных в сеансе продуктов
					$strProductList = "<p><label>Образцы продукции:</label>";
					$arrProducts = $arrEditedInterview['products'];
					$i = 1;
					foreach($arrProducts as $product)
					{
						$strProductList .= "<label>$i. $product[name]</label>";
						$i++;
					}
					
					//выводим форму для добавления нового продукта
					$strProductList .= $this->displayProductForm();
					return $strProductList;
				}
			}
		}
	}
	
	/**
	 * Генерирует форму, позволяющую редактировать данные об
	 * образце продукции или создавать новый в системе.
	 *
	 * @return string: HTML-разметка формы для редактирования 
	 * информации об образце продукции
	 */
	public function displayProductForm($id=NULL)
	{
		if ( !empty($id) )
		{
			$objProduct = $this->_loadProductById();
		}
		return <<<PRODUCT_FORM
		<form action="assets/inc/process.inc.php" method="post">
		<fieldset>
			<label for="product_name">Название образца продукции:</label>
			<input type="text" name="product_name" 
				id="product_name" value="$objProduct->name"/>
			<input type="hidden" name="product_id" value="$objProduct->id"/>
			<input type="hidden" name="action" value="product_edit" />
			<input type="hidden" name="token" value="$_SESSION[token]" />
			<input type="submit" name="taster_submit" value="Добавить в дегустационный лист" />
		</fieldset>
	</form>
PRODUCT_FORM;
	}
	
	public function processProductForm()
	{
		/*
		 * Выход, если значение "action" задано неправильно
		 */
		if ($_POST['action'] !== 'product_edit' )
		{
			return "Некорректная попытка вызова метода processProductForm";
		}
		
		/*
		 * извлечь данные из формы
		 */
		$strName = htmlentities($_POST['product_name'], ENT_QUOTES);
		
		/*
		 * Если id не был передан, создать новый образец продукта в системе
		 */
		if ( empty($_POST['product_id']) )
		{
			$strQuery = "INSERT INTO `products`
							(`product_name`)
						VALUES
							(:name)";
		}
		/*
		 * Обновить информацию об образце, если она редактировалась
		 */
		else
		{
			// Привести id образца к целочисленному типу в интересах
			// безопасности
			$id = (int) $_POST['product_id'];
			$strQuery = "UPDATE `products`
						SET
							`product_name`=:name
						WHERE `taster_id`=$id";
		}
		
		/*
		 * После привязки данных выполнить запрос создания или 
		 * редактирования информации о дегустаторе
		 */
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->bindParam(":name", $strName, PDO::PARAM_STR);
			$stmt->execute();
			$stmt->closeCursor();
			
			/*
			 * после сохранения в базе данных сохранить данные об образце в сеансе
			 */
			//сохраняем имя и уникальный идентификатор
			if ( empty($id) )
			{
				//получаем идентификатор только что созданого образца
				$id = $this->_objDB->lastInsertId();
			}
			$arrProduct = array(
				'name' => $strName,
				'id' => $id
			);
			if ( !isset($_SESSION['edited_interview']['products']) )
			{
				$_SESSION['edited_interview']['products'] = array();
				$_SESSION['edited_interview']['num_products'] = 0;
			}
			array_push($_SESSION['edited_interview']['products'], $arrProduct);
			$_SESSION['edited_interview']['num_products']++;
			return true;
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}
	}
	
	private function _loadProductById()
	{
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
}
 
?>