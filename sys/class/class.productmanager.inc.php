﻿<?php

/**
 * Обеспечивает работу с образцами продукции: создание в системе, 
 * редактирование данных об образце
 */
class ProductManager extends DB_Connect
{
	/**
	 * Создает объект базы
	 * 
	 * При создании экземпляра этого класса конструктор принимает
	 * объект базы данных в качестве параметра. Если значение этого 
	 * параметра отлично от null, оно сохраняется в закрытом 
	 * свойстве $_objDB. Если же это значение равно null, то вместо
	 * этого создается и сохраняется новый PDO-объект.
	 *
	 * @param object $dbo:
	 * @return void
	 */
	public function __construct($dbo=NULL)
	{
		/**
		 * Вызвать конструктор родительского класса для проверки
		 * существования объекта базы данных
		 */
		parent::__construct($dbo);
	}
	
	/**
	 * Возвращает форму для выбора одного образца продукции из 
	 * выпадающего списка. Список формируется из всех образцов 
	 * зарегистрированных в системе
	 *
	 * @return string: HTML-разметка
	 */
	public function buildProductList()
	{
		/*
		 * Получаем список всех образцов продуктов зарегистрированных в системе
		 */
		$arrProducts = $this->_createProductObj(); 
		
		/*
		 * Создать HTML-разметку выпадающего списка дегустаторов
		 */
		$strProductList = "<select name=\"product_id\">\n\r";
		foreach ( $arrProducts as $objProduct )
		{
			$strProductList .= "<option value=\"$objProduct->id\">$objProduct->name</option>\n\r";
		}
		$strProductList .="</select>";
		return <<<PRODUCT_LIST_FORM
		<form action="assets/inc/process.inc.php" method="post">
		<fieldset>
			$strProductList
			<input type="hidden" name="action" value="product_choise" />
			<input type="hidden" name="token" value="$_SESSION[token]" />
			<input type="submit" name="taster_submit" value="Добавить" />
			<a href="editInterview.php" class="admin">Отмена</a>
		</fieldset>
		</form>
PRODUCT_LIST_FORM;
	}
	
	/**
	 * Генерирует форму, позволяющую редактировать данные о
	 * дегустаторе или создавать нового в системе.
	 *
	 * @return string: HTML-разметка формы для редактирования 
	 * информации о дегустаторе
	 */
	public function displayProductForm()
	{
		/**
		 * Проведить, был ли передан идентификатор
		 */
		if ( isset($_POST['product_id']) )
		{
			// Принудительно задать целочисленный тип для
			// обеспечения безопасности данных
			$id = (int) $_POST['product_id'];
		}
		else
		{
			$id = NULL;
		}
		
		/**
		 * Если был передан ID, загрузить соотвествующую информацию
		 */
		if ( !empty($id) )
		{
			$objProduct = $this->getProductById($id);
			
			/**
			 * Если не был возвращен объект, возвратить NULL 
			 */
			if ( !is_object($objProduct) )
			{
				return NULL;
			}
		}
		
		/**
		 * Создать разметку
		 */
		return <<<FORM_MARKUP
	<form action="assets/inc/process.inc.php" method="post">
		<fieldset>
			<label for="product_name">Название образца продукции:</label>
			<input type="text" name="product_name" 
				id="product_name" value="$objProduct->surname"/>
			<input type="hidden" name="product_id" value="$objProduct->id"/>
			<input type="hidden" name="action" value="product_edit" />
			<input type="hidden" name="token" value="$_SESSION[token]" />
			<input type="submit" name="taster_submit" value="Сохранить" />
			<a href="choiseProduct.php" class="admin">Отмена</a>
		</fieldset>
	</form>
FORM_MARKUP;
	}
	
	public function processProductForm()
	{
		/*
		 * Выход, если значение "action" задано неправильно
		 */
		if ($_POST['action'] !== 'product_edit' )
		{
			return "Некорректная попытка вызова метода processProductForm";
		}
		
		/*
		 * извлечь данные из формы
		 */
		$strName = htmlentities($_POST['product_name'], ENT_QUOTES);
		
		/*
		 * Если id не был передан, создать новый образец продукции в системе
		 */
		if ( empty($_POST['product_id']) )
		{
			$strQuery = "INSERT INTO `products`
							(`product_name`)
						VALUES
							(:name)";
		}
		/*
		 * Обновить информацию об образце, если она редактировалась
		 */
		else
		{
			// Привести id образца к целочисленному типу в интересах
			// безопасности
			$id = (int) $_POST['product_id'];
			$strQuery = "UPDATE `products`
						SET
							`product_name`=:name
						WHERE `taster_id`=$id";
		}
		
		/*
		 * После привязки данных выполнить запрос создания или 
		 * редактирования информации о дегустаторе
		 */
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->bindParam(":name", $strName, PDO::PARAM_STR);
			$stmt->execute();
			$stmt->closeCursor();
			
			return TRUE;
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}
	}
	
	/**
	 * Загружает информацию об образцах продукции в массив
	 * 
	 * @param int $id: необязательный идентификатор (ID),
	 * используемый для фильтрации результатов
	 * @return array: массив образцов, извлеченных из базы данных
	 */
	private function _loadProductData($id=NULL)
	{
		$strQuery = "SELECT
						`product_id`,
						`product_name`
					FROM `products`";
		
		/*
		 * Если предоставлен идентификатор дегустатора, добавить предложение
		 * WHERE, чтобы запрос возвращал только это событие
		 */
		if ( !empty($id) )
		{
			$strQuery .= "WHERE `product_id`=:id LIMIT 1";
		}
		
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			
			/*
			 * Привязать параметр, если был передан идентификатор
			 */
			if ( !empty($id) )
			{
				$stmt->bindParam(":id", $id, PDO::PARAM_INT);
			}
			
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			
			return $arrResults;
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
		}
	}
	
	/**
	 * Загружает все образцы продукции, зарегистрированных в системе, в массив
	 * 
	 * @return array: информация о дегустаторах
	 */
	private function _createProductObj($id=NULL)
	{
		/*
		 * Загрузить массив информации о дегустаторах
		 */
		$arrProducts = $this->_loadProductData($id);
		
		/*
		 * Создать новый массив объектов
		 */
		$arrObjProducts = array();
		$i = 0;
		foreach( $arrProducts as $product )
		{
			try
			{
				$arrObjProducts[$i++] = new Product($product);
			}
			catch ( Exception $e )
			{
				die ($e->getMessage() );
			}
		}
		return $arrObjProducts;
	}
	
	/**
	 * Возвращает объект образца продукции
	 *
	 * @param int $id: идентификатор образца
	 * @return object: объект образца
	 */
	public function getProductById($id)
	{
		/*
		 * Если идентификатор не передан, возвратить NULL
		 */
		if ( empty($id) )
		{
			return NULL;
		}
		
		/*
		 * Загрузить данные об образце в массив
		 */
		$arrProduct = $this->_loadProductData($id);
		
		/*
		 * Возвратить объект дегустатора
		 */
		if ( isset($arrProduct[0]) )
		{
			return new Product($arrProduct[0]);
		}
		else
		{
			return NULL;
		}
	}
}
 
?>