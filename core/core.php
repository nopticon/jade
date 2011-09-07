<?php
/*
$Id: core.php,v 3.1 2009/01/15 15:09:00 Psychopsia Exp $

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
if (!defined('XFS')) exit;

function htmlencode($str, $multibyte = false)
{
	$result = trim(htmlentities(str_replace(array("\r\n", "\r", '\xFF'), array("\n", "\n", ' '), $str)));
	$result = (STRIP) ? stripslashes($result) : $result;
	if ($multibyte)
	{
		$result = preg_replace('#&amp;(\#\d+;)#', '&\1', $result);
	}
	$result = preg_replace('#&amp;((.*?);)#', '&\1', $result);
	
	return $result;
}

function set_var(&$result, $var, $type, $multibyte = false, $regex = '')
{
	settype($var, $type);
	$result = $var;

	if ($type == 'string')
	{
		$result = htmlencode($result, $multibyte);
	}
}

//
// Get value of request var
//
function request_var($var_name, $default, $multibyte = false, $regex = '')
{
	if (!isset($_REQUEST[$var_name]) || (is_array($_REQUEST[$var_name]) && !is_array($default)) || (is_array($default) && !is_array($_REQUEST[$var_name])))
	{
		return (is_array($default)) ? array() : $default;
	}

	$var = $_REQUEST[$var_name];
	if (!is_array($default))
	{
		$type = gettype($default);
		$var = ($var);
	}
	else
	{
		list($key_type, $type) = each($default);
		$type = gettype($type);
		$key_type = gettype($key_type);
	}

	if (is_array($var))
	{
		$_var = $var;
		$var = array();

		foreach ($_var as $k => $v)
		{
			if (is_array($v))
			{
				foreach ($v as $_k => $_v)
				{
					set_var($k, $k, $key_type);
					set_var($_k, $_k, $key_type);
					set_var($var[$k][$_k], $_v, $type, $multibyte);
				}
			}
			else
			{
				set_var($k, $k, $key_type);
				set_var($var[$k], $v, $type, $multibyte);
			}
		}
	}
	else
	{
		set_var($var, $var, $type, $multibyte);
	}
	
	return $var;
}

function _utf8($a)
{
	if (is_array($a))
	{
		foreach ($a as $k => $v)
		{
			$a[$k] = _utf8($v);
		}
	}
	else
	{
		$a = utf8_decode($a);
	}
	
	return $a;
}

function uset(&$k, $v)
{
	$response = false;
	if (isset($k[$v]))
	{
		$response = $k[$v];
		unset($k[$v]);
	}
	
	return $response;
}

function _fatal($code = 404, $errfile = '', $errline = '', $errmsg = '', $errno = 0)
{
	global $db;
	
	if (isset($db))
	{
		$db->sql_close();
	}
	
	switch ($code)
	{
		case 504:
			echo '<b>PHP Notice</b>: in file <b>' . $errfile . '</b> on line <b>' . $errline . '</b>: <b>' . $errmsg . '</b><br>';
			break;
		case 505:
			echo '<b>Another Error</b>: in file <b>' . basename($errfile) . '</b> on line <b>' . $errline . '</b>: <b>' . $errmsg . '</b><br>';
			break;
		case 506:
			die('USER_ERROR: ' . $errmsg);
			break;
		default:
			$error_path = './style/server-error/%s.htm';
			
			switch ($errno)
			{
				case 2: $filepath = sprintf($error_path, 'no' . $errno); break;
				default: $filepath = sprintf($error_path, $code); break;
			}
			if (!@file_exists($filepath))
			{
				$filepath = sprintf($error_path, 'default');
			}
			
			$v_host = get_protocol() . get_host();
			
			// MySQL error
			if ($code == 507)
			{
				global $core;
				
				$e_mysql = explode('///', $errmsg);
				$i_errmsg = str_replace(array("\n", "\t"), array('<br>', '&nbsp;&nbsp;&nbsp;'), implode('<br><br>', $e_mysql));
				$v_time = @date('r', time());
				
				$mail_extra = "From: info@nopticon.com\n" . "Return-Path: info@nopticon.com\nMessage-ID: <" . md5(uniqid(time())) . "@nopticon.com>\nMIME-Version: 1.0\nContent-type: text/html; charset=ISO-8859-1\nContent-transfer-encoding: 8bit\nDate: " . $v_time . "\nX-Priority: 2\nX-MSMail-Priority: High\n";
				$mail_to = (!empty($_SERVER['SERVER_ADMIN'])) ? $_SERVER['SERVER_ADMIN'] : 'mysql@nopticon.com';
				$mail_result = @mail($mail_to, 'MySQL: Error', preg_replace("#(?<!\r)\n#s", "\n", _page() . '<br />' . $v_time . '<br /><br />' . $i_errmsg), $mail_extra, '-finfo@nopticon.com');
				
				$errmsg = (is_local()) ? '<br><br>' . $i_errmsg : '';
			}
			
			$repl = array(
				array('{ERROR_LINE}', '{ERROR_FILE}', '{ERROR_MSG}', '{HTTP_HOST}', '{REQUEST_URL}'),
				array($errline, $errfile, $errmsg, $v_host . str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF']), $_SERVER['REQUEST_URI'])
			);
			
			header("HTTP/1.1 404 Not Found");
			header("Status: 404 Not Found");
			
			echo str_replace($repl[0], $repl[1], implode('', @file($filepath)));
			exit();
			break;
	}
	
	return;
}

function msg_handler($errno, $msg_text, $errfile, $errline)
{
	global $user;
	
	switch ($errno)
	{
		case E_NOTICE:
		case E_WARNING:
			_fatal(504, $errfile, $errline, $msg_text, $errno);
			break;
		case E_USER_ERROR:
			_fatal(506, '', '', $msg_text, $errno);
			break;
		case E_USER_NOTICE:
			_fatal(503, '', '', _lang($msg_text), $errno);
			break;
		default:
			_fatal(505, $errfile, $errline, $msg_text, $errno);
			break;
	}
	return;
}

function fwrite_line($f, $a)
{
	$fp = @fopen($f, 'a+');
	fwrite($fp, $a . "\n");
	fclose($fp);
	
	return $a;
}

//
// Thanks to:
// SNEAK: Snarkles.Net Encryption Assortment Kit
// Copyright (c) 2000, 2001, 2002 Snarkles (webgeek@snarkles.net)
//
// Used Functions: hex2asc()
//
function hex2asc($str)
{
	$str2 = '';
	for ($n = 0, $end = strlen($str); $n < $end; $n += 2)
	{
		$str2 .=  pack('C', hexdec(substr($str, $n, 2)));
	}
	
	return $str2;
}

function encode($str)
{
	return bin2hex(base64_encode($str));
}

function decode($str)
{
	return base64_decode(hex2asc($str));
}

function array_strpos($haystack, $needle)
{
	if (!is_array($needle) || empty($haystack)) return false;
	
	foreach ($needle as $char)
	{
		if ($pos = strpos($haystack, $char) !== false) return $pos;
	}
	return false;
}

function array_key($a, $k)
{
	return (isset($a[$k])) ? $a[$k] : false;
}

function strpos_pad(&$haystack, $needle, $remove_needle = false)
{
	if ($pos = strpos(' ' . $haystack, $needle) !== false)
	{
		if ($remove_needle)
		{
			$haystack = str_replace($needle, '', $haystack);
		}
		
		return (int) $pos;
	}
	
	return false;
}

function array_isset($a, $f)
{
	foreach ($f as $fk => $fv)
	{
		if (!isset($a[$fk]))
		{
			$a[$fk] = $fv;
		}
	}
	return $a;
}

function array_compare($needle, $haystack, $match_all = true)
{
	if (!is_array($needle) || count($haystack) > count($needle))
	{
		return false;
	}
	
	$count = 0;
	$result = false;
	foreach ($needle as $k => $v)
	{
		if (!isset($haystack[$k]))
		{
			if ($match_all)
			{
				return false;
			}
			continue;
		}
		
		if (is_array($v))
		{
			$result = array_compare($v, $haystack[$k], $match_all);
		}
		
		$result = ($haystack[$k] === $v) && (($match_all && (!$count || $result)) || !$match_all) || (!$match_all && $result);
		$count++;
	}
	
	return $result;
}

function _array_keys($ary, $d = array())
{
	$a = array();
	foreach ($ary as $k => $v)
	{
		if (!is_string($k))
		{
			$k = $v;
			$v = $d;
		}
		$a[$k] = $v;
	}
	
	return $a;
}

function preg_array($pattern, $ary)
{
	$a = array();
	foreach ($ary as $each)
	{
		$a[] = sprintf($pattern, $each);
	}
	
	return $a;
}

function entity_decode($s)
{
	return html_entity_decode($s);
}

function _lang($k)
{
	global $user;
	
	$k2 = strtoupper($k);
	return  (isset($user->lang[$k2])) ? $user->lang[$k2] : $k;
}

function is_lang($k)
{
	global $user;
	
	return isset($user->lang[strtoupper($k)]);
}

function w($a = '')
{
	if (empty($a)) return array();
	
	return explode(' ', $a);
}

function _hash($v, $t = 1)
{
	$response = $v;
	for ($i = 0; $i < $t; $i++)
	{
		$response = md5($response);
	}
	return $response;
}

function is_numb($v)
{
	return @preg_match('/^\d+$/', $v);
}

function dvar($v, $d)
{
	$v = (isset($v) && !empty($v)) ? $v : $d;
	return $v;
}

function _alias($a, $orig = array('-', '_'), $repl = '')
{
	return _rm_acute(str_replace(array_merge(array('.', ' '), $orig), $repl, strtolower($a)));
}

function _rm_acute($a)
{
	return preg_replace('#\&(\w)(tilde|acute)\;#i', '\1', $a);
}

function _low($a, $match = false)
{
	if (empty($a) || ($match && !preg_match('#^([A-Za-z0-9\-\_\ ]+)$#is', $a)))
	{
		return false;
	}
	
	return _alias($a);
}

function _fullname($d)
{
	if (!isset($d['user_firstname']) || !isset($d['user_lastname']))
	{
		return '';
	}
	return implode(' ', array_map('trim', array($d['user_firstname'], $d['user_lastname'])));
}

function _extension($file)
{
	return strtolower(str_replace('.', '', substr($file, strrpos($file, '.'))));
}

function is_ghost()
{
	return request_var('ghost', 0);
}

function request_method()
{
	return strtolower($_SERVER['REQUEST_METHOD']);
}

function _ajax_callback($code, $url)
{
	global $core;
	
	if (is_ghost() === 1)
	{
		if (is_array($code))
		{
			$extra = $code;
			$code = array_push($extra);
		}
		echo $core->je[$code] . ((isset($extra) && count($extra)) ? ':' . implode(':', $extra) : '');
	}
	
	redirect($url);
}

function _vs($a, $p = '')
{
	$b = array();
	foreach ($a as $k => $v)
	{
		$b[strtoupper((($p != '') ? $p . '_' : '') . $k)] = $v;
	}
	return $b;
}

/*function _message($a)
{
	return str_replace("\n", '<br />', $a);
}*/

