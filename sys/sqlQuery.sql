CREATE DATABASE IF NOT EXISTS `interviewer`
	DEFAULT CHARACTER SET utf8
	COLLATE utf8_unicode_ci;

/*таблица для хранения блоков впросов*/
CREATE TABLE IF NOT EXISTS `interviewer`.`clusters` (
	`cluster_id`		INT(11) NOT NULL AUTO_INCREMENT,
	`cluster_name`		VARCHAR(80),
	`cluster_numQuestions` INT(3),
	`cluster_type`		INT(1),
	
	PRIMARY KEY (`cluster_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

/* таблица для хранения данных о текущем опросе*/
CREATE TABLE IF NOT EXISTS `interviewer`.`current_interviews` (
	`current_interview_id`		INT(11) NOT NULL AUTO_INCREMENT,
	`current_interview_date`	DATETIME,
	`interview_id`				INT(11),
	
	PRIMARY KEY (`current_interview_id`),
	FOREIGN KEY (`interview_id`) REFERENCES interviews(`interview_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

/*таблица для хранения групп кондитерских изделий*/
CREATE TABLE IF NOT EXISTS `interviewer`.`productgroups` (
	`productgroup_id`		INT(11) NOT NULL AUTO_INCREMENT,
	`productgroup_name`	VARCHAR(100) DEFAULT NULL,
	
	PRIMARY KEY (`productgroup_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

/*таблица для хранения дегустационных листов*/
CREATE TABLE IF NOT EXISTS `interviewer`.`interviews` (
	`interview_id`		INT(11) NOT NULL AUTO_INCREMENT,
	`interview_name`	VARCHAR(100) DEFAULT NULL,
	`interview_type`	INT(1),
	`cluster_id`		INT(11),
    `enterprise_id`		INT(11),
	`interview_date`	DATE,
	
	PRIMARY KEY (`interview_id`),
	FOREIGN KEY (`cluster_id`) REFERENCES clusters(`cluster_id`),
        FOREIGN KEY (`enterprise_id`) REFERENCES enterprises(`enterprise_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;


/*таблица для хранения вопросов*/
CREATE TABLE IF NOT EXISTS `interviewer`.`questions` (
	`question_id`		INT(11) NOT NULL AUTO_INCREMENT,
	`cluster_id`		INT(11),
	`question_text`		TEXT,
	`question_rate`		FLOAT UNSIGNED,
	`question_numAns`	TINYINT UNSIGNED,
	`question_type`		INT(1),
	
	PRIMARY KEY (`question_id`),
	FOREIGN KEY (`cluster_id`) REFERENCES clusters(`cluster_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `interviewer`.`activequestions` (
	`activequestions_id`	INT(11) NOT NULL AUTO_INCREMENT,
	`interview_id`		INT(11),
	`question_id`		INT(11),
	
	PRIMARY KEY (`activequestions_id`),
	FOREIGN KEY (`interview_id`) REFERENCES interviews(`cluster_id`),
        FOREIGN KEY (`question_id`) REFERENCES questions(`question_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;


/*таблица для хранения варантов ответа*/
CREATE TABLE IF NOT EXISTS `interviewer`.`responseOptions` (
	`responseOption_id` 		INT(11) NOT NULL AUTO_INCREMENT,
	`question_id`			INT(11),
	`responseOption_text`		VARCHAR(100),
	`responseOption_num`		INT(3),
	responseOption_isCorrect 	BOOL,
	PRIMARY KEY (`responseOption_id`),
	FOREIGN KEY (`question_id`) REFERENCES questions(`question_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;


/*таблица для хранения образцов продукции*/
CREATE TABLE IF NOT EXISTS `interviewer`.`products` (
	`product_id`	INT(11) NOT NULL AUTO_INCREMENT,
	`product_name`	VARCHAR(100),
    `productgroup_id`	INT(11),
    `enterprise_id`	INT(11),
	
	PRIMARY KEY (`product_id`),
        FOREIGN KEY (`productgroup_id`) REFERENCES productgroups(`productgroup_id`),
        FOREIGN KEY (`enterprise_id`) REFERENCES enterprises(`enterprise_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

/*таблица для хранения выпускающих предприятий*/
CREATE TABLE IF NOT EXISTS `interviewer`.`enterprises` (
	`enterprise_id`	INT(11) NOT NULL AUTO_INCREMENT,
	`enterprise_name`	VARCHAR(100),
	
	PRIMARY KEY (`enterprise_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;


/* таблица обеспечивающивающая связь конкретного образца продукции
 * с конкретным дегустационным листом*/
CREATE TABLE IF NOT EXISTS `interviewer`.`interview_product` (
	`interview_product_id`	INT(11) NOT NULL AUTO_INCREMENT,
	`interview_id`			INT(11),
	`product_id`			INT(11),
	
	PRIMARY KEY (`interview_product_id`),
	FOREIGN KEY (`interview_id`) REFERENCES interviews(`interview_id`),
	FOREIGN KEY (`product_id`) REFERENCES products(`product_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;


/*таблица для хранения данных о дегустаторе*/
CREATE TABLE IF NOT EXISTS `interviewer`.`tasters` (
	`taster_id`			INT(11) NOT NULL AUTO_INCREMENT,
	`taster_surname`	VARCHAR(80),
	`taster_name`		VARCHAR(80),
	`taster_sex`		CHAR(1),
    `taster_date_birth` DATETIME,
	`taster_preffered`	VARCHAR(80),
	`taster_residense`	VARCHAR(80),
	`taster_allergy`	VARCHAR(80),
	`taster_work`		VARCHAR(80),
	`taster_in_research`VARCHAR(80),
	`taster_scale_from` INT(11),
	`taster_scale_to` 	INT(11),
	
	PRIMARY KEY (`taster_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

/*таблица для хранения ответов данных дегустатором*/
CREATE TABLE IF NOT EXISTS `interviewer`.`answers` (
	`answer_id`				INT(11) NOT NULL AUTO_INCREMENT,
	`taster_id`				INT(11),
	`interview_product_id`	INT(11),
	`responseOption_id`		INT(11),
	`question_id`			INT(11),
	`ts`					DATETIME,
	`comment`				TEXT,
	
	PRIMARY KEY (`answer_id`),
	FOREIGN KEY (`taster_id`) REFERENCES tasters(`taster_id`),
	FOREIGN KEY (`interview_product_id`) REFERENCES interview_product(`interview_product_id`),
	FOREIGN KEY (`responseOption_id`) REFERENCES responseOptions(`responseOption_id`),
	FOREIGN KEY (`question_id`) REFERENCES questions(`question_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;


/*таблица для хранения порядка следования образцов в методе треугольника*/
CREATE TABLE IF NOT EXISTS `interviewer`.`productorders` (
	`productorder_id`	INT(11) NOT NULL AUTO_INCREMENT,
	`interview_id`		INT(11),
	`pos1`				CHAR(1),
	`pos2`				CHAR(1),
	`pos3`				CHAR(1),
	
	PRIMARY KEY (`productorder_id`),
	FOREIGN KEY (`pos1`) REFERENCES tasters(`taster_id`),
	FOREIGN KEY (`interview_id`) REFERENCES interviews(`interview_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

/*таблица для хранения ответов данных дегустатором на вопросы метода треугольника*/
CREATE TABLE IF NOT EXISTS `interviewer`.`trianganswers` (
	`trianganswer_id`		INT(11) NOT NULL AUTO_INCREMENT,
	`interview_id`			INT(11),
	`taster_id`				INT(11),
	`product_id`			INT(11),
	`ts`					DATETIME,
	`comment`				TEXT,
	
	PRIMARY KEY (`trianganswer_id`),
	FOREIGN KEY (`taster_id`) REFERENCES tasters(`taster_id`),
	FOREIGN KEY (`product_id`) REFERENCES products(`product_id`),
	FOREIGN KEY (`interview_id`) REFERENCES interviews(`interview_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;


/*таблица для хранения данных пользователей-администраторов*/
CREATE TABLE IF NOT EXISTS `interviewer`.`users` (
	`user_id`	INT(11) NOT NULL AUTO_INCREMENT,
	`user_name`	VARCHAR(100),
    `user_pass`	VARCHAR(100),
	PRIMARY KEY (`user_id`)
) ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_unicode_ci;

/* единственный пользователь: admin 123456
*/
INSERT INTO `interviewer`.`users`
		(
			`user_name`,
			`user_pass`
		)
		VALUES
		(
			'admin',
			'123456'
		);