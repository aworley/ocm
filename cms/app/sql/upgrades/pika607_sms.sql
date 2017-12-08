INSERT INTO menu_act_type VALUES ('S', 'SMS Message', 5);
ALTER TABLE `cms`.`cases` 
ADD COLUMN `unread_sms` TINYINT(4) NOT NULL DEFAULT 0 AFTER `vawa_served`;