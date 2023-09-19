DROP TABLE IF EXISTS `#__plg_webservices_balancirk_storage_table_1`;

    CREATE TABLE `#__plg_webservices_balancirk_storage_table_1`(
        `id` SERIAL NOT NULL COMMENT "The auto-increment pk of this i.e. plg_webservices_balancirk_storage_table_1 table",
        `name` VARCHAR(255) NOT NULL COMMENT "Required (can't be null) name field",
        `address` VARCHAR(255) NULL COMMENT "Example 'Address' field of plg_webservices_balancirk_storage_table_1 if no value provided, will be NULL",
        `city` VARCHAR(128) NULL COMMENT "Example 'City' field of plg_webservices_balancirk_storage_table_1 if no value provided, will be NULL",
        `state` VARCHAR(128) NULL COMMENT "Example 'State' field of plg_webservices_balancirk_storage_table_1 if no value provided, will be NULL",
        `zip_postcode` MEDIUMINT NULL COMMENT "Example 'Postal code' field of plg_webservices_balancirk_storage_table_1 if no value provided, will be NULL",
        PRIMARY KEY(`id`)
    ) ENGINE = InnoDB;

    /* Testing insertion into our newly created table */
    INSERT INTO `#__plg_webservices_balancirk_storage_table_1` (`name`) VALUES
        ("Example.com"),
        ("Foo Bar Bat");
    