<?php

/**
 * Управляет выполнением административных задач
 */
class Admin extends DB_Connect
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
	 * Проверяет действительность учетных данных пользователя
	 *
	 * @return mixed: TRUE в случае успешного завершения, иначе 
	 * сообщение об ошибке
	 */
	public function processLoginForm()
	{
		/*
		 * Аварийное завершение, если был отправлен недействительный
		 * атрибут ACTION
		 */
		if ($_POST['action'] != 'user_login' )
		{
			return "В processLoginFrom передано недействительное значение
					атрибута ACTION";
		}
		
		/*
		 * Маскировать пользовательский ввод в целях безопасности
		 */
		$strUName = htmlentities($_POST['uname'], ENT_QUOTES);
		$strPword = htmlentities($_POST['pword'], ENT_QUOTES); 
		
		/*
		 * Извлеч из базы данных совпадающую информацию, если она существует
		 */
		$strQuery = "SELECT
						`user_id`, `user_name`, `user_pass`
					FROM `users`
					WHERE 
						`user_name` = :uname
					LIMIT 1";
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->bindParam(':uname', $strUName, PDO::PARAM_STR);
			$stmt->execute();
			$arrUser = array_shift($stmt->fetchAll());
			$stmt->closeCursor();
		}
		catch ( Exception $e )
		{
			die($e->getMessage() );
		}
		
		/*
		 * Аварийное завершение, если имя пользователя не 
		 * согласуется ни с одной записью в БД
		 */
		if ( !isset($arrUser) )
		{
			return "Пользователя с таким именем не существует в БД.";
		}
		
		/*
		 * Проверить, совпадает ли введенный пароль с сохраненным в БД 
		 */
		if ( $arrUser['user_pass'] == $strPword)
		{
			/*
			 * Сохранить пользовательскую информацию в сеансе в виде массива
			 */
			$_SESSION['user'] = array(
					'id' => $arrUser['user_id'],
					'name' => $arrUser['user_name']
				);
			return TRUE;
		}
		else
		{
			return "Неверное имя пользователя или пароль";
		}	
	}
	
	/**
	 * Завершает сеанс пользователя
	 *
	 * @return mixed: TRUE в случае успешного завершения, иначе 
	 * сообщение об ошибке
	 */
	public function processLogout()
	{
		/*
		 * Аварийное завершение, если был отправлен недействительный
		 * атрибут ACTION
		 */
		if ($_POST['action'] != 'user_logout' )
		{
			return "В processLogout передано недействительное значение
					атрибута ACTION";
		}
		
		/*
		 * Завершить сеанс работы
		 */
		session_destroy();
		return TRUE;
	}
}
 
?>