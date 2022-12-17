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

/* Add dummy data for tests. Must be removed afterwards */
INSERT INTO `#__balancirk_members_additional` (
    `id`, `firstname`, `street`, `number`, `bus`, 
    `postcode`, `city`, `phone`
) VALUES
('156', 'Kris', 'Alverbergstraat', '63', NULL, '3500', 'Hasselt', '+32478260721'),
('157', 'Nora', 'Alverbergstraat', '63', NULL, '3500', 'Hasselt', '+32456354336');

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
    `email` varchar(100) NOT NULL,
    `birthdate` date NOT NULL,
	`UITPas` varchar(13),
	`photo` varchar(255),
    `state` char(15),
    `ordering` int(11) NOT NULL DEFAULT 0
);

/* Add dummy data for tests. Must be removed afterwards */
INSERT INTO `#__balancirk_students` (
    `id`, `firstname`, `name`, `street`, `number`, `bus`, 
    `postcode`, `city`, `phone`, `email`, `birthdate`, `state`
) VALUES
('1', 'Kris', 'Cox', 'Alverbergstraat', '63', NULL, '3500', 'Hasselt', '+32478260721', 'cox.kris@gmail.com', '1973-10-09', '1'),
('2', 'Nora', 'Cox', 'Alverbergstraat', '63', NULL, '3500', 'Hasselt', '+32456354336', 'cox.nora@gmail.com', '2009-11-01', '1');

/**************************************************************************************************
*                                                                                                 * 
*  SQL script for table Parent                                                                  * 
*                                                                                                 * 
**************************************************************************************************/

CREATE TABLE IF NOT EXISTS `#__balancirk_parents` (
    `parent` int(11) NOT NULL,
    `child` int(11) NOT NULL,
    `primary` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`parent`, `child`),
    CONSTRAINT `fk_parent` 
        FOREIGN KEY (parent) 
            REFERENCES `#__balancirk_members_additional` (id),
    CONSTRAINT `fk_childs` 
        FOREIGN KEY (child) 
            REFERENCES `#__balancirk_students` (id)
);

INSERT INTO `#__balancirk_parents` (
    `parent`, `child`, `primary`
) VALUES
    (156, 1, 1),
    (156, 2, 0);

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
    `state` char(15) NOT NULL,
    CONSTRAINT `fk_types`
        FOREIGN KEY (type)
            REFERENCES `#__balancirk_types` (id)
);

CREATE OR REPLACE VIEW `#__balancirk_lessons_complete` 
    AS SELECT a.`id`, a.`name`, b.`name` as `type`, a.`fee`, a.`year`, a.`start`, a.`end`, 
            a.`start_registration`, a.`end_registration`, a.`state` 
            FROM `#__balancirk_lessons` a
                INNER JOIN `#__balancirk_types` b
                    ON a.type = b.id;

/**************************************************************************************************
*                                                                                                 * 
*  SQL script for table hours                                                                     * 
*                                                                                                 * 
**************************************************************************************************/

CREATE TABLE IF NOT EXISTS `#__balancirk_hours`(
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `day` varchar(40) NOT NULL,
    `lesson` int(11) NOT NULL,
    CONSTRAINT `fk_lessons`
        FOREIGN KEY (lesson)
            REFERENCES `#__balancirk_lessons` (id)
);

/**************************************************************************************************
*                                                                                                 * 
*  SQL script for table presences                                                                 * 
*                                                                                                 * 
**************************************************************************************************/

CREATE TABLE IF NOT EXISTS `#__balancirk_presences`(
    `lesson` int(11) NOT NULL,
    `student` int(11) NOT NULL,
    PRIMARY KEY (`lesson`, `student`), 
    CONSTRAINT `fk_lesson`
        FOREIGN KEY (lesson)
            REFERENCES `#__balancirk_lessons` (id),
    CONSTRAINT `fk_student`
        FOREIGN KEY (student)
            REFERENCES `#__balancirk_students` (id)
);

/**************************************************************************************************
*                                                                                                 * 
*  SQL script for table teachers                                                                  * 
*                                                                                                 * 
**************************************************************************************************/

CREATE TABLE IF NOT EXISTS `#__balancirk_teachers`(
        `member` int(11) NOT NULL,
        `les` int(11) NOT NULL,
        PRIMARY KEY (`member`, `les`)
);