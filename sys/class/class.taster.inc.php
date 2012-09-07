<?php

/**
 * Хранит информацию о дегустаторе 
 */
class Taster extends DВ_Connect
{
	/**
	 * Идентификатор дегустатора
	 * 
	 * @var int
	 */
	public $id
	
	/**
	 * Определяет фамилию дегустатора
	 * 
	 * @var string
	 */
	public $surname;
	
	/**
	 * Определяет имя дегустатора
	 *
	 * @var string
	 */
	public $name;
	
	/**
	 * Определяет пол дегустатора
	 *
	 * @var string: "м" или "ж"
	 */
	public $sex;
	 
	/**
	 * Принимает массив данных о дегустаторе и сохраняет его
	 */
	public function __construct($arrTaster)
	{
		if ( is_array($arrTaster) )
		{
			$this->id = $arrTaster['taster_id'];
			$this->surname = $arrTester['taster_surname'];
			$this->name = $arrTester['taster_name'];
			$this->sex = $arrTester['taster_sex'];
		}
		else
		{
			throw new Exception("Не были предоставлены данные о дегустаторе.");
		}
	}
}

?>