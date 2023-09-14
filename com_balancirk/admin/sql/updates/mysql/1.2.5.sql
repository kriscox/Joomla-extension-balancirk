/**************************************************************************************************
*                                                                                                 * 
*  SQL script to upate lessons table                                                              * 
*                                                                                                 * 
*    added orderning column                                                                       * 
*                                                                                                 * 
**************************************************************************************************/
ALTER TABLE `#__balancirk_lessons` 
	ADD COLUMN `ordening` int(11) NOT NULL DEFAULT 0,
	ADD COLUMN `lesdays` int(11) unsigned DEFAULT NULL COMMENT '64 = maandag, \n32 = dinsdag, \n16 = woensdag, \n8 = donderdag, \n4 = vrijdag, \n2 = zaterdag, \n1 = zondag';


ALTER VIEW `#__balancirk_lessons_complete` AS
SELECT
    `a`.`id` AS `id`
    , `a`.`name` AS `name`
    , `b`.`name` AS `type`
    , `a`.`fee` AS `fee`
    , `a`.`year` AS `year`
    , `a`.`start` AS `start`
    , `a`.`end` AS `end`
    , `a`.`start_registration` AS `start_registration`
    , `a`.`end_registration` AS `end_registration`
    , `a`.`lesdays` AS `lesdays`
    , `a`.`state` AS `state`
    ,(
        SELECT
            count(0)
        FROM
            `#__balancirk_subscriptions` `s`
        WHERE
            `s`.`lesson` = `a`.`id`
    ) AS `numberOfStudents`
FROM
    (
        `#__balancirk_lessons` `a`
    JOIN `#__balancirk_types` `b` ON
        (
            `a`.`type` = `b`.`id`
        )
    );

/**************************************************************************************************
*                                                                                                 * 
*    Drop table balancirk_hours                                                                   * 
*                                                                                                 * 
**************************************************************************************************/
DROP TABLE IF EXISTS `#__balancirk_hours`;


/**************************************************************************************************
*																								  *
*    Alter table balancirk_presences                                                              *
*																								  *
**************************************************************************************************/
DROP TABLE IF EXISTS `#__balancirk_presences`;

CREATE TABLE `#__balancirk_presences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lesson` int(11) NOT NULL,
  `student` int(11) NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `presence` (`lesson`,`student`, `date`),
  CONSTRAINT `fk_presence_lesson` FOREIGN KEY (`lesson`) REFERENCES `#__balancirk_lessons` (`id`),
  CONSTRAINT `fk_presence_student` FOREIGN KEY (`student`) REFERENCES `#__balancirk_students` (`id`)
);

CREATE OR REPLACE VIEW `#__balancirk_presences_view`
AS 
SELECT p.`id`, s.`name` , s.`firstname` , l.`name` as `lesson`, p.`date` 
	FROM `#__balancirk_presences` p
		INNER JOIN `#__balancirk_students` s
			ON p.`student` =s.`id` 
		INNER JOIN `#__balancirk_lessons` l
			ON p.`lesson` = l.`id` 