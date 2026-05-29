/**************************************************************************************************
 *                                                                                                 *
 *  Restore the lessons_complete view for sites where 1.3.2.sql left it in an inconsistent state. *
 *                                                                                                 *
 *  The ALTER TABLE column additions from 1.2.12.sql and 1.2.20.sql already ran on all            *
 *  upgraded sites, so the columns exist. Only the view needs to be recreated here.               *
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