function _link($mod = '', $attr = false, $ts = true)
{
	global $core;
	$url = $core->v('address') . (($mod != '') ? $mod . (($ts) ? '/' : '') : '');
	
	if ($attr !== false)
	{
		if (is_array($attr))
		{
			$arg = '';
			foreach ($attr as $k => $v)
			{
				if ($v !== '') $arg .= (($arg != '') ? ((is_string($k)) ? '.' : '/') : '') . ((is_string($k) && $k != '') ? $k . ':' : '') . $v;
			}
			$url .= $arg;
		} else {
			$url .= $attr;
		}
		
		$url .= ($mod != 'get' && $ts && !empty($attr)) ? '/' : '';
	}
	
	return $url;
}

function _link_apnd_empty($a)
{
	return !empty($a);
}

function _link_apnd($u, $a)
{
	$eu = array_values(array_filter(explode('/', $u), '_link_apnd_empty'));
	$last = array_pop($eu);
	
	if (strpos($last, ':') !== false)
	{
		$eu[] = $last . '.' . $a;
	} else {
		$eu = array_merge($eu, array($last, $a));
	}
	
	$eu[0] .= '/';
	return implode('/', $eu) . '/';
}

function request_type_redirect($a = 'post')
{
	if (request_method() != $a)
	{
		redirect(_link());
	}
	return true;
}

