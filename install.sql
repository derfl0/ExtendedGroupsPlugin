CREATE TABLE `statusgruppen_additional` (
  `statusgruppe_id` varchar(32) NOT NULL,
  `waitinglist` enum('true','false') NOT NULL DEFAULT 'false',
  `visible` enum('true','false') NOT NULL DEFAULT 'true',
  `termine` enum('true','false') NOT NULL DEFAULT 'false',
  PRIMARY KEY (`statusgruppe_id`)
);

CREATE TABLE `statusgruppen_termine` (
`statusgruppe_id` varchar(32) NOT NULL,
`assign_id` varchar(32) NOT NULL,
PRIMARY KEY (`statusgruppe_id`,`assign_id`)
);