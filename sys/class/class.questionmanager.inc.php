<?php

/**
 * Обеспечивает работу с вопросами
 */
class QuestionManager extends DB_Connect
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
	 * Метод возвращает разметку для отображения списка вопросов
	 *
	 * @return string: HTML-строка
	 */
	public function getQuestionList()
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
					$arrQuestions = $this->getQuestionListObjByClusterId($arrEditedInterview['cluster']['cluster_id']);
					
					/*
					 * создать разметку для списка вопросов
					 */
					$strQuestionsList = "";
					$i = 1;
					foreach( $arrQuestions as $objQuestion )
					{
						if ( $arrEditedInterview['interview_type'] == M_PROFIL)
						{
							$strOptionsList = "<label>Варианты ответа:</label>";
							foreach ( $objQuestion->arrResponseOptions as $option )
							{
								$strOptionsList .=<<<OPTION_LIST
								<label>$option->num</label>
								<label>$option->text</label>
OPTION_LIST;
							}
						}
						if ( $arrEditedInterview['interview_type'] == M_COMPLX )
						{
							$strOptionsList = "<label>Шкала ответа от 1 до 7.</label>";
						}
						$strQuestionsList .=<<<QUESTION_LIST
						<p>
						<form action="editQuestion.php" method="post">
							<legend>Вопрос №$i</legend>
							<fieldset>
								<label>$objQuestion->text</label>
								<label>Вес показателя:</label>
								<label>$objQuestion->rate</label>
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
					<a href="editQuestion.php" class="admin quest">Добавить вопрос в дегустационный лист</a>
NEW_QUESTION_BUTTON;
					
					return $strQuestionsList;
				}
			}
			else if ($arrEditedInterview['interview_type'] == M_COMPLX) 
			{
				/*
				 * Ищем в базе данных блок вопросов составленный по методу комплексной оценки
				 */
				$strQuery = "SELECT 
								`cluster_id`
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
						'num_questions' => 0
					);
				}
				else
				{
					$cluster = array(
						'num_questions' => 0
					);
				}
			
			$_SESSION['edited_interview']['cluster'] = $cluster;
			return <<<NEW_QUESTION_BUTTON
					<label>Вопросы для комплексной оценки еще не были созданы</label>
					<a href="editQuestion.php" class="admin quest">Добавить вопрос в дегустационный лист</a>
NEW_QUESTION_BUTTON;
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
	public function getQuestionListObjByClusterId($clusterId)
	{
		/*
		 * Получить идентификаторы вопросов из базы даннных
		 */
		$strQuery = "SELECT
						`questions`.`question_id`
					FROM `questions`
					WHERE `questions`.`cluster_id` = $clusterId
					ORDER BY `question_id`";
						
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
		
		if ( $_SESSION['edited_interview']['interview_type'] == M_PROFIL )
		{
			//создаем разметку для вывода вариантов ответа
			$strOptionList = "";
			$arrOptions = $objQuestion->arrResponseOptions;
			
			for ($i = 0; $i < NUM_OF_OPTIONS; $i++)
			{
				$num = $i + 1;
				$option = $arrOptions[$i];
				$strOptionList .=<<<OPTION_LIST
				<label>$num</label>
				<label>$option->text</label>
OPTION_LIST;
			}
		}
		if ( $_SESSION['edited_interview']['interview_type'] == M_COMPLX )
		{
			$strOptionList = "<label>Шкала ответа от 1 до 7.</label>";
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
			<input type="hidden" name="action" value="edit_question" />
			<input type="submit" name="question_submit" class="quest" value="Редактировать" />
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
		
		if ( $_SESSION['edited_interview']['interview_type'] == M_PROFIL )
		{
			/*
			 * Создать разметку для вариантов ответа
			 */
			$strOptionsList = "<label>Варианты ответа:</label>";
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
				$strOptionsList = "<label>Варианты ответа:</label>";
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
		}
		if ( $_SESSION['edited_interview']['interview_type'] == M_COMPLX )
		{
			$strOptionsList = "<label>Шкала ответа от 1 до 7 баллов.</lable>";
		}
			
		return <<<QUESTION_FORM
		<form action="assets/inc/process.inc.php" method="post">
		<legend>Вопрос №$questionNumber</legend>
		<fieldset>
			<label for="question_text">Текст вопроса:</label>
			<textarea name="question_text" 
				id="question_text">$objQuestion->text</textarea>
			<label for="question_rate">Вес показателя:</label>
			<input type="text" name="question_rate" 
				id="question_rate" value="$objQuestion->rate"/>
			$strOptionsList
			$strQuestionId
			<input type="hidden" name="action" value="question_edit" />
			<input type="hidden" name="token" value="$_SESSION[token]" />
			<input type="submit" name="question_submit" value="$strSubmit" />
			<a href="editInterview.php">Отмена</a>
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
		$strQuestionRate = str_replace(",", ".", htmlentities($_POST['question_rate'], ENT_QUOTES));
		
		/*
		 *извлечь варианты ответа на вопрос из формы
		 */
		$options = array();
		if ( $_SESSION['edited_interview']['interview_type'] == M_PROFIL )
		{
			for ($i = 0; $i < NUM_OF_OPTIONS; $i++)
			{
				$options[$i] = array('responseOption_text' => htmlentities($_POST['option'.$i], ENT_QUOTES));
			}
		}
		if ( $_SESSION['edited_interview']['interview_type'] == M_COMPLX )
		{
			for ($i = 0; $i < NUM_OF_OPTIONS; $i++)
			{
				$options[$i] = array('responseOption_text' => '');
			}
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
		
		/*
		 * Возвратить ID события
		 */
		//return $id;
				
		return TRUE;
	}
	
	
	
}
 
?>