INSERT INTO menu_act_type VALUES ('S', 'SMS Message', 5);
ALTER TABLE cases ADD COLUMN unread_sms TINYINT(4) NOT NULL DEFAULT 0 
	AFTER vawa_served;

ALTER TABLE activities 
	ADD COLUMN sms_mins_before INT, 
	ADD COLUMN sms_send_time BIGINT, 
	ADD COLUMN sms_mobile VARCHAR(11),
	ADD COLUMN sms_message_id INT,
	ADD COLUMN sms_extra_message VARCHAR(255),
	ADD COLUMN sms_send_failures TINYINT, 
	ADD COLUMN sms_act_id INT,
	ADD COLUMN sms_count TINYINT;
    
    
CREATE TABLE menu_sms_messages (
  `value` tinyint NOT NULL DEFAULT '0',
  `label` char(255) NOT NULL DEFAULT '',
  `menu_order` tinyint NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `val` (`value`),
  KEY `menu_order` (`menu_order`)
) ENGINE=MyISAM;

INSERT INTO `menu_sms_messages` VALUES (0,'Hello, this is Legal Services, reminding you about your court date.  Please remember to arrive 45 minutes before your scheduled time.  Your court appointment is:',1);

CREATE TABLE menu_sms_mins_before (
  `value` INT NOT NULL DEFAULT '0',
  `label` char(64) NOT NULL DEFAULT '',
  `menu_order` tinyint NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `val` (`value`),
  KEY `menu_order` (`menu_order`)
) ENGINE=MyISAM;

INSERT INTO `menu_sms_mins_before` VALUES (-1,'Do not send reminder',0),(30,'30 minutes before Start Time',1),(60,'60 minutes before Start Time',2),(90,'90 minutes before Start Time',3),(1440,'1 day before Start Time',4),(2880,'2 days before Start Time',5);
