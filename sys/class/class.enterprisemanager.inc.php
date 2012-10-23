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
		
		$strEnterpriseList = "<label><strong>Список выпускающих предприятий:</strong></label>\n<ul>";
		
		foreach($arrEnterprises as $objEnteprise)
		{
			$strEnterpriseList .= "<li>$objEnteprise->name</li>";
		}
		$strEnterpriseList .= "</ul>";
		
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
	 * Метод возвращает массив объектов класса Group зарегистрированных в системе
	 *
	 * @return array
	 */
	private function _getEnterpriseList()
	{
		/*
		 * Получить идентификаторы и названия групп изделий из базы даннных
		 */
		$strQuery = "SELECT 
						`enterprise_id`, 
						`enterprise_name` 
					FROM `enterprises`";
						
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
}
 
?>