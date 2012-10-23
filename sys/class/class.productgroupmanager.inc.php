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
		
		$strGroupList = "<label><strong>Список групп кондитерских изделий</strong></label>\n<ul>";
		
		foreach($arrGroups as $objProductGroup)
		{
			$strGroupList .= "<li>$objProductGroup->name</li>";
		}
		$strGroupList .= "</ul>";
		
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
	 * Метод возвращает разметку для отображения вопроса с кнопкой для перехода
	 * к редактированию
	 *
	 * @param int: идентификатор вопрос в сеансе
	 * @return string: HTML-разметка
	 */
	public function displayQuestion($id)
	{
		//получаем вопрос из сеанса
		$objQuestion = $_SESSION['edited_interview']['cluster']['questions'][$id];
		
		if ( $_SESSION['edited_interview']['interview_type'] == M_PROFIL )
		{
			//создаем разметку для вывода вариантов ответа
			$strOptionList = "";
			$arrOptions = $objQuestion->arrResponseOptions;
			
			for ($i = 0; $i < NUM_OF_OPTIONS; $i++)
			{
				$num = $i + 1;
				$option = $arrOptions[$i];
				$strOptionList .=<<<OPTION_LIST
				<label>$num</label>
				<label>$option->text</label>
OPTION_LIST;
			}
		}
		if ( $_SESSION['edited_interview']['interview_type'] == M_COMPLX )
		{
			$strOptionList = "<label>Шкала ответа от 1 до 7.</label>";
		}
		$questionNumber = $id + 1;
		return <<<QUESTION_VIEW
	<p>
	<form action="editQuestion.php" method="post">
		<legend>Вопрос №$questionNumber</legend>
		<fieldset>
			<label>$objQuestion->text</label>
			<label>Вес показателя:</label>
			<label>$objQuestion->rate</label>
			<label>Варианты ответа:</label>
			$strOptionList
			<input type="hidden" name="question_id" value="$id"/>
			<input type="hidden" name="token" value="$_SESSION[token]" />
			<input type="hidden" name="action" value="edit_question" />
			<input type="submit" name="question_submit" class="quest" value="Редактировать" />
		</fieldset>
	</form>
	</p>
QUESTION_VIEW;
	}

	
	/**
	 * Генерирует форму, позволяющую редактировать вопрос или создавать новый в сеансе.
	 *
	 * @return string: HTML-разметка формы для редактирования 
	 * вопроса
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
	 * Метод осуществляет запись в базу данных информации о новом
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
		
}
 
?>