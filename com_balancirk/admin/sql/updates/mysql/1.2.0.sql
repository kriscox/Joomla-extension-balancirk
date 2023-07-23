/**************************************************************************************************
*                                                                                                 * 
*  SQL script to upate lessons_complete view                                                      * 
*                                                                                                 * 
*    added number of subscription                                                                 * 
*                                                                                                 * 
**************************************************************************************************/
CREATE OR REPLACE VIEW `#__balancirk_lessons_complete` 
    AS SELECT a.`id`, a.`name`, b.`name` as `type`, a.`fee`, a.`year`, a.`start`, a.`end`, 
            a.`start_registration`, a.`end_registration`, a.`state`, 
			(SELECT COUNT(*) FROM `#__balancirk_subscriptions` WHERE `lesson` = a.`id`) AS 'numberOfStudents'
            FROM `#__balancirk_lessons` a
                INNER JOIN `#__balancirk_types` b
                    ON a.`type` = b.`id`;