CREATE TABLE IF NOT EXISTS `#__members` (
    `id` SERIAL,
    `name` varchar(50) NOT NULL,
    `surname` varchar(50) NOT NULL,
    `username` varchar(50) NOT NULL,
    `email` varchar(100) NOT NULL,
    `street` varchar(255),
    `number` varchar(10),
    `bus` varchar(10),
    `postalcode` varchar(10),
    `municipality` varchar(50),
    `phone` char(15),
    `userid_joomla` int,
    `subscription_date` date,
    PRIMARY KEY (`id`)
);

INSERT INTO `#__members` (
    `name`, `surname`, `username`, `email`, `street`, `number`, `bus`, 
    `postalcode`, `municipality`, `phone`, `userid_joomla`
) VALUES
('Kris', 'Cox', 'krisc', 'cox.kris@gmail.com', 'Alverbergstraat', '63', NULL, '3500', 'Hasselt', '+32478260721', '50'),
('Nora', 'Cox', 'norac', 'cox.nora@gmail.com', 'Alverbergstraat', '63', NULL, '3500', 'Hasselt', '+32456354336', '50');

