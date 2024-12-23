/**************************************************************************************************
*                                                                                                 * 
*  SQL script for table MEMBERS                                                                   * 
*                                                                                                 * 
*  Is relate with the users standard table in joomla, but adds information about the members      *
*                                                                                                 * 
**************************************************************************************************/
CREATE TABLE IF NOT EXISTS `#__balancirk_members_additional` (
    `id` int(11) NOT NULL PRIMARY KEY,
    `firstname` varchar(255) NOT NULL,
    `street` varchar(255),
    `number` varchar(10),
    `bus` varchar(10),
    `postcode` varchar(10),
    `city` varchar(50),
    `phone` char(15),
    `ordering` int(11) NOT NULL DEFAULT 0
);

CREATE OR REPLACE VIEW `#__balancirk_members` 
    AS SELECT u.* , m.firstname, m.street, m.number, m.bus, m.postcode, m.city,
            m.phone, m.ordering
            FROM `#__balancirk_members_additional` m
                INNER JOIN `#__users` u
                    ON u.id = m.id;

/**************************************************************************************************
*                                                                                                 * 
*  SQL script for table STUDENTS                                                                  * 
*                                                                                                 * 
**************************************************************************************************/
CREATE TABLE IF NOT EXISTS `#__balancirk_students` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` varchar(255) NOT NULL,
    `firstname` varchar(255) NOT NULL,
    `street` varchar(255),
    `number` varchar(10),
    `bus` varchar(10),
    `postcode` varchar(10),
    `city` varchar(50),
    `phone` char(15),
    `email` varchar(100),
    `birthdate` date NOT NULL,
	`uitpas` varchar(13),
	`photo` varchar(255),
	`allow_photo` boolean NOT NULL DEFAULT 0,
    `state` char(15),
    `ordering` int(11) NOT NULL DEFAULT 0
);

/**************************************************************************************************
*                                                                                                 * 
*  SQL script for table Parent                                                                  * 
*                                                                                                 * 
**************************************************************************************************/

CREATE TABLE IF NOT EXISTS `#__balancirk_parents` (
	`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `parent` int(11) NOT NULL,
    `child` int(11) NOT NULL,
    `primary` tinyint(1) NOT NULL DEFAULT 0,
    UNIQUE (`parent`, `child`),
    CONSTRAINT `fk_parent_parent` 
        FOREIGN KEY (parent) 
            REFERENCES `#__balancirk_members_additional` (id),
    CONSTRAINT `fk_parent_childs` 
        FOREIGN KEY (child) 
            REFERENCES `#__balancirk_students` (id)
);

/**************************************************************************************************
*                                                                                                 * 
*  SQL script for table types                                                                     * 
*                                                                                                 * 
**************************************************************************************************/

CREATE TABLE IF NOT EXISTS `#__balancirk_types` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` varchar(40) NOT NULL
);

/**************************************************************************************************
*                                                                                                 * 
*  SQL script for table lessons                                                                   * 
*                                                                                                 * 
**************************************************************************************************/

CREATE TABLE IF NOT EXISTS `#__balancirk_lessons` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` varchar(40) NOT NULL,
    `type` int(11) NOT NULL,
    `fee` float NOT NULL,
    `year` decimal(4,0) NOT NULL,
    `start` date NOT NULL,
    `end` date NOT NULL,
    `start_registration` date NOT NULL,
    `end_registration` date NOT NULL,
    `max_students` int(11) NOT NULL,
	`lesdays` int(11) DEFAULT NULL COMMENT '64 = maandag, \n32 = dinsdag, \n16 = woensdag, \n8 = donderdag, \n4 = vrijdag, \n2 = zaterdag, \n1 = zondag',
    `state` char(15) NOT NULL,
    `ordering` int(11) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_lesson_types`
        FOREIGN KEY (`type`)
            REFERENCES `#__balancirk_types` (id)
);

/**************************************************************************************************
*                                                                                                 * 
*  SQL script for table subscriptions                                                             * 
*                                                                                                 * 
**************************************************************************************************/

CREATE TABLE IF NOT EXISTS `#__balancirk_subscriptions`(
	`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY, 
    `lesson` int(11) NOT NULL,
    `student` int(11) NOT NULL,
    `subscribed` TINYINT(1) NOT NULL,
    UNIQUE (`lesson`, `student`), 
    CONSTRAINT `fk_subscription_lesson`
        FOREIGN KEY (lesson)
            REFERENCES `#__balancirk_lessons` (id),
    CONSTRAINT `fk_subscription_student`
        FOREIGN KEY (student)
            REFERENCES `#__balancirk_students` (id)
);

