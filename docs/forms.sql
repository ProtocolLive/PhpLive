CREATE TABLE `forms_forms` (
  `form_id` int UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `site` varchar(10),
  `form` varchar(10) NOT NULL,
  `method` varchar(10) NOT NULL,
  `action` varchar(100)
);

CREATE TABLE `forms_fields` (
  `field_id` int UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `form_id` int UNSIGNED NOT NULL,
  `field` varchar(10) NOT NULL,
  `db` varchar(10),
  `onlyedit` tinyint UNSIGNED NOT NULL DEFAULT 0,
  `type` varchar(10) NOT NULL,
  `size` tinyint UNSIGNED,
  `style` varchar(1024),
  `class` varchar(10),
  `js_event` varchar(10),
  `js_code` varchar(100),
  FOREIGN KEY (`form_id`) REFERENCES `forms_forms`(`form_id`)
);