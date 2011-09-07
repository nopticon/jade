<?php
/*
$Id: ftp.php,v 1.2 2006/02/06 08:05:11 Psychopsia Exp $

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

class ftp
{
	var $use_ftp;
	var $conn_id;
	
	function ftp()
	{
		global $config;
		
		$this->use_ftp = true;
		if (preg_match('/localhost/', $config['server_name']))
		{
			$this->use_ftp = false;
		}
		
		if ($this->use_ftp)
		{
			define('FTP_ASCII', 0);
			define('FTP_BINARY', 1);
		}
		
		return;
	}
	
	function ftp_connect($host, $port = 21, $timeout = 10)
	{
		if ($this->use_ftp)
		{
			$this->conn_id = ftp_connect($host, $port, $timeout);
			return $this->conn_id;
		}
		
		return false;
	}
	
	function ftp_login($ftp_user, $ftp_pass)
	{
		if ($this->use_ftp)
		{
			return @ftp_login($this->conn_id, $ftp_user, $ftp_pass);
		}
		
		return false;
	}
	
	function ftp_quit()
	{
		if ($this->use_ftp)
		{
			if ($this->conn_id)
			{
				@ftp_close($this->conn_id);
			}
		}
		
		return;
	}
	
	function ftp_pwd()
	{
		if ($this->use_ftp)
		{
			return @ftp_pwd($this->conn_id);
		}
		
		return false;
	}
	
	function ftp_nlist($d = './')
	{
		if ($this->use_ftp)
		{
			return @ftp_nlist($this->conn_id, $d);
		}
		
		return false;
	}
	
	function ftp_chdir($ftp_dir)
	{
		if ($this->use_ftp)
		{
			return @ftp_chdir($this->conn_id, $ftp_dir);
		}
		
		return false;
	}
	
	function ftp_mkdir($ftp_dir)
	{
		if ($this->use_ftp)
		{
			return @ftp_mkdir($this->conn_id, $ftp_dir);
		}
		
		return false;
	}
	
	function ftp_site($cmd)
	{
		if ($this->use_ftp)
		{
			return @ftp_site($this->conn_id, $cmd);
		}
		
		return false;
	}
	
	function ftp_cdup()
	{
		if ($this->use_ftp)
		{
			return @ftp_cdup($this->conn_id);
		}
		
		return false;
	}
	
	function ftp_put($remote_file, $local_file)
	{
		if (!file_exists($local_file))
		{
			return false;
		}
		
		if ($this->use_ftp)
		{
			return @ftp_put($this->conn_id, $remote_file, $local_file, FTP_BINARY);
		}
		else
		{
			return @copy($local_file, $remote_file);
		}
		
		return;
	}
	
	function ftp_rename($src, $dest)
	{
		return @ftp_rename($this->conn_id, $src, $dest);
	}
}

?>