<?php

/**
 * Обеспечивает работу с группами кондитерских изделий
 */
class InterviewManager extends DB_Connect
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
	public function buildInterviewList()
	{
		/*
		 * получить все группы изделий зарегистрированных в системе 
		 * в виде массива объектов
		 */
		$arrInterviews = $this->_getInterviewList();
		
		$strSubmit = "Удалить выбранные дегустационные листы";
		
		$strInterviewList = "<form action=\"assets/inc/process.inc.php\" method=\"post\"<legend>Список дегустационных листов</legend>\n\t<ul>\n";
		$strInterviewList .= "<input type=\"hidden\" name=\"action\" value=\"interview_delete\" />\t";
		$strInterviewList .= "<input type=\"hidden\" name=\"token\" value=\"$_SESSION[token]\" />";
		
		$i = 1;
		foreach($arrInterviews as $objInterview)
		{
			$strInterviewList .= "<li>\t<input type=\"checkbox\" name=\"interview_id_form[]\" value=".$objInterview['interview_id']."> ";
			$strInterviewList .= $objInterview['interview_name']."</li>\n\t";
			$i++;
		}
		$strInterviewList .= "</ul><input type=\"submit\" name=\"interview_submit\" value=\"$strSubmit\" /></form>";
		
		return $strInterviewList;
	}
	
	
	/**
	 * Метод возвращает массив объектов класса Interview зарегистрированных в системе
	 *
	 * @return array
	 */
	private function _getInterviewList()
	{
		/*
		 * Получить идентификаторы и названия групп изделий из базы даннных
		 */
		$strQuery = "SELECT 
						`interview_id`, 
						`interview_name` 
					FROM `interviews`";
						
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
	 * Метод осуществляет удаление дегустационных листов
	 *
	 *@return string: сообщение о результате
	 */
	public function delInterviews()
	{
		// если можно удалить все группы - результат выполнения функции должен быть Истина
		$strRes = TRUE;
		//строка запроса для получеения списка групп, участвующих в опросах
		$strQuery = "SELECT DISTINCT 
						`interview_id` 
					FROM `current_interviews`";
		try
		{
			$stmt = $this->_objDB->prepare($strQuery);
			$stmt->execute();
			$arrResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
				
			for ($i = 0; $i < count($_POST['interview_id_form']); $i++)
			{	
				$exist = array();
				//проверка на возможность удаления
				foreach($arrResults as $elem)
				{
					if ($_POST['interview_id_form'][$i] == $elem['interview_id'])
					{
						$exist[$i] = TRUE;
					}
				}
				foreach($arrResults as $elem)
				{
					if (!$exist[$i])
					{
						$strQuery = "DELETE FROM `interviews` WHERE `interview_id` = ".$_POST['interview_id_form'][$i];
						$strQuery2 = "DELETE FROM `interview_product` WHERE `interview_id` = ".$_POST['interview_id_form'][$i];
																		
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
						$strRes = "Удаление невозможно - по дегустационному листу уже есть данные";
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