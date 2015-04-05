<?php

$queries = [];

$queries[] = <<<SQL
CREATE TABLE IF NOT EXISTS `entries` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `schema` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `user` int(11) unsigned NOT NULL,
  `creation_date` datetime NOT NULL,
  `modification_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `schema` (`schema`),
  KEY `user` (`user`),
  KEY `creation_date` (`creation_date`),
  KEY `modification_date` (`modification_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

$queries[] = <<<SQL
CREATE TABLE IF NOT EXISTS `forgotpass` (
  `user` int(11) NOT NULL DEFAULT '0',
  `token` varchar(6) COLLATE utf8_unicode_ci NOT NULL,
  `expiry` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

$queries[] = <<<SQL
CREATE TABLE IF NOT EXISTS `sync` (
  `handle` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `guid` varchar(13) COLLATE utf8_unicode_ci NOT NULL,
  `object` text,
  PRIMARY KEY (`guid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE utf8_unicode_ci;
SQL;

$queries[] = <<<SQL
CREATE TABLE IF NOT EXISTS `sessions` (
  `session` varchar(255) CHARACTER SET utf8 NOT NULL,
  `expires` int(10) unsigned NOT NULL DEFAULT '0',
  `data` text CHARACTER SET utf8,
  PRIMARY KEY (`session`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

$queries[] = <<<SQL
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `first_name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_seen` datetime DEFAULT '0000-00-00 00:00:00',
  `default_section` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `auth_token_active` enum('yes','no') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
  `language` varchar(15) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
SQL;

return $queries;
