/**************************************************************************************************
 *                                                                                                 *
 *  SQL script to update lessons table                                                             *
 *                                                                                                 *
 *    added subscription and waiting list email template columns                                   *
 *                                                                                                 *
 **************************************************************************************************/
ALTER TABLE `#__balancirk_lessons`
ADD COLUMN `subscription_email_subject` varchar(255) DEFAULT NULL
AFTER `max_age`,
    ADD COLUMN `subscription_email_body` text DEFAULT NULL
AFTER `subscription_email_subject`,
    ADD COLUMN `waitinglist_email_subject` varchar(255) DEFAULT NULL
AFTER `subscription_email_body`,
    ADD COLUMN `waitinglist_email_body` text DEFAULT NULL
AFTER `waitinglist_email_subject`;