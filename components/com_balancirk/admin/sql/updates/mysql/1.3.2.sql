/**************************************************************************************************
 *                                                                                                 *
 *  Repair script: ensure min_age/max_age and email template columns exist on lessons table.       *
 *                                                                                                 *
 *  Each column is added in a separate ALTER TABLE statement so that a "Duplicate column"          *
 *  error on an already-upgraded site does not prevent the remaining columns from being added.     *
 *                                                                                                 *
 *  Note: ADD COLUMN IF NOT EXISTS is NOT used because it is unsupported on MySQL < 8.0.3.        *
 *  Joomla's schema updater logs and skips individual statement errors, so duplicate-column        *
 *  errors are harmless here.                                                                      *
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
