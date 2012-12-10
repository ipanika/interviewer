<?php

/**
 * Обеспечивает работу с группами кондитерских изделий
 */
class ProductGroupManager extends DB_Connect
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
	 * @param int $type: определяем один из 4-х видов дегустационных листов 
	 * @param object $dbo: объект базы данных
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
	 * Метод возвращает разметку для отображения списка зарегистрированнных 
	 * в системе групп кондитерских изделий
	 *
	 * @return string: HTML-строка
	 */
	public function buildProductGroupList()
	{
		/*
		 * получить все группы изделий зарегистрированных в системе 
		 * в виде массива объектов
		 */
		$arrGroups = $this->_getProductGroupList();
		
		$strSubmit = "Удалить выбранные группы";
		
		$strGroupList = "<form action=\"assets/inc/process.inc.php\" method=\"post\"><legend>Список групп кондитерских изделий</legend>\n\t<ul>\n";
		$strGroupList .= "<input type=\"hidden\" name=\"action\" value=\"productgroup_delete\" />\t";
		$strGroupList .= "<input type=\"hidden\" name=\"token\" value=\"$_SESSION[token]\" />";
		
		$i = 1;
		foreach($arrGroups as $objProductGroup)
		{
			$strGroupList .= "<li>\t<input type=\"checkbox\" name=\"productgroup_id_form[]\" value=$objProductGroup->id> $objProductGroup->name</li>\n\t";
			$i++;
		}
		$strGroupList .= "</ul><input type=\"submit\" name=\"productgroup_submit\" value=\"$strSubmit\" /></form>";
		
		return $strGroupList;
	}
	
	/**
	 * Метод возвращает разметку для отображения выпадающего списка  
	 * зарегистрированнных в системе групп кондитерских изделий
	 *
	 * @return string: HTML-строка
	 */
	public function getDropDownList()
	{
		/*
		 * получить все группы изделий зарегистрированных в системе 
		 * в виде массива объектов
		 */
		$arrGroups = $this->_getProductGroupList();
		
		$strGroupList = "<select name=\"productgroup_id\">\n\r";
		foreach ( $arrGroups as $objGroup )
		{
			$strGroupList .= "<option value=\"$objGroup->id\">$objGroup->name</option>\n\r";
		}
		$strGroupList .="</select><br>";
		
		return $strGroupList;
	}
	
	
	/**
	 * Метод возвращает массив объектов класса Group зарегистрированных в системе
	 *
	 * @return array
	 */
	private function _getProductGroupList()
	{
		/*
		 * Получить идентификаторы и названия групп изделий из базы даннных
		 */
		$strQuery = "SELECT 
						`productgroup_id`, 
						`productgroup_name` 
					FROM `productgroups`";
						
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
				
			$arrProductGroups = array();
			$i = 0;
			foreach($arrResults as $elem )
			{
				try
				{
					$arrProductGroups[$i++] = new ProductGroup($elem);
				}
				catch ( Exception $e )
				{
					die ($e->getMessage() );
				}
			}			
			return $arrProductGroups;
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
		}
	}
	
	
	
	/**
	 * Генерирует форму, позволяющую новую группу изделий в системе.
	 *
	 * @return string: HTML-разметка формы для редактирования 
	 */
	public function displayProductGroupForm()
	{
		/*
		 * Инициализировать переменную, хранящую текст надписи на 
		 * кнопке отправки формы
		 */
		$strSubmit = "Сохранить группу кондитерских изделий";
		
		return <<<PRODUCT_GROUP_FORM
	<form action="assets/inc/process.inc.php" method="post">
	<fieldset>
		<label for="productgroup_name">Название группы изделий:</label>
		<input type="text" name="productgroup_name" 
			id="productgroup_name" value=""/>
		<input type="hidden" name="action" value="productgroup_edit" />
		<input type="hidden" name="token" value="$_SESSION[token]" />
		<input type="submit" name="productgroup_submit" value="$strSubmit" />
		<a href="groupList.php" class="button">Отмена</a>
	</fieldset>
	</form>
PRODUCT_GROUP_FORM;
	}
	
	/**
	 * Метод осуществляет запись в базу данных информации о новой
	 * группе кондитерских изделий
	 *
	 * @return mixed: TRUE в случае успешного завершения или 
	 * сообщение об ошибке в случае сбоя
	 */
	public function processProductGroupForm()
	{
		/*
		 * Выход, если значение "action" задано неправильно
		 */
		if ($_POST['action'] !== 'productgroup_edit' )
		{
			return "Некорректная попытка вызова метода processProductGroupForm";
		}
		
		/*
		 * извлечь данные из формы
		 */
		$strGroupName = htmlentities($_POST['productgroup_name'], ENT_QUOTES);
		
		$strQuery = "INSERT INTO `productgroups`
						(`productgroup_name`)
					VALUES
						(:name)";
				
		/*
		 * После привязки данных выполнить запрос создания группы изделий
		 */
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->bindParam(":name", $strGroupName, PDO::PARAM_STR);
			$stmt->execute();
			$stmt->closeCursor();
			
			return TRUE;
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}
	}
	
	/*
	* Метод осуществляет удаление групп кондитерских изделий
	*
	*@return string: сообщение о результате
	*/
	public function delProductGroups()
	{
		// если можно удалить все группы - результат выполнения функции должен быть Истина
		$strRes = TRUE;
		//строка запроса для получеения списка групп, участвующих в опросах
		$strQuery = "SELECT DISTINCT 
						`productgroup_id` 
					FROM `products` 
					LEFT JOIN `interview_product` 
						ON `products`.`product_id` = `interview_product`.`product_id`";
		
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			
			for ($i = 0; $i < count($_POST['productgroup_id_form']); $i++)
			{	
				
				$exist = array();
				//проверка на возможность удаления
				foreach($arrResults as $elem)
				{
					if ($_POST['productgroup_id_form'][$i] == $elem['productgroup_id'])
					{
						$exist[$i] = TRUE;
					}
				}
				foreach($arrResults as $elem)
				{
					if (!$exist[$i])
					{
						$strQuery = "DELETE FROM `products` WHERE `productgroup_id` = ".$_POST['productgroup_id_form'][$i];
						$strQuery2 = "DELETE FROM `productgroups` WHERE `productgroup_id` = ". $_POST['productgroup_id_form'][$i];
						
						try
						{
							$stmt = $this->_objDB->prepare($strQuery);
							$stmt->execute();
							$stmt->closeCursor();
							$stmt = $this->_objDB->prepare($strQuery2);
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
						$strRes ="Удаление невозможно - группа уже используется в опросе";
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
		
}
 
?>