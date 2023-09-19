ALTER TABLE `#__plg_webservices_balancirk_storage_table_1` ADD `new_field_from_update` TEXT NULL DEFAULT NULL AFTER `zip_postcode`,
    ADD FULLTEXT `idx_new_field_from_update` (`new_field_from_update`);
    