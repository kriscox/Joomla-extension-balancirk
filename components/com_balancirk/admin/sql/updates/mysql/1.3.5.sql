/**************************************************************************************************
 *                                                                                                 *
 *  Repair script for sites where 1.3.2.sql ran with errors (ADD COLUMN IF NOT EXISTS was used    *
 *  and failed on MySQL < 8.0.3, leaving some columns missing).                                   *
 *                                                                                                 *
 *  Each statement is intentionally split so a "Duplicate column" error on a site that already    *
 *  has the column does not block the remaining statements.                                        *
 *                                                                                                 *
 **************************************************************************************************/
ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `min_age` int(11) DEFAULT NULL AFTER `max_students`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `max_age` int(11) DEFAULT NULL AFTER `min_age`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `subscription_email_subject` varchar(255) DEFAULT NULL AFTER `max_age`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `subscription_email_body` text DEFAULT NULL AFTER `subscription_email_subject`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `waitinglist_email_subject` varchar(255) DEFAULT NULL AFTER `subscription_email_body`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `waitinglist_email_body` text DEFAULT NULL AFTER `waitinglist_email_subject`;

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
