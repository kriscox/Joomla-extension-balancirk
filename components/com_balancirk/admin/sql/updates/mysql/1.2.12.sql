/**************************************************************************************************
*                                                                                                 *
*  SQL script to update lessons table                                                             *
*                                                                                                 *
*    added min_age and max_age columns                                                            *
*                                                                                                 *
**************************************************************************************************/
ALTER TABLE `#__balancirk_lessons`
ADD COLUMN `min_age` int(11) DEFAULT NULL AFTER `max_students`,
ADD COLUMN `max_age` int(11) DEFAULT NULL AFTER `min_age`;

CREATE OR REPLACE VIEW `#__balancirk_lessons_complete` AS
SELECT
    a.`id`,
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
        SELECT
            COUNT(*)
        FROM
            `#__balancirk_subscriptions`
        WHERE
            `lesson` = a.`id`
            AND `subscribed` = 0
    ) AS `numberOfStudents`,
    (
        SELECT
            COUNT(*)
        FROM
            `#__balancirk_subscriptions`
        WHERE
            `lesson` = a.`id`
            AND `subscribed` = 1
    ) AS `numberOnWaitingList`
FROM
    `#__balancirk_lessons` a
    INNER JOIN `#__balancirk_types` b ON a.`type` = b.`id`;