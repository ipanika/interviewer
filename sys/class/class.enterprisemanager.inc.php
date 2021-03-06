<?php

/**
 * Обеспечивает работу с предприятиями
 */
class EnterpriseManager extends DB_Connect
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
	 * в системе предприятий
	 *
	 * @return string: HTML-строка
	 */
	public function buildEnterpriseList()
	{
		/*
		 * получить все группы изделий зарегистрированных в системе 
		 * в виде массива объектов
		 */
		$arrEnterprises = $this->_getEnterpriseList();
		
		$strSubmit = "Удалить выбранные предприятия";
		
		$strEnterpriseList = "<form action=\"assets/inc/process.inc.php\" method=\"post\"<legend>Список выпускающих предприятий:</legend>\n\t<ul>\n";
		$strEnterpriseList .= "<input type=\"hidden\" name=\"action\" value=\"enterprise_delete\" />\t";
		$strEnterpriseList .= "<input type=\"hidden\" name=\"token\" value=\"$_SESSION[token]\" />";
		
		$i = 1;
		foreach($arrEnterprises as $objEnteprise)
		{
			
			$strEnterpriseList .= "<li>\t<input type=\"checkbox\" name=\"enterprise_id_form[]\" value=$objEnteprise->id> $objEnteprise->name</li>\n\t";
			$i++;
		}
		$strEnterpriseList .= "</ul><input type=\"submit\" name=\"enterprise_submit\" value=\"$strSubmit\" /></form>";
		
		return $strEnterpriseList;
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
		$arrEnterprises = $this->_getEnterpriseList();
		
		$strEnterpriseList = "<select name=\"enterprise_id\">\n\r";
		foreach ( $arrEnterprises as $objEnteprise )
		{
			$strEnterpriseList .= "<option value=\"$objEnteprise->id\">$objEnteprise->name</option>\n\r";
		}
		$strEnterpriseList .="</select><br>";
		
		return $strEnterpriseList;
	}
	
	
	/**
	 * Метод возвращает массив объектов класса Enterprise зарегистрированных в системе
	 *
	 * @return array
	 */
	private function _getEnterpriseList($id=NULL)
	{
		/*
		 * Получить идентификаторы и названия групп изделий из базы даннных
		 */
		$strQuery = "SELECT 
						`enterprise_id`, 
						`enterprise_name` 
					FROM `enterprises`";
		
		/*
		 * Если передан идентификатор, добавить условие
		 */
		if ( $id != NULL )
		{
			$strQuery .= "\nWHERE `enterprise_id` = $id
						 LIMIT 1";
		}
						
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
				
			$arrEnterprises = array();
			$i = 0;
			foreach($arrResults as $elem )
			{
				try
				{
					$arrEnterprises[$i++] = new Enterprise($elem);
				}
				catch ( Exception $e )
				{
					die ($e->getMessage() );
				}
			}			
			return $arrEnterprises;
		}
		catch ( Exception $e )
		{
			die ( $e->getMessage() );
		}
	}
	
	/**
	 * Возвращает объект Enterprise с заданным идентификотором
	 *
	 * @param int: идентификатор объекта
	 * @return obj
	 */
	public function getEnterpriseById($id)
	{
		return $this->_getEnterpriseList($id)[0];
	}
	
	
	
	
	/**
	 * Генерирует форму, позволяющую новую группу изделий в системе.
	 *
	 * @return string: HTML-разметка формы для редактирования 
	 */
	public function displayEnterpriseForm()
	{
		/*
		 * Инициализировать переменную, хранящую текст надписи на 
		 * кнопке отправки формы
		 */
		$strSubmit = "Сохранить новое предприятие";
		
		return <<<PRODUCT_GROUP_FORM
	<form action="assets/inc/process.inc.php" method="post">
	<fieldset>
		<label for="enterprise_name">Название выпускающего предприятия:</label>
		<input type="text" name="enterprise_name" 
			id="enterprise_name" value=""/>
		<input type="hidden" name="action" value="enterprise_edit" />
		<input type="hidden" name="token" value="$_SESSION[token]" />
		<input type="submit" name="enterprise_submit" value="$strSubmit" />
		<a href="enterpriseList.php" class="button">Отмена</a>
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
	public function processEnterpriseForm()
	{
		/*
		 * Выход, если значение "action" задано неправильно
		 */
		if ($_POST['action'] !== 'enterprise_edit' )
		{
			return "Некорректная попытка вызова метода processEnterpriseForm";
		}
		
		/*
		 * извлечь данные из формы
		 */
		$strEnterpriseName = htmlentities($_POST['enterprise_name'], ENT_QUOTES);
		
		$strQuery = "INSERT INTO `enterprises`
						(`enterprise_name`)
					VALUES
						(:name)";
				
		/*
		 * После привязки данных выполнить запрос создания предприятия
		 */
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->bindParam(":name", $strEnterpriseName, PDO::PARAM_STR);
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
	* Метод осуществляет удаление выпускающих предприятий
	*
	*@return string: сообщение о результате
	*/
	public function delEnterprise()
	{
		// если можно удалить все группы - результат выполнения функции должен быть Истина
		$strRes = TRUE;
		//строка запроса для получеения списка групп, участвующих в опросах
		$strQuery = "SELECT DISTINCT 
						`enterprise_id` 
					FROM `interviews` ";
		
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
				
			for ($i = 0; $i < count($_POST['enterprise_id_form']); $i++)
			{	
				
				$exist = array();
				//проверка на возможность удаления
				foreach($arrResults as $elem)
				{
					if ($_POST['enterprise_id_form'][$i] == $elem['enterprise_id'])
					{
						$exist[$i] = TRUE;
					}
				}
				
				
					if (!$exist[$i])
					{
						$strQuery = "DELETE FROM `products` WHERE `enterprise_id` = ".$_POST['enterprise_id_form'][$i];
						$strQuery2 = "DELETE FROM `enterprises` WHERE `enterprise_id` = ". $_POST['enterprise_id_form'][$i];
						
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
						$strRes = "Удаление невозможно - предприятие уже используется в опросе";
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