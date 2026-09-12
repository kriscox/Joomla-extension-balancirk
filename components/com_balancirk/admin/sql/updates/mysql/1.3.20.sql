/**************************************************************************************************
 *                                                                                                 *
 *  Prepare teached/teachers data for composite FK fk_teached_teacher                              *
 *  (teacher, lesson) → teachers(member, lesson).                                                  *
 *                                                                                                 *
 *  The FK drop/add itself runs idempotently from script.php::migrateTeachedTeacherForeignKey().  *
 *                                                                                                 *
 **************************************************************************************************/

INSERT IGNORE INTO `#__balancirk_teachers` (`member`, `lesson`)
SELECT DISTINCT t.`teacher`, t.`lesson`
FROM `#__balancirk_teached` t
LEFT JOIN `#__balancirk_teachers` te
    ON te.`member` = t.`teacher`
    AND te.`lesson` = t.`lesson`
WHERE te.`id` IS NULL;
