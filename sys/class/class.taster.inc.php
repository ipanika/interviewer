<?php

/**
 * Хранит информацию о дегустаторе 
 */
class Taster
{
	/**
	 * Идентификатор дегустатора
	 * 
	 * @var int
	 */
	public $id;
	
	/**
	 * Определяет фамилию дегустатора
	 * 
	 * @var string
	 */
	public $surname;
	
	/**
	 * Определяет имя дегустатора
	 *
	 * @var string
	 */
	public $name;
	
	/**
	 * Определяет пол дегустатора
	 *
	 * @var string: "м" или "ж"
	 */
	public $sex;
	
	/**
	 * Дата рождения
	 *
	 * @var string: ГГГГ-ММ-ДД
	 */
	public $dateBirth;
	
	/**
	 * Предпочитаемая группа кондитерских изделий
	 *
	 * @var string
	 */
	public $preffered;
	
	/**
	 * Место проживания
	 *
	 * @var string
	 */
	public $residense;
	
	/**
	 * Аллергия
	 *
	 * @var string
	 */
	public $allergy;
	
	/**
	 * Характер работы
	 *
	 * @var string
	 */
	public $work;
	
	/**
	 * Участие в исследованиях
	 *
	 * @var string
	 */
	public $inResearch;
	
	/**
	 * Нижняя граница шкалы дохода в месяц
	 *
	 * @var real
	 */
	public $scaleFrom;
	
	/**
	 * Верхняя граница шкалы дохода в месяц
	 *
	 * @var real
	 */
	public $scaleTo;
	 
	/**
	 * Принимает массив данных о дегустаторе и сохраняет его
	 *
	 * @param array $arrTaster
	 * @return void
	 */
	public function __construct($arrTaster)
	{
		if ( is_array($arrTaster) )
		{
			$this->id = $arrTaster['taster_id'];
			$this->surname = $arrTaster['taster_surname'];
			$this->name = $arrTaster['taster_name'];
			$this->sex = $arrTaster['taster_sex'];
			$this->dateBirth = $arrTaster['taster_date_birth'];
			$this->preffered = $arrTaster['taster_preffered'];
			$this->residense = $arrTaster['taster_residense'];
			$this->allergy = $arrTaster['taster_allergy'];
			$this->work = $arrTaster['taster_work'];
			$this->inResearch = $arrTaster['taster_in_research'];
			$this->scaleFrom = $arrTaster['taster_scale_from'];
			$this->scaleTo = $arrTaster['taster_scale_to'];
		}
		else
		{
			throw new Exception("Не были предоставлены данные о дегустаторе.");
		}
	}
}

?>