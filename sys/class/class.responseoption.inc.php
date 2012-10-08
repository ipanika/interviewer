<?php

/**
 * Хранит информацию о варианте ответа
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
	 * Номер варианта ответа
	 *
	 * @var int
	 */
	public $num;
	
	/**
	 * Указывает правильный вариант ответа 
	 *
	 * @var bool
	 */
	public $isCorrect;
	 
	/**
	 * Принимает массив данных о варианте ответа и сохраняет его
	 *
	 * @param array $arrResponseOption
	 * @return void
	 */
	public function __construct($arrResponseOption)
	{
		if ( is_array($arrResponseOption) )
		{
			$this->id = $arrResponseOption['responseOption_id'];
			$this->text = $arrResponseOption['responseOption_text'];
			$this->num = $arrResponseOption['responseOption_num'];
			$this->isCorrect = (bool)$arrResponseOption['responseOption_isCorrect'];
		}
		else
		{
			throw new Exception("Не были предоставлены данные о варианте ответа.");
		}
	}
}

?>