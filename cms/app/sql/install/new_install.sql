--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `act_id` int(11) NOT NULL default '0',
  `act_date` date default NULL,
  `act_time` time default NULL,
  `act_end_time` time default NULL,
  `hours` decimal(4,2) default NULL,
  `completed` tinyint(4) NOT NULL default '0',
  `act_type` char(1) NOT NULL default 'T',
  `category` char(3) default NULL,
  `case_id` int(11) default NULL,
  `user_id` int(11) default NULL,
  `pba_id` int(11) default NULL,
  `funding` char(3) default NULL,
  `summary` varchar(75) default NULL,
  `notes` text,
  `last_changed` timestamp NOT NULL,
  created timestamp NULL DEFAULT NULL,
  last_changed_user_id int DEFAULT NULL,
  `om_code` char(3) default NULL,
  `ph_measured` mediumint(9) default NULL,
  `ph_estimated` mediumint(9) default NULL,
  `estimate_notes` tinytext,
  `act_end_date` date default NULL,
  `problem` char(2) default NULL,
  `location` varchar(5) default NULL,
  `media_items` smallint(6) default NULL,
  PRIMARY KEY  (`act_id`),
  KEY `ud` (`user_id`,`act_date`),
  KEY `case_id` (`case_id`),
  KEY `act_type` (`act_type`),
  KEY `act_date` (`act_date`),
  KEY `act_time` (`act_time`),
  KEY `completed` (`completed`)
) ENGINE = INNODB;

--
-- Table structure for table `aliases`
--

CREATE TABLE `aliases` (
  `alias_id` int(11) NOT NULL default '0',
  `contact_id` int(11) NOT NULL default '0',
  `primary_name` tinyint(4) NOT NULL default '0',
  `first_name` char(50) default NULL,
  `middle_name` char(50) default NULL,
  `last_name` char(50) default NULL,
  `extra_name` char(20) default NULL,
  `mp_first` char(8) default NULL,
  `mp_last` char(8) default NULL,
  `ssn` char(11) default NULL,
  PRIMARY KEY  (`alias_id`),
  KEY `first_name` (`first_name`),
  KEY `middle_name` (`middle_name`),
  KEY `extra_name` (`extra_name`),
  KEY `ssn` (`ssn`),
  KEY `contact_id` (`contact_id`),
  KEY `mp_first` (`mp_first`),
  KEY `mp_last` (`mp_last`),
  KEY `sorting` (`last_name`,`first_name`,`extra_name`,`middle_name`),
  KEY `last_name` (`last_name`),
  KEY `test` (`primary_name`,`contact_id`,`mp_first`,`mp_last`,`ssn`)
) ENGINE = INNODB;

-- 
-- Table structure for table `case_tabs`
-- 

