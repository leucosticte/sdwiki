CREATE TABLE sd_cursor(
-- Primary key
sdc_id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
-- Key
sdc_key varchar(255) binary NOT NULL default '',
-- Value
sdc_value varchar(255) binary NOT NULL default ''
);