<?php

/**
 * Хранит информацию об образце продукции
 */
class Product
{
	/**
	 * Идентификатор образца
	 * 
	 * @var int
	 */
	public $id;
	
	/**
	 * Название образца
	 * 
	 * @var string
	 */
	public $name;
	
	/**
	 * Идентификатор группы кондитерских изделий
	 * к которой принадлежит образец
	 * 
	 * @var int
	 */
	public $productGroupId;
	
	/**
	 * Идентификатор выпускающего предприятия
	 * 
	 * @var int
	 */
	public $enterpriseId;
	
	
	/**
	 * Принимает массив данных об образце продукции
	 *
	 * @param array $arrProduct
	 * @return void
	 */
	public function __construct($arrProduct)
	{
		if ( is_array($arrProduct) )
		{
			$this->id = $arrProduct['product_id'];
			$this->name = $arrProduct['product_name'];
			$this->productGroupId = $arrProduct['productgroup_id'];
			$this->enterpriseId = $arrProduct['enterprise_id'];
		}
		else
		{
			throw new Exception("Не были предоставлены данные об образце продукции.");
		}
	}
}

?>