function _filename($a, $b)
{
	return $a . '.' . $b;
}

function _message($a)
{
	include_once(XFS . 'core/markdown.php');
	
	return Markdown($a);
}

function _postbox($ref = '', $prefix = 'postbox')
{
	global $nucleo, $user, $style;
	
	$u_block = ($user->d('is_member')) ? 'in' : 'out';
	
	$style->assign_block_vars($prefix, w());
	
	$style->assign_block_vars($prefix . '.' . $u_block, array(
		'V_REF' => $ref,
		'V_OUT' => sprintf(_lang('LOGIN_TO_POST'), _link('signup')))
	);
	
	return;
}

function _pagination($url_format_smp, $url_apnd, $total_items, $per_page, $offset)
{
	global $user, $style;
	
	$begin_end = 3;
	$from_middle = 1;
	
	$total_pages = ceil($total_items / $per_page);
	$on_page = floor($offset / $per_page) + 1;
	$url_format = _link_apnd($url_format_smp, $url_apnd);
	
	$tag = array(
		'strong' => '<strong>%d</strong>',
		'span' => '<span> ... </span>',
		'a' => '<a href="%s">%s</a>'
	);
	
	$pages = '';
	if ($total_pages > ((2 * ($begin_end + $from_middle)) + 2))
	{
		$init_page_max = ($total_pages > $begin_end) ? $begin_end : $total_pages;
		for ($i = 1; $i < $init_page_max + 1; $i++)
		{
			$pages .= _space($pages) . _pagination_multi($i, $on_page, $per_page, $tag, $url_format, $url_format_smp);
		}
		
		if ($total_pages > $begin_end)
		{
			if ($on_page > 1 && $on_page < $total_pages)
			{
				$pages .= ($on_page > ($begin_end + $from_middle + 1)) ? $tag['span'] : '';

				$init_page_min = ($on_page > ($begin_end + $from_middle)) ? $on_page : ($begin_end + $from_middle + 1);
				$init_page_max = ($on_page < $total_pages - ($begin_end + $from_middle)) ? $on_page : $total_pages - ($begin_end + $from_middle);

				for ($i = $init_page_min - $from_middle; $i < $init_page_max + ($from_middle + 1); $i++)
				{
					$pages .= _space($pages) . _pagination_multi($i, $on_page, $per_page, $tag, $url_format, $url_format_smp);
				}
				
				$pages .= ($on_page < $total_pages - ($begin_end + $from_middle)) ? $tag['span'] : '';
			} else {
				$pages .= $tag['span'];
			}
			
			for ($i = $total_pages - ($begin_end - 1); $i < $total_pages + 1; $i++)
			{
				$pages .= _space($pages) . _pagination_multi($i, $on_page, $per_page, $tag, $url_format, $url_format_smp);
			}
		}
	} elseif ($total_pages > 1) {
		for ($i = 1; $i < $total_pages + 1; $i++)
		{
			$pages .= _space($pages) . _pagination_multi($i, $on_page, $per_page, $tag, $url_format, $url_format_smp);
		}
	}
	
	$prev = ($on_page > 1) ? sprintf($tag['a'], sprintf($url_format, (($on_page - 2) * $per_page)), sprintf(_lang('PAGES_PREV'), $per_page)) : '';
	$next = ($on_page < $total_pages) ? sprintf($tag['a'], sprintf($url_format, ($on_page * $per_page)), sprintf(_lang('PAGES_NEXT'), $per_page)) : '';
	
	$rest = array(
		'NUMS' => $pages,
		'PREV' => $prev,
		'NEXT' => $next,
		'ON' => sprintf(_lang('PAGES_ON'), $on_page, max($total_pages, 1))
	);
	return $rest;
}

