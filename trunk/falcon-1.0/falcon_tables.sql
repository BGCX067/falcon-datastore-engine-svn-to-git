
-- --------------------------------------------------------

--
-- 테이블 구조 `falcon_datamap`
--

CREATE TABLE IF NOT EXISTS `falcon_datamap` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `super` int(10) unsigned NOT NULL,
  `document` int(10) unsigned NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `document` (`document`),
  KEY `key` (`key`(10)),
  KEY `value` (`value`(10))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 테이블 구조 `falcon_datastore`
--

CREATE TABLE IF NOT EXISTS `falcon_datastore` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `uuid` char(36) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `uuid` (`uuid`(8))
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- 테이블 구조 `falcon_document`
--

CREATE TABLE IF NOT EXISTS `falcon_document` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `datastore` int(10) unsigned NOT NULL,
  `documentName` varchar(255) NOT NULL,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`),
  KEY `datastore` (`datastore`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
