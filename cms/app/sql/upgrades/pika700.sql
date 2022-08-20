ALTER TABLE contacts 
  ADD COLUMN c4a_gender CHAR(3),
  ADD COLUMN c4a_gender_notes VARCHAR(255),
  ADD COLUMN c4a_sex_at_birth CHAR(1),
  ADD COLUMN c4a_identity CHAR(3),
  ADD COLUMN c4a_identity_notes VARCHAR(255);

CREATE TABLE menu_c4a_gender (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`menu_order`),
  KEY `label` (`label`),
  KEY `val` (`value`)
) ENGINE=MyISAM;

CREATE TABLE menu_c4a_sex_at_birth (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`menu_order`),
  KEY `label` (`label`),
  KEY `val` (`value`)
) ENGINE=MyISAM;

CREATE TABLE menu_c4a_identity (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`menu_order`),
  KEY `label` (`label`),
  KEY `val` (`value`)
) ENGINE=MyISAM;

INSERT INTO `menu_c4a_gender` VALUES ('M','Male',0),('F','Female',1),('TFM','Transgender Female to Male',2),('TMF','Transgender Male to Female',3),('GNB','Genderqueer/Gender Non-binary',4),('NL','Not listed, please specify',5),('D','Declined/not stated',6);
INSERT INTO `menu_c4a_sex_at_birth` VALUES ('M','Male',0),('F','Female',1),('D','Declined/not stated',2);
INSERT INTO `menu_c4a_identity` VALUES ('S','Straight/Heterosexual',0),('B','Bisexual',1),('GLS','Gay/Lesbian/Same-Gender Loving',2),('QU','Questioning/Unsure',3),('NL','Not listed.  Please specify',4),('D','Declined/not stated',5);

select count(*) into @old_column from information_schema.columns
  where table_schema = DATABASE() and table_name = 'cases'
  and column_name LIKE 'justice_gap_2017';

set @q = if (@old_column > 0,
  'ALTER TABLE cases CHANGE COLUMN justice_gap_2017 lsc_justice_gap TINYINT',
  'ALTER TABLE cases ADD COLUMN lsc_justice_gap TINYINT');

prepare sm0 from @q;
execute sm0;



DROP TABLE IF EXISTS `menu_justice_gap_2017`;

CREATE TABLE `menu_lsc_justice_gap` (
  `value` tinyint(4) DEFAULT NULL,
  `label` char(128) DEFAULT NULL,
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
INSERT INTO `menu_lsc_justice_gap` VALUES (1,'Unable to Serve - Ineligible',0),(2,'Unable to Serve - Conflict of Interest',1),(3,'Unable to Serve - Outside of Program Priorities or Case Acceptance Guidelines',2),(4,'Unable to Serve - Insufficient Resources',3),(5,'Unable to Serve - Other Reasons',4),(6,'Unable to Serve Fully - Insufficient Resources - Provision of Legal Information or Pro Se Resources',5),(7,'Unable to Serve Fully - Insufficient Resources - Provided Limited Service',6),(12,'Unable to Serve Fully - Insufficient Resources - Provided Some Extended Service',7),(8,'Fully Served - Provision of Legal Information or Pro Se Resources',8),(9,'Fully Served - Provision of Limited Services',9),(10,'Fully Served - Extended Service Case Accepted',10),(11,'Pending',11);

CREATE TABLE `interviews` (
  `interview_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(255) DEFAULT 'INTERVIEW',
  `interview_text` mediumtext,
  `enabled` tinyint(1) DEFAULT '0',
  `last_modified` timestamp NULL DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`interview_id`),
  KEY `name` (`name`)
) ENGINE = INNODB;

ALTER TABLE cases 
ADD COLUMN lsc_cfr_1605 TINYINT default NULL,
ADD COLUMN lsc_cfr_1609 TINYINT default NULL AFTER lsc_cfr_1605,
ADD COLUMN lsc_cfr_1612 TINYINT default NULL AFTER lsc_cfr_1609,
ADD COLUMN lsc_cfr_1620 TINYINT default NULL AFTER lsc_cfr_1612,
ADD COLUMN lsc_cfr_1633 TINYINT default NULL AFTER lsc_cfr_1620,
ADD COLUMN lsc_cfr_1636 TINYINT default NULL AFTER lsc_cfr_1633,
ADD COLUMN lsc_cfr_1637 TINYINT default NULL AFTER lsc_cfr_1636;

ALTER TABLE cases
CHANGE outcome_income_before outcome_income_after_service MEDIUMINT default NULL,
CHANGE outcome_income_after outcome_income_no_service MEDIUMINT default NULL,
CHANGE outcome_assets_before outcome_assets_after_service MEDIUMINT default NULL,
CHANGE outcome_assets_after outcome_assets_no_service MEDIUMINT default NULL,
CHANGE outcome_debt_before outcome_debt_after_service MEDIUMINT default NULL,
CHANGE outcome_debt_after outcome_debt_no_service MEDIUMINT default NULL;

ALTER TABLE cases 
ADD COLUMN ca_outcome_amount_obtained MEDIUMINT default NULL AFTER outcome_debt_no_service,
ADD COLUMN ca_outcome_monthly_obtained MEDIUMINT default NULL AFTER ca_outcome_amount_obtained,
ADD COLUMN ca_outcome_amount_reduced MEDIUMINT default NULL AFTER ca_outcome_monthly_obtained,
ADD COLUMN ca_outcome_monthly_reduced MEDIUMINT default NULL AFTER ca_outcome_amount_reduced;

INSERT INTO `settings` VALUES ('ca_iolta_outcomes', '0');

ALTER TABLE outcome_goals MODIFY goal CHAR(255);
ALTER TABLE cases ADD COLUMN udf JSON;

CREATE TABLE `screens` (
  `screen_name` char(32) NOT NULL,
  `screen_fields` text,
  `screen_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `udfs` (
  `udf_id` int(11) DEFAULT NULL,
  `label` char(64) DEFAULT NULL,
  `data_type` char(2) DEFAULT NULL,
  `table_name` char(32) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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
