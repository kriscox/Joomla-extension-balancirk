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