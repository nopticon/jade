
-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `_config`
--

CREATE TABLE IF NOT EXISTS `_config` (
  `config_name` varchar(255) NOT NULL,
  `config_value` varchar(255) NOT NULL,
  PRIMARY KEY (`config_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Volcado de datos para la tabla `_config`
--

INSERT INTO `_config` (`config_name`, `config_value`) VALUES
('address', 'http://localhost/jade/'),
('cookie_domain', ''),
('cookie_name', 'ucs'),
('cookie_path', '/'),
('cron_enabled', '1'),
('default_email', ''),
('default_lang', 'es'),
('domain', 'localhost'),
('mail_port', '110'),
('mail_server', ''),
('mail_ticket_key', ''),
('mail_ticket_login', ''),
('max_login_attempts', '5'),
('session_gc', '3600'),
('session_last_gc', '1315434438'),
('session_length', '3600'),
('signin_pop', '0'),
('site_title', 'Jade'),
('xs_auto_compile', '1'),
('xs_check_switches', '0'),
('xs_def_template', ''),
('xs_use_cache', '0'),
('xs_warn_includes', '1');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `_email`
--

CREATE TABLE IF NOT EXISTS `_email` (
  `email_id` mediumint(8) NOT NULL AUTO_INCREMENT,
  `email_active` tinyint(1) NOT NULL,
  `email_data` varchar(50) NOT NULL,
  `email_from` varchar(200) NOT NULL,
  `email_from_address` varchar(200) NOT NULL,
  `email_subject` varchar(255) NOT NULL,
  `email_message` text NOT NULL,
  `email_lastvisit` int(11) NOT NULL,
  `email_last` int(11) NOT NULL,
  `email_start` int(11) NOT NULL,
  `email_end` int(11) NOT NULL,
  PRIMARY KEY (`email_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `_email_test`
--

CREATE TABLE IF NOT EXISTS `_email_test` (
  `address_id` mediumint(5) NOT NULL AUTO_INCREMENT,
  `address_name` varchar(200) NOT NULL,
  `address_last` varchar(200) NOT NULL,
  `address_account` varchar(200) NOT NULL,
  `address_genre` varchar(200) NOT NULL,
  PRIMARY KEY (`address_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Volcado de datos para la tabla `_email_test`
--

INSERT INTO `_email_test` (`address_id`, `address_name`, `address_last`, `address_account`, `address_genre`) VALUES
(1, 'Guillermo', 'Azurdia', 'info@nopticon.com', '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `_groups`
--

CREATE TABLE IF NOT EXISTS `_groups` (
  `group_id` mediumint(5) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(255) NOT NULL,
  `group_email` varchar(255) NOT NULL,
  `group_email_template` text NOT NULL,
  `group_auth` text NOT NULL,
  `group_mod` int(11) NOT NULL,
  `group_color` varchar(6) NOT NULL,
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `_groups_members`
--

CREATE TABLE IF NOT EXISTS `_groups_members` (
  `member_id` int(11) NOT NULL AUTO_INCREMENT,
  `member_group` mediumint(5) NOT NULL,
  `member_uid` int(11) NOT NULL,
  `member_mod` mediumint(5) NOT NULL,
  PRIMARY KEY (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `_log`
--

CREATE TABLE IF NOT EXISTS `_log` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `log_time` int(11) NOT NULL,
  `log_uid` int(11) NOT NULL,
  `log_method` varchar(25) NOT NULL,
  `log_actions` text NOT NULL,
  PRIMARY KEY (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `_members`
--

CREATE TABLE IF NOT EXISTS `_members` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_type` smallint(2) NOT NULL,
  `user_active` tinyint(1) NOT NULL DEFAULT '0',
  `user_internal` tinyint(1) NOT NULL,
  `user_mtype` tinyint(1) NOT NULL,
  `user_login` varchar(25) NOT NULL,
  `user_username` varchar(25) NOT NULL,
  `user_password` varchar(255) NOT NULL,
  `user_lastvisit` int(11) NOT NULL,
  `user_auto_session` tinyint(1) NOT NULL,
  `user_current_ip` varchar(50) NOT NULL,
  `user_lastpage` varchar(255) NOT NULL,
  `user_firstname` varchar(255) NOT NULL,
  `user_lastname` varchar(255) NOT NULL,
  `user_name_show` varchar(255) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `user_gender` tinyint(1) NOT NULL,
  `user_date` int(11) NOT NULL,
  `user_dateformat` varchar(15) NOT NULL,
  `user_timezone` tinyint(3) NOT NULL,
  `user_dst` tinyint(1) NOT NULL,
  `user_login_tries` smallint(2) NOT NULL,
  `user_stats` tinyint(1) NOT NULL,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Volcado de datos para la tabla `_members`
--

INSERT INTO `_members` (`user_id`, `user_type`, `user_active`, `user_internal`, `user_mtype`, `user_login`, `user_username`, `user_password`, `user_lastvisit`, `user_auto_session`, `user_current_ip`, `user_lastpage`, `user_firstname`, `user_lastname`, `user_name_show`, `user_email`, `user_gender`, `user_date`, `user_dateformat`, `user_timezone`, `user_dst`, `user_login_tries`, `user_stats`) VALUES
(1, 0, 0, 1, 0, '', 'nobody', 'nobody', 0, 0, '', '', 'nobody', 'nobody', '', '', 0, 0, 'd M Y H:i', -6, 0, 0, 1),
(2, 3, 1, 0, 0, 'adm', 'adm', '8929b61a0e6b985943edc6d13c9992dc661e4f09', 1307986639, 0, '', '', 'Administrador', '', '', '', 0, 0, 'd M Y H:i', -6, 0, 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `_members_auth`
--

CREATE TABLE IF NOT EXISTS `_members_auth` (
  `auth_id` int(11) NOT NULL AUTO_INCREMENT,
  `auth_uid` mediumint(5) NOT NULL,
  `auth_field` int(11) NOT NULL DEFAULT '0',
  `auth_value` mediumint(5) NOT NULL DEFAULT '0',
  PRIMARY KEY (`auth_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `_members_auth_fields`
--

CREATE TABLE IF NOT EXISTS `_members_auth_fields` (
  `field_id` int(11) NOT NULL AUTO_INCREMENT,
  `field_alias` varchar(50) NOT NULL,
  `field_name` varchar(50) NOT NULL,
  `field_global` tinyint(1) NOT NULL,
  PRIMARY KEY (`field_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;

--
-- Volcado de datos para la tabla `_members_auth_fields`
--

INSERT INTO `_members_auth_fields` (`field_id`, `field_alias`, `field_name`, `field_global`) VALUES
(1, 'home', 'home', 1),
(2, 'ext', 'ext', 1),
(3, 'sign', 'sign', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `_sessions`
--

CREATE TABLE IF NOT EXISTS `_sessions` (
  `session_id` varchar(50) NOT NULL DEFAULT '',
  `session_user_id` mediumint(5) NOT NULL DEFAULT '0',
  `session_last_visit` int(11) NOT NULL DEFAULT '0',
  `session_start` int(11) NOT NULL DEFAULT '0',
  `session_time` int(11) NOT NULL DEFAULT '0',
  `session_ip` varchar(40) NOT NULL DEFAULT '',
  `session_browser` varchar(255) NOT NULL,
  `session_page` varchar(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;