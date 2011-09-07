<?php
/*
$Id: db.php,v 1.1.1.1 2006/08/02 16:07:31 Psychopsia Exp $

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

class db
{
	var $db_connect_id;
	var $query_result;
	var $row = array();
	var $rowset = array();
	var $num_queries = 0;
	var $return_on_error = false;
	var $history = array();

	//
	// Constructor
	//
	function db($d = false)
	{
		$pwd = $this->file($d);
		if ($this->db_connect_id = @mysql_connect($this->server.':3729', $this->user, $pwd))
		{
			if (@mysql_select_db($this->dbname))
			{
				return $this->db_connect_id;
			}
		}
		
		return $this->sql_error('');
	}
	
	//
	// Other base methods
	//
	function sql_close()
	{
		if ($this->db_connect_id)
		{
			if ($this->query_result && @is_resource($this->query_result))
			{
				@mysql_free_result($this->query_result);
			}
			
			if (is_resource($this->db_connect_id))
			{
				return @mysql_close($this->db_connect_id);
			}
		}
		
		return false;
	}

	//
	// Base query method
	//
	function sql_query($query = '', $transaction = FALSE)
	{
		if (is_array($query))
		{
			foreach ($query as $sql)
			{
				$this->sql_query($sql);
			}
			return;
		}
		
		// Remove any pre-existing queries
		unset($this->query_result);
		
		if (!empty($query))
		{
			$this->num_queries++;
			$this->history[] = $query;
			
			if (!$this->query_result = @mysql_query($query, $this->db_connect_id))
			{
				$this->sql_error($query);
			}
			
			$this->log($query);
		}
		
		if ($this->query_result)
		{
			unset($this->row[$this->query_result]);
			unset($this->rowset[$this->query_result]);
			
			return $this->query_result;
		}
		
		return ( $transaction == END_TRANSACTION ) ? true : false;
	}
	
	function sql_query_limit($query, $total, $offset = 0)
	{
		if ($query != '')
		{
			$this->query_result = false;

			// if $total is set to 0 we do not want to limit the number of rows
			if (!$total)
			{
				$total = -1;
			}

			$query .= "\n LIMIT " . ((!empty($offset)) ? $offset . ', ' . $total : $total);

			return $this->sql_query($query);
		}
		
		return false;
	}
	
	function _sql_transaction($status = 'begin')
	{
		switch ($status)
		{
			case 'begin':
				return @mysql_query('BEGIN', $this->db_connect_id);
			break;

			case 'commit':
				return @mysql_query('COMMIT', $this->db_connect_id);
			break;

			case 'rollback':
				return @mysql_query('ROLLBACK', $this->db_connect_id);
			break;
		}

		return true;
	}
	
	// Idea for this from Ikonboard
	function sql_build_array($query, $assoc_ary = false)
	{
		if (!is_array($assoc_ary))
		{
			return false;
		}

		$fields = array();
		$values = array();
		if ($query == 'INSERT')
		{
			foreach ($assoc_ary as $key => $var)
			{
				$fields[] = $key;

				if (is_null($var))
				{
					$values[] = 'NULL';
				}
				elseif (is_string($var))
				{
					$values[] = "'" . $this->sql_escape($var) . "'";
				}
				else
				{
					$values[] = (is_bool($var)) ? intval($var) : $var;
				}
			}

			$query = ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $values) . ')';
		}
		else if ($query == 'UPDATE' || $query == 'SELECT')
		{
			$values = array();
			foreach ($assoc_ary as $key => $var)
			{
				if (is_null($var))
				{
					$values[] = "$key = NULL";
				}
				elseif (is_string($var))
				{
					$values[] = "$key = '" . $this->sql_escape($var) . "'";
				}
				else
				{
					$values[] = (is_bool($var)) ? "$key = " . intval($var) : "$key = $var";
				}
			}
			$query = implode(($query == 'UPDATE') ? ', ' : ' AND ', $values);
		}

		return $query;
	}
	
	function sql_num_queries()
	{
		return $this->num_queries;
	}

	//
	// Other query methods
	//
	function sql_numrows($query_id = 0)
	{
		if(!$query_id)
		{
			$query_id = $this->query_result;
		}
		
		return ($query_id) ? @mysql_num_rows($query_id) : false;
	}
	
	function sql_affectedrows()
	{
		return ($this->db_connect_id) ? @mysql_affected_rows($this->db_connect_id) : false;
	}
	
	function sql_numfields($query_id = 0)
	{
		if(!$query_id)
		{
			$query_id = $this->query_result;
		}
		
		return ($query_id) ? @mysql_num_fields($query_id) : false;
	}
	function sql_fieldname($offset, $query_id = 0)
	{
		if(!$query_id)
		{
			$query_id = $this->query_result;
		}
		
		return ($query_id) ? @mysql_field_name($query_id, $offset) : false;
	}
	function sql_fieldtype($offset, $query_id = 0)
	{
		if(!$query_id)
		{
			$query_id = $this->query_result;
		}
		
		return ($query_id) ? @mysql_field_type($query_id, $offset) : false;
	}
	function sql_fetchrow($query_id = 0)
	{
		if(!$query_id)
		{
			$query_id = $this->query_result;
		}
		if (!$query_id)
		{
			return false;
		}
		
		$this->row['' . $query_id . ''] = @mysql_fetch_array($query_id);
		return @$this->row['' . $query_id . ''];
	}
	function sql_fetchrowset($query_id = 0)
	{
		if(!$query_id)
		{
			$query_id = $this->query_result;
		}
		if($query_id)
		{
			unset($this->rowset[$query_id]);
			unset($this->row[$query_id]);
			$result = array();
			while($this->rowset['' . $query_id . ''] = @mysql_fetch_array($query_id))
			{
				$result[] = $this->rowset['' . $query_id . ''];
			}
			return $result;
		}
		
		return false;
	}
	function sql_fetchfield($field, $rownum = -1, $query_id = 0)
	{
		if(!$query_id)
		{
			$query_id = $this->query_result;
		}
		if($query_id)
		{
			if($rownum > -1)
			{
				$result = @mysql_result($query_id, $rownum, $field);
			}
			else
			{
				if(empty($this->row[$query_id]) && empty($this->rowset[$query_id]))
				{
					if($this->sql_fetchrow())
					{
						$result = $this->row['' . $query_id . ''][$field];
					}
				}
				else
				{
					if($this->rowset[$query_id])
					{
						$result = $this->rowset[$query_id][0][$field];
					}
					else if($this->row[$query_id])
					{
						$result = $this->row[$query_id][$field];
					}
				}
			}
			return (isset($result)) ? $result : false;
		}
		
		return false;
	}
	
	function sql_rowseek($rownum, $query_id = 0){
		if(!$query_id)
		{
			$query_id = $this->query_result;
		}
		
		return ($query_id) ? @mysql_data_seek($query_id, $rownum) : false;
	}
	
	function sql_nextid()
	{
		return ($this->db_connect_id) ? @mysql_insert_id($this->db_connect_id) : false;
	}
	
	function sql_freeresult($query_id = false)
	{
		if(!$query_id)
		{
			$query_id = $this->query_result;
		}

		if ($query_id)
		{
			unset($this->row[$query_id]);
			unset($this->rowset[$query_id]);
			$this->query_result = false;

			@mysql_free_result($query_id);

			return true;
		}
		
		return false;
	}
	
	function sql_escape($msg)
	{
		return mysql_real_escape_string($msg);
	}
	
	//
	function sql_cache($a_sql, $sid = '', $private = true)
	{
		global $user;
		
		$sql = "SELECT *
			FROM _search_cache
			WHERE cache_sid = '" . $this->sql_escape($sid) . "'";
		if ($private)
		{
			$sql .= " AND cache_uid = " . (int) $user->data['user_id'];
		}
		$result = $this->sql_query($sql);
		
		$query = '';
		if ($row = $this->sql_fetchrow($result))
		{
			$query = $row['cache_query'];
		}
		$this->sql_freeresult($result);
		
		if (!empty($sid) && empty($query))
		{
			_fatal();
		}
		
		if (empty($query) && !empty($a_sql))
		{
			$sid = md5(unique_id());
			
			$insert = array(
				'cache_sid' => $sid,
				'cache_query' => $a_sql,
				'cache_uid' => $user->data['user_id'],
				'cache_time' => time()
			);
			$sql = 'INSERT INTO _search_cache' . $this->sql_build_array('INSERT', $insert);
			$this->sql_query($sql);
			
			$query = $a_sql;
		}
		
		$all_rows = 0;
		if (!empty($query))
		{
			//$all_sql = str_replace('*', 'COUNT(*) AS total', );
			$result = $this->sql_query($query);
			
			$all_rows = $this->sql_numrows($result);
			$this->sql_freeresult($result);
			/*
			if ($row = $this->sql_fetchrow($result))
			{
				$all_rows = $row['total'];
			}*/
		}
		
		$has_limit = false;
		if (preg_match('#LIMIT ([0-9]+)(\, ([0-9]+))?#is', $query, $limits))
		{
			$has_limit = $limits[1];
		}
		
		return array('sid' => $sid, 'query' => $query, 'limit' => $has_limit, 'total' => $all_rows);
	}
	
	function sql_cache_limit(&$arr, $start, $end = 0)
	{
		if ($arr['limit'] !== false)
		{
			$arr['query'] = preg_replace('#(LIMIT) ' . $arr['limit'] . '#is', '\\1 ' . $start, $arr['query']);
		}
		else
		{
			$arr['query'] .= ' LIMIT ' . $start . (($end) ? ', ' . $end : '');
		}
		
		return;
	}
	
	//
	function report($replace = true)
	{
		global $style;
		
		$style->assign_block_vars('queriess', array());
		
		foreach ($this->history as $item)
		{
			if ($replace)
			{
				$item = str_replace(array("\n", "\t"), array('<br />', '&nbsp;&nbsp;'), $item);
			}
			
			$style->assign_block_vars('queriess.item', array(
				'QUERY' => $item)
			);
		}
		return;
	}
	
	function file($d)
	{
		if (!is_array($d))
		{
			if (!@file_exists('./.ht'.'da') || !$a = @file('./.ht'.'da')) exit();
			
			$d = explode(',', decode($a[0]));
		}
		
		foreach (array('server' => 0, 'user' => 1, 'dbname' => 3) as $vv => $k)
		{
			$this->{$vv} = decode($d[$k]);
		}
		return decode($d[2]);
	}
	
	function log($query)
	{
		global $user;
		
		$exclude = array('config', 'members', 'sessions', 'members_log');
		$allow = array('INSERT', 'UPDATE', 'DELETE');
		
		$query = preg_replace(preg_array('#%s#is', array("\n", "\r", "\t")), array(' ', '', ''), $query);
		
		preg_match('#^(' . implode('|', $allow) . ') ?(FROM|INTO)? _([a-z]+)#is', $query, $s);
		
		if (count($s) && in_array($s[1], $allow) && !in_array($s[3], $exclude))
		{
			/*$insert = array(
				'uid' => (int) $user->data['user_id'],
				'time' => $user->time,
				'ip' => $user->ip,
				'sql' => $query
			);
			$sql = 'INSERT INTO _members_log' . $this->sql_build_array('INSERT', $insert);
			$this->sql_query($sql);
			*/
		}
	}

	function sql_error($sql = '')
	{
		if (!$this->return_on_error)
		{
			_fatal(507, '', '', $sql . '///' . @mysql_error(), @mysql_errno());
		}
		
		$result = array(
			'message' => @mysql_error($this->db_connect_id),
			'code' => @mysql_errno($this->db_connect_id)
		);

		return $result;
	}
}

?>
