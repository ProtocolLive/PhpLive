CREATE TABLE `sys_groups` (
  `group_id` int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `group` varchar(45) NOT NULL UNIQUE KEY
);
insert into sys_groups(`group`) values('Everyone'),('Authenticated users'),('Administrators');

CREATE TABLE `sys_resources` (
  `resource_id` int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `resource` varchar(45) NOT NULL UNIQUE KEY
);

CREATE TABLE `sys_perms` (
  `perm_id` int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `user_id` int unsigned DEFAULT NULL,
  `group_id` int unsigned DEFAULT NULL,
  `resource_id` int unsigned NOT NULL,
  `r` tinyint unsigned NOT NULL DEFAULT 0,
  `w` tinyint unsigned NOT NULL DEFAULT 0,
  `o` tinyint unsigned NOT NULL DEFAULT 0,
  FOREIGN KEY (`resource_id`) REFERENCES `sys_resources` (`resource_id`),
  FOREIGN KEY (`group_id`) REFERENCES `sys_groups` (`group_id`)
);

CREATE TABLE `sys_usergroup` (
  `usergroup_id` int unsigned NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `group_id` int unsigned NOT NULL
);