/**************************************************************************************************
 *                                                                                                 *
 *  SQL script to update lessons table                                                             *
 *                                                                                                 *
 *    added subscription and waiting list email template columns                                   *
 *                                                                                                 *
 **************************************************************************************************/
ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `subscription_email_subject` varchar(255) DEFAULT NULL AFTER `max_age`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `subscription_email_body` text DEFAULT NULL AFTER `subscription_email_subject`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `waitinglist_email_subject` varchar(255) DEFAULT NULL AFTER `subscription_email_body`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `waitinglist_email_body` text DEFAULT NULL AFTER `waitinglist_email_subject`;