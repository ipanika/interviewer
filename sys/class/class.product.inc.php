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
	 * Текст ответа
	 * 
	 * @var string
	 */
	public $name;
	
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
		}
		else
		{
			throw new Exception("Не были предоставлены данные об образце продукции.");
		}
	}
}

?>