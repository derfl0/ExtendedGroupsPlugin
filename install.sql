CREATE TABLE `statusgruppen_additional` (
`statusgruppe_id` varchar(32) NOT NULL,
`waitinglist` tinyint(1) NOT NULL DEFAULT '0',
`visible` tinyint(1) NOT NULL DEFAULT '0',
`termine` tinyint(1) NOT NULL DEFAULT '0',
PRIMARY KEY (`statusgruppe_id`)
);
CREATE TABLE `statusgruppen_termine` (
`statusgruppe_id` varchar(32) COLLATE latin1_bin NOT NULL,
`assign_id` varchar(32) COLLATE latin1_bin NOT NULL,
PRIMARY KEY (`statusgruppe_id`,`assign_id`)
);