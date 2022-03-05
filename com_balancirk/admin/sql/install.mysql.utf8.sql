DROP TABLE IF EXISTS `#__Balancirk_Student`;

CREATE TABLE `#__Balancirk_Student` ( 
    `id` SERIAL NOT NULL, 
    `firstname` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `parent_id` INT NOT NULL,
    `birthdate` DATE,
    `dialcode` SMALLINT,
    `phone` DECIMAL[10],
    `email` VARCHAR(255),
    `remarks` TEXT,
    `use_photos` BOOLEAN NOT NULL,
    `uitpassnr` DECIMAL[13],
    PRIMARY KEY (`id`),
    INDEX (`name`, `firstname`, `parent_id`),
    FOREIGN KEY (parent_id)
        REFERENCES `#__users`(`id`)
        ON UPDATE CASCADE ON DELETE RESTRICT,
) ENGINE = InnoDB; 
