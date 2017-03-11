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
