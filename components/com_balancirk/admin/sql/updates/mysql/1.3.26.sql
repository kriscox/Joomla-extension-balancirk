ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `registration_closed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `max_students`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `yearstart_email_subject` varchar(255) DEFAULT NULL AFTER `promotion_email_body`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `yearstart_email_body` text DEFAULT NULL AFTER `yearstart_email_subject`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `rejection_email_subject` varchar(255) DEFAULT NULL AFTER `yearstart_email_body`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `rejection_email_body` text DEFAULT NULL AFTER `rejection_email_subject`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `cancellation_email_subject` varchar(255) DEFAULT NULL AFTER `rejection_email_body`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `cancellation_email_body` text DEFAULT NULL AFTER `cancellation_email_subject`;

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
    a.`registration_closed`,
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
