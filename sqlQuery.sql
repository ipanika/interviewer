SELECT
	COUNT(DISTINCT `answers`.`taster_id`) AS taster_amount
FROM `answers` 
LEFT JOIN `responseoptions` ON `responseoptions`.`responseOption_id` = `answers`.`responseOption_id`
WHERE `answers`.`interview_product_id` 
IN (
SELECT 
`interview_product_id`
FROM `interview_product`
WHERE `interview_id` = 19)


/*Вопросы с вариантами ответов для выбранного дегустационного листа*/
SELECT 
	`interviews`.`interview_id`,
        `questions`.`question_id`,
        `questions`.`question_text`,
        `questions`.`question_rate`,
        `responseoptions`.`responseOption_id`,
        `responseoptions`.`responseOption_num`,
        `responseoptions`.`responseOption_text`
FROM `interviews` 
LEFT JOIN `clusters` ON `clusters`.`cluster_id` = `interviews`.`cluster_id`
LEFT JOIN `questions` ON `questions`.`cluster_id` = `clusters`.`cluster_id`
LEFT JOIN `responseoptions`ON `responseoptions`.`question_id` = `questions`.`question_id`

WHERE `interviews`.`interview_id` = 19

ORDER BY `questions`.`question_id`, `responseoptions`.`responseOption_num`






SELECT
	*
FROM `answers`
LEFT JOIN `interview_product` 
	ON `interview_product`.`interview_product_id` = `answers`.`interview_product_id`

WHERE `interview_product`.`interview_id` = 19
ORDER BY `answers`.`interview_product_id`







CREATE TEMPORARY TABLE IF NOT EXISTS tmpt
SELECT
	COUNT(DISTINCT `answers`.`interview_product_id`, `answers`.`responseOption_id`) AS amount,
        `answers`.`interview_product_id`,
        `answers`.`responseOption_id`
        
FROM `answers`
LEFT JOIN `interview_product` 
	ON `interview_product`.`interview_product_id` = `answers`.`interview_product_id`
WHERE `interview_product`.`interview_id` = 19
GROUP BY `answers`.`responseOption_id`
ORDER BY `answers`.`interview_product_id`;

SELECT 
	`interviews`.`interview_id`,
        `questions`.`question_id`,
        `questions`.`question_text`,
        `questions`.`question_rate`,
        `responseoptions`.`responseOption_id`,
        `responseoptions`.`responseOption_num`,
        `responseoptions`.`responseOption_text`,
        `tmpt`.`amount`,
        `tmpt`.`interview_product_id`
FROM `interviews` 
LEFT JOIN `clusters` ON `clusters`.`cluster_id` = `interviews`.`cluster_id`
LEFT JOIN `questions` ON `questions`.`cluster_id` = `clusters`.`cluster_id`
LEFT JOIN `responseoptions`ON `responseoptions`.`question_id` = `questions`.`question_id`
LEFT JOIN tmpt 
	ON `responseoptions`.`responseOption_id` = `tmpt`.`responseOption_id`  

WHERE `interviews`.`interview_id` = 19

ORDER BY `questions`.`question_id`, `responseoptions`.`responseOption_num`, `tmpt`.`interview_product_id`
















SELECT
	COUNT(DISTINCT `answers`.`interview_product_id`, `answers`.`responseOption_id`) AS amount,
        `answers`.`interview_product_id`,
        `answers`.`responseOption_id`,
		`products`.`product_name`,
        `responseoptions`.`responseOption_text`,
        `responseoptions`.`responseOption_num`,
        `questions`.`question_id`,
        `questions`.`question_text`,
        `questions`.`question_rate`
        
FROM `answers`
LEFT JOIN `interview_product` 
	ON `interview_product`.`interview_product_id` = `answers`.`interview_product_id`
LEFT JOIN `products`
	ON `products`.`product_id` = `interview_product`.`product_id`
LEFT JOIN `responseoptions` 
	ON `responseoptions`.`responseOption_id` = `answers`.`responseOption_id`
LEFT JOIN `questions`
	ON `questions`.`question_id` = `responseoptions`.`question_id`

WHERE `interview_product`.`interview_id` = 19

GROUP BY `answers`.`responseOption_id`
ORDER BY `answers`.`interview_product_id`, `questions`.`question_id`, `responseoptions`.`responseOption_num`









/*Отчет по результатам опроса*/


SELECT 
	`answers`.`interview_product_id`,
    `products`.`product_name`,
	`answers`.`responseOption_id`,
    COUNT(*) AS amount_taster,
    `responseoptions`.`responseOption_text`,
    `responseoptions`.`responseOption_num`,
	`questions`.`question_id`,
	`questions`.`question_text`,
	`questions`.`question_rate`
FROM `answers`

LEFT JOIN `interview_product` 
	ON `interview_product`.`interview_product_id` = `answers`.`interview_product_id`
LEFT JOIN `products`
	ON `products`.`product_id` = `interview_product`.`product_id`
LEFT JOIN `responseoptions` 
	ON `responseoptions`.`responseOption_id` = `answers`.`responseOption_id`
LEFT JOIN `questions`
	ON `questions`.`question_id` = `responseoptions`.`question_id`
        
WHERE `interview_product`.`interview_id` = 19

GROUP BY `answers`.`interview_product_id`, `answers`.`responseOption_id`
ORDER BY `answers`.`interview_product_id`, `questions`.`question_id`, `responseoptions`.`responseOption_num`






