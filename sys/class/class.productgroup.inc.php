<?php

/**
 * Хранит информацию об образце продукции
 */
class ProductGroup
{
	/**
	 * Идентификатор группы изделий
	 * 
	 * @var int
	 */
	public $id;
	
	/**
	 * Название группы изделий
	 * 
	 * @var string
	 */
	public $name;
	
	/**
	 * Принимает массив данных о группе изделий
	 *
	 * @param array $arrProductGroup
	 * @return void
	 */
	public function __construct($arrProductGroup)
	{
		if ( is_array($arrProductGroup) )
		{
			$this->id = $arrProductGroup['productgroup_id'];
			$this->name = $arrProductGroup['productgroup_name'];
		}
		else
		{
			throw new Exception("Не были предоставлены данные о группе кондитерских изделий.");
		}
	}
}

?>