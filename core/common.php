<?php
/*
$Id: common.php,v 2.1 2009/01/11 09:53:00 Psychopsia Exp $

<Ximod, a web development framework.>
Copyright (C) <2009>  <Nopticon>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

error_reporting(E_ALL);
//error_reporting(E_ERROR | E_WARNING | E_PARSE);
//set_magic_quotes_runtime(0);

if (@ini_get('register_globals'))
{
	foreach ($_REQUEST as $var_name => $void)
	{
		unset(${$var_name});
	}
}

if (!defined('XFS')) define('XFS', './');

require_once(XFS . 'core/core.php');
require_once(XFS . 'core/db.php');
require_once(XFS . 'core/styles.php');
require_once(XFS . 'core/session.php');

foreach (w('db style user core') as $w) $$w = new $w;

if (!defined('XCORE')) _xfs();

?>
