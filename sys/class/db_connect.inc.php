<?php

/**
 * Осуществляет подключение к базе данных (доспуп к БД, проверка и т.п.)
 */
class DB_Connect
{
	/**
	 * Переменная для хранения объекта базы данных
	 *
	 * @var object: объект базы данных
	 */
	protected $objDB;
	
	/**
	 * Проверить наличие объекта БД, а в случает его отсутствия
	 * создать новый
	 * 
	 * @param object $dbo: объект базы данных
	 */
	protected function __construct($dbo = NULL)
	{
		if ( is_object($dbo) )
		{
			$this->$objDB = $dbo;
		}
		else
		{
			// Константы определены в файле
			// /sys/config/db-cred.inc.php
			$strDSN = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME;
			try
			{
				$this->objDB = new PDO($strDSN, DB_USER, DB_PASS);
			}
			catch ( Exception $e )
			{
				// Если не удается установить соединение с БД,
				// вывести сообщение об ошибке
				die ( $e->getMessage() );
			}
		}
	}
}
?>