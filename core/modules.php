<?php
/*
$Id: modules.php,v 1.6 2009/01/12 15:00:00 Psychopsia Exp $

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

if (@file_exists('./base/project.php'))
{
	require_once('./base/project.php');
}

if (!class_exists('project'))
{
	class project { }
}

class xmd extends project
{
	var $arg = array();
	var $error = array();
	var $level = array();
	var $tree = array();
	var $paget = array();
	var $nav = array();
	
	var $mode;
	var $manage;
	var $module;
	var $submit;
	var $template;
	
	var $je = array(
		'OK' => '~[200]',
		'CN' => '~[201]'
	);
	
	function xmd()
	{
		$this->submit = _button();
	}
	
	function v($var_name, $default, $multibyte = false)
	{
		if (!isset($this->arg[$var_name]) || (is_array($this->arg[$var_name]) && !is_array($default)) || (is_array($default) && !is_array($this->arg[$var_name])))
		{
			return (is_array($default)) ? array() : $default;
		}
	
		$var = $this->arg[$var_name];
		if (!is_array($default))
		{
			$type = gettype($default);
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
	
	function xlevel()
	{
		foreach ($this->arg as $k => $v)
		{
			if (preg_match('#^x(\d+)$#is', $k))
			{
				$this->level[$k] = $v;
			}
		}
		
		foreach (w('x1 x2') as $k)
		{
			if (!isset($this->level[$k]))
			{
				$this->level[$k] = '.';
			}
		}
		ksort($this->level);
		
		$keys = array();
		foreach ($this->level as $k => $v)
		{
			$keys[] = "['" . $v . "']";
			
			eval('$exists = isset($this->methods' . implode('', $keys) . ');');
			if (!$exists)
			{
				$key = count($keys) - 1;
				$keys[$key] = (isset($keys[$key])) ? $keys[$key] : '';
				$value = str_replace(array('[', ']', "'"), '', $keys[$key]);
				unset($keys[$key]);
				
				eval('$exists = isset($this->methods' . implode('', $keys) . ') && in_array(\'' . $value . '\', $this->methods' . implode('', $keys) . ');');
			}
			
			if (!$exists) $this->level[$k] = 'home';
		}
		
		return;
	}
	
	function rvar($k, $v)
	{
		$this->arg[$k] = $v;
		return $v;
	}
	
	function rlev($k, $v)
	{
		$this->level[$k] = $v;
		return $v;
	}
	
	function __($v, $m = '$this->v')
	{
		$v = _array_keys($v);
		
		$a = array();
		foreach ($v as $varname => $options)
		{
			if (!is_array($options) || !isset($options['default']))
			{
				if (is_array($options) && !count($options)) $options = '';
				
				$options = array('default' => $options);
			}
			if (!isset($options['type']))
			{
				$options['type'] = 'text';
			}
			
			switch ($options['type'])
			{
				case 'checkbox':
					$a[$varname] = (isset($_POST[$varname])) ? true : false;
					break;
				default:
					eval('$a[$varname] = ' . $m . '($varname, $options[\'default\']);');
					break;
			}
		}
		
		return $a;
	}
	
	function _vr($v, $arr, $px)
	{
		if (!is_array($v) || !is_array($arr))
		{
			return;
		}
		
		foreach ($v as $k => $kv)
		{
			if (!in_array($k, $arr) && $kv === '')
			{
				$this->_error('E_' . strtoupper($px) . '_' . strtoupper($k), false);
			}
		}
		
		return;
	}
	
	function auth_access($user_id)
	{
		global $user;
		
		return true; // $user->auth_query($this->module);
	}
	
	function check()
	{
		/*if (!in_array($this->mode, array_keys($this->methods)))
		{
			$this->mode = 'home';
		}
		
		if (empty($this->methods[$this->mode]) || !in_array($this->manage, $this->methods[$this->mode]))
		{
			$this->manage = 'home';
		}*/
	}
	
	function method()
	{
		$f = '';
		foreach ($this->level as $k => $v)
		{
			$f .= '_' . $v;
		}
		
		if (!method_exists($this, $f))
		{
			_fatal();
		}
		return $this->{$f}();
	}
	
	function error($str, $prefix = true)
	{
		$str = ($prefix) ? $this->module . '_' . $str : $str;
		$this->error[] = strtoupper($str);
	}
	
	function get_errors()
	{
		return error_list($this->error, '$');
	}
	
	function errors()
	{
		return count($this->error) ? true : false;
	}
	
	function _error($str, $prefix = true)
	{
		$this->error($str, $prefix);
		$this->e('!');
		
		return;
	}
	
	function extract($mode, $ary, $field)
	{
		$a = array();
		if (!is_array($ary))
		{
			return $a;
		}
		
		switch ($mode)
		{
			case 'field':
				foreach ($ary as $v)
				{
					$a[] = $v[$field];
				}
				break;
		}
		
		return $a;
	}
	
	function implode($glue, $pieces)
	{
		if (!is_array($pieces) || !count($pieces))
		{
			return -1;
		}
		
		return implode($glue, $pieces);
	}
	
	function alias_id($tree, $alias = 'tree_alias', $id = 'tree_id')
	{
		if ($tree[$alias] == 'home')
		{
			$tree[$alias] = false;
		}
		
		if ($tree[$alias] === false)
		{
			return '';
		}
		
		return (!empty($tree[$alias])) ? $tree[$alias] : $tree[$id];
	}
	
	function _sql($sql)
	{
		global $db;
		
		return $db->sql_query($sql);
	}
	
	function _field($sql, $field, $def = false)
	{
		global $db;
		
		$result = $db->sql_query($sql);
		$response = $db->sql_fetchfield($field);
		$db->sql_freeresult($result);
		
		if ($response === false)
		{
			$response = $def;
		}
		
		return $response;
	}
	
	function _fieldrow($sql)
	{
		global $db;
		
		$result = $db->sql_query($sql);
		
		$response = false;
		if ($row = $db->sql_fetchrow($result))
		{
			$row['_numrows'] = $db->sql_numrows($result);
			$response = $row;
		}
		$db->sql_freeresult($result);
		
		return $row;
	}
	
	function _rowset($sql, $field_a = false, $field_b = false, $grouped = false)
	{
		global $db;
		
		$result = $db->sql_query($sql);
		
		$a = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$z = ($field_b === false) ? $row : $row[$field_b];
			if ($field_a === false) {
				$a[] = $z;
			} else {
				eval('$a[$row[$field_a]]' . (($grouped) ? '[]' : '') . ' = $z;');
			}
		}
		$db->sql_freeresult($result);
		
		return $a;
	}
	
	function _nextid()
	{
		global $db;
		
		return $db->sql_nextid();
	}
	
	function _escape($sql)
	{
		global $db;
		
		return $db->sql_escape($sql);
	}
	
	function _build_array($cmd, $a)
	{
		global $db;
		
		return $db->sql_build_array($cmd, $a);
	}
	
	function _affectedrows()
	{
		global $db;
		
		return $db->sql_affectedrows();
	}
	
	function sql_cache($a_sql, $sid = '', $private = true)
	{
		global $db;
		
		return $db->sql_cache($a_sql, $sid, $private);
	}
	
	function sql_cache_limit(&$arr, $start, $end = 0)
	{
		global $db;
		
		$a = $db->sql_cache_limit($arr, $start, $end);
		return $a;
	}
	
	function _numrows(&$a)
	{
		$response = $a['_numrows'];
		unset($a['_numrows']);
		return $response;
	}
	
	function sql_close()
	{
		global $db;
		
		if (isset($db))
		{
			$db->sql_close();
			return true;
		}
		return false;
	}
	
	function as_vars($vars)
	{
		global $style;
		$style->assign_vars($vars);
	}
	
	function _checkbox($v, $s_fields)
	{
		$ret = array();
		if (empty($s_fields))
		{
			return $ret;
		}
		
		$fields = explode(',', $s_fields);
		foreach ($fields as $field)
		{
			if (!isset($v[$field]))
			{
				continue;
			}
			
			$ret += array(
				'_FORM_' . strtoupper($field) . '_YES' => ($v[$field]),
				'_FORM_' . strtoupper($field) . '_NO' => (!$v[$field])
			);
		}
		
		return $ret;
	}
	
	function cc($s)
	{
		return html_entity_decode($s);
	}
	
	function _select($sql, $el, $id, $name)
	{
		global $db, $style;
		
		$rows = $this->_rowset($sql, $id);
		if (count($rows))
		{
			$style->assign_block_vars($el, array());
			
			foreach ($rows as $row)
			{
				$style->assign_block_vars($el . '.item', array(
					'ID' => $row[$id],
					'NAME' => $row[$name])
				);
			}
		}
		
		return;
	}
	
	function e($msg = '')
	{
		global $db, $user;
		
		if ($msg == '!' && !$this->errors())
		{
			return false;
		}
		
		// GZip
		if (!isset($this->config['ob_gz']))
		{
			if (strstr($user->browser, 'compatible') || strstr($user->browser, 'Gecko'))
			{
				ob_start('ob_gzhandler');
				$this->config['ob_gz'] = true;
			}
		}
		
		// Headers
		header('Cache-Control: private, no-cache="set-cookie", pre-check=0, post-check=0');
		header('Expires: 0');
		header('Pragma: no-cache');
		
		//
		if (!is_array($msg))
		{
			if (is_lang($msg))
			{
				$msg = _lang($msg);
			}
			else if ($msg == '!')
			{
				$msg = '#' . $this->get_errors();
			}
		}
		
		$db->sql_close();
		
		if (!is_array($msg))
		{
			echo $msg;
		}
		else
		{
			echo '<pre>';
			print_r($msg);
			echo '</pre>';
		}
		die();
	}
	
	function push(&$v, $a)
	{
		if (!is_array($a) || !count($a))
		{
			return array();
		}
		
		if (!isset($v) || !is_array($v) || !count($v))
		{
			$v = array();
		}
		$v += $a;
	}
	
	function query_vars($query, $a)
	{
		$orig = $repl = array();
		foreach ($a as $k => $v)
		{
			$orig[] = '{v_' . $k . '}';
			$repl[] = $v;
		}
		
		return str_replace($orig, $repl, $query);
	}
	
	function v_array($v)
	{
		$a = array();
		foreach ($v as $k => $v)
		{
			if (!is_string($k))
			{
				$k = $v;
				$v = '';
			}
			$a[$k] = $v;
		}
		
		return $a;
	}
	
	function nav()
	{
		$nav = array('a' => array(), 'b' => '');
		foreach ($this->level as $k => $v)
		{
			if ($v == 'home')
			{
				continue;
			}
			
			$nav['a'][] = "'" . $k . "' => '" . $v . "'";
			$nav['b'] .= '_' . $v;
			eval('$this->navigation(\'' . $nav['b'] . '\', array(' . implode(',', $nav['a']) . '));');
		}
		
		return;
	}
	
	function navigation($k, $v = false, $m = false)
	{
		if (empty($k)) return;
		
		$m = ($m !== false || $k == 'home') ? $m : $this->module;
		$this->nav[$k] = ($v !== false) ? _link($m, $v) : '';
		return;
	}
	
	function get_navigation()
	{
		global $user;
		
		$a = array();
		foreach ($this->nav as $k => $v)
		{
			$is_link = !empty($v);
			$a[] = (($is_link) ? '<a href="' . $v . '">' : '') . _lang($k) . (($is_link) ? '</a>' : '');
		}
		
		return $this->implode(' &rsaquo; ', $a);
	}
	
	function page_title($k = false)
	{
		if ($k !== false)
		{
			$this->paget = $k;
		}
		
		return $this->paget;
	}
	
	function xml($xml)
	{
		$response = '<?xml version="1.0" ?><tree>' . "\n" . $xml . "\n" . '</tree>';
		
		// XML headers
		header("Expires: 0");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Pragma: no-cache");
		header("Content-Type: application/xml; charset=utf-8");
		
		$this->e($response);
		return;
	}
}

?>