CREATE OR REPLACE VIEW `#__balancirk_subscriptions_view`
	AS SELECT `s`.`id` as id, t.`id` as 'studentid', t.`name`, t.`firstname`, 
		l.`id` as 'lessonid', l.`name` as 'lesson', l.`type`, l.`fee`, l.`year`,
		l.`start`, l.`end`, l.`start_registration`, l.`end_registration`, l.`state`,
        s.`subscribed`
		FROM `#__balancirk_subscriptions` as s
			INNER JOIN `#__balancirk_lessons` as l
				ON s.`lesson` = l.`id`
			INNER JOIN `#__balancirk_students` as t
				on s.`student` = t.`id`;

CREATE OR REPLACE VIEW `#__balancirk_lessons_complete` 
    AS SELECT a.`id`, a.`name`, b.`name` as `type`, a.`fee`, a.`year`, a.`start`, a.`end`, 
            a.`start_registration`, a.`end_registration`, a.`state`, a.`lesdays`,
			(SELECT COUNT(*) FROM `#__balancirk_subscriptions` WHERE `lesson` = a.`id` and `subscribed` = 0) AS 'numberOfStudents',
            (SELECT COUNT(*) FROM `#__balancirk_subscriptions` WHERE `lesson` = a.`id` and `subscribed` = 1) AS 'numberOnWaitingList'
            FROM `#__balancirk_lessons` a
                INNER JOIN `#__balancirk_types` b
                    ON a.`type` = b.`id`;

/**************************************************************************************************
*                                                                                                 * 
*  SQL script for table presences                                                                 * 
*                                                                                                 * 
**************************************************************************************************/

CREATE TABLE IF NOT EXISTS `#__balancirk_presences` (
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
			ON p.`lesson` = l.`id`;

/**************************************************************************************************
*                                                                                                 * 
*  SQL script for table teachers                                                                  * 
*                                                                                                 * 
**************************************************************************************************/

CREATE TABLE IF NOT EXISTS `#__balancirk_teachers`(
	`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `member` int(11) NOT NULL,
    `lesson` int(11) NOT NULL,
    UNIQUE (`member`, `les`)
);

/**************************************************************************************************
*                                                                                                 * 
*  SQL script for table holidays                                                                  * 
*                                                                                                 * 
**************************************************************************************************/

CREATE TABLE IF NOT EXISTS `#__balancirk_holidays`(
	`id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	`year` decimal(4,0) NOT NULL,
	`startDate` date NOT NULL,
	`endDate` date NOT NULL,
	`summary` varchar(100) NOT NULL,
	UNIQUE (`year`, `startDate`)
);

/**************************************************************************************************
*                                                                                                 *
*  SQL script for table mailmessages                                                              *
*                                                                                                 *
**************************************************************************************************/
CREATE TABLE IF NOT EXISTS `#__balancirk_mailmessages`(
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` varchar(255) NOT NULL,
    `subject` varchar(255) NOT NULL,
    `message` text NOT NULL,
    `date` datetime NOT NULL,
    `state` char(15) NOT NULL,
    `ordering` int(11) NOT NULL DEFAULT 0
);

/***********************************************************************************************
*                                                                                              *
*  SQL script for view subscriptions for comptable                                             *
*                                                                                              *
***********************************************************************************************/
CREATE OR REPLACE VIEW `#__balancirk_subscriptions_comptable` 
    AS SELECT `m`.`firstname` AS `firstname`, `m`.`name` AS `name`,
    concat(`m`.`street`, ' ', `m`.`number`) AS `adres`, `m`.`bus` AS `bus`, 
    `m`.`postcode` AS `postcode`, `m`.`city` AS `city`, `m`.`email` AS `email`, 
    `s`.`lesson` AS `lesson`, `s`.`firstname` AS `voornaam kind`, `s`.`name` AS `naam kind`,
    `st`.`uitpas` AS `uitpas`
FROM `tc_balancirk_subscriptions_view` `s`
    JOIN `tc_balancirk_students` `st` ON `s`.`studentid` = `st`.`id`
    JOIN `tc_balancirk_parents` `p` ON `p`.`child` = `s`.`studentid` AND `p`.`primary` = 1
    JOIN `tc_balancirk_members` `m` ON `m`.`id` = `p`.`parent`;