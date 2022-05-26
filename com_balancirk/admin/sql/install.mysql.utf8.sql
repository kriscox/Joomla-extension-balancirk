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
    `postalcode` varchar(10),
    `municipality` varchar(50),
    `phone` char(15),
    `ordering` int(11) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_users` 
        FOREIGN KEY (id) 
            REFERENCES `#__users` (id)
        ON UPDATE RESTRICT
);

/* Add dummy data for tests. Must be removed afterwards */
INSERT INTO `#__balancirk_members_additional` (
    `id`, `firstname`, `street`, `number`, `bus`, 
    `postalcode`, `municipality`, `phone`
) VALUES
('156', 'Kris', 'Alverbergstraat', '63', NULL, '3500', 'Hasselt', '+32478260721'),
('157', 'Nora', 'Alverbergstraat', '63', NULL, '3500', 'Hasselt', '+32456354336');

CREATE OR REPLACE VIEW `#__balancirk_members` 
    AS SELECT u.* , m.firstname, m.street, m.number, m.bus, m.postalcode, m.municipality,
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
    `id` int(11) NOT NULL PRIMARY KEY,
    `name` varchar(255) NOT NULL,
    `firstname` varchar(255) NOT NULL,
    `street` varchar(255),
    `number` varchar(10),
    `bus` varchar(10),
    `postalcode` varchar(10),
    `municipality` varchar(50),
    `phone` char(15),
    `email` varchar(100) NOT NULL,
    `birthdate` date NOT NULL,
    `state` char(15),
    `ordering` int(11) NOT NULL DEFAULT 0
);

/* Add dummy data for tests. Must be removed afterwards */
INSERT INTO `#__balancirk_students` (
    `id`, `firstname`, `name`, `street`, `number`, `bus`, 
    `postalcode`, `municipality`, `phone`, `email`, `birthdate`, `state`
) VALUES
('1', 'Kris', 'Cox', 'Alverbergstraat', '63', NULL, '3500', 'Hasselt', '+32478260721', 'cox.kris@gmail.com', '1973-10-09', '1'),
('2', 'Nora', 'Cox', 'Alverbergstraat', '63', NULL, '3500', 'Hasselt', '+32456354336', 'cox.nora@gmail.com', '2009-11-01', '1');

/**************************************************************************************************
*                                                                                                 * 
*  SQL script for table Parent                                                                  * 
*                                                                                                 * 
**************************************************************************************************/

CREATE TABLE IF NOT EXISTS `#__balancirk_parent` (
    `parent` int(11) NOT NULL,
    `child` int(11) NOT NULL,
    PRIMARY KEY (`parent`, `child`),
    CONSTRAINT `fk_parent` 
        FOREIGN KEY (parent) 
            REFERENCES `#__balancirk_members_additional` (id),
    CONSTRAINT `fk_childs` 
        FOREIGN KEY (child) 
            REFERENCES `#__balancirk_students` (id)
);

INSERT INTO `#__balancirk_parent` (
    `parent`, `child`
) VALUES
    (156, 1),
    (156, 2);

