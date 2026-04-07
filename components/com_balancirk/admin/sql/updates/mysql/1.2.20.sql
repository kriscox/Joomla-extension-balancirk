ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `subscription_email_subject` varchar(255) DEFAULT NULL AFTER `max_age`,
    ADD COLUMN `subscription_email_body` text DEFAULT NULL AFTER `subscription_email_subject`,
    ADD COLUMN `waitinglist_email_subject` varchar(255) DEFAULT NULL AFTER `subscription_email_body`,
    ADD COLUMN `waitinglist_email_body` text DEFAULT NULL AFTER `waitinglist_email_subject`;

CREATE OR REPLACE VIEW `#__balancirk_lessons_complete` 
    AS SELECT a.`id`, a.`name`, b.`name` as `type`, a.`fee`, a.`year`, a.`start`, a.`end`, 
            a.`start_registration`, a.`end_registration`, a.`state`, a.`lesdays`, a.`max_students`,
            a.`min_age`, a.`max_age`, a.`subscription_email_subject`, a.`subscription_email_body`,
            a.`waitinglist_email_subject`, a.`waitinglist_email_body`,
            (SELECT COUNT(*) FROM `#__balancirk_subscriptions` WHERE `lesson` = a.`id` and `subscribed` = 0) AS 'numberOfStudents',
            (SELECT COUNT(*) FROM `#__balancirk_subscriptions` WHERE `lesson` = a.`id` and `subscribed` = 1) AS 'numberOnWaitingList'
            FROM `#__balancirk_lessons` a
                INNER JOIN `#__balancirk_types` b
                    ON a.`type` = b.`id`;
