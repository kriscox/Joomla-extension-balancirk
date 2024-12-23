/**************************************************************************************************
*                                                                                                 *
* Alter view #__balancirk_subscriptions_view add subscription id to field list					  *
*                                                                                                 *
**************************************************************************************************/
CREATE OR REPLACE VIEW `#__balancirk_subscriptions_view` 
	AS SELECT `s`.`id` AS `id`, `t`.`id` AS `studentid`, `t`.`name` AS `name`, `t`.`firstname` AS `firstname`,
    `l`.`id` AS `lessonid` , `l`.`name` AS `lesson` , `l`.`type` AS `type` , `l`.`fee` AS `fee` , 
	`l`.`year` AS `year` , `l`.`start` AS `start` , `l`.`end` AS `end` , 
	`l`.`start_registration` AS `start_registration` , `l`.`end_registration` AS `end_registration`, 
    `l`.`state` AS `state` , `s`.`subscribed` AS `subscribed`
	 FROM `tc_balancirk_subscriptions` `s` 
	 	JOIN `tc_balancirk_lessons` `l` ON `s`.`lesson` = `l`.`id`
    	JOIN `tc_balancirk_students` `t` ON `s`.`student` = `t`.`id`
    ;

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