CREATE TABLE `case_tabs` (
  `tab_id` tinyint(3) NOT NULL default '0',
  `name` varchar(65) NOT NULL default 'New Tab',
  `file` varchar(255) default NULL,
  `enabled` tinyint(1) default '1',
  `tab_order` tinyint(3) default '0',
  `autosave` tinyint(1) default '0',
  `tab_row` tinyint(3) default '1',
  `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `last_modified` timestamp NOT NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`tab_id`),
  KEY `order` (`tab_order`)
) ENGINE = INNODB;

--
-- Table structure for table `cases`
--

CREATE TABLE `cases` (
  `case_id` int(11) NOT NULL default '0',
  `number` varchar(24) default NULL,
  `client_id` int(11) default NULL,
  `user_id` int(11) default NULL,
  `cocounsel1` int(11) default NULL,
  `cocounsel2` int(11) default NULL,
  `office` char(3) default NULL,
  `problem` char(2) default NULL,
  `sp_problem` char(3) default NULL,
  `status` char(1) NOT NULL default '1',
  `open_date` date default NULL,
  `close_date` date default NULL,
  `close_code` char(3) default NULL,
  `reject_code` char(3) default NULL,
  `poten_conflicts` tinyint(4) NOT NULL default '1',
  `conflicts` tinyint(4) default NULL,
  `funding` char(3) default NULL,
  `undup` tinyint(4) default NULL,
  `referred_by` char(3) default NULL,
  `intake_type` char(3) default NULL,
  `intake_user_id` int(11) default NULL,
  `last_changed` timestamp NOT NULL,
  `created` TIMESTAMP NULL DEFAULT NULL,
  `income` decimal(9,2) default NULL,
  `assets` decimal(9,2) default NULL,
  `poverty` decimal(5,2) default NULL,
  `income_type0` char(3) default NULL,
  `annual0` decimal(9,2) default NULL,
  `income_type1` char(3) default NULL,
  `annual1` decimal(9,2) default NULL,
  `income_type2` char(3) default NULL,
  `annual2` decimal(9,2) default NULL,
  `income_type3` char(3) default NULL,
  `annual3` decimal(9,2) default NULL,
  `income_type4` char(3) default NULL,
  `annual4` decimal(9,2) default NULL,
  `asset_type0` char(3) default '1',
  `asset0` decimal(9,2) default NULL,
  `asset_type1` char(3) default '2',
  `asset1` decimal(9,2) default NULL,
  `asset_type2` char(3) default '3',
  `asset2` decimal(9,2) default NULL,
  `asset_type3` char(3) default '4',
  `asset3` decimal(9,2) default NULL,
  `asset_type4` char(3) default '5',
  `asset4` decimal(9,2) default NULL,
  `adults` tinyint(4) default NULL,
  `children` tinyint(4) default NULL,
  `persons_helped` tinyint(4) default NULL,
  `citizen` char(3) default NULL,
  `citizen_check` tinyint(4) default NULL,
  `client_age` smallint(3) default NULL,
  `outcome` char(3) default NULL,
  outcome_notes TEXT default NULL,
	outcome_income_after_service MEDIUMINT default NULL,
	outcome_income_no_service MEDIUMINT default NULL,
	outcome_assets_after_service MEDIUMINT default NULL,
	outcome_assets_no_service MEDIUMINT default NULL,
	outcome_debt_after_service MEDIUMINT default NULL,
	outcome_debt_no_service MEDIUMINT default NULL,
	ca_outcome_amount_obtained MEDIUMINT default NULL,
	ca_outcome_monthly_obtained MEDIUMINT default NULL,
	ca_outcome_amount_reduced MEDIUMINT default NULL,
	ca_outcome_monthly_reduced MEDIUMINT default NULL,
  `lsc_income_change` tinyint(4) default NULL,
  `just_income` char(3) default NULL,
  `main_benefit` varchar(4) default NULL,
  `sex_assault` tinyint(4) default NULL,
  `stalking` tinyint(4) default NULL,
  `case_county` varchar(25) default NULL,
  `rural` tinyint(4) default NULL,
  `good_story` tinyint(4) default NULL,
  `case_zip` varchar(15) default NULL,
  `elig_notes` varchar(150) default NULL,
  `cause_action` varchar(100) default NULL,
  `lit_status` char(3) default NULL,
  `judge_name` varchar(50) default NULL,
  `court_name` varchar(50) default NULL,
  `court_address` varchar(50) default NULL,
  `court_address2` varchar(50) default NULL,
  `court_city` varchar(25) default NULL,
  `court_state` varchar(25) default NULL,
  `court_zip` varchar(15) default NULL,
  `docket_number` varchar(20) default NULL,
  `date_filed` date default NULL,
  `protected` tinyint(4) default NULL,
  `why_protected` varchar(50) default NULL,
  `pba_id1` int(11) default NULL,
  `pba_id2` int(11) default NULL,
  `pba_id3` int(11) default NULL,
  `referral_date` date default NULL,
  `compensated` tinyint(4) default NULL,
  `thank_you_sent` tinyint(4) default NULL,
  `date_sent` date default NULL,
  `payment_received` tinyint(4) default NULL,
  `program_filed` tinyint(4) default NULL,
  `dollars_okd` decimal(8,2) default NULL,
  `hours_okd` decimal(8,2) default NULL,
  `destroy_date` date default NULL,
  `dom_viol` tinyint(4) default NULL,
  `veteran_household` tinyint(4) default NULL,
  `source_db` varchar(16) default NULL,
  `in_holding_pen` tinyint(4) default NULL,
  `doc1` int(11) default NULL,
  `doc2` int(11) default NULL,
  `vawa_served` tinyint(4) default NULL,
  `unread_sms` tinyint(4) NOT NULL default 0,
  lsc_justice_gap TINYINT,
  PRIMARY KEY  (`case_id`),
  UNIQUE KEY `number` (`number`),
  KEY `client_id` (`client_id`),
  KEY `office` (`office`),
  KEY `problem` (`problem`),
  KEY `status` (`status`),
  KEY `funding` (`funding`),
  KEY `open_date` (`open_date`),
  KEY `close_date` (`close_date`),
  KEY `user_id` (`user_id`),
  KEY `cocounsel1` (`cocounsel1`),
  KEY `cocounsel2` (`cocounsel2`)
) ENGINE = INNODB;


CREATE TABLE `compens` (
  `compen_id` int(11) NOT NULL default '0',
  `case_id` int(11) NOT NULL default '0',
  `billing_date` date default NULL,
  `payment_date` date default NULL,
  `billing_amount` decimal(8,2) default NULL,
  `payment_amount` decimal(8,2) default NULL,
  `billing_hours` decimal(8,2) default NULL,
  `notes` varchar(128) default NULL,
  `time_amount` decimal(8,2) default NULL,
  `expenses_amount` decimal(8,2) default NULL,
  `donated_amount` decimal(8,2) default NULL,
  PRIMARY KEY  (`compen_id`),
  KEY `case_id` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Table structure for table `conflict`
--

CREATE TABLE `conflict` (
  `conflict_id` int(11) NOT NULL default '0',
  `contact_id` int(11) NOT NULL default '0',
  `case_id` int(11) NOT NULL default '0',
  `relation_code` tinyint(4) NOT NULL default '0',
  PRIMARY KEY  (`conflict_id`),
  KEY `contact_id` (`contact_id`),
  KEY `case_id` (`case_id`),
  KEY `relation_code` (`relation_code`)
) ENGINE = INNODB;

--
-- Table structure for table `contacts`
--

CREATE TABLE `contacts` (
  `contact_id` int(11) NOT NULL default '0',
  `first_name` varchar(50) default NULL,
  `middle_name` varchar(25) default NULL,
  `last_name` varchar(50) NOT NULL default 'NONAME',
  `extra_name` varchar(10) default NULL,
  `alt_name` varchar(50) default NULL,
  `title` varchar(10) default NULL,
  `mp_first` varchar(8) default NULL,
  `mp_last` varchar(8) default NULL,
  `mp_alt` varchar(8) default NULL,
  `address` varchar(50) default NULL,
  `address2` varchar(50) default NULL,
  `city` varchar(25) default NULL,
  `state` varchar(25) default NULL,
  `zip` varchar(15) default NULL,
  `county` varchar(25) default NULL,
  `area_code` char(3) default NULL,
  `phone` varchar(15) default NULL,
  `phone_notes` varchar(50) default NULL,
  `area_code_alt` char(3) default NULL,
  `phone_alt` varchar(15) default NULL,
  `phone_notes_alt` varchar(50) default NULL,
  `email` varchar(50) default NULL,
  `org` varchar(35) default NULL,
  `birth_date` date default NULL,
  `ssn` varchar(11) default NULL,
  `language` char(3) default NULL,
  `gender` char(1) default NULL,
  `ethnicity` char(3) default NULL,
  `notes` text,
  `disabled` tinyint(4) default NULL,
  `residence` char(3) default NULL,
  `marital` char(3) default NULL,
  `frail` tinyint(4) default NULL,
  PRIMARY KEY  (`contact_id`),
  KEY `sorting` (`last_name`,`first_name`,`extra_name`,`middle_name`),
  KEY `mp_names` (`mp_last`,`mp_first`),
  KEY `ssn` (`ssn`),
  KEY `phone` (`phone`),
  KEY `phone_alt` (`phone_alt`)
) ENGINE = INNODB;

--
-- Table structure for table `counters`
--

CREATE TABLE `counters` (
  `id` char(32) NOT NULL default 'COUNTERNAME',
  `count` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE = INNODB;


-- 
-- Table structure for table `doc_storage`
-- 

CREATE TABLE `doc_storage` (
  `doc_id` int(11) NOT NULL default '0',
  `doc_name` varchar(255) NOT NULL default 'NONAME.txt',
  `doc_data` longblob,
  `doc_text` mediumtext,
  `doc_size` mediumint(9) default '0',
  `mime_type` varchar(255) default 'application/octet-stream',
  `doc_type` char(3) default 'C',
  `description` varchar(255) default NULL,
  `created` date default '0000-00-00',
  `case_id` int(11) default NULL,
  `user_id` int(11) default '0',
  `report_name` varchar(55) default NULL,
  `folder` tinyint(1) default '0',
  `folder_ptr` int(11) default NULL,
  PRIMARY KEY  (`doc_id`),
  KEY `case_id` (`case_id`),
  KEY `folder` (`folder`),
  KEY `folder_ptr` (`folder_ptr`),
  KEY `doc_type` (`doc_type`),
  KEY `report_name` (`report_name`)
) ENGINE=InnoDB;

-- 
-- Table structure for table `flags`
-- 

CREATE TABLE `flags` (
  `flag_id` int(11) NOT NULL default '0',
  `name` varchar(32) default 'pika_flag',
  `description` varchar(255) default NULL,
  `rules` text,
  `enabled` tinyint(1) default '0',
  `created` timestamp NULL default '0000-00-00 00:00:00',
  `last_modified` timestamp NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`flag_id`),
  KEY `name` (`name`)
) ENGINE = INNODB;

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `group_id` char(12) NOT NULL default 'NOGROUP',
  `read_office` char(64) default NULL,
  `read_all` tinyint(4) NOT NULL default '0',
  `edit_office` char(64) default NULL,
  `edit_all` tinyint(4) NOT NULL default '0',
  `users` tinyint(4) NOT NULL default '0',
  `pba` tinyint(4) NOT NULL default '0',
  `motd` tinyint(4) NOT NULL default '0',
  `reports` text,
  PRIMARY KEY  (`group_id`)
) ENGINE = INNODB;

--
-- Table structure for table `interviews`
--

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

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_act_type` (
  `value` char(1) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`menu_order`),
  KEY `label` (`label`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_annotate_activities` (
  `value` char(32) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`value`),
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_annotate_cases` (
  `value` char(32) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`value`),
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_annotate_contacts` (
  `value` char(32) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`value`),
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_asset_type` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `val` (`value`),
  KEY `menu_order` (`menu_order`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_attorney_status` (
  `value` char(1) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`menu_order`),
  KEY `label` (`label`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_case_status` (
  `value` char(1) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_category` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_citizen` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_close_code` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_close_code_2007` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_close_code_2008` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_comparison` (
  `value` tinyint(4) NOT NULL DEFAULT '0',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `val` (`value`),
  KEY `menu_order` (`menu_order`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_comparison_sql` (
  `value` varchar(25) NOT NULL DEFAULT '0',
  `label` varchar(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `val` (`value`),
  KEY `menu_order` (`menu_order`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_disposition` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_doc_type` (
  `value` char(1) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`menu_order`),
  KEY `label` (`label`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_dom_viol` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_ethnicity` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_funding` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_gender` (
  `value` char(1) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `val` (`value`),
  KEY `menu_order` (`menu_order`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_income_freq` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `val` (`value`),
  KEY `menu_order` (`menu_order`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_income_type` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_intake_type` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_just_income` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `val` (`value`),
  KEY `menu_order` (`menu_order`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_language` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_lit_status` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_lsc_income_change` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_lsc_other_services` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_main_benefit` (
  `value` char(4) NOT NULL DEFAULT '',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` smallint(6) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_marital` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_office` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_outcome` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_poverty` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_problem` (
  `value` char(2) NOT NULL DEFAULT '0',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_problem_2007` (
  `value` char(2) NOT NULL DEFAULT '0',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_problem_2008` (
  `value` char(2) NOT NULL DEFAULT '0',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_referred_by` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_reject_code` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_relation_codes` (
  `value` tinyint(4) NOT NULL DEFAULT '0',
  `label` char(30) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_report_format` (
  `value` char(4) NOT NULL DEFAULT '',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`menu_order`),
  KEY `label` (`label`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_residence` (
  `value` char(3) NOT NULL DEFAULT '',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_sp_problem` (
  `value` char(3) NOT NULL DEFAULT '0',
  `label` char(80) NOT NULL DEFAULT '',
  `menu_order` smallint(6) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_transfer_mode` (
  `value` tinyint(4) NOT NULL DEFAULT '0',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `val` (`value`),
  KEY `menu_order` (`menu_order`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_undup` (
  `value` tinyint(4) NOT NULL DEFAULT '0',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `val` (`value`),
  KEY `menu_order` (`menu_order`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_yes_no` (
  `value` tinyint(4) NOT NULL DEFAULT '0',
  `label` char(65) NOT NULL DEFAULT '',
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `val` (`value`),
  KEY `menu_order` (`menu_order`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

CREATE TABLE `menu_lsc_justice_gap` (
  `value` tinyint(4) DEFAULT NULL,
  `label` char(128) DEFAULT NULL,
  `menu_order` tinyint(4) NOT NULL DEFAULT '0',
  KEY `label` (`label`),
  KEY `menu_order` (`menu_order`),
  KEY `val` (`value`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Table structure for table `motd`
--
CREATE TABLE `motd` (
  `motd_id` int(11) NOT NULL default '0',
  `user_id` int(11) default NULL,
  `full_name` varchar(80) default NULL,
  `title` varchar(255) default NULL,
  `content` text,
  `last_modified` timestamp NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` timestamp NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`motd_id`)
) ENGINE = INNODB;

--
-- Table structure for table outcome_goals
--

CREATE TABLE outcome_goals (
  outcome_goal_id INT NOT NULL default '0',
  goal CHAR(255),
  problem CHAR(2),
  active TINYINT,
  outcome_goal_order INT,
  PRIMARY KEY  (outcome_goal_id),
  KEY problem (problem)
) ENGINE = INNODB;

--
-- Table structure for table outcome_goals
--

CREATE TABLE outcomes (
  outcome_id INT NOT NULL default '0',
  case_id INT,
  outcome_goal_id INT,
  result TINYINT,
  PRIMARY KEY  (outcome_id),
  KEY case_id (case_id)
) ENGINE = INNODB;

--
-- Table structure for table `pb_attorneys`
--

CREATE TABLE `pb_attorneys` (
  `pba_id` int(11) NOT NULL default '0',
  `atty_id` varchar(20) default NULL,
  `first_name` varchar(50) default NULL,
  `middle_name` varchar(20) default NULL,
  `last_name` varchar(50) default NULL,
  `extra_name` varchar(20) default NULL,
  `email` varchar(35) default NULL,
  `firm` varchar(255) default NULL,
  `phone_notes` varchar(50) default NULL,
  `address` varchar(50) default NULL,
  `address2` varchar(25) default NULL,
  `city` varchar(25) default NULL,
  `state` varchar(25) default NULL,
  `zip` varchar(15) default NULL,
  `county` varchar(50) default NULL,
  `languages` varchar(100) default NULL,
  `practice_areas` varchar(100) default NULL,
  `notes` varchar(50) default NULL,
  `last_case` date default NULL,
  `username` varchar(16) DEFAULT NULL,
  `password` varchar(64) DEFAULT NULL,
  `enabled` tinyint(4) DEFAULT '0',
  `session_data` text,
  PRIMARY KEY  (`pba_id`)
) ENGINE = INNODB;

--
-- Table structure for table `rss_feeds`
--

CREATE TABLE `rss_feeds` (
  `feed_id` int(11) NOT NULL default '0',
  `name` varchar(80) default 'No Name',
  `feed_url` tinytext,
  `feed_cache` mediumtext,
  `feed_type` tinyint(1) default NULL,
  `enabled` tinyint(1) default '0',
  `list_limit` tinyint(1) default '5',
  `last_modified` timestamp NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `created` timestamp NULL default '0000-00-00 00:00:00',
  PRIMARY KEY  (`feed_id`)
) ENGINE = INNODB;


-- 
-- Table structure for table `settings`
-- 

CREATE TABLE settings (
  `label` char(255) NOT NULL default '',
  `value` mediumtext NOT NULL,
  PRIMARY KEY  (`label`)
) ENGINE=InnoDB;

-- 
-- Table structure for table `transfer_options`
-- 

CREATE TABLE `transfer_options` (
  `transfer_option_id` int(11) NOT NULL default '0',
  `label` varchar(64) NOT NULL default 'NONAME',
  `url` varchar(128) NOT NULL default '',
  `transfer_mode` tinyint(4) default '1',
  `user` varchar(32) default NULL,
  `password` varchar(32) default NULL,
  PRIMARY KEY  (`transfer_option_id`)
) ENGINE = INNODB;


CREATE TABLE transfers (
  transfer_id INT NOT NULL default '0',
  user_id INT,
  json_data TEXT,
  created TIMESTAMP NULL DEFAULT NULL,
  accepted TINYINT,
  accepted_date TIMESTAMP,
  accepted_user_id INT,
  PRIMARY KEY  (transfer_id),
  KEY accepted (accepted)
) ENGINE = INNODB;


--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL default '0',
  `username` varchar(25) NOT NULL default '',
  `password` varchar(128) NOT NULL default '',
  `enabled` tinyint(4) NOT NULL default '0',
  `group_id` varchar(12) NOT NULL default 'NOGROUP',
  `first_name` varchar(50) default NULL,
  `middle_name` varchar(20) default NULL,
  `last_name` varchar(50) default 'NONAME',
  `extra_name` varchar(20) default NULL,
  `description` varchar(30) default NULL,
  `email` varchar(35) default NULL,
  `attorney` tinyint(4) default NULL,
  `atty_id` varchar(20) default NULL,
  `session_data` text,
  `firm` varchar(64) default NULL,
  `address` varchar(64) default NULL,
  `address2` varchar(64) default NULL,
  `city` varchar(24) default NULL,
  `state` varchar(24) default NULL,
  `zip` varchar(15) default NULL,
  `county` varchar(64) default NULL,
  `phone_notes` varchar(64) default NULL,
  `languages` varchar(64) default NULL,
  `practice_areas` varchar(64) default NULL,
  `notes` varchar(255) default NULL,
  `last_case` date default NULL,
  `password_expire` int(11) default '0',
  PRIMARY KEY  (`user_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE = INNODB;

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `user_session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `logout` tinyint(1) DEFAULT NULL,
  `session_id` varchar(32) NOT NULL,
  `ip_address` varchar(15) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created` int NULL DEFAULT NULL,
  `last_updated` int NULL DEFAULT NULL,
  PRIMARY KEY (`user_session_id`),
  UNIQUE KEY `session_id_value` (`session_id`),
  KEY `user_id` (`user_id`),
  KEY `enabled` (`logout`)
) ENGINE = INNODB;

--
-- Table structure for table `zip_codes`
--

CREATE TABLE `zip_codes` (
  `city` char(30) default NULL,
  `state` char(2) default NULL,
  `zip` char(5) default NULL,
  `area_code` char(3) default NULL,
  `county` char(27) default NULL
) ENGINE=MyISAM;

INSERT INTO `settings` VALUES ('act_interval','0'),('admin_email','admin@legalaidprogram'),('autofill_time_funding','1'),
('autonumber_on_new_case','1'),('cookie_prefix','Pika CMS at Legal Services Program'),('enable_benchmark','0'),('enable_compression','1'),('enable_system','1'),('force_https','1'),('owner_name','Legal Services Program'),('password_expire','0'),('pass_min_length','8'),('pass_min_strength','3'),('session_timeout','28800'),('time_zone','America/New_York'),('time_zone_offset','0'),('open_outcomes', '0'),('multi_outcomes', '1');

INSERT INTO `case_tabs` VALUES (1, 'Notes', 'case-act.php', 1, 1, 0, 1, '2009-07-21 17:34:51', '2009-12-29 23:08:37');
INSERT INTO `case_tabs` VALUES (2, 'Conflicts', 'case-conflict.php', 1, 2, 0, 1, '2009-07-21 17:39:16', '2009-11-23 15:11:04');
INSERT INTO `case_tabs` VALUES (3, 'Eligibility', 'case-elig.php', 1, 3, 0, 1, '2009-07-21 17:40:06', '2009-11-23 15:11:04');
INSERT INTO `case_tabs` VALUES (4, 'Info', 'case-info.php', 1, 4, 0, 1, '2009-07-21 17:40:25', '2009-12-30 00:19:58');
INSERT INTO `case_tabs` VALUES (5, 'Pro Bono', 'case-pb.php', 1, 5, 0, 1, '2009-12-30 11:43:36', '2009-11-23 15:11:29');
INSERT INTO `case_tabs` VALUES (6, 'Documents', 'case-docs.php', 1, 6, 0, 1, '2009-07-21 18:00:45', '2009-12-29 22:52:27');
INSERT INTO `case_tabs` VALUES (7, 'Compensation', 'case-compen.php', 1, 7, 0, 1, '2009-07-21 18:00:45', '2009-12-29 22:52:27');
INSERT INTO `case_tabs` VALUES (8, 'LITC', 'case-litc.php', 1, 8, 0, 1, '2009-07-21 18:00:45', '2009-12-29 22:52:27');

INSERT INTO counters VALUES ('users',1);
INSERT INTO counters VALUES ('pb_attorneys',0);
INSERT INTO counters VALUES ('motd',0);
INSERT INTO counters VALUES ('contacts',0);
INSERT INTO counters VALUES ('case_number',0);
INSERT INTO counters VALUES ('conflict',0);
INSERT INTO counters VALUES ('compens',0);
INSERT INTO counters VALUES ('charges',0);
INSERT INTO counters VALUES ('cases',0);
INSERT INTO counters VALUES ('case_charges',0);
INSERT INTO counters VALUES ('aliases',0);
INSERT INTO counters VALUES ('activities',0);
INSERT INTO counters VALUES ('doc_storage',0);
INSERT INTO counters VALUES ('flags',12);
INSERT INTO counters VALUES ('transfer_options',0);
INSERT INTO counters VALUES ('case_tabs',8);
INSERT INTO counters VALUES ('rss_feeds',3);

-- 
-- Dumping data for table `flags`
-- 

INSERT INTO `flags` VALUES (1, 'poverty_125', 'Client Over Income [125%]', 'a:1:{i:0;a:4:{s:10:"field_name";s:13:"cases.poverty";s:10:"comparison";s:1:"5";s:5:"value";s:3:"125";s:3:"and";a:2:{i:0;a:3:{s:14:"and_field_name";s:13:"cases.poverty";s:14:"and_comparison";s:1:"7";s:9:"and_value";s:5:"187.5";}i:1;a:3:{s:14:"and_field_name";s:17:"cases.just_income";s:14:"and_comparison";s:1:"1";s:9:"and_value";s:0:"";}}}}', 1, '2008-10-03 12:32:25', '0000-00-00 00:00:00');
INSERT INTO `flags` VALUES (2, 'poverty_187.5', 'Client Over Income [187.5%]', 'a:1:{i:0;a:3:{s:10:"field_name";s:13:"cases.poverty";s:10:"comparison";s:1:"5";s:5:"value";s:5:"187.5";}}', 1, '2008-10-03 12:29:02', '0000-00-00 00:00:00');
INSERT INTO `flags` VALUES (3, 'assets', 'Asset information is blank', 'a:1:{i:0;a:4:{s:10:"field_name";s:12:"cases.asset0";s:10:"comparison";s:1:"1";s:5:"value";s:0:"";s:3:"and";a:4:{i:0;a:3:{s:14:"and_field_name";s:12:"cases.asset1";s:14:"and_comparison";s:1:"1";s:9:"and_value";s:0:"";}i:1;a:3:{s:14:"and_field_name";s:12:"cases.asset2";s:14:"and_comparison";s:1:"1";s:9:"and_value";s:0:"";}i:2;a:3:{s:14:"and_field_name";s:12:"cases.asset3";s:14:"and_comparison";s:1:"1";s:9:"and_value";s:0:"";}i:3;a:3:{s:14:"and_field_name";s:12:"cases.asset4";s:14:"and_comparison";s:1:"1";s:9:"and_value";s:0:"";}}}}', 1, '2008-10-02 10:58:04', '0000-00-00 00:00:00');
INSERT INTO `flags` VALUES (4, 'household_size', 'Household Size Info is Blank', 'a:1:{i:0;a:4:{s:10:"field_name";s:14:"cases.children";s:10:"comparison";s:1:"7";s:5:"value";s:1:"1";s:3:"and";a:1:{i:0;a:3:{s:14:"and_field_name";s:12:"cases.adults";s:14:"and_comparison";s:1:"7";s:9:"and_value";s:1:"1";}}}}', 1, '2008-10-02 14:51:08', '0000-00-00 00:00:00');
INSERT INTO `flags` VALUES (5, 'citizenship', 'Citizenship Status is Blank', 'a:1:{i:0;a:3:{s:10:"field_name";s:13:"cases.citizen";s:10:"comparison";s:1:"1";s:5:"value";s:0:"";}}', 1, '2008-10-02 15:06:22', '0000-00-00 00:00:00');
INSERT INTO `flags` VALUES (6, 'invalid_citizenship', 'Invalid Citizenship Status', 'a:1:{i:0;a:4:{s:10:"field_name";s:13:"cases.citizen";s:10:"comparison";s:1:"3";s:5:"value";s:1:"A";s:3:"and";a:2:{i:0;a:3:{s:14:"and_field_name";s:13:"cases.citizen";s:14:"and_comparison";s:1:"3";s:9:"and_value";s:1:"B";}i:1;a:3:{s:14:"and_field_name";s:13:"cases.citizen";s:14:"and_comparison";s:1:"2";s:9:"and_value";s:0:"";}}}}', 1, '2008-10-02 15:14:33', '0000-00-00 00:00:00');
INSERT INTO `flags` VALUES (7, 'conflicts', 'This Case has a Conflict of Interest', 'a:1:{i:0;a:3:{s:10:"field_name";s:15:"cases.conflicts";s:10:"comparison";s:1:"5";s:5:"value";s:1:"0";}}', 1, '2008-10-02 16:04:32', '0000-00-00 00:00:00');
INSERT INTO `flags` VALUES (8, 'potential_conflicts', 'This Case has a Potential Conflict of Interest', 'a:1:{i:0;a:4:{s:10:"field_name";s:21:"cases.poten_conflicts";s:10:"comparison";s:1:"5";s:5:"value";s:1:"0";s:3:"and";a:2:{i:0;a:3:{s:14:"and_field_name";s:15:"cases.conflicts";s:14:"and_comparison";s:1:"3";s:9:"and_value";s:1:"0";}i:1;a:3:{s:14:"and_field_name";s:15:"cases.conflicts";s:14:"and_comparison";s:1:"3";s:9:"and_value";s:1:"1";}}}}', 1, '2008-10-03 12:21:09', '0000-00-00 00:00:00');
INSERT INTO `flags` VALUES (9, 'funding', 'Funding Code is Blank', 'a:1:{i:0;a:3:{s:10:"field_name";s:13:"cases.funding";s:10:"comparison";s:1:"1";s:5:"value";s:0:"";}}', 1, '2008-10-03 12:23:32', '0000-00-00 00:00:00');
INSERT INTO `flags` VALUES (10, 'income', 'Income Info is Blank', 'a:1:{i:0;a:4:{s:10:"field_name";s:13:"cases.annual0";s:10:"comparison";s:1:"1";s:5:"value";s:0:"";s:3:"and";a:4:{i:0;a:3:{s:14:"and_field_name";s:13:"cases.annual1";s:14:"and_comparison";s:1:"1";s:9:"and_value";s:0:"";}i:1;a:3:{s:14:"and_field_name";s:13:"cases.annual2";s:14:"and_comparison";s:1:"1";s:9:"and_value";s:0:"";}i:2;a:3:{s:14:"and_field_name";s:13:"cases.annual3";s:14:"and_comparison";s:1:"1";s:9:"and_value";s:0:"";}i:3;a:3:{s:14:"and_field_name";s:13:"cases.annual4";s:14:"and_comparison";s:1:"1";s:9:"and_value";s:0:"";}}}}', 1, '2008-10-03 12:26:13', '0000-00-00 00:00:00');
INSERT INTO `flags` VALUES (11, 'problem', 'LSC Problem Code is Blank', 'a:1:{i:0;a:3:{s:10:"field_name";s:13:"cases.problem";s:10:"comparison";s:1:"1";s:5:"value";s:0:"";}}', 1, '2008-10-03 12:40:36', '0000-00-00 00:00:00');
INSERT INTO `flags` VALUES (12, 'num_opposings', 'No Opposing Parties Have Been Entered', 'a:1:{i:0;a:3:{s:10:"field_name";s:15:"relation_code.2";s:10:"comparison";s:1:"7";s:5:"value";s:1:"1";}}', 1, '2008-10-03 12:41:38', '0000-00-00 00:00:00');

INSERT INTO `groups` VALUES ('system', NULL, 1, NULL, 1, 1, 1, 1, NULL);
INSERT INTO `groups` VALUES ('default', NULL, 1, NULL, 1, 0, 0, 0, NULL);

INSERT INTO `menu_act_type` VALUES ('N','Case Note',0),('L','LSC Other Services',1),('T','Time Slip',2),('K','Tickler',3),('C','Appointment',4);
INSERT INTO `menu_annotate_activities` VALUES ('act_id','act_id',0),('act_date','Activity Date',1),('act_time','Activity Start Time',2),('act_end_time','Activity End Time',3),('hours','Hours',4),('completed','Completed',5),('act_type','Type of Activity',6),('category','Category',7),('case_id','case_id',8),('user_id','User ID',9),('pba_id','PBA ID',10),('funding','Funding Source Code',11),('summary','Summary',12),('notes','Notes',13),('last_changed','Last Updated',14),('om_code','LSC OS Code',15),('ph_measured','LSC OS PH Measured',16),('ph_estimated','LSC OS PH Estimated',17),('estimate_notes','LSC OS Estimate Notes',18),('act_end_date','Activity End Date',19),('problem','LSC Problem Code',20),('location','Location',21),('media_items','OS Media Items',22);
INSERT INTO `menu_annotate_cases` VALUES ('number','Case Number',0),('user_id','Primary Attorney ID',1),('cocounsel1','Co-counsel #1 ID',2),('cocounsel2','Co-counsel #2 ID',3),('office','Office Code',4),('problem','LSC Problem Code',5),('sp_problem','Special Problem Code',6),('status','Case Status',7),('open_date','Open Date',8),('close_date','Closing Date',9),('close_code','Closing Code',10),('reject_code','Rejection Code',11),('poten_conflicts','Potential Conflicts Exist',12),('conflicts','Conflicts Exist',13),('funding','Funding Source Code',14),('undup','LSC Unduplicated Service',15),('referred_by','Referred By',16),('intake_type','Type of Intake',17),('intake_user_id','Intake User ID',18),('last_changed','Last Changed Date',19),('created','Created Date',20),('income','Total Income',21),('assets','Total Assets',22),('poverty','% of Poverty',23),('adults','# Adults',24),('children','# Children',25),('persons_helped','# Persons Helped',26),('citizen','Citizenship Status',27),('citizen_check','Citizenship Verified',28),('client_age','Primary Client Age',29),('dom_viol','Domestic Abuse',30),('outcome','Outcome',31),('just_income','Income Justification',32),('main_benefit','Main Benefit',33),('sex_assault','Sexual Assault',34),('stalking','Stalking',35),('case_county','Case County',36),('rural','Rural',37),('good_story','Good Story',38),('case_zip','Case Zipcode',39),('elig_notes','Eligibility Notes',40),('cause_action','Cause of Action',41),('lit_status','Litigation Status',42),('judge_name','Judge Name',43),('court_name','Court Name',44),('court_address','Court Address Line 1',45),('court_address2','Court Address Line 2',46),('court_city','Court City',47),('court_state','Court State',48),('court_zip','Court Zip',49),('docket_number','Docket Number',50),('date_filed','Filed Date',51),('protected','Protected Information',52),('why_protected','Reason Protected',53),('pba_id1','PBA ID #1',54),('pba_id2','PBA ID #2',55),('pba_id3','PBA ID #3',56),('referral_date','Referral Date',57),('compensated','Compensated',58),('thank_you_sent','Thank You Letter Sent',59),('date_sent','Sent Date',60),('payment_received','Payment Received',61),('program_filed','Program Filed',62),('dollars_okd','Dollars OKD',63),('hours_okd','Hours OKD',64),('destroy_date','Destroy Date',65),('in_holding_pen','In Holding Pen',66),('vawa_served','VAWA Service Level',67);
INSERT INTO `menu_annotate_contacts` VALUES ('contact_id','contact_id',0),('first_name','First Name',1),('middle_name','Middle Name',2),('last_name','Last Name',3),('extra_name','Extra Name',4),('alt_name','Alternative Name',5),('title','Title/Position',6),('mp_first','mp_first',7),('mp_last','mp_last',8),('mp_alt','mp_alt',9),('address','Address Line 1',10),('address2','Address Line 2',11),('city','City',12),('state','State',13),('zip','Zipcode',14),('county','County',15),('area_code','Area Code',16),('phone','Phone',17),('phone_notes','Phone Notes',18),('area_code_alt','Alt. Area Code',19),('phone_alt','Alt Phone',20),('phone_notes_alt','Alt. Phone Notes',21),('email','E-mail',22),('org','Organization',23),('birth_date','Date of Birth',24),('ssn','Social Security Number',25),('language','Language',26),('gender','Gender',27),('ethnicity','Ethnicity',28),('notes','Notes',29),('disabled','Disabled',30),('residence','Residence',31),('marital','Marital Status',32),('frail','Frail/Needy',33);
INSERT INTO `menu_asset_type` VALUES ('1','Personal Property',0),('2','Real Property',1),('3','Checking',2),('4','Savings',3),('5','Automobile',4),('9','Other',5);
INSERT INTO `menu_attorney_status` VALUES ('0','N/A',0),('1','Staff',1),('2','Volunteer',2);
INSERT INTO `menu_case_status` VALUES ('1','Pending',0),('2','Accepted',1),('5','Accepted/PAI',2),('4','Transferred',3),('6','Not Served',4);
INSERT INTO `menu_category` VALUES ('Cl','C1---Phone call or interview',0),('C2','C2---Review pleadings/correspondence',1),('C3','C3---Draft pleadings/correspondence',2),('C4','C4---Legal research/analysis',3),('C5','C5---Discovery/investigation',4),('C6','C6---Negotiations',5),('C7','C7---Hearing/trial preparation',6),('C8','C8---Administrative appearance',7),('C9','C9---Court appearance',8),('C10','C10--Travel',9),('C11','C11--Consultation re: case/client',10),('C12','C12--Grievances from clients',11),('C13','C13--Volunteer cases',12),('C14','C14--Appellate work',13),('C15','C15--Community work',14),('C16','C16--Legislative work',15),('C17','C17--Other',16),('M10','M10--Intake',17),('M11','M11--Grievances from non-clients',18),('M12','M12--Case acceptance and unit meetings',19),('M13','M13--Case review',20),('M20','M20--Outreach',21),('M21','M21--Community legal education',22),('M22','M22--Pro se and self-help',23),('M23','M23--Legal training of lay service providers',24),('M24','M24--Hotlines, questions/referrals',25),('M25','M25--Other collaboration and impact work',26),('M31','M31--Bar activities',27),('M32','M32--Volunteer recruitment/maintenance',28),('M33','M33--Placement efforts',29),('M34','M34--Training the bar, developing materials',30),('M35','M35--PBI administration.',31),('M40','M40--Training & task forces',32),('M41','M41--Tracking legal developments',33),('M50','M50--Supervising cases/matters',34),('M51','M51--Program management',35),('M52','M52--Training others, developing materials',36),('M60','M60--Matters travel',37),('M61','M61--Other/miscellaneous',38),('S10','S10--Supervision/personnel',39),('S11','S11--Evaluations',40),('S12','S12--Recruitment and hiring',41),('S13','S13--Union',42),('S21','S21--Office management',43),('S22','S22--Program management',44),('S23','S23--Fundraising',45),('S24','S24--Grants administration',46),('S25','S25--Training others, developing materials',47),('S26','S26--Boards',48),('S31','S31--Supporting Activities travel',49),('S32','S32--Time keeping',50),('S33','S33--Other/miscellaneous',51),('X1','X1---Vacation & Compensatory Leave',52),('X2','X2---Sick leave',53),('X3','X3---Holidays',54),('X4','X4---Other paid leave',55),('X5','X5---Leave without pay',56);
INSERT INTO `menu_citizen` VALUES ('A','Citizen',0),('B','Eligible Alien',1),('C','Undocumented Alien',2);
INSERT INTO `menu_close_code` VALUES ('A','Counsel and Advice',0),('B','Limited Action',1),('F','Negot. Settlement (w/o Lit.)',2),('G','Negot. Settlement (w/ Lit.)',3),('H','Admin. Agency Decision',4),('IA','Uncontested Court Decision',5),('IB','Contested Court Decision',6),('IC','Appeals',7),('K','Other',8),('L','Extensive Service',9);
INSERT INTO `menu_close_code_2007` VALUES ('A','Counsel and Advice',0),('B','Brief Service',1),('C','Referred after Legal Assess.',2),('D','Insufficient Merit to Proceed',3),('E','Client Withdrew',4),('F','Negot. Settlement (w/o Lit.)',5),('G','Negot. Settlement (w/ Lit.)',6),('H','Admin. Agency Decision',7),('I','Court Decision',8),('J','Change in Eligibility Status',9),('K','Other',10);
INSERT INTO `menu_close_code_2008` VALUES ('A','Counsel and Advice',0),('B','Limited Action',1),('F','Negot. Settlement (w/o Lit.)',2),('G','Negot. Settlement (w/ Lit.)',3),('H','Admin. Agency Decision',4),('IA','Uncontested Court Decision',5),('IB','Contested Court Decision',6),('IC','Appeals',7),('K','Other',8),('L','Extensive Service',9);
INSERT INTO `menu_comparison` VALUES (1,'is blank',0),(2,'is NOT blank',1),(3,'!= (NOT Equal)',2),(4,'== (Equals)',3),(5,'> (Greater Than)',4),(6,'>= (Greater Than or Equal)',5),(7,'< (Less Than)',6),(8,'<= (Less Than or Equal)',7),(9,'Between',8);
INSERT INTO `menu_comparison_sql` VALUES ('=','=',0),('LIKE','= (wildcard match)',1),('!=','NOT =',2),('>','&gt;',3),('<','&lt;',4),('between','between',5),('is blank','is blank',6),('is not blank','is not blank',7);
INSERT INTO `menu_disposition` VALUES ('11','Dismissed by Court',11),('12','Convicted: At Trial--Top Count',12),('13','Acquitted',13),('15','Convicted: At Trial--Lesser',15),('17','Extradited',17),('18','Conviction: By Plea-- Lesser',18),('28','Bench Warrant Issued',28),('31','ACD',31),('32','Relieved-LAS/18-B',32),('33','Relieved-Retained Counsel',33),('35','Conviction: By Plea--Top Count',35),('34','Transfered to different court',34),('36','Remand to Family Court',36),('37','Dismissed by Grand Jury',37),('38','Cut Slip Ordered',38),('39','Warrant Vacated',39),('40','Conflict Of Interest',40),('41','Dismissed by Prosecution',41),('42','Dismissed & Sealed',42),('43','Abated',43),('44','Hung Jury',44),('45','Consolidated',45),('46','Resentenced',46),('47','Resentenced to Probation',47),('48','Bench Trial-Guilty',48),('49','Bench Trial-Not Guilty',49),('50','Jury Trial-Guilty',50),('51','Jury Trial-Not Guilty',51),('52','No True Bill',52),('53','VOCD',53),('54','VOCD',54),('55','Relieved-Retained PVT. Counsel',55),('56','Dismissed-No True Bill',56),('57','D-730 EXAM',57),('58','Transferred to Family Court',58),('59','Probation Terminated',59);
INSERT INTO `menu_doc_type` VALUES ('C','Case Files',0),('U','User Files',1),('F','Forms',2),('R','Saved Report Files',3);
INSERT INTO `menu_dom_viol` VALUES ('0','No',0),('1','Yes - Abuse to Female',1),('2','Yes - Abuse to Male',2),('3','Yes (Don\'t Use)',3);
INSERT INTO `menu_ethnicity` VALUES ('10','White',0),('20','Black',1),('30','Hispanic',2),('40','Native American',3),('50','Asian, Pacific Islander',4),('99','Other',6);
INSERT INTO `menu_funding` VALUES ('1','LSC',0),('2','IOLTA',1),('3','HUD - FHIP',2),('4','Title III',3);
INSERT INTO `menu_gender` VALUES ('F','Female',0),('M','Male',1);
INSERT INTO `menu_income_freq` VALUES ('A','Annual',0),('M','Monthly',1),('B','Bi-Weekly',2),('W','Weekly',3);
INSERT INTO `menu_income_type` VALUES ('9','Spousal Maint.',0),('11','Worker\'s Comp.',1),('12','Disability',2),('1','Employment',3),('4','General Assistance',4),('3','SSI',5),('6','Child Support',6),('8','No Income',7),('18','Other',8),('13','Pension',9),('2','Social Security',10),('14','Trust, Interest, Div.',11),('15','Unemployment',12),('16','Veteran Benefits',13),('17','Senior, Unknown',14);
INSERT INTO `menu_intake_type` VALUES ('T','Telephone',0),('W','Walk-In',1),('C','Circuit Rider',2),('L','Letter',3);
INSERT INTO `menu_just_income` VALUES ('A','Govmt. Benefits Program',0),('B','High medical expenses',1),('C','Lack of Affordable Altern.',2),('D','Title III',3);
INSERT INTO `menu_language` VALUES ('A','Albanian',0),('B','Cambodian',1),('C','Creole',2),('D','Somali',3),('E','English',4),('F','French',6),('G','German',7),('H','Sign Language',8),('I','Italian',9),('J','Japanese',10),('K','Korean',11),('L','Hmong',12),('M','Mandarin',13),('O','Other',14),('P','Polish',15),('R','Russian',16),('S','Spanish',17),('T','Turkish',18),('V','Vietnamese',19),('W','Serbian',20),('X','Cantonese',21),('Y','Yiddish',22);
INSERT INTO `menu_lit_status` VALUES ('1','Defendant',0),('2','Petitioner',1),('3','Plaintiff',2),('4','Respondent',3),('5','Appellant',4);
INSERT INTO `menu_lsc_income_change` VALUES ('0','Not Likely to Change',0),('1','Likely to Increase',1),('2','Likely to Decrease',2);
INSERT INTO `menu_lsc_justice_gap` VALUES (1,'Unable to Serve - Ineligible',0),(2,'Unable to Serve - Conflict of Interest',1),(3,'Unable to Serve - Outside of Program Priorities or Case Acceptance Guidelines',2),(4,'Unable to Serve - Insufficient Resources',3),(5,'Unable to Serve - Other Reasons',4),(6,'Unable to Serve Fully - Insufficient Resources - Provision of Legal Information or Pro Se Resources',5),(7,'Unable to Serve Fully - Insufficient Resources - Provided Limited Service',6),(12,'Unable to Serve Fully - Insufficient Resources - Provided Some Extended Service',7),(8,'Fully Served - Provision of Legal Information or Pro Se Resources',8),(9,'Fully Served - Provision of Limited Services',9),(10,'Fully Served - Extended Service Case Accepted',10),(11,'Pending',11);
INSERT INTO `menu_lsc_other_services` VALUES ('101','101 - Presentations to community groups',0),('102','102 - Legal education brochures',1),('103','103 - Legal education materials posted on web sites',2),('104','104 - Newsletter articles addressing Legal Ed topics',3),('105','105 - Video legal education materials',4),('109','109 - Other CLE',5),('111','111 - Workshops or Clinics',6),('112','112 - Help desk at court',7),('113','113 - Self-help printed materials (e.g. divorce kits)',8),('114','114 - Self-help materials posted on web site',9),('115','115 - Self-help materials posted on kiosks',10),('119','119 - Other Pro Se assistance',11),('121','121 - Referred to other provider of civil legal services',12),('122','122 - Referred to private bar',13),('123','123 - Referred to provider of human or social services',14),('129','129 - Referred to other source of  assistance',15);
INSERT INTO `menu_main_benefit` VALUES ('0000','0000 00 No Main Benefit for Client',0),('0101','0101 01 Obtained federal bankruptcy protection',1),('0201','0201 02 Stopped debt collection harassment',2),('0301','0301 03 Overcame illegal sales contracts and/or warranties',3),('0401','0401 04 Overcame discrimination in obtaining credit',4),('0501','0501 05 Prevented or overcame utility termination',5),('0601','0601 06 Loans/Installment Purch.',6),('0701','0701 07 Prevented or overcame utility termination',7),('0801','0801 08 Unfair Sales Practices',8),('0901','0901 09 Obtained advice, brief services or referral on Consumer matter',9),('1103','1103 11 Obtained advice, brief services or referral on an Ed. matter',10),('2101','2101 21 Overcame job discrimination',11),('2201','2201 22 Obtained wages due',12),('2903','2903 29 Obtained advice, brief services or referral on Employment. matter',13),('3001','3001 30 Successful Adoption',14),('3102','3102 31 Obtained or preserved right to visitation',15),('3201','3201 32 Obtained a divorce, legal separation, or annulment',16),('3302','3302 33 Obtained guardianship for adoption for dependent child',17),('3401','3401 34 Name Change',18),('3501','3501 35 Prevented termination of parental rights',19),('3601','3601 36 Established paternity for a child',20),('3701','3701 37 Obtained protective order for victim of domestic violence',21),('3802','3802 38 Removed/Reduced Unfair Child Support',22),('3901','3901 39 Obtained advice, brief services or referral on a Family matter',23),('4101','4101 41 Delinquent',24),('4203','4203 42 Obtained advice, brief services or referral on Juvenile matter',25),('4901','4901 49 Other Juvenile',26),('5101','5101 51 Gained access to Medicare or Medicaid provider',27),('5201','5201 52 Obtained/preserved/increased Medicare or Medicaid benefits/rights',28),('5907','5907 59 Obtained advice, brief services or referral on a Health matter',29),('6101','6101 61 Obtained access to housing',30),('6201','6201 62 Avoided foreclosure or other loss of home',31),('6305','6305 63 Obtained repairs to dwelling',32),('6401','6401 64 Prevented denial of public housing tenant\'s rights',33),('6902','6902 69 Obtained advice, brief services or referral on a Housing matter',34),('7101','7101 71 Obtained/preserved/increased AFDC/other welfare benefit/right',35),('7201','7201 72 Black Lung',36),('7301','7301 73 Obtained/preserved/increased food stamps eligibility/right',37),('7401','7401 74 Social Security',38),('7501','7501 75 Obtained/preserved/increased SSI/SSD benefit/right',39),('7601','7601 76 Obtained/preserved/increased Unemployment comp. benefit/right',40),('7701','7701 77 Obtained/preserved/increased Veterans Benefits',41),('7801','7801 78 Obtained/preserved/increased Worker\'s Compensation',42),('7901','7901 79 Obtained advice, brief services or referral on an Income M. matter',43),('8105','8105 81 Other Immigration Benefit',44),('8201','8201 82 Mental Health',45),('8301','8301 83 Prisoner\'s Rights',46),('8402','8402 84 Obtained/preserved/increased benefits/rights of instit. persons',47),('8901','8901 89 Obtained advice, brief services or referral on an Ind. Rights matter',48),('9102','9102 91 Obtained assistance with other structural or governance issues.',49),('9201','9201 92 Indian / Tribal Law',50),('9301','9301 93 Overcame illegal taking of or restriction to a driver\'s license',51),('9401','9401 94 Defended a Torts action',52),('9502','9502 95 Obtained a living will or health proxy or power of attorney',53),('9901','9901 99 Obtained other benefit',54);
INSERT INTO `menu_marital` VALUES ('S','Single',0),('M','Married',1),('D','Divorced',2),('W','Widowed',3),('P','Separated',4);
INSERT INTO `menu_office` VALUES ('M','Main Office',0),('T','Townsville',1),('S','Springfield',2),('CC','Capitol City',3),('P','Parma',4);
INSERT INTO `menu_outcome` VALUES ('1','Hearing Won',0),('2','Hearing Lost',1),('3','Settled Favorably',2),('4','Settled Unfavorably',3),('5','Other Favorable',4),('6','Other Unfavorable',5),('7','No Effect',6),('8','Dismissed',7);
INSERT INTO `menu_poverty` VALUES ('0','4020',0),('1','11490',1),('2','15510',2),('3','19530',3),('4','23550',4),('5','27570',5),('6','31590',6),('7','35610',7),('8','39630',8);
INSERT INTO `menu_problem` VALUES ('01','01 - Bankruptcy/Debtor Relief',0),('02','02 - Collection (Repo/Def/Garnish)',1),('03','03 - Contracts/Warranties',2),('04','04 - Collection Practices/Creditor Harassment',3),('05','05 - Predatory Lending Practices (Not Mortgages)',4),('06','06 - Loans/Installment Purch.',5),('07','07 - Public Utilities',6),('08','08 - Unfair and Deceptive Sales Practices',7),('09','09 - Other Consumer/Finance.',8),('11','11 - Reserved',9),('12','12 - Discipline (Including Expulsion and Suspension)',10),('13','13 - Special Education/Learning Disabilities',11),('14','14 - Access to Education (Including Bilingual, Residency, Testing)',12),('15','15 - Vocational Education',13),('16','16 - Student Financial Aid',14),('19','19 - Other Education',15),('21','21 - Employment Discrimination',16),('22','22 - Wage Claims and other FLSA (Fair Labor Standards Act) Issues',17),('23','23 - EITC (Earned Income Tax Credit)',18),('24','24 - Taxes (Not EITC)',19),('25','25 - Employee Rights',20),('26','26 - Agricultural Worker Issues (Not Wage Claims/FLSA Issues)',21),('29','29 - Other Employment',22),('30','30 - Adoption',23),('31','31 - Custody/Visitation',24),('32','32 - Divorce/Separ./Annul.',25),('33','33 - Adult Guardianship/Conservatorship',26),('34','34 - Name Change',27),('35','35 - Parental Rights Termin.',28),('36','36 - Paternity',29),('37','37 - Domestic Abuse',30),('38','38 - Support',31),('39','39 - Other Family',32),('41','41 - Delinquent',33),('42','42 - Neglected/Abused/Depend.',34),('43','43 - Emancipation',35),('44','44 - Minor Guardianship/Conservatorship',36),('49','49 - Other Juvenile',37),('51','51 - Medicaid',38),('52','52 - Medicare',39),('53','53 - Government Children\'s Health Insurance Program',40),('54','54 - Home and Community Based Care',41),('55','55 - Private Health Insurance',42),('56','56 - Long Term Health Care Facilities',43),('57','57 - State and Local Health',44),('59','59 - Other Health',45),('61','61 - Fed. Subsidized Housing',46),('62','62 - Homeownership/Real Prop. (Not Foreclosure)',47),('63','63 - Private Landlord/Tenant',48),('64','64 - Public Housing',49),('65','65 - Mobile Homes',50),('66','66 - Housing Discrimination',51),('67','67 - Mortgage Foreclosure (Not Predatory Lending Practices)',52),('68','68 - Mortgage Predatory Lending/Practices',53),('69','69 - Other Housing',54),('71','71 - TANF',55),('72','72 - Social Security (Not SSDI)',56),('73','73 - Food Stamps / Commodities',57),('74','74 - SSDI',58),('75','75 - SSI',59),('76','76 - Unemployment Compensation',60),('77','77 - Veterans Benefits',61),('78','78 - State and Local Income Maintenance',62),('79','79 - Other Income Maintanence',63),('81','81 - Immigration / Natural.',64),('82','82 - Mental Health',65),('84','84 - Physically Disabled Rghts',66),('85','85 - Civil Rights',67),('86','86 - Human Trafficking',68),('89','89 - Other Individual Rights',69),('91','91 - Legal Assistance to Non-Profit Organization or Group (Including Inc./Dis.)',70),('92','92 - Indian / Tribal Law',71),('93','93 - Licenses (Drivers, Occupational, and Others)',72),('94','94 - Torts',73),('95','95 - Wills and Estates',74),('96','96 - Advance Directives/Powers of Attorney',75),('97','97 - Municipal Legal Needs',76),('99','99 - Other Miscellaneous',77);
INSERT INTO `menu_problem_2007` VALUES ('01','01 - Bankruptcy/Debtor Relief',0),('02','02 - Collection (Repo/Def/Garnish)',1),('03','03 - Contracts/Warranties',2),('04','04 - Credit Access',3),('05','05 - Energy (Other than Public Utils)',4),('06','06 - Loans/Installment Purch.',5),('07','07 - Public Utilities',6),('08','08 - Unfair Sales Practices',7),('09','09 - Other Consumer/Finance.',8),('11','11 - Education',9),('21','21 - Job Discrimination',10),('22','22 - Wage Claims',11),('29','29 - Other Employment',12),('30','30 - Adoption',13),('31','31 - Custody/Visitation',14),('32','32 - Divorce/Separ./Annul.',15),('33','33 - Guardianship / Conserv.',16),('34','34 - Name Change',17),('35','35 - Parental Rights Termin.',18),('36','36 - Paternity',19),('37','37 - Spouse Abuse',20),('38','38 - Support',21),('39','39 - Other Family',22),('41','41 - Delinquent',23),('42','42 - Neglected/Abused/Depend.',24),('49','49 - Other Juvenile',25),('51','51 - Medicaid',26),('52','52 - Medicare',27),('59','59 - Other Health',28),('61','61 - Fed. Subsidized Housing',29),('62','62 - Homeownership/Real Prop.',30),('63','63 - Landlord/Tenant not Pub.H',31),('64','64 - Other Public Housing',32),('69','69 - Other Housing',33),('71','71 - AFDC / Other Welfare',34),('72','72 - Black Lung',35),('73','73 - Food Stamps / Commodities',36),('74','74 - Social Security',37),('75','75 - SSI',38),('76','76 - Unemployment Compensation',39),('77','77 - Veterans Benefits',40),('78','78 - Worker\'s Compensation',41),('79','79 - Other Income Maintanence',42),('81','81 - Immigration / Natural.',43),('82','82 - Mental Health',44),('83','83 - Prisoner\'s Rights',45),('84','84 - Physically Disabled Rghts',46),('89','89 - Other Individual Rights',47),('91','91 - Incorporation / Diss.',48),('92','92 - Indian / Tribal Law',49),('93','93 - Licenses (Auto and Other)',50),('94','94 - Torts',51),('95','95 - Wills and Estates',52),('99','99 - Other Miscellaneous',53);
INSERT INTO `menu_problem_2008` VALUES ('01','01 - Bankruptcy/Debtor Relief',0),('02','02 - Collection (Repo/Def/Garnish)',1),('03','03 - Contracts/Warranties',2),('04','04 - Collection Practices/Creditor Harassment',3),('05','05 - Predatory Lending Practices (Not Mortgages)',4),('06','06 - Loans/Installment Purch.',5),('07','07 - Public Utilities',6),('08','08 - Unfair and Deceptive Sales Practices',7),('09','09 - Other Consumer/Finance.',8),('11','11 - Reserved',9),('12','12 - Discipline (Including Expulsion and Suspension)',10),('13','13 - Special Education/Learning Disabilities',11),('14','14 - Access to Education (Including Bilingual, Residency, Testing)',12),('15','15 - Vocational Education',13),('16','16 - Student Financial Aid',14),('19','19 - Other Education',15),('21','21 - Employment Discrimination',16),('22','22 - Wage Claims and other FLSA (Fair Labor Standards Act) Issues',17),('23','23 - EITC (Earned Income Tax Credit)',18),('24','24 - Taxes (Not EITC)',19),('25','25 - Employee Rights',20),('26','26 - Agricultural Worker Issues (Not Wage Claims/FLSA Issues)',21),('29','29 - Other Employment',22),('30','30 - Adoption',23),('31','31 - Custody/Visitation',24),('32','32 - Divorce/Separ./Annul.',25),('33','33 - Adult Guardianship/Conservatorship',26),('34','34 - Name Change',27),('35','35 - Parental Rights Termin.',28),('36','36 - Paternity',29),('37','37 - Domestic Abuse',30),('38','38 - Support',31),('39','39 - Other Family',32),('41','41 - Delinquent',33),('42','42 - Neglected/Abused/Depend.',34),('43','43 - Emancipation',35),('44','44 - Minor Guardianship/Conservatorship',36),('49','49 - Other Juvenile',37),('51','51 - Medicaid',38),('52','52 - Medicare',39),('53','53 - Government Children\'s Health Insurance Program',40),('54','54 - Home and Community Based Care',41),('55','55 - Private Health Insurance',42),('56','56 - Long Term Health Care Facilities',43),('57','57 - State and Local Health',44),('59','59 - Other Health',45),('61','61 - Fed. Subsidized Housing',46),('62','62 - Homeownership/Real Prop. (Not Foreclosure)',47),('63','63 - Private Landlord/Tenant',48),('64','64 - Public Housing',49),('65','65 - Mobile Homes',50),('66','66 - Housing Discrimination',51),('67','67 - Mortgage Foreclosure (Not Predatory Lending Practices)',52),('68','68 - Mortgage Predatory Lending/Practices',53),('69','69 - Other Housing',54),('71','71 - TANF',55),('72','72 - Social Security (Not SSDI)',56),('73','73 - Food Stamps / Commodities',57),('74','74 - SSDI',58),('75','75 - SSI',59),('76','76 - Unemployment Compensation',60),('77','77 - Veterans Benefits',61),('78','78 - State and Local Income Maintenance',62),('79','79 - Other Income Maintanence',63),('81','81 - Immigration / Natural.',64),('82','82 - Mental Health',65),('84','84 - Physically Disabled Rghts',66),('85','85 - Civil Rights',67),('86','86 - Human Trafficking',68),('89','89 - Other Individual Rights',69),('91','91 - Legal Assistance to Non-Profit Organization or Group (Including Inc./Dis.)',70),('92','92 - Indian / Tribal Law',71),('93','93 - Licenses (Drivers, Occupational, and Others)',72),('94','94 - Torts',73),('95','95 - Wills and Estates',74),('96','96 - Advance Directives/Powers of Attorney',75),('97','97 - Municipal Legal Needs',76),('99','99 - Other Miscellaneous',77);
INSERT INTO `menu_referred_by` VALUES ('Z','Farm Advocate',15),('Y','Adult Farm Mgmt.',14),('U','Unknown',13),('0','Other',12),('T','Telephone Book',11),('S','Social Agency',10),('Q','GA > SSI via DHS',9),('P','Prior Use',8),('L','Other LS Program',7),('G','Outreach',6),('F','Friend',5),('E','Family',4),('D','Community Organization',3),('C','Court',2),('B','Private Bar',1),('A','Advertisement',0);
INSERT INTO `menu_reject_code` VALUES ('10','Other',9),('9','Excessive Assets',8),('8','Likelihood of Success',7),('7','Conflict of Interest',6),('6','Non-critical Legal Need',5),('5','LSC Exclusion',4),('4','Affordable Altern. Avail.',3),('3','Fee Generating',2),('2','Out of Service Area',1),('1','Over Income',0);
INSERT INTO `menu_relation_codes` VALUES (1,'Client',0),(2,'Opposing Party',1),(3,'Opposing Counsel',2),(7,'Adverse Household',3),(6,'Non Adv. Household',4),(5,'Judge',5),(50,'Referral Agency',6),(99,'Other',7);
INSERT INTO `menu_report_format` VALUES ('html','Normal',0),('pdf','PDF',1),('csv','Spreadsheet',2);
INSERT INTO `menu_residence` VALUES ('A','Apartment',0),('B','Rented Home',1),('C','Condominium',2),('H','House',3),('I','Institutionalized',4),('J','Jail',5),('N','Nursing Home',6),('O','Assisted Living',7),('P','Prison',8),('T','Mobile Home',9),('X','Relatives',10),('Y','Shelter',11),('Z','Homeless',12);
INSERT INTO `menu_sp_problem` VALUES ('010','010 - Chapter 7 Bankruptcy',0),('011','011 - Chapter 13 Wage Bank.',1),('012','012 - Chapter 12 Bank. Farm',2),('015','015 - Farm Repossession Moratorium',3),('020','020 - Garnishment/Attachment',4),('021','021 - Repossession/Deficiency',5),('022','022 - Other Collection Practice',6),('023','023 - Liens - Mechanics, etc.',7),('024','024 - Farm Chattel Repossession',8),('025','025 - Farm Chattel Release',9),('026','026 - Farm Chattel Other Art. 9',10),('027','027 - Farm Foreclosure Non Home',11),('030','030 - Sales Contracts',12),('031','031 - Service Contracts',13),('032','032 - Inadequate Repairs',14),('033','033 - Defective Goods',15),('034','034 - Insurance Claims',16),('035','035 - Insurance Questions/Analysis',17),('036','036 - Cemetery Lots',18),('037','037 - Farm Lease - Chattel',19),('038','038 - Farm Lease - Realty',20),('040','040 - Credit Access',21),('041','041 - Farm Loan App FmHA',22),('042','042 - Farm Loan App Private Lender',23),('050','050 - Energy Other than Utilities.',24),('060','060 - Truth-in-Lending',25),('062','062 - Loans, Non-collection',26),('063','063 - Farm Loan - Negotiated w/ FmHA',27),('064','064 - Farm Loan - Negotiated w Priva',28),('065','065 - Farm Loan - Non-collection',29),('070','070 - Utility Shut-off',30),('071','071 - Other Utility',31),('080','080 - Unfair Sales Practices',32),('090','090 - Financial Problems Generally',33),('091','091 - Other Consumer',34),('092','092 - Farm Financial - Other',35),('110','110 - Sch. Disc.-Suspension',36),('111','111 - Sch. Disc.-Expulsion',37),('112','112 - Sch. Disc.-Other',38),('113','113 - Special Ed. - Elig/Assess',39),('114','114 - Special Ed. - Services',40),('115','115 - Special Ed. - Placement',41),('116','116 - Special Ed. - Discipline',42),('117','117 - Special Ed. - Other',43),('118','118 - Early Interv/Childhd Educ',44),('119','119 - Sec. 504 Sch. Accom',45),('120','120 - Homeless Student',46),('121','121 - LEP Student',47),('122','122 - Extracurricular Activity',48),('123','123 - Other Education Programs',49),('124','124 - Low Student Achievement',50),('125','125 - Grad. Requirements',51),('126','126 - Sch. Enrollment/Placement',52),('127','127 - Sch. Dist. Transfer',53),('128','128 - Truancy',54),('129','129 - Sch. Bus Transportation',55),('130','130 - Discrimination/Bias',56),('131','131 - Harassment/Maltreatment',57),('132','132 - Mental Health.Social Serv',58),('133','133 - Vocational Ed.',59),('134','134 - Student Loans',60),('135','135 - Other Education',61),('210','210 - Job Discrimination',62),('220','220 - Wage Claims',63),('221','221 - AWPA',64),('230','230 - Migrant & SAWPA Claims',65),('240','240 - Fair Labor Standards Act',66),('250','250 - Farm Labor Contract Regis',67),('260','260 - Pesticide Claims',68),('270','270 - H-2 & H-2a Workers',69),('280','280 - Wagner-Peyser Act',70),('290','290 - Employment Termination',71),('291','291 - CETA, WIN Other Training',72),('292','292 - Employment Conditions',73),('293','293 - Employment Contracts',74),('294','294 - Other Employment',75),('300','300 - Adoption',76),('310','310 - Visitation',77),('311','311 - Custody',78),('312','312 - Custody with Abuse',79),('313','313 - Visitation w/ Safety Iss.',80),('314','314 - Teenage Client Safety',81),('320','320 - Divorce/Separation',82),('330','330 - Guardian/Conservator',83),('340','340 - Name Change',84),('350','350 - Par. Rgts.Term. Prv.',85),('360','360 - Paternity',86),('370','370 - Family/HH Abuse',87),('371','371 - OFP threats/old evid.',88),('372','372 - OFP Custody',89),('373','373 - OFP Screening Problem',90),('374','374 - OFP Language/Cultural',91),('375','375 - OFP Interstate/Foreign',92),('376','376 - OFP for Minor',93),('377','377 - Abuse - Mediation',94),('378','378 - Abuse - Victim\'s Rights',95),('379','379 - Abuse - Other',96),('380','380 - Child Support',97),('383','383 - Rem/Red Unfair Csupport',98),('390','390 - Other Family',99),('410','410 - Delinquent',100),('420','420 - Dependency/Neglect',101),('490','490 - Status Offense',102),('491','491 - Other Juvenile',103),('510','510 - Medical Assistance',104),('520','520 - Medicare',105),('530','530 - Hill-Burton',106),('531','531 - GAMC',107),('532','532 - Other Health',108),('591','591 - Minnesota Care',109),('620','620 - Default, Delinquency',110),('621','621 - HUD Assignment',111),('622','622 - Contract for Deed Cancel',112),('623','623 - Mortgage Foreclosure',113),('625','625 - Purchase/Sale Real Prop.',114),('626','626 - Real Property Liens',115),('627','627 - Rehab Prog for Homeowners',116),('628','628 - Homestead Transfers',117),('629','629 - Other Real Property',118),('630','630 - Tenant Remedies Actions',119),('631','631 - Rent W/H & UD (Fritz)',120),('632','632 - Other Maint/Repair Prob',121),('633','633 - Other Private UD',122),('634','634 - Lockout/Distraint',123),('635','635 - Utility shut-off by LL',124),('636','636 - Action for Rent by LL',125),('637','637 - Security Deposits',126),('638','638 - Other $ Claim by Tenant',127),('639','639 - Other Private LL/Tenant',127),('640','640 - Public Housing Admissions',127),('641','641 - Public Hsng Evict - No UD',127),('642','642 - Public Hsng UD',127),('649','649 - Public Hsing - Other',127),('650','650 - Sec 8 Admission/Cert',127),('651','651 - Sec 8 Evictions - No UD',127),('652','652 - Sec 8 UD',127),('653','653 - Sec 8 Term of Certificate',127),('659','659 - Other Section 8',127),('660','660 - Sec 221/236 Admissions',127),('661','661 - Sec 221/236 Evict - No UD',127),('662','662 - Sec 221/236 Subsidized',127),('681','681 - Farm Moratorium - Homeste',127),('682','682 - Farm Cont Cancel - Home',127),('683','683 - Farm Foreclosure - Home',127),('684','684 - Farm Loan - FmHA - Home',127),('685','685 - Farm Loan/ Private/  Home',127),('690','690 - Discrimination',127),('691','691 - Displacement',127),('697','697 - Expungement - Criminal',127),('699','699 - Miscellaneous Other',127),('710','710 - MFIP Appl/Eligibility',127),('711','711 - MFIP Financial',127),('712','712 - MFIP Social Svcs IV-D',127),('713','713 - GA Eligibility',127),('714','714 - GA Financial',127),('715','715 - GA - Service',127),('716','716 - MSA',127),('717','717 - Other Soc Svcs - WIN',127),('718','718 - Other Welfare - Child Wel',127),('719','719 - EA and EGA',127),('720','720 - Mental Health',127),('721','721 - Child Care Disputes',127),('722','722 - Employment Sanction',127),('723','723 - Paternity Sanction',127),('724','724 - Full Family Sanction',127),('725','725 - Five Year Limit Terminati',127),('726','726 - Expungement - Criminal',127),('730','730 - Food Stamps - Eligibility',127),('731','731 - Food Stamps - Financial',127),('732','732 - Other Food Stamps',127),('740','740 - OASDI - Overpay/Financial',127),('741','741 - OASDI Disability Issues',127),('742','742 - OASDI - SSA Other',127),('750','750 - SSI - Overpayments/Financ',127),('751','751 - SSI Disability',127),('752','752 - SSI - Other',127),('753','753 - Ramsey County SSI Contrac',127),('760','760 - Unemployment Compens.',127),('770','770 - Veteran\'s Benefits',127),('780','780 - Worker\'s Compensation',127),('790','790 - Other Government Benefits',127),('810','810 - Immigration/Nat.',127),('811','811 - Citizenship',127),('820','820 - Commitment Generally',127),('821','821 - Restoration to Capacity',127),('822','822 - Challenge to Orig Commit',127),('823','823 - Condition of Confinement',127),('824','824 - Change in Commitment',127),('825','825 - Other Mental Health',127),('830','830 - Prisoner\'s Rights',127),('840','840 - Physically Disabled Rgts.',127),('841','841 - Other Disabled Person Rts',127),('890','890 - Other Individual Rights',127),('910','910 - Incorporation/Dissolution',127),('920','920 - Indian Tribal Law',127),('930','930 - Licenses (Auto & Other)',127),('940','940 - Torts',127),('941','941 - Negligence - Plaintiff',127),('942','942 - Negligence - Defendant',127),('943','943 - Intentional Torts',127),('950','950 - Wills',127),('951','951 - Estate Plan/Inheritance',127),('952','952 - Cert. of Survivorship',127),('953','953 - Other Estate / Probate',127),('960','960 - Tax Issues',127),('990','990 - Other Miscellaneous',127);
INSERT INTO `menu_transfer_mode` VALUES (1,'Pika->Pika',0);
INSERT INTO `menu_undup` VALUES (1,'Unduplicated Service',0),(0,'Duplicated Service',1);
INSERT INTO `menu_yes_no` VALUES (1,'Yes',0),(0,'No',1);

INSERT INTO `rss_feeds` VALUES (1, 'Pika Software Blog', 'http://pikasoftware.blogspot.com/feeds/posts/default?alt=atom', '', 2, 0, 3, '2009-12-30 10:39:08', '2009-04-10 11:00:00');
INSERT INTO `rss_feeds` VALUES (2, 'LSNTAP', 'http://lsntap.org/rss.xml', '', 1, 0, 3, '2009-12-30 10:39:21', '2009-04-16 11:36:06');
INSERT INTO `rss_feeds` VALUES (3, 'Legal Services Corp - LSC Updates', 'http://lsc.gov/lscfeed.xml', '', 1, 0, 3, '2011-09-08 14:27:51', '2011-09-08 14:27:21');

-- 
-- Dumping data for table `users`
-- 

INSERT INTO `users` VALUES (1, 'pikasupport', '', 1, 'system', 'Default', NULL, 'Account', NULL, NULL, 'support@legalservices.org', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
