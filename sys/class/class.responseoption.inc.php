<?php

/**
 * Хранит информацию о дегустаторе 
 */
class ResponseOption
{
	/**
	 * Идентификатор варианта ответа
	 * 
	 * @var int
	 */
	public $id;
	
	/**
	 * Текст ответа
	 * 
	 * @var string
	 */
	public $text;
	
	/**
	 * Количество вариантов ответа на вопрос
	 *
	 * @var int
	 */
	public $numAns;
	
	/**
	 * Список вариантов ответа
	 *
	 * @var array: "м" или "ж"
	 */
	public $sex;
	 
	/**
	 * Принимает массив данных о дегустаторе и сохраняет его
	 *
	 * @param array $arrTaster
	 * @return void
	 */
	public function __construct($arrTaster)
	{
		if ( is_array($arrTaster) )
		{
			$this->id = $arrTaster['taster_id'];
			$this->surname = $arrTaster['taster_surname'];
			$this->name = $arrTaster['taster_name'];
			$this->sex = $arrTaster['taster_sex'];
		}
		else
		{
			throw new Exception("Не были предоставлены данные о дегустаторе.");
		}
	}
}

?>