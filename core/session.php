<?php
/*
$Id: session.php,v 1.1.1.1 2006/08/02 16:07:31 Psychopsia Exp $

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

class session
{
	var $session_id = '';
	var $cookie_data = array();
	var $data = array();
	var $contacts = array();
	var $browser = '';
	var $ip = '';
	var $i_ip = '';
	var $page = '';
	var $time = 0;
	
	function start($update_page = true, $auto_session = false)
	{
		global $db, $core;
		
		$this->time = time();
		$this->browser = (!empty($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$this->page = _page();
		$this->ip = (!empty($_SERVER['REMOTE_ADDR'])) ? htmlspecialchars($_SERVER['REMOTE_ADDR']) : '';
		$this->i_ip = (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) ? htmlspecialchars($_SERVER['HTTP_X_FORWARDED_FOR']) : $this->ip;
		
		if ($pos_ip = strpos($this->i_ip, ','))
		{
			$this->i_ip = substr($this->i_ip, 0, $pos_ip);
		}
		
		if (array_strpos($this->page, array('ext')) !== false)
		{
			$update_page = false;
			$auto_session = false;
		}
		
		if (strpos($this->page, 'upgrade') === false)
		{
			if (strstr($this->browser, 'Firefox'))
			{
				include_once(XFS . 'core/browser.php');
				$xbrowser = new browser();
				
				if (version_compare($xbrowser->Version, '2.0.0.0', '<='))
				{
					redirect(_link('upgrade'));
				}
			}
		}
		
		$this->cookie_data = array();
		if (isset($_COOKIE[$core->v('cookie_name') . '_sid']) || isset($_COOKIE[$core->v('cookie_name') . '_u']))
		{
			$this->cookie_data['u'] = request_var($core->v('cookie_name') . '_u', 0);
			$this->session_id = request_var($core->v('cookie_name') . '_sid', '');
		}
		
		// Is session_id is set
		if (!empty($this->session_id))
		{
			$sql = "SELECT m.*, s.*
				FROM _sessions s, _members m
				WHERE s.session_id = '" . $db->sql_escape($this->session_id) . "'
					AND m.user_id = s.session_user_id";
			$result = $db->sql_query($sql);
			
			$this->data = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			
			// Did the session exist in the DB?
			if (isset($this->data['user_id']))
			{
				$s_ip = implode('.', array_slice(explode('.', $this->data['session_ip']), 0, 4));
				$u_ip = implode('.', array_slice(explode('.', $this->ip), 0, 4));
				
				if ($u_ip == $s_ip && $this->data['session_browser'] == $this->browser)
				{
					// Only update session DB a minute or so after last update or if page changes
					if ($this->time - $this->data['session_time'] > 60 || $this->data['session_page'] != $this->page)
					{
						$sql = 'UPDATE _sessions
							SET session_time = ' . (int) $this->time . (($update_page) ? ", session_page = '" . $db->sql_escape($this->page) . "'" : '') . " 
							WHERE session_id = '" . $db->sql_escape($this->session_id) . "'";
						$db->sql_query($sql);
					}
					
					if ($update_page)
					{
						$this->data['session_page'] = $this->page;
					}
					
					// Ultimately to be removed
					$this->data['is_member'] = ($this->data['user_id'] != U_GUEST) ? true : false;
					$this->data['is_founder'] = ($this->data['user_id'] != U_GUEST && $this->data['user_type'] == U_FOUNDER) ? true : false;
					$this->data['is_bot'] = false;
					
					if (!$auto_session || $this->data['is_member'])
					{
						return true;
					}
				}
			}
		}
		
		//
		// Check auto session
		//
		$asc = false;
		if ($auto_session && $update_page)
		{
			if ($asc = $this->auto_session($auto_session))
			{
				return $asc;
			}
		}
		
		if ($auto_session && !$asc)
		{
			//return true;
		}
		
		// If we reach here then no (valid) session exists. So we'll create a new one
		return $this->session_create(false, $update_page);
	}
	
	/**
	* Create a new session
	*
	* If upon trying to start a session we discover there is nothing existing we
	* jump here. Additionally this method is called directly during login to regenerate
	* the session for the specific user. In this method we carry out a number of tasks;
	* garbage collection, (search)bot checking, banned user comparison. Basically
	* though this method will result in a new session for a specific user.
	*/
	function session_create($user_id = false, $update_page = true)
	{
		global $db, $core;
		
		$this->data = array();
		
		// Garbage collection ... remove old sessions updating user information
		// if necessary. It means (potentially) 11 queries but only infrequently
		if ($this->time > $core->v('session_last_gc') + $core->v('session_gc'))
		{
			$this->session_gc();
		}
		
		// If we've been passed a user_id we'll grab data based on that
		if ($user_id !== false)
		{
			$this->cookie_data['u'] = $user_id;
			
			$sql = 'SELECT *
				FROM _members
				WHERE user_id = ' . (int) $this->cookie_data['u'] . '
					AND user_type <> 2';
			$result = $db->sql_query($sql);
			
			$this->data = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
		}
		
		// If no data was returned one or more of the following occured:
		// User does not exist
		// User is inactive
		// User is bot
		if (!count($this->data) || !is_array($this->data))
		{
			$this->cookie_data['u'] = U_GUEST;
			
			$sql = 'SELECT *
				FROM _members
				WHERE user_id = ' . (int) $this->cookie_data['u'];
			$result = $db->sql_query($sql);
			
			$this->data = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
		}
		
		if ($this->data['user_id'] != U_GUEST)
		{
			$sql = 'SELECT session_time, session_id
				FROM _sessions
				WHERE session_user_id = ' . (int) $this->data['user_id'] . '
				ORDER BY session_time DESC';
			$result = $db->sql_query_limit($sql, 1);
			
			if ($sdata = $db->sql_fetchrow($result))
			{
				$this->data = array_merge($sdata, $this->data);
				unset($sdata);
				$this->session_id = $this->data['session_id'];
				
				$db->sql_freeresult($result);
	  		}
			
			$this->data['session_last_visit'] = (isset($this->data['session_time']) && $this->data['session_time']) ? $this->data['session_time'] : (($this->data['user_lastvisit']) ? $this->data['user_lastvisit'] : $this->time);
		}
		else
		{
			$this->data['session_last_visit'] = $this->time;
		}
		
		// At this stage we should have a filled data array, defined cookie u and k data.
		// data array should contain recent session info if we're a real user and a recent
		// session exists in which case session_id will also be set
		
		//
		// Do away with ultimately?
		$this->data['is_member'] = ($this->data['user_id'] != U_GUEST) ? true : false;
		$this->data['is_founder'] = ($this->data['user_id'] != U_GUEST && $this->data['user_type'] == U_FOUNDER) ? true : false;
		$this->data['is_bot'] = false;
		
		//
		//
		
		// Create or update the session
		$sql_ary = array(
			'session_user_id' => (int) $this->data['user_id'],
			'session_start' => (int) $this->time,
			'session_last_visit' => (int) $this->data['session_last_visit'],
			'session_time' => (int) $this->time,
			'session_browser' => (string) $this->browser,
			'session_ip' => (string) $this->ip
		);
		
		if ($update_page)
		{
			$sql_ary['session_page'] = (string) $this->page;
			$this->data['session_page'] = $sql_ary['session_page'];
		}
		
		$sql = 'UPDATE _sessions SET ' . $db->sql_build_array('UPDATE', $sql_ary) . "
			WHERE session_id = '" . $db->sql_escape($this->session_id) . "'";
		if (!$this->session_id || !$db->sql_query($sql) || !$db->sql_affectedrows())
		{
			$this->session_id = $this->data['session_id'] = md5(unique_id());
			
			$sql_ary['session_id'] = (string) $this->session_id;
			
			$sql = 'INSERT INTO _sessions' . $db->sql_build_array('INSERT', $sql_ary);
			$db->sql_query($sql);
		}
		
		$cookie_expire = $this->time + 31536000;
		$this->set_cookie('u', $this->cookie_data['u'], $cookie_expire);
		$this->set_cookie('sid', $this->session_id, 0);
		
		unset($cookie_expire);
		
		return true;
	}
	
	function auto_session(&$auto_session)
	{
		global $db;
		
		$sql = "SELECT user_id, user_username, user_auto_session
			FROM _members
			WHERE user_current_ip = '" . $db->sql_escape($this->i_ip) . "'
				AND user_auto_session = 1
				AND user_inactive = 0
				AND user_internal = 1
				AND user_id <> 1";
		$result = $db->sql_query($sql);
		
		$contacts = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$contacts[] = $row;
		}
		$db->sql_freeresult($result);
		
		if ($s = count($contacts))
		{
			if ($s == 1)
			{
				$this->session_create($contacts[0]['user_id'], true);
				return true;
			}
			
			$this->contacts = $contacts;
			$auto_session = true;
		}
		
		return false;
	}
	
	/**
	* Kills a session
	*
	* This method does what it says on the tin. It will delete a pre-existing session.
	* It resets cookie information and update the users information from the relevant
	* session data. It will then grab guest user information.
	*/
	function session_kill()
	{
		global $db;
		
		$sql = "DELETE FROM _sessions
			WHERE session_id = '" . $db->sql_escape($this->session_id) . "'
				AND session_user_id = " . (int) $this->data['user_id'];
		$db->sql_query($sql);
		
		if ($this->data['user_id'] != U_GUEST)
		{
			// Delete existing session, update last visit info first!
			$sql = 'UPDATE _members
				SET user_lastvisit = ' . (int) $this->data['session_time'] . '
				WHERE user_id = ' . (int) $this->data['user_id'];
			$db->sql_query($sql);
			
			// Reset the data array
			$sql = 'SELECT *
				FROM _members
				WHERE user_id = ' . U_GUEST;
			$result = $db->sql_query($sql);
			
			$this->data = array();
			$this->data = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
		}
		
		$cookie_expire = $this->time - 31536000;
		$this->set_cookie('u', '', $cookie_expire);
		$this->set_cookie('sid', '', $cookie_expire);
		unset($cookie_expire);
		
		$this->session_id = '';
		
		return true;
	}
	
	/**
	* Session garbage collection
	*
	* Effectively we are deleting any sessions older than an admin definable 
	* limit. Due to the way in which we maintain session data we have to 
	* ensure we update user data before those sessions are destroyed. 
	* In addition this method removes autologin key information that is older 
	* than an admin defined limit.
	*/
	function session_gc()
	{
		global $core, $db;
		
		// Get expired sessions, only most recent for each user
		$sql = 'SELECT session_user_id, session_page, MAX(session_time) AS recent_time
			FROM _sessions
			WHERE session_time < ' . ($this->time - $core->v('session_length')) . '
			GROUP BY session_user_id, session_page';
		$result = $db->sql_query_limit($sql, 5);
		
		$del_user_id = '';
		$del_sessions = 0;
		while ($row = $db->sql_fetchrow($result))
		{
			if ($row['session_user_id'] != U_GUEST)
			{
				$sql = 'UPDATE _members
					SET user_lastvisit = ' . $row['recent_time'] . ", user_lastpage = '" . $db->sql_escape($row['session_page']) . "'
					WHERE user_id = " . $row['session_user_id'];
				$db->sql_query($sql);
			}
			
			$del_user_id .= (($del_user_id != '') ? ', ' : '') . (int) $row['session_user_id'];
			$del_sessions++;
		}
		$db->sql_freeresult($result);
		
		if ($del_user_id != '')
		{
			// Delete expired sessions
			$sql = 'DELETE FROM _sessions
				WHERE session_user_id IN (' . $del_user_id . ')
					AND session_time < ' . (int) ($this->time - $core->v('session_length'));
			$db->sql_query($sql);
		}
		
		if ($del_sessions < 5)
		{
			// Less than 5 sessions, update gc timer ... else we want gc
			// called again to delete other sessions
			$core->update_config('session_last_gc', $this->time);
		}

		return;
	}
	
	/**
	* Sets a cookie
	*
	* Sets a cookie of the given name with the specified data for the given length of time.
	*/
	function set_cookie($name, $cookiedata, $cookietime)
	{
		global $core;
		
		if ($core->v('cookie_domain') != 'localhost')
		{
			setcookie($core->v('cookie_name') . '_' . $name, $cookiedata, $cookietime, $core->v('cookie_path'), $core->v('cookie_domain'));
		}
		else
		{
			setcookie($core->v('cookie_name') . '_' . $name, $cookiedata, $cookietime, $core->v('cookie_path'));
		}
	}
	
	function d($d)
	{
		return (isset($this->data[$d])) ? $this->data[$d] : false;
	}
}

