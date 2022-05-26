CREATE TABLE IF NOT EXISTS `#__members` (
    `id` int(11) NOT NULL PRIMARY KEY,
    `firstname` varchar(255) NOT NULL,
    `street` varchar(255),
    `number` varchar(10),
    `bus` varchar(10),
    `postalcode` varchar(10),
    `municipality` varchar(50),
    `phone` char(15),
    `ordering` int(11) NOT NULL DEFAULT 0,
    CONSTRAINT `fk_users` 
        FOREIGN KEY (id) 
            REFERENCES `#__users` (id)
        ON DELETE CASCADE
        ON UPDATE RESTRICT
);

INSERT INTO `#__members` (
    `id`, `firstname`, `street`, `number`, `bus`, 
    `postalcode`, `municipality`, `phone`
) VALUES
('156', 'Kris', 'Alverbergstraat', '63', NULL, '3500', 'Hasselt', '+32478260721'),
('157', 'Nora', 'Alverbergstraat', '63', NULL, '3500', 'Hasselt', '+32456354336');