function _pagination_multi($i, $on_page, $per_page, $tag, $url_format, $url_format_smp)
{
	if ($i == $on_page)
	{
		$page = sprintf($tag['strong'], $i);
	} else {
		$this_page = ($i > 1) ? sprintf($url_format, (($i - 1) * $per_page)) : $url_format_smp;
		$page = sprintf($tag['a'], $this_page, $i);
	}
	return $page;
}

function _space($a)
{
	return ($a != '') ? ' ' : '';
}

function _countries()
{
	global $core;
	
	if (!$countries = $core->cache_load('countries'))
	{
		$sql = 'SELECT *
			FROM _countries
			ORDER BY country_id';
		if ($countries = $this->_rowset($sql, 'country_id'))
		{
			$core->cache_store('countries', $countries);
		}
	}
	
	return $countries;
}

function _location($id, $extra = '', $s = '')
{
	if (empty($s))
	{
		$list = _countries();
	}
	
	return (($extra != '') ? $extra . ', ' : '') . ((isset($list[$id])) ? $list[$id] : $s);
}

function _login($message = false, $error = false)
{
	global $user, $style;
	
	if (empty($user->data))
	{
		$user->start();
	}
	if (empty($user->lang))
	{
		$user->setup();
	}
	
	if ($user->d('is_member'))
	{
		return;
	}
	
	if ($user->d('is_bot'))
	{
		redirect(_link());
	}
	
	if ($error === false)
	{
		$error = ($message !== false) ? _lang($message) : false;
	}
	
	if ($error !== false && !empty($error))
	{
		$style->assign_block_vars('error', array(
			'MESSAGE' => $error)
		);
	}
	
	$sv = array(
		'REDIRECT_TO' => str_replace(_link(), '', $user->data['session_page'])
	);
	_layout('login', 'LOGIN', $sv);
}

