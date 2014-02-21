CREATE TABLE `statusgruppen_additional` (
  `statusgruppe_id` varchar(32) NOT NULL,
  `waitinglist` enum('true','false') NOT NULL DEFAULT 'false',
  `visible` enum('true','false') NOT NULL DEFAULT 'true',
  PRIMARY KEY (`statusgruppe_id`)
);