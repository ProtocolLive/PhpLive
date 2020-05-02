CREATE TABLE `sys_logs` (
  `log_id` int UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `time` datetime NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `log` int UNSIGNED NOT NULL,
  `target` int UNSIGNED DEFAULT NULL,
  `ip` varchar(100) NOT NULL,
  `ipreverse` varchar(255) NOT NULL,
  `agent` varchar(255) NOT NULL,
  `query` varchar(1024) NOT NULL
);