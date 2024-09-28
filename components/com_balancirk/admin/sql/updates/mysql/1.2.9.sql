/**************************************************************************************************
*                                                                                                 *
*  SQL script for table mailmessages                                                              *
*                                                                                                 *
**************************************************************************************************/
CREATE TABLE IF NOT EXISTS `#__balancirk_mailmessages` (
    `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` varchar(255) NOT NULL,
    `subject` varchar(255) NOT NULL,
    `message` text NOT NULL,
    `date` datetime NOT NULL,
    `state` char(15) NOT NULL,
    `ordering` int(11) NOT NULL DEFAULT 0
);

/**************************************************************************************************
*
*  SQL script for table teachers (change column name)
*
**************************************************************************************************/
ALTER TABLE `#__balancirk_teachers` CHANGE les lesson int(11) NOT NULL;
ALTER TABLE `#__balancirk_teachers` ADD CONSTRAINT `fk_teachers_lesson` FOREIGN KEY (`lesson`) REFERENCES `#__balancirk_lessons(id)`;
ALTER TABLE `#__balancirk_teachers` ADD CONSTRAINT `fk_teachers_lesson` FOREIGN KEY (`member`) REFERENCES `#__balancirk_members_additional(id)`;


/**************************************************************************************************
*
*  SQL script for table teached 
*
**************************************************************************************************/
CREATE TABLE IF NOT EXISTS `#__balancirk_teached` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lesson` int(11) NOT NULL,
  `teacher` int(11) NOT NULL,
  `date` date NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `teached` (`lesson`,`teacher`, `date`),
  CONSTRAINT `fk_teached_lesson` FOREIGN KEY (`lesson`) REFERENCES `#__balancirk_lessons` (`id`),
  CONSTRAINT `fk_teached_member` FOREIGN KEY (`teacher`) REFERENCES `#__balancirk_members_additional` (`id`),
  CONSTRAINT `fk_teached_teacher` FOREIGN KEY (`teacher`) REFERENCES `#__balancirk_teachers` (`member`)
);