function redirect($url)
{
	global $db;

	if (!empty($db))
	{
		$db->sql_close();
	}
	$url = trim($url);
	
	// Prevent external domain injection
	if (strpos($url, '://') !== false)
	{
		$url_path = parse_url($url, PHP_URL_HOST);
		if ($url_path === false || $url_path != get_host())
		{
			_fatal();
		}
	}
	else
	{
		if (substr($url, 0, 1) === '/')
		{
			$url = substr($url, 1);
		}
		$url = _link() . $url;
	}
	
	$head = 'Location: ' . $url;
	
	if (is_ghost())
	{
		echo $head;
	}
	else
	{
		header($head);
	}
	exit();
}

function _localtime()
{
	global $user;
	
	return time() + $user->timezone + $user->dst;
}

function _password($str)
{
	return sha1(sha1(sha1($str)));
}

//
function window_refresh($url, $sec)
{
	global $style;
	
	$style->assign_block_vars(array(
		'URL' => $url,
		'SECONDS' => $sec)
	);
}

function unique_id()
{
	list($sec, $usec) = explode(' ', microtime());
	mt_srand((float) $sec + ((float) $usec * 100000));
	return uniqid(mt_rand(), true);
}

function is_local()
{
	return ($_SERVER['SERVER_NAME'] == 'localhost') ? true : false;
}

function get_protocol($use_https = false)
{
	$use_http = ((int) $_SERVER['SERVER_PORT'] !== 443) ? true : false;
	
	if ($use_https) $use_http = false;
	
	return (($use_http) ? 'http://' : 'https://');
}

function get_host()
{
	return $_SERVER['HTTP_HOST'];
}

