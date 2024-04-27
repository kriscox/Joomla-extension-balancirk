/**************************************************************************************************
*                                                                                                 * 
*  SQL script to upate subsctiption table                                                         * 
*                                                                                                 * 
*    added subscribed column                                                                       * 
*                                                                                                 * 
**************************************************************************************************/
ALTER TABLE `#__balancirk_subscriptions`
	ADD COLUMN `subscribed` TINYINT(1) NOT NULL DEFAULT 0;

CREATE OR REPLACE VIEW `#__balancirk_subscriptions_view`
	AS SELECT t.`id` as 'studentid', t.`name`, t.`firstname`, 
		l.`id` as 'lessonid', l.`name` as 'lesson', l.`type`, l.`fee`, l.`year`,
		l.`start`, l.`end`, l.`start_registration`, l.`end_registration`, l.`state`,
        s.`subscribed`
		FROM `#__balancirk_subscriptions` as s
			INNER JOIN `#__balancirk_lessons` as l
				ON s.`lesson` = l.`id`
			INNER JOIN `#__balancirk_students` as t
				on s.`student` = t.`id`;

/**************************************************************************************************
*                                                                                                 *
*  SQL script to add column max_students to lessons table                                         *
*                                                                                                 *
*    added max_students column                                                                    *
*                                                                                                 *
**************************************************************************************************/
ALTER TABLE `#__balancirk_lessons`
	ADD COLUMN `max_students` INT(11) NOT NULL DEFAULT 12;

CREATE OR REPLACE VIEW `#__balancirk_lessons_complete` 
    AS SELECT a.`id`, a.`name`, b.`name` as `type`, a.`fee`, a.`year`, a.`start`, a.`end`, 
            a.`start_registration`, a.`end_registration`, a.`max_students`, a.`state`, a.`lesdays`,
			(SELECT COUNT(*) FROM `#__balancirk_subscriptions` WHERE `lesson` = a.`id` and `subscribed` = 0) AS 'numberOfStudents',
            (SELECT COUNT(*) FROM `#__balancirk_subscriptions` WHERE `lesson` = a.`id` and `subscribed` = 1) AS 'numberOnWaitingList'
            FROM `#__balancirk_lessons` a
                INNER JOIN `#__balancirk_types` b
                    ON a.`type` = b.`id`;