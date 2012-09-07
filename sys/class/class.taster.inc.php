<?php

/**
 * Хранит информацию о дегустаторе 
 */
class Taster
{
	/**
	 * Идентификатор дегустатора
	 * 
	 * @var int
	 */
	public $id;
	
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