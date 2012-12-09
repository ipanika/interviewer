<?php

/**
 * Хранит информацию о вопросе
 */
class Question
{
	/**
	 * Идентификатор вопроса
	 * 
	 * @var int
	 */
	public $id;
	
	/**
	 * Текст вопроса
	 *
	 * @var string
	 */
	public $text;
	
	/**
	 * Вес показателя
	 * 
	 * @var real
	 */
	public $rate;
	
	/**
	* Тип вопроса - открытый или закрытый
	* @var int
	*/
	public $type;
	
	/**
	 * Количество вариантов ответа на вопрос
	 *
	 * @var int
	 */
	public $numAns;
	
	/**
	 * Список вариантов ответа
	 *
	 * @var array
	 */
	public $arrResponseOptions;
	
	/**
	 * Принимает массив данных о вопросе и сохраняет его.
	 * Так же получает информацию о вариантах ответа.
	 *
	 * @param array $arrQuestion
	 * @param object $objDB
	 * @return void
	 */
	public function __construct($objDB=NULL)
	{
		/*
		 * Вызвать конструктор родительского класса для проверки
		 * существования объекта базы данных
		 */
		//parent::__construct($objDB);
	}
	
	/**
	 * Метод создает объект Вопрос по информации переданной в параметре
	 *
	 * @param array: массив содержащий текст вопроса, вес показателя
	 *					мессив вариантов ответа
	 * @return object
	 */
	static function createQuestion($arrQuestion)
	{
		$objQuestion = new self();
		
		$objQuestion->text = $arrQuestion['text'];
		$objQuestion->rate = $arrQuestion['rate'];
		$objQuestion->type = $arrQuestion['type'];
		
		if ($objQuestion->type == Q_OPEN)
		{
			return $objQuestion;
		}
		
		$objQuestion->arrResponseOptions = array();
		for($i = 0; $i < NUM_OF_OPTIONS; $i++ )
		{
			$option = $arrQuestion['options'][$i];
			try
			{
				$objQuestion->arrResponseOptions[$i] = new ResponseOption($option);
			}
			catch ( Exception $e )
			{
				die ($e->getMessage() );
			}
		}
		
		return $objQuestion;
	}
	
	/**
	 * Метод возвращает объект Вопрос сохраненный в базе данных
	 *
	 * @param int: id
	 * @param object: объект базы данных
	 * @return object
	 */
	static function getQuestionById($id, $objDB)
	{
		if ( empty($id) )
		{
			return NULL;
		}
		
		$objQuestion = new self(/*$objDB*/);
		
		/*
		 * Получаем данные о вопросе из базы данных
		 */
		$strQuery = "SELECT 
						`question_text`, 
						`question_rate`,
						question_type
					FROM `questions` 
					WHERE `question_id` = :id
					LIMIT 1";
					
		/*
		 * Проверить что был передан объект базы данных
		 */
		try
		{
			$stmt = $objDB->prepare($strQuery);
			$stmt->bindParam(":id", $id, PDO::PARAM_INT);
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			
			if ( !is_array($arrResults) )
			{
				return NULL;
			}
			
			$objQuestion->id = $id;
			$objQuestion->text = $arrResults[0]['question_text'];
			$objQuestion->rate = $arrResults[0]['question_rate'];
			$objQuestion->type = $arrResults[0]['question_type'];
		
			/*
			 * Получить варианты ответов из базы данных
			 */
			If ($objQuestion->type == Q_OPEN) 
			{
				return $objQuestion;
			}
			
			$strQuery = "SELECT 
							`responseoptions`.`responseOption_id`,
							`responseoptions`.`responseOption_text`,
							`responseoptions`.`responseOption_num`,
							`responseoptions`.`responseOption_isCorrect`
						FROM `responseoptions`
						WHERE `responseoptions`.`question_id` = $id";
			
			
			try
			{
				$stmt = $objDB->prepare($strQuery);
				$stmt->execute();
				$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$stmt->closeCursor();
			
				/*
				 * Созадать новый массив объектов
				 */
				$objQuestion->arrResponseOptions = array();
				$i = 0;
				foreach( $arrResults as $option )
				{
					try
					{
						$objQuestion->arrResponseOptions[$i++] = new ResponseOption($option);
					}
					catch ( Exception $e )
					{
						die ($e->getMessage() );
					}
				}
			}
			catch ( Exception $e )
			{
				die ( $e->getMessage() );
			}
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
		}
		
		return $objQuestion;
	}
}

?>