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
INSERT INTO `menu_lsc_justice_gap` VALUES (1,'Unable to Serve - Ineligible',0),(2,'Unable to Serve - Conflict of Interest',1),(3,'Unable to Serve - Outside of Program Priorities or Case Acceptance Guidelines',2),(4,'Unable to Serve - Insufficient Resources',3),(5,'Unable to Serve - Other Reasons',4),(6,'Unable to Serve Fully - Insufficient Resources - Provision of Legal Information or Pro Se Resources',5),(7,'Unable to Serve Fully - Insufficient Resources - Provided Limited Service or Closing Code L',6),(8,'Fully Served - Provision of Legal Information or Pro Se Resources',7),(9,'Fully Served - Provision of Limited Services or Closing Code L',8),(10,'Fully Served - Extended Service Case Accepted',9),(11,'Pending',10);