// Current page
function _page()
{
	return get_protocol() . get_host() . ((!empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '');
}

//
function hidden($ary)
{
	$hidden = '';
	foreach ($ary as $k => $v)
	{
		$hidden .= '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
	}
	return $hidden;
}

function _button($name = 'submit')
{
	return (isset($_POST[$name])) ? true : false;
}

function error_list($error, $glue = '<br />')
{
	global $user;
	
	return implode($glue, preg_replace('#^([A-Z_]+)$#e', "(!empty(\$user->lang['\\1'])) ? \$user->lang['\\1'] : '\\1'", $error));
}

function ksql($prefix, $ary)
{
	$prefix = ($prefix != '') ? $prefix . '_' : '';
	
	$a = array();
	foreach ($ary as $k => $v)
	{
		$a[$prefix . $k] = $v;
	}
	return $a;
}

function nobody()
{
	global $core;
	
	if (!$a = $core->cache_load('nobody'))
	{
		$sql = "SELECT user_id
			FROM _members
			WHERE user_username = 'nobody'";
		if ($a = $this->_field($sql, 'user_id'))
		{
			$core->cache_store('nobody', $a);
		}
	}
	return $a;
}

// Code from php.net @ http://www.php.net/manual/en/function.json-encode.php#82904
if (!function_exists('json_encode'))
{
	function json_encode($a = false)
	{
		if (is_null($a)) return 'null';
		if ($a === false) return 'false';
		if ($a === true) return 'true';
		
		if (is_scalar($a))
		{
			if (is_float($a))
			{
				// Always use "." for floats.
				return floatval(str_replace(",", ".", strval($a)));
			}
			
			if (is_string($a))
			{
				static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
				return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
			}
			else
				return $a;
		}
		
		$isList = true;
		for ($i = 0, reset($a); $i < count($a); $i++, next($a))
		{
			if (key($a) !== $i)
			{
				$isList = false;
			   break;
		   }
	   }
	   
	   $result = array();
	   if ($isList)
	   {
		   foreach ($a as $v) $result[] = json_encode($v);
		   return '[' . join(',', $result) . ']';
	   }
	   else
	   {
		   foreach ($a as $k => $v) $result[] = json_encode($k).':'.json_encode($v);
		   return '{' . join(',', $result) . '}';
	   }
   }
}

// Code from php.net @ http://www.php.net/manual/en/function.json-decode.php#87776
if (!function_exists('json_decode'))
{
	function json_code($json)
	{
		// Remove curly brackets to beware from regex errors
		$json = substr($json, strpos($json,'{')+1, strlen($json));
		$json = substr($json, 0, strrpos($json,'}'));
		$json = preg_replace('/(^|,)([\\s\\t]*)([^:]*) (([\\s\\t]*)):(([\\s\\t]*))/s', '$1"$3"$4:', trim($json));
		
		return json_decode('{'.$json.'}', true);
	}
}

// Code from phpBB 3 (download.php)
function set_modified_headers($stamp, $browser)
{
	// let's see if we have to send the file at all
	$last_load 	=  isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtotime(trim($_SERVER['HTTP_IF_MODIFIED_SINCE'])) : false;
	if ((strpos(strtolower($browser), 'msie 6.0') === false) && (strpos(strtolower($browser), 'msie 8.0') === false))
	{
		if ($last_load !== false && $last_load <= $stamp)
		{
			if (@php_sapi_name() === 'CGI')
			{
				header('Status: 304 Not Modified', true, 304);
			}
			else
			{
				header('HTTP/1.0 304 Not Modified', true, 304);
			}
			// seems that we need those too ... browsers
			header('Pragma: public');
			header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 31536000));
			return true;
		}
		else
		{
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $stamp) . ' GMT');
		}
	}
	return false;
}

function _layout($filename, $pagetitle = false, $v_custom = false)
{
	global $db, $core, $user, $style, $starttime;
	
	// GZip
	if (strstr($user->browser, 'compatible') || strstr($user->browser, 'Gecko'))
	{
		ob_start('ob_gzhandler');
	}
	
	// Headers
	header('Cache-Control: private, no-cache="set-cookie", pre-check=0, post-check=0');
	header('Expires: 0');
	header('Pragma: no-cache');
	
	if ($pagetitle !== false)
	{
		if (!is_array($pagetitle))
		{
			$pagetitle = array($pagetitle);
		}
		
		foreach ($pagetitle as $k => $v)
		{
			$pagetitle[$k] = _lang($v);
		}
		$pagetitle = implode(' . ', $pagetitle);
	}
	
	//
	$v_assign = array(
		'SITE_TITLE' => $core->v('site_title'),
		'PAGE_TITLE' => $pagetitle,
		'S_REDIRECT' => $user->data['session_page'],
		'F_SQL' => $db->sql_num_queries()
	);
	if ($v_custom !== false)
	{
		$v_assign += $v_custom;
	}
	
	$filename = (strpos($filename, '#')) ? str_replace('#', '.', $filename) : $filename . '.htm';
	$style->set_filenames(array(
		'body' => $filename)
	);
	$db->report();
	
	$mtime = explode(' ', microtime());
	$v_assign['F_TIME'] = sprintf('%.2f', ($mtime[0] + $mtime[1] - $starttime));
	
	$style->assign_vars($v_assign);
	$style->pparse('body');
	
	$db->sql_close();
	exit();
}

