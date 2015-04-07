<?php

$queries = [];

$queries[] = <<<SQL
create table if not exists `entries` (
  `entry_id` int(11) unsigned not null auto_increment,
  `schema_id` varchar(64) not null,
  `user_id` int(11) unsigned not null,
  `creation_date` datetime not null,
  `modification_date` datetime not null,
  primary key (`entry_id`),
  key `schema_id` (`schema_id`),
  key `schema_id_to_entry_id` (`entry_id`, `schema_id`),
  key `creation_date` (`creation_date`),
  key `modification_date` (`modification_date`)
);
SQL;

$queries[] = <<<SQL
create table if not exists `forgotpass` (
  `user_id` int(11) unsigned not null,
  `token` varchar(6) not null,
  `expires` datetime not null,
  primary key (`user_id`)
);
SQL;

$queries[] = <<<SQL
create table if not exists `schemas` (
  `schema_id` varchar(64) not null,
  `object` text,
  primary key (`schema_id`)
);
SQL;

$queries[] = <<<SQL
create table if not exists `sessions` (
  `session` varchar(255) not null,
  `expires` int(10) unsigned not null default '0',
  `data` text,
  primary key (`session`)
);
SQL;

$queries[] = <<<SQL
create table if not exists `users` (
  `user_id` int(11) unsigned not null auto_increment,
  `username` varchar(20) not null default '',
  `password` varchar(255) not null default '',
  `first_name` varchar(100) default null,
  `last_name` varchar(100) default null,
  `email` varchar(255) default null,
  `last_seen` datetime default '0000-00-00 00:00:00',
  `default_section` varchar(255) not null,
  `auth_token_active` enum('yes','no') not null default 'no',
  `language` varchar(15) default null,
  primary key (`user_id`),
  unique key `username` (`username`)
);
SQL;

return $queries;