/**
* Base user class
*
* This is the overarching class which contains (through session extend)
* all methods utilised for user functionality during a session.
*/
class user extends session
{
	var $lang = array();
	var $theme = array();
	var $date_format;
	var $timezone;
	var $dst;

	var $lang_name;
	var $lang_path;
	var $control;
	
	var $auth;
	var $auth_ch = array();
	
	function setup()
	{
		global $db, $style, $core;
		
		$this->lang_name = $core->v('default_lang');
		$this->lang_path = './base/lang/' . $this->lang_name . '/';
		
		$this->data['user_lang'] = 'es';
		$this->date_format = $this->data['user_dateformat'];
		$this->timezone = $this->data['user_timezone'] * 3600;
		$this->dst = $this->data['user_dst'] * 3600;
		
		// We include common language file here to not load it every time a custom language file is included
		$lang = &$this->lang;
		if ((include($this->lang_path . 'main.php')) === FALSE)
		{
			die('Language file ' . $this->lang_path . 'main.php couldn\'t be opened.');
		}
		
		$style->set_template('./style');
		
		return;
	}
	
	function format_date($gmepoch, $format = false, $forcedate2 = false, $force_function = 'date')
	{
		$forcedate = ($force_function == 'date') ? false : true;
		return $this->format_date_date($gmepoch, $format, $forcedate);
	}
	
