/**************************************************************************************************
 *                                                                                                 *
 *  Repair script: ensure min_age/max_age and email template columns exist on lessons table.       *
 *                                                                                                 *
 *  The original 1.2.12 and 1.2.20 ALTER TABLE statements combined multiple ADD COLUMN clauses     *
 *  where later clauses referenced columns added earlier in the same statement. This can fail      *
 *  on certain MariaDB/MySQL versions. This script safely re-adds all potentially missing          *
 *  columns for sites that upgraded through the broken scripts.                                    *
 *                                                                                                 *
 **************************************************************************************************/
ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN IF NOT EXISTS `min_age` int(11) DEFAULT NULL AFTER `max_students`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN IF NOT EXISTS `max_age` int(11) DEFAULT NULL AFTER `min_age`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN IF NOT EXISTS `subscription_email_subject` varchar(255) DEFAULT NULL AFTER `max_age`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN IF NOT EXISTS `subscription_email_body` text DEFAULT NULL AFTER `subscription_email_subject`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN IF NOT EXISTS `waitinglist_email_subject` varchar(255) DEFAULT NULL AFTER `subscription_email_body`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN IF NOT EXISTS `waitinglist_email_body` text DEFAULT NULL AFTER `waitinglist_email_subject`;

CREATE OR REPLACE VIEW `#__balancirk_lessons_complete` AS
SELECT a.`id`,
    a.`name`,
    b.`name` AS `type`,
    a.`fee`,
    a.`year`,
    a.`start`,
    a.`end`,
    a.`start_registration`,
    a.`end_registration`,
    a.`state`,
    a.`lesdays`,
    a.`max_students`,
    a.`min_age`,
    a.`max_age`,
    (
        SELECT COUNT(*)
        FROM `#__balancirk_subscriptions`
        WHERE `lesson` = a.`id`
            AND `subscribed` = 0
    ) AS `numberOfStudents`,
    (
        SELECT COUNT(*)
        FROM `#__balancirk_subscriptions`
        WHERE `lesson` = a.`id`
            AND `subscribed` = 1
    ) AS `numberOnWaitingList`
FROM `#__balancirk_lessons` a
    INNER JOIN `#__balancirk_types` b ON a.`type` = b.`id`;
