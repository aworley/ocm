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
