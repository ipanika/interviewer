<?php

/**
 * Хранит информацию о предприятии
 */
class Enterprise
{
	/**
	 * Идентификатор предприятия
	 * 
	 * @var int
	 */
	public $id;
	
	/**
	 * Название предприятия
	 * 
	 * @var string
	 */
	public $name;
	
	/**
	 * Принимает массив данных о предприятии
	 *
	 * @param array $arrEnterprise
	 * @return void
	 */
	public function __construct($arrEnterprise)
	{
		if ( is_array($arrEnterprise) )
		{
			$this->id = $arrEnterprise['enterprise_id'];
			$this->name = $arrEnterprise['enterprise_name'];
		}
		else
		{
			throw new Exception("Не были предоставлены данные о предприятии.");
		}
	}
}

?>