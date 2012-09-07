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
	 */
	public function buildTasterList()
	{
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
			<input type="text" name="taster_sex"
				id="taster_sex" value="$objTaster->sex"/>
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
	 *
	 */
	 
	/**
	 * Предназначен для проверки формы и сохранения или редактирования
	 * данных о дегустаторе
	 */
	public function processTasterForm()
	{
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
	}
	
	private function _loadTasterById($id)
	{
	}
}
 
?>