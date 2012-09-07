<?php

/**
 * Обеспечивает работу с дегустаторами: создание в системе, 
 * редактирование данных о дегустаторе
 */
class TasterManager extends DB_Connect
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
	 * Возвращает HTML-разметку для отображения выпадающего списка 
	 * всех дегустаторов зарегистрированных в системе
	 *
	 * @return string: HTML-разметка
	 */
	public function buildTasterList()
	{
		/*
		 * Получаем список всех дегустаторов зарегистрированных в системе
		 */
		$arrTasters = $this->_createTasterObj(); 
		
		/*
		 * Создать HTML-разметку выпадающего списка дегустаторов
		 */
		$html = "<select name=\"taster_id\">\n\r";
		$html .= "<option disabled>Выберите себя из списка</option>\n\r";
		foreach ( $arrTasters as $objTaster )
		{
			$html .= "<option value=\"$objTaster->id\">$objTaster->surname $objTaster->name</option>\n\r";
		}
		$html .="</select>";
		return $html;
	}
	
	/**
	 * Генерирует форму, позволяющую редактировать данные о
	 * дегустаторе или создавать нового в системе.
	 *
	 * @return string: HTML-разметка фармы для редактирования 
	 * информации о дегустаторе
	 */
	public function displayTasterForm()
	{
		/**
		 * Проведить, был ли передан идентификатор
		 */
		if ( isset($_POST['taster_id']) )
		{
			// Принудительно задать целочисленный тип для
			// обеспечения безопасности данных
			$id = (int) $_POST['taster_id'];
		}
		else
		{
			$id = NULL;
		}
		
		/*
		 * Сгенерировать разметку для выбора пола дегустатора
		 * по умолчанию установлен мужской пол
		 */
		$strSexList = "<select name=\"taster_sex\">
							<option selected value=\"М\">M</option>
							<option value=\"Ж\">Ж</option>
						</select>";
		
		/**
		 * Если был передан ID, загрузить соотвествующую информацию
		 */
		if ( !empty($id) )
		{
			$objTaster = $this->_loadTasterById($id);
			
			/**
			 * Если не был возвращен объект, возвратить NULL 
			 */
			if ( !is_object($objTaster) )
			{
				return NULL;
			}
			
			/*
			 * Изменить разметку для выбора пола дегустатора
			 */
			
			//если дегустатор, данные которого изменяются мужчина ничего не делаем
			if (  $objTaster->sex !== "M" )
			{
				$strSexList = "<select name=\"taster_sex\">
								<option value=\"М\">M</option>
								<option selected value=\"Ж\">Ж</option>
							</select>";
			}
			 
		}
		
		/**
		 * Создать разметку
		 */
		return <<<FORM_MARKUP
	<form action="assets/inc/process.inc.php" method="post"
		<fieldset>
			<label for="taster_surname">Фамилия</label>
			<input type="text" name="taster_surname" 
				id="taster_surmane" value="$objTaster->surname"/>
			<label for="taster_name">Имя</label>
			<input type="text" name="taster_name"
				id="taster_name" value="$objTaster->name"/>
			<label for="taster_sex">Пол</label>
			$strSexList
			<input type="hidden" name="taster_id" value="$objTaster->id"/>
			<input type="hidden" name="action" value="taster_edit" />
			<input type="hidden" name="token" value="$_SESSION[token]" />
			<input type="submit" name="taster_submit" value="Сохранить" />
			или <a href="./">отмена</a>
		</fieldset>
	</form>
FORM_MARKUP;
	}
	
	
	/**
	 *
	 */
	
	/**
	 *
	 */
	
	/**
	 *
	 */
	 
	
	 
	/**
	 * Предназначен для проверки формы и сохранения или редактирования
	 * данных о дегустаторе
	 *
	 * @return mixed: TRUE в случае успешного завершения или 
	 * сообщение об ошибке в случае сбоя
	 */
	public function processTasterForm()
	{
		/*
		 * Выход, если значение "action" задано неправильно
		 */
		if ($_POST['action'] !== 'taster_edit' )
		{
			return "Некорректная попытка вызова метода processTasterForm";
		}
		
		/*
		 * извлечь данные из формы
		 */
		$strSurname = htmlentities($_POST['taster_surname'], ENT_QUOTES);
		$strName = htmlentities($_POST['taster_name'], ENT_QUOTES);
		$strSex = htmlentities($_POST['taster_sex'], ENT_QUOTES);
		
		/*
		 * Если id не был передан, созадать нового дегустатора в системе
		 */
		if ( empty($_POST['taster_id']) )
		{
			$strQuery = "INSERT INTO `tasters`
							(`taster_surname`, `taster_name`,`taster_sex`)
						VALUES
							(:surname, :name, :sex";
		}
		/*
		 * Обновить информацию о дегустаторе, если она редактировалась
		 */
		else
		{
			// Привести id дегустатора к целочисленному типу в интересах
			// безопасности
			$id = (int) $_POST['taster_id'];
			$strQuery = "UPDATE `tasters`
						SET
							`taster_surname`=:surname,
							`taster_name`=:name,
							`taster_sex`=:sex
						WHERE `taster_id`=$id";
		}
		
		/*
		 * После привязки данных выполнить запрос создания или 
		 * редактирования информации о дегустаторе
		 */
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->bindParam(":surname", $strSurname, PDO::PARAM_STR);
			$stmt->bindParam(":name", $strName, PDO::PARAM_STR);
			$stmt->bindParam(":sex", $strSex, PDO::PARAM_STR);
			$stmt->execute();
			$stmt->closeCursor();
			return true;
		}
		catch (Exception $e)
		{
			return $e->getMessage();
		}
	}
	
	/**
	 * Загружает информацию о пользователе (пользователях) в массив
	 * 
	 * @param int $id: необязательный идентификатор (ID),
	 * используемый для фильтрации результатов
	 * @return array: массив дегустаторов, извлеченных из базы данных
	 */
	private function _loadTasterData($id=NULL)
	{
		$strQuery = "SELECT
						`taster_id`,
						`taster_surname`,
						`taster_name`,
						`taster_sex`
					FROM `tasters`";
		
		/*
		 * Если предоставлен идентификатор дегустатора, добавить предложение
		 * WHERE, чтобы запрос возвращал только это событие
		 */
		if ( !empty($id) )
		{
			$strQuery .= "WHERE `taster_id`=:id LIMIT 1";
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
	 * Загружает всех дегустаторов, зарегистрированных в системе, в массив
	 * 
	 * @return array: информация о дегустаторах
	 */
	private function _createTasterObj($id=NULL)
	{
		/*
		 * Загрузить массив информации о дегустаторах
		 */
		$arrTasters = $this->_loadTasterData($id);
		
		/*
		 * Созадать новый массив объектов
		 */
		$arrObjTasters = array();
		$i = 0;
		foreach( $arrTasters as $taster )
		{
			try
			{
				$arrObjTasters[$i++] = new Taster($taster);
			}
			catch ( Exception $e )
			{
				die ($e->getMessage() );
			}
		}
		return $arrObjTasters;
	}
	
	private function _loadTasterById($id)
	{
	}
}
 
?>