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