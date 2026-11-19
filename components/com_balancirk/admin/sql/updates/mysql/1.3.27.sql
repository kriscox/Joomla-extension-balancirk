/**************************************************************************************************
*                                                                                                 *
*  SQL script to update students table                                                            *
*                                                                                                 *
*    added increased_benefit column (recht op verhoogde tegemoetkoming)                           *
*                                                                                                 *
**************************************************************************************************/
ALTER TABLE `#__balancirk_students`
    ADD COLUMN `increased_benefit` boolean NOT NULL DEFAULT 0 AFTER `uitpas`;

CREATE OR REPLACE VIEW `#__balancirk_subscriptions_comptable` AS
SELECT m.`firstname` AS `firstname`,
    m.`name` AS `name`,
    concat(m.`street`, ' ', m.`number`) AS `address`,
    m.`bus` AS `bus`,
    m.`postcode` AS `postcode`,
    m.`city` AS `city`,
    m.`email` AS `email`,
    s.`lesson` AS `lesson`,
    s.`firstname` AS `student_firstname`,
    s.`name` AS `student_name`,
    st.`uitpas` AS `uitpas`,
    st.`mutuality` AS `mutuality`,
    st.`increased_benefit` AS `increased_benefit`
FROM `#__balancirk_subscriptions_view` s
    JOIN `#__balancirk_students` st ON s.`studentid` = st.`id`
    JOIN `#__balancirk_parents` p ON p.`child` = s.`studentid`
    AND p.`primary` = 1
    JOIN `#__balancirk_members` m ON m.`id` = p.`parent`;