	function timeDiff($timestamp, $detailed = false, $n = 0)
	{
		// If the difference is positive "ago" - negative "away"
		$now = time();
		$action = ($timestamp >= $now) ? 'away' : 'ago';
		$diff = ($action == 'away' ? $timestamp - $now : $now - $timestamp);
		
		// Set the periods of time
		$periods = array('s', 'm', 'h', 'd', 's', 'm', 'a');
		$lengths = array(1, 60, 3600, 86400, 604800, 2630880, 31570560);
		
		// Go from decades backwards to seconds
		$result = array();
		
		$i = count($lengths);
		$time = '';
		while ($i >= $n)
		{
			$item = $lengths[$i - 1];
			if ($diff < $item)
			{
				$i--;
				continue;
			}
			
			$val = floor($diff / $item);
			$diff -= ($val * $item);
			$result[] = $val . $periods[($i - 1)];
			
			if (!$detailed)
			{
				$i = 0;
			}
			$i--;
		}
		
		return (count($result)) ? $result : false;
	}
	
	function format_date_date($gmepoch, $format = false, $forcedate = false)
	{
		static $lang_dates, $midnight;
		
		if (empty($lang_dates))
		{
			foreach ($this->lang['datetime'] as $match => $replace)
			{
				$lang_dates[$match] = $replace;
			}
		}
		
		$format = (!$format) ? $this->date_format : $format;
		
		if (!$midnight)
		{
			list($d, $m, $y) = explode(' ', gmdate('j n Y', time() + $this->timezone + $this->dst));
			$midnight = gmmktime(0, 0, 0, $m, $d, $y) - $this->timezone - $this->dst;
		}
		
		if ($forcedate != false)
		{
			$a = $this->timeDiff($gmepoch, 1, 2);
			if ($a !== false)
			{
				if (count($a) < 4)
				{
					return implode(' ', $a);
				}
			}
			else
			{
				return '< 1 minuto';
			}
			
			//$a = ($a !== false) ? implode(' ', $a) : '- 1 minuto';
			//$a = array_splice($a, -3);
			//return $a;
		}
		
		return strtr(@gmdate(str_replace('|', '', $format), $gmepoch + $this->timezone + $this->dst), $lang_dates);
		
		//if (strpos($format, '|') === false || (!($gmepoch > $midnight) && !($gmepoch > $midnight - 86400 && !$forcedate)))
		//if (strpos($format, '|') === false || (!($gmepoch > $midnight && !$forcedate) && !($gmepoch > $midnight - 86400 && !$forcedate)))
		//if (strpos($format, '|') === false && (!($gmepoch > $midnight) && !($gmepoch > $midnight - 86400)) && !$forcedate)
	}
	
