CREATE DATABASE IF NOT EXISTS `DB_CRUD`;
USE `DB_CRUD`;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` text,
  `password` text,
  `role` text,
  `createDate` text,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = latin1;


INSERT INTO `users` (`id`, `username`, `password`, `role`, `createDate`)
VALUES
	(1,'admin@email.com','12345','admin','01/01/2021'),
	(2,'user@email.com','12345','user','02/01/2021');

ALTER TABLE `users`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 3;
COMMIT;