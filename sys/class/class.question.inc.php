<?php

/**
 * Хранит информацию о дегустаторе 
 */
class Question extends DB_Connect
{
	/**
	 * Идентификатор вопроса
	 * 
	 * @var int
	 */
	public $id;
	
	/**
	 * Вес показателя
	 * 
	 * @var real
	 */
	public $rate;
	
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
	public $arrResponseOptions
	
	/**
	 * Принимает массив данных о вопросе и сохраняет его.
	 * Так же получает информацию о вариантах ответа.
	 *
	 * @param array $arrQuestion
	 * @param object $objDB
	 * @return void
	 */
	public function __construct($arrQuestion, $objDB=NULL)
	{
		/*
		 * Вызвать конструктор родительского класса для проверки
		 * существования объекта базы данных
		 */
		parent::__construct($dbo);
		
		if ( is_array($arrQuestion) )
		{
			$this->id = $arrQuestion['question_id'];
			$this->surname = $arrQuestion['question_rate'];
			$this->name = $arrQuestion['question_numAns'];
			
			/*
			 * Получить из базы данных все варианты ответа на данный вопрос
			 */
			$strQuery = "SELECT 
							`responseoptions`.`responseOption_id`,
							`responseoptions`.`responseOption_text`,
							`responseoptions`.`responseOption_num`,
							`responseoptions`.`responseOption_isCorrect`
						FROM `responseoptions`
						WHERE `responseoptions`.`question_id` = $this->id";
			try
			{
				$stmt = $this->_objDB->prepare($strQuery);
			
				$stmt->execute();
				$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$stmt->closeCursor();
			
				/*
				 * Созадать новый массив объектов
				*/
				$arrResponseOptions = array();
				$i = 0;
				foreach( $arrResults as $option )
				{
					try
					{
						$arrResponseOptions[$i++] = new ResponseOption($option);
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
		else
		{
			throw new Exception("Не были предоставлены данные о вопросе.");
		}
	}
}

?>