	function _groups()
	{
		global $db, $core;
		
		if (!$groups = $core->cache_load('groups'))
		{
			$sql = 'SELECT *
				FROM _groups
				ORDER BY group_id';
			$result = $db->sql_query($sql);
			
			$groups = array();
			while ($row = $db->sql_fetchrow($result))
			{
				$groups[$row['group_id']] = $row;
			}
			$db->sql_freeresult($result);
		}
		
		return $groups;
	}
	
	function auth_groups()
	{
		global $db;
		
		$groups = array();
		if ($this->data['is_founder'])
		{
			$groups = array_keys($this->_groups());
		}
		
		if (!count($groups))
		{
			$sql = 'SELECT g.group_id
				FROM _groups g, _groups_members m
				WHERE g.group_id = m.ug_group
					AND m.ug_member = ' . (int) $this->data['user_id'];
			$result = $db->sql_query($sql);
			
			$groups = array();
			while ($row = $db->sql_fetchrow($result))
			{
				$groups[] = $row['group_id'];
			}
			$db->sql_freeresult($result);
		}
		
		return (count($groups) ? implode(',', $groups) : '0');
	}
	
	function auth_uid($uid)
	{
		$uid = ($uid !== false) ? $uid : $this->data['user_id'];
		return (!$this->data['is_founder']) ? $uid : true;
	}
	
