create table sys_groups(
  group_id int unsigned not null auto_increment primary key,
  group varchar(45) not null unique key
);
insert into sys_groups(group) values('Everyone'),('Authenticated users'),('Administrators');

create table sys_resources(
  resource_id int unsigned not null auto_increment primary key,
  site varchar(100),
  resource varchar(100) not null
);

create table sys_perms(
  perm_id int unsigned not null auto_increment primary key,
  user_id int unsigned,
  group_id int unsigned,
  resource_id int unsigned not null,
  r tinyint unsigned not null default 0,
  w tinyint unsigned not null default 0,
  o tinyint unsigned not null default 0
);

create table sys_usergroup(
  usergroup_id int unsigned not null auto_increment primary key,
  user_id int unsigned not null,
  group_id int unsigned not null
);

alter table sys_perms
  add constraint sys_perms_ibfk_1 foreign key(resource_id) references sys_resources(resource_id) on delete CASCADE on update CASCADE,
  add constraint sys_perms_ibfk_2 foreign key(group_id) references sys_groups(group_id) on delete CASCADE on update CASCADE;

alter table sys_usergroup
  add constraint sys_usergroup_ibfk_1 foreign key(group_id) references sys_groups(group_id) on delete CASCADE on update CASCADE;