CREATE TABLE `users`
(
  `id` int
(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar
(255) DEFAULT NULL,
  `email` varchar
(255) DEFAULT NULL,
  `phone` varchar
(45) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY
(`id`)
) ENGINE=InnoDB