	function auth_query($uid = false)
	{
		global $db, $s;
		
		$this->auth[$uid] = array();
		
		$sql = 'SELECT auth_name, auth_value
			FROM _members_auth
			WHERE auth_uid = ' . (int) $uid . '
			ORDER BY auth_name';
		$result = $db->sql_query($sql);
		
		while ($row = $db->sql_fetchrow($result))
		{
			$k = unserialize(decode($row['auth_name']));
			$v = unserialize(decode($row['auth_value']));
			
			$this->auth[$uid][$k] = $v;
		}
		$db->sql_freeresult($result);
		
		return $this->auth[$uid];
	}
	
	function auth_get($name, $in_a = false, $uid = false)
	{
		$uid = $this->auth_uid($uid);
		if ($uid === true)
		{
			return true;
		}
		
		if (!isset($this->auth[$uid]))
		{
			$this->auth_query($uid);
		}
		
		if (!isset($this->auth[$uid][$name]))
		{
			return $this->auth_update($name, 0, $uid, true);
		}
		
		if ($in_a !== false)
		{
			// TODO
		}
		
		return $this->auth[$uid][$name];
	}
	
	function auth_modify($k, $v, $uid = false)
	{
		global $db, $s;
		
		if ($uid === false)
		{
			$uid = $this->data['user_id'];
		}
		
		$sql = 'SELECT auth_name
			FROM _members_auth
			WHERE auth_id = ' . (int) $k;
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$sql_v = encode(serialize($v));
			$sql_k = unserialize(decode($row['auth_name']));
			
			$sql = "UPDATE _members_auth
				SET auth_value = '" . $db->sql_escape($sql_v) . "'
				WHERE auth_id = " . (int) $k;
			$db->sql_query($sql);
			
			$this->auth[$uid][$sql_k] = $v;
		}
		$db->sql_freeresult($result);
		
		return;
	}
	
	function auth_update($k, $v, $uid = false, $force = false)
	{
		global $db, $s;
		
		if ($uid === false)
		{
			$uid = $this->data['user_id'];
		}
		
		/*$uid = $this->auth_uid($uid);
		if ($uid === true)
		{
			return true;
		}*/
		
		$sql_k = encode(serialize($k));
		$sql_v = encode(serialize($v));
		
		if ($force === false)
		{
			$sql = "UPDATE _members_auth
				SET auth_value = '" . $db->sql_escape($sql_v) . "'
				WHERE auth_uid = " . (int) $uid . "
					AND auth_name = '" . $db->sql_escape($sql_k) . "'";
			$db->sql_query($sql);
		}
		
		if (!$db->sql_affectedrows() || $force === true)
		{
			$insert = array(
				'auth_uid' => (int) $uid,
				'auth_name' => $sql_k,
				'auth_value' => $sql_v
			);
			$sql = 'INSERT INTO _members_auth' . $db->sql_build_array('INSERT', $insert);
			$db->sql_query($sql);
		}
		
		$this->auth[$uid][$k] = $v;
		
		return;
	}
	
	function auth_remove($k, $uid = false)
	{
		global $db, $s;
		
		if ($uid === false)
		{
			$uid = $this->data['user_id'];
		}
		
		$sql_k = encode(serialize($k));
		
		$sql = "DELETE FROM _members_auth
			WHERE auth_name = '" . $db->sql_escape($sql_k) . "'
				AND auth_uid = " . (int) $uid;
		$db->sql_query($sql);
		
		$this->auth[$uid][$k] = false;
		
		return true;
	}
}

class core
{
	var $cache = array();
	var $config = array();
	var $cache_dir;
	
	function core()
	{
		global $db;
		
		$sql = 'SELECT *
			FROM _config';
		$result = $db->sql_query($sql);
		
		while ($row = $db->sql_fetchrow($result))
		{
			$this->config[$row['config_name']] = $row['config_value'];
		}
		$db->sql_freeresult($result);
		
		$this->config['request_method'] = strtolower($_SERVER['REQUEST_METHOD']);
		
		$this->cache_dir = XFS . 'core/cache/';
	}
	
