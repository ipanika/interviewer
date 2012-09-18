CREATE DATABASE IF NOT EXISTS `interviewer`
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_unicode_ci;# 1 row affected.

	
/*FOREIGN KEY (prod_id) REFERENCES product(prod_id)*/

/*таблица для хранения блоков впросов*/
CREATE TABLE IF NOT EXISTS `interviewer`.`clusters` (
	`cluster_id`		INT(11) NOT NULL AUTO_INCREMENT,
	`cluster_name`		VARCHAR(80),
	
	PRIMARY KEY (`cluster_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;# MySQL returned an empty result set (i.e. zero rows).



/*таблица для хранения дегустационных листов*/
CREATE TABLE IF NOT EXISTS `interviewer`.`interviews` (
	`interview_id`		INT(11) NOT NULL AUTO_INCREMENT,
	`interview_name`	VARCHAR(100) DEFAULT NULL,
	`interview_type`	INT(1),
	`cluster_id`		INT(11),
	
	PRIMARY KEY (`interview_id`),
	FOREIGN KEY (`cluster_id`) REFERENCES clusters(`cluster_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;# MySQL returned an empty result set (i.e. zero rows).


/*таблица для хранения вопросов*/
CREATE TABLE IF NOT EXISTS `interviewer`.`questions` (
	`question_id`		INT(11) NOT NULL AUTO_INCREMENT,
	`cluster_id`		INT(11),
	`question_text`		TEXT,
	`question_rate`		FLOAT UNSIGNED,
	`question_numAns`	TINYINT UNSIGNED,
	
	PRIMARY KEY (`question_id`),
	FOREIGN KEY (`cluster_id`) REFERENCES clusters(`cluster_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;# MySQL returned an empty result set (i.e. zero rows).


/*таблица для хранения варантов ответа*/
CREATE TABLE IF NOT EXISTS `interviewer`.`responseOptions` (
	`responseOption_id` 		INT(11) NOT NULL AUTO_INCREMENT,
	`question_id`			INT(11),
	`responseOption_text`		VARCHAR(100),
	`responseOption_num`		INT(3),
	responseOption_isCorrect 	BOOL,
	PRIMARY KEY (`responseOption_id`),
	FOREIGN KEY (`question_id`) REFERENCES questions(`question_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;# MySQL returned an empty result set (i.e. zero rows).


/*таблица для хранения образцов продукции*/
CREATE TABLE IF NOT EXISTS `interviewer`.`products` (
	`product_id`	INT(11) NOT NULL AUTO_INCREMENT,
	`product_name`	VARCHAR(100),
	
	PRIMARY KEY (`product_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;# MySQL returned an empty result set (i.e. zero rows).


/* таблица обеспечивающивающая связь конкретного образца продукции
 * с конкретным дегустационным листом*/
CREATE TABLE IF NOT EXISTS `interviewer`.`interview_product` (
	`interview_product_id`	INT(11) NOT NULL AUTO_INCREMENT,
	`interview_id`			INT(11),
	`product_id`			INT(11),
	
	PRIMARY KEY (`interview_product_id`),
	FOREIGN KEY (`interview_id`) REFERENCES interviews(`interview_id`),
	FOREIGN KEY (`product_id`) REFERENCES products(`product_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;# MySQL returned an empty result set (i.e. zero rows).


/*таблица для хранения данных о дегустаторе*/
CREATE TABLE IF NOT EXISTS `interviewer`.`tasters` (
	`taster_id`		INT(11) NOT NULL AUTO_INCREMENT,
	`taster_surname`	VARCHAR(80),
	`taster_name`	VARCHAR(80),
	`taster_sex`	CHAR(1),
	
	PRIMARY KEY (`taster_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;# MySQL returned an empty result set (i.e. zero rows).


/*таблица для хранения ответов данных дегустатором*/
CREATE TABLE IF NOT EXISTS `interviewer`.`answers` (
	`answer_id`				INT(11) NOT NULL AUTO_INCREMENT,
	`taster_id`				INT(11),
	`interview_product_id`	INT(11),
	`responseOption_id`		INT(11),
	`ts`					DATETIME,
	`comment`				TEXT,
	
	PRIMARY KEY (`answer_id`),
	FOREIGN KEY (`taster_id`) REFERENCES tasters(`taster_id`),
	FOREIGN KEY (`interview_product_id`) REFERENCES interview_product(`interview_product_id`),
	FOREIGN KEY (`responseOption_id`) REFERENCES responseOptions(`responseOption_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;# MySQL returned an empty result set (i.e. zero rows).


/*начальное заполнение таблиц данными*/

/*список образцов продукции*/
INSERT INTO `interviewer`.`products`
	(`product_name`) VALUES
	('карамель "Апельсин"'),
	('карамель "Яблоко"'),
	('карамель "Вишня"'),
	('карамель "Клубника"');

/*добавление блока вопросов*/
INSERT INTO `interviewer`.`clusters`
	(`cluster_name`) VALUES
	('Карамель');# 1 row affected.

/*добавление дегустационного листов*/
INSERT INTO `interviewer`.`interviews`
	(
		`interview_name`,	/* VARCHAR(100) DEFAULT NULL, */
		`interview_type`,	/* INT(1), */
		`cluster_id`		/* INT(11), */
	) VALUES
	(
		'Сравнительная дегустация фруктовых карамелек',
		1, /*тип профильный метод имеет идентификатор 1*/
		1
	);# 1 row affected.

/*добавления списка вопросов*/
INSERT INTO `interviewer`.`questions`
	(	
		`cluster_id`,
		`question_text`,
		`question_rate`,
		`question_numAns`
	) VALUES 
	(
		1,
		'Общее впечатление о продукте',
		3.5,
		3
	),
	(
		1,
		'Сладость',
		1.5,
		3
	),
	(
		1,
		'Послевкусие',
		3.5,
		3
	);
	
/*добавление вариантов ответов*/
INSERT INTO `interviewer`.`responseOptions`
	(
		`question_id`,
		`responseOption_text`,
		`responseOption_num`,
		responseOption_isCorrect
	) VALUES
	(
		1,
		'Не нравится',
		1,
		NULL
	),
	(
		1,
		'Отчасти нравится, отчасти нет',
		2,
		NULL
	),
	(
		1,
		'Нравится',
		3,
		NULL
	),
	(
		2,
		'Слишком сладкая',
		1,
		NULL
	),
	(
		2,
		'Такая, как нужно',
		2,
		NULL
	),
	(
		2,
		'Слишком сильная',
		3,
		NULL
	),
	(
		3,
		'Слишком слабое',
		1,
		NULL
	),
	(
		3,
		'Такое, как нужно',
		2,
		NULL
	),
	(
		3,
		'Слишком сильное',
		3,
		NULL
	);
	
/*обеспечиваем связь между образцами продукции и дегустационным листом*/
INSERT INTO `interviewer`.`interview_product` 
	(`interview_id`, `product_id`) VALUES 
	(1, 1),
	(1, 2),
	(1, 3),
	(1, 4);
	

/*таблица для хранения данных о дегустаторе*/
INSERT INTO `interviewer`.`tasters` 
	( `taster_surname`, `taster_name`, `taster_sex` ) VALUES
	( 'Иванов', 'Сергей', 'М'),
	('Петров','Василий', 'М');
	
	
/* таблица для хранения данных о текущем опросе*/
CREATE TABLE IF NOT EXISTS `interviewer`.`current_interviews` (
	`current_interview_id`		INT(11) NOT NULL AUTO_INCREMENT,
	`current_interview_date`	DATETIME,
	`interview_id`				INT(11),
	
	PRIMARY KEY (`current_interview_id`),
	FOREIGN KEY (`interview_id`) REFERENCES interviews(`interview_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

INSERT INTO `interviewer`.`current_interviews`
	( `current_interview_date`, `interview_id` ) VALUES
	( '2012-09-17 8:26:00', 1 );
	
/*
 текущий опрос в системе
*/
SELECT * /*`interviews`.`interview_type`*/
FROM `interviews`
WHERE `interviews`.`interview_id`
IN (

SELECT `current_interviews`.`interview_id`
FROM `current_interviews`
ORDER BY `current_interviews`.`current_interview_date` DESC
)
LIMIT 1;