function _xfs($mod = false, $wdir = false, $warg = false)
{
	global $user, $style;
	
	include_once(XFS . 'core/modules.php');
	
	if ($mod === false)
	{
		$mod = request_var('module', '');
	}
	$mod = (!empty($mod)) ? $mod : 'home';
	
	$mod_dir = './base/_' . $mod;
	$p_dir = ($wdir === false && @file_exists($mod_dir) && is_dir($mod_dir)) ? true : false;
	
	if (!$p_dir)
	{
		$mod_dir = './base/_' . (($wdir !== false) ? $wdir . '/_' : '') . $mod;
		
		$mod_path = $mod_dir . '.php';
		$mod_class = '__' . $mod;
		
		if (!@file_exists($mod_path))
		{
			_fatal();
		}
		include_once($mod_path);
		
		if (!class_exists($mod_class))
		{
			_fatal();
		}
		$module = new $mod_class();
	}
	
	if ($warg === false)
	{
		$warg = array();
		$arg = request_var('args', '');
		if (!empty($arg))
		{
			foreach (explode('.', $arg) as $v)
			{
				$el = explode(':', $v);
				if (isset($el[0]) && isset($el[1]) && !empty($el[0]))
				{
					$warg[$el[0]] = $el[1];
				}
			}
		}
		
		if (isset($_POST) && count($_POST))
		{
			$_POST = _utf8($_POST);
			$warg = array_merge($warg, $_POST);
		}
	}
	
	if ($p_dir)
	{
		_xfs(((isset($warg['x1'])) ? $warg['x1'] : ''), $mod, $warg);
	}
	else
	{
		if (isset($module->auth) && $module->auth)
		{
			$v_auth_exclude = (isset($module->auth_exclude) && (isset($warg['x1']) && in_array($warg['x1'], $module->auth_exclude))) ? true : false;
			if (!$v_auth_exclude)
			{
				_login();
			}
		}
		
		$warg_x = 0;
		foreach ($warg as $warg_k => $warg_v)
		{
			if (preg_match('/x\d+/i', $warg_k))
			{
				$warg_x = str_replace('x', '', $warg_k);
			}
		}
		
		if ($wdir !== false)
		{
			for ($i = 0; $i < $warg_x; $i++)
			{
				$warg['x' . ($i + 1)] = (isset($warg['x' + ($i + 2)])) ? $warg['x' + ($i + 2)] : '';
			}
		}
	}
	
	date_default_timezone_set('America/Guatemala');
	
	$module->arg = $warg;
	$module->xlevel();
	if (!method_exists($module, $module->level['x1']))
	{
		_fatal();
	}
	
	// Session start
	$user->start(true);
	$user->setup();
	
	if (!$module->auth_access($user->data))
	{
		_fatal();
	}
	
	if (!defined('LIB')) define('LIB', '../space/');
	
	if (!defined('LIBD')) define('LIBD', _link() . str_replace('../', '', LIB));
	
	$module->module = $mod;
	
	if (@method_exists($module, 'install'))
	{
		$module->install();
	}
	
	$module->navigation('home', '', '');
	$module->navigation($module->module, '');
	
	$module->{$module->level['x1']}();
	
	if (empty($module->template))
	{
		$module->template = $mod;
	}
	
	if (@file_exists('./base/tree'))
	{
		$menu = array_map('trim', @file('./base/tree'));
		foreach ($menu as $i => $row)
		{
			if (!$i) $style->assign_block_vars('tree', array());
			
			$row = trim($row);
			$row_level = strripos($row, '*') + 1;
			preg_match('#^\*{0,} (.*?) <(.*?)>$#i', $row, $row_key);
			
			$row_mod = array(dvar(array_key(explode('/', $row_key[2]), 1), 'index'));
			
			if ($row_level > 1) $row_mod[] = array_key(explode(':', array_key(explode('.', array_key(explode('/', $row_key[2]), 2)), 0)), 1);
			
			$row_auth = implode('_', $row_mod);
			if (!$user->auth_get($row_auth)) continue;
			
			$row_style = '.row' . (($row_level == 1) ? '' : '.sub' . ($row_level - 1));
			$style->assign_block_vars('tree' . $row_style, array(
				'V_NAME' => trim(str_replace('*', '', $row_key[1])),
				'V_LINK' => _link() . substr($row_key[2], 1))
			);
		}
	}
	
	//
	// Output template
	$page_smodule = 'CONTROL_' . strtoupper($mod);
	if (is_lang($page_smodule))
	{
		$module->page_title($page_smodule);
	}
	
	$sv = array(
		'MODE' => $module->level['x1'],
		'MANAGE' => $module->level['x2'],
		'NAVIGATION' => $module->get_navigation()
	);
	_layout($module->template, $module->page_title(), $sv);
}

define('U_GUEST', 1);
define('U_FOUNDER', 3);
define('STRIP', (get_magic_quotes_gpc()) ? true : false);
set_error_handler('msg_handler');

?>