	function update_config($config_name, $config_value)
	{
		global $db;
		
		$update = array('config_value' => $config_value);
		
		$sql = 'UPDATE _config
			SET ' . $db->sql_build_array('UPDATE', $update) . "
			WHERE config_name = '" . $db->sql_escape($config_name) . "'";
		$db->sql_query($sql);
		
		if (!$db->sql_affectedrows() && !isset($this->config[$config_name]))
		{
			$update['config_name'] = $config_name;
			
			$sql = 'INSERT INTO _config' . $db->sql_build_array('INSERT', $update);
			$db->sql_query($sql);
		}
		
		$this->config[$config_name] = $config_value;
	}
	
	function v($k, $v = false)
	{
		global $db;
		
		$a = (isset($this->config[$k])) ? $this->config[$k] : false;
		
		if ($v !== false)
		{
			$update = array('config_value' => $v);
			
			if ($a !== false)
			{
				$sql = 'UPDATE _config
					SET ' . $db->sql_build_array('UPDATE', $update) . "
					WHERE config_name = '" . $db->sql_escape($k) . "'";
			}
			else
			{
				$update['config_name'] = $k;
				$sql = 'INSERT INTO _config' . $db->sql_build_array('INSERT', $update);
			}
			$db->sql_query($sql);
			$this->config[$k] = $a = $v;
		}
		
		return $a;
	}
	
	function cache_crypt($str)
	{
		return sha1($str);
	}
	
	function cache_load($var)
	{
		$filename = $this->cache_dir . $this->cache_crypt($var);
		
		if (@file_exists($filename))
		{
			if (!@include($filename))
			{
				return $this->cache_unload($var);
			}
			
			if (!empty($this->cache[$var]))
			{
				return $this->cache[$var];
			}
			
			return true;
		}
		
		return;
	}
	
	function cache_unload()
	{
		foreach (func_get_args() as $var)
		{
			$cache_filename = $this->cache_dir . $this->cache_crypt($var);
			if (@file_exists($cache_filename))
			{
				@unlink($cache_filename);
			}
		}
		
		return;
	}
	
	function cache_store($var, $data)
	{
		$this->cache_unload($var);
		$filename = $this->cache_dir . $this->cache_crypt($var);
		
		$fp = @fopen($filename, 'w');
		if ($fp)
		{
			$file_buffer = '<?php $' . 'this->cache[\'' . $var . '\'] = ' . ((is_array($data)) ? $this->format($data) : "'" . str_replace("'", "\\'", str_replace('\\', '\\\\', $data)) . "'") . '; ?>';
			
			@flock($fp, LOCK_EX);
			fputs($fp, $file_buffer);
			@flock($fp, LOCK_UN);
			fclose($fp);
			
			@chmod($filename, 0777);
		}
		
		return;
	}
	
	//
	// Borrowed from phpBB 2.2 : acm_file.php
	//
	function format($data)
	{
		$lines = array();
		foreach ($data as $k => $v)
		{
			if (is_array($v))
			{
				$lines[] = "'$k' => " . $this->format($v);
			}
			elseif (is_int($v))
			{
				$lines[] = "'$k' => $v";
			}
			elseif (is_bool($v))
			{
				$lines[] = "'$k' => " . (($v) ? 'TRUE' : 'FALSE');
			}
			else
			{
				$lines[] = "'$k' => '" . str_replace("'", "\\'", str_replace('\\', '\\\\', $v)) . "'";
			}
		}
		return 'array(' . implode(',', $lines) . ')';
	}
	
	function auth($v)
	{
		global $user;
		
		return $user->auth_get($v);
	}
	function select($name, $ary)
	{
		$select = '';
		foreach ($ary as $k => $v)
		{
			$select .= '<option value="' . $k . '">' . $v . '</option>';
		}
		
		return '<select name="' . $name . '">' . $select . '</select>';
	}
	
	function yes_no($name, $selected = 1)
	{
		global $user;
		
		$selected = (int) $selected;
		$class = ($selected) ? 'yes' : 'no';
		
		$html = '<span id="swyn_' . $name . '" class="swyn_' . $class . '">' . _lang(strtoupper($class)) . '</span><input type="hidden" id="tswyn_' . $name . '" name="' . $name . '" value="' . $selected . '" />
		<script type="text/javascript">
		//<![CDATA[
		_.input.radio(\'swyn_' . $name . '\');
		//]]>
		</script>';
		return $html;
	}
}

?>