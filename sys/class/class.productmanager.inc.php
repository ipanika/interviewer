<?php

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
	 * @param int: идентификатор образца
	 * @return string: HTML-разметка
	 */
	public function buildProductList($enterpriseId)
	{
	
		// попытка получить идентификатор предприятия если он не был передан в параметрах вызова
		if ( empty($enterpriseId) )
		{
			$enterpriseId = $_SESSION['edited_interview']['enterprise']['enterprise_id'];
		}
		/*
		 * Получаем список всех образцов продуктов зарегистрированных в системе
		 */
		$arrProducts = $this->_createProductObj($enterpriseId); 
		
		/*
		 * Создать HTML-разметку выпадающего списка образцов продукции
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
	 * Генерирует форму, позволяющую создавать новый образец продукции
	 * в системе.
	 *
	 * @return string: HTML-разметка формы для редактирования 
	 * информации об образце
	 */
	public function displayProductForm()
	{
		/*
		 * Получить выпадающий список групп кондитерских изделий
		 */
		$objProductGroupManager = new ProductGroupManager($this->_objDB);
		$strProductGroupList = $objProductGroupManager->getDropDownList();
		
		/*
		 * Получить выпадающий список выпускающих предприятий
		 */
		$objEnterpriseManager = new EnterpriseManager($this->_objDB);
		$strEnterpriseList = $objEnterpriseManager->getDropDownList();
			
		/**
		 * Создать разметку
		 */
		return <<<FORM_MARKUP
	<form action="assets/inc/process.inc.php" method="post" enctype="multipart/form-data">
		<fieldset>
			<label for="product_name">Название образца продукции:</label>
			<input type="text" name="product_name" 
				id="product_name" value=""/>
			<label>Группа кондитерских изделий:</label>
			$strProductGroupList
			<label>Выпускающее предприятие:</label>
			$strEnterpriseList
			<label>Документы связанные с образцом:</label>
			<input name="file[]" type="file" accept="image/jpeg,image/png,image/gif">
			<input name="file[]" type="file" accept="image/jpeg,image/png,image/gif">
			<input name="file[]" type="file" accept="image/jpeg,image/png,image/gif">
			<input name="file[]" type="file" accept="image/jpeg,image/png,image/gif">
			<input type="hidden" name="action" value="product_edit" />
			<input type="hidden" name="token" value="$_SESSION[token]" />
			<input type="submit" name="taster_submit" class="add_new_product" value="Сохранить" />
			<a href="choiseProduct.php" class="admin add_new_product_cancel">Отмена</a>
		</fieldset>
	</form>
FORM_MARKUP;
	}
	
	/**s
	 * Метод возвращает разметку для отображения списка зарегистрированнных 
	 * в системе продуктов
	 *
	 * @return string: HTML-строка
	 */
	public function buildProductListForDelete()
	{
		/*
		 * получить все продукты, зарегистрированные в системе 
		 * в виде массива объектов
		 */
		$arrProducts = $this->_getProductList();
		
		$strSubmit = "Удалить выбранные продукты";
		
		$strProductList = "<form action=\"assets/inc/process.inc.php\" method=\"post\"<legend>Список зарегистрированных продуктов:</legend>\n\t<ul>\n";
		$strProductList .= "<input type=\"hidden\" name=\"action\" value=\"product_delete\" />\t";
		$strProductList .= "<input type=\"hidden\" name=\"token\" value=\"$_SESSION[token]\" />";
		
		$i = 1;
		foreach($arrProducts as $objProduct)
		{
			
			$strProductList .= "<li>\t<input type=\"checkbox\" name=\"product_id_form[]\" value=$objProduct->id> $objProduct->name</li>\n\t";
			$i++;
		}
		$strProductList .= "</ul><input type=\"submit\" name=\"product_submit\" value=\"$strSubmit\" /></form>";
		
		return $strProductList;
	}
	
	
	
	/*
	* Метод осуществляет удаление выбранных продуктов
	*
	*@return string: сообщение о результате
	*/
	public function delProducts()
	{
		// если можно удалить все группы - результат выполнения функции должен быть Истина
		$strRes = TRUE;
		//строка запроса для получеения списка продуктов, участвующих в опросах
		$strQuery = "SELECT DISTINCT 
						`product_id` 
					FROM `interview_product` ";
		
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
				
			for ($i = 0; $i < count($_POST['product_id_form']); $i++)
			{	
				
				$exist = array();
				//проверка на возможность удаления
				foreach($arrResults as $elem)
				{
					if ($_POST['product_id_form'][$i] == $elem['product_id'])
					{
						$exist[$i] = TRUE;
					}
				}
				
				foreach($arrResults as $elem)
				{
					if (!$exist[$i])
					{
						$strQuery = "DELETE FROM `products` WHERE `product_id` = ".$_POST['product_id_form'][$i];
											
						try
						{
							$stmt = $this->_objDB->prepare($strQuery);
							$stmt->execute();
							$stmt->closeCursor();
							
						}
						catch ( Exception $e )
						{
							die ($e->getMessage());
						}
					}
					else
					{	
						$strRes = FALSE;
						print_r("Удаление невозможно - продукт уже используется в опросе");
					}
				}
			}
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
		}
		
		return $strRes;
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
		$productGroupId = (int)$_POST['productgroup_id'];
		$enterpriseId =  (int)$_POST['enterprise_id'];
		
			$strQuery = "INSERT INTO `products`
							(
								`product_name`,
								`productgroup_id`,
								`enterprise_id`
							)
						VALUES
							(
								:name,
								$productGroupId,
								$enterpriseId
							)";
		
		
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
			
			/*
			 * Получить идентификатор только что созданного продукта
			 */
			$id = $this->_objDB->lastInsertId();
			/*
			 * Создать директорию для хранения связанных с образцом документов
			 */
			$strDirName = '../../../uploads/product'.$id;
			mkdir($strDirName, 0777);
			
			/*
			 * Скопировать загруженные файлы в каталог продукта
			 */
			for ($i=0; $i<count($_FILES['file']['tmp_name']);$i++)
			{
				move_uploaded_file($_FILES['file']['tmp_name'][$i], $strDirName.'/'. $_FILES['file'] ['name'][$i]);
			}
			
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
	private function _loadProductData($id=NULL, $enterpriseId=NULL)
	{
		$strQuery = "SELECT
						`product_id`,
						`product_name`,
						`productgroup_id`,
						`enterprise_id`
					FROM `products`";
					
		// накладываемые условия на выборку
		$strCondition1 = "1";
		$strCondition2 = "1";
		
		/*
		 * Если передан идентификатор предприятия добавить в условие отбора
		 */
		if ( !empty($enterpriseId) )
		{
			$strCondition1 = " `enterprise_id` = $enterpriseId";
		}
		/*
		 * Если предоставлен идентификатор образца , добавить предложение
		 * WHERE, чтобы запрос возвращал только один объект
		 */
		if ( !empty($id) )
		{
			$strCondition2 = "`product_id`= $id LIMIT 1";
		}
		
		$strQuery .= "\nWHERE $strCondition1 AND $strCondition2";
		
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			
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
	 * Загружает образцы продукции, зарегистрированных в системе, в массив
	 * при этом накладывается фильтр по выпускающему предприятию
	 * 
	 * @param int: идентификатор выпускающего предприятия
	 * @return array: информация о образцах продукции
	 */
	private function _createProductObj($enterpriseId)
	{
		/*
		 * Загрузить массив информации о образцах
		 */
		$arrProducts = $this->_loadProductData(NULL,$enterpriseId);
		
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
	
	
	/**
	 * Метод возвращает массив объектов класса Product зарегистрированных в системе
	 *
	 * @return array
	 */
	private function _getProductList($id=NULL)
	{
		/*
		 * Получить идентификаторы и названия групп изделий из базы даннных
		 */
		$strQuery = "SELECT 
						`product_id`, 
						`product_name` 
					FROM `products`";
		
		/*
		 * Если передан идентификатор, добавить условие
		 */
		if ( $id != NULL )
		{
			$strQuery .= "\nWHERE `product_id` = $id
						 LIMIT 1";
		}
						
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
				
			$arrProducts = array();
			$i = 0;
			foreach($arrResults as $elem )
			{
				try
				{
					$arrProducts[$i++] = new Product($elem);
				}
				catch ( Exception $e )
				{
					die ($e->getMessage() );
				}
			}			
			return $arrProducts;
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
		}
	}
}
 
?>