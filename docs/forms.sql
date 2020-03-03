CREATE TABLE `forms_forms`(
  `form_id` int UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `site` varchar(10),
  `form` varchar(15) NOT NULL,
  `method` varchar(10) NOT NULL,
  `action` varchar(100),
  `autocomplete` tinyint UNSIGNED NOT NULL DEFAULT 1
);

CREATE TABLE `forms_fields`(
  `field_id` int UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `form_id` int UNSIGNED NOT NULL,
  `label` varchar(16),
  `name` varchar(15),
  `default` varchar(20),
  `type` varchar(10) NOT NULL,
  `onlyedit` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `size` tinyint UNSIGNED,
  `style` varchar(1024),
  `class` varchar(10),
  `js_event` varchar(10),
  `js_code` varchar(512),
  `order` tinyint UNSIGNED NOT NULL DEFAULT 100,
  FOREIGN KEY (`form_id`) REFERENCES `forms_forms`(`form_id`) ON DELETE CASCADE ON UPDATE CASCADE
);