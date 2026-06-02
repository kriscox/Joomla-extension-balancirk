/**************************************************************************************************
 *                                                                                                 *
 *  Recreate the lessons_complete view.                                                            *
 *                                                                                                 *
 *  The min_age/max_age and email template columns are created once by 1.2.12.sql and 1.2.20.sql.  *
 *  They are no longer re-added here: re-adding existing columns aborted the update with a          *
 *  "Duplicate column" error. This script now only (re)creates the view.                           *
 *                                                                                                 *
 **************************************************************************************************/
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
