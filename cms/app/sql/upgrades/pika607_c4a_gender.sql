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
