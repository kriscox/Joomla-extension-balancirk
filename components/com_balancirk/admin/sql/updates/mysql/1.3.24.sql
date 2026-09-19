ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `promotion_email_subject` varchar(255) DEFAULT NULL AFTER `waitinglist_email_body`;

ALTER TABLE `#__balancirk_lessons`
    ADD COLUMN `promotion_email_body` text DEFAULT NULL AFTER `promotion_email_subject`;
