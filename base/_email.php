<?php
/*
<Jade, Email Server.>
Copyright (C) <2011>  <NPT>

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

class __email extends xmd {
	var $methods = array(
		'create' => array(),
		'clear' => array(),
		'check' => array(),
		'report' => array(),
		'total' => array(),
		'edit' => array(),
		'valid' => array(),
		'table' => array()
	);
	
	function home() {
		global $core, $user, $style;
		
		$sql = 'SELECT *
			FROM _email
			WHERE email_active = 1
			ORDER BY email_id
			LIMIT 1';
		if (!$email = sql_fieldrow($sql)) {
			$this->e('No queue.');
		}
		
		set_time_limit(0);
		
		if (!$email['email_batch']) {
			$email['email_batch'] = 200;
		}
		
		$sql = 'SELECT *
			FROM ??
			ORDER BY address_id
			LIMIT ??, ??';
		if ($members = sql_rowset(sql_filter($sql, $email['email_data'], $email['email_last'], $email['email_batch']))) {
			if (!$email['email_start']) {
				$sql = 'UPDATE _email SET email_start = ?
					WHERE email_id = ?';
				sql_query(sql_filter($sql, time(), $email['email_id']));
			}
		}
		
		$i = 0;
		$sent_to = array();
		
		foreach ($members as $row) {
			$address_account = trim($row['address_account']);
			
			if (!preg_match('/^[a-z0-9\.\-_\+]+@[a-z0-9\-_]+\.([a-z0-9\-_]+\.)*?[a-z]+$/is', $address_account)) {
				continue;
			}
			
			if (!$i) {
				include(XFS . 'core/emailer.php');
				$emailer = new emailer();
			}
			
			$emailer->use_template('mass');
			$emailer->format('html');
			
			$emailer->from($email['email_from'] . ' <' . $email['email_from_address'] . '>');
			$emailer->set_subject(entity_decode($email['email_subject']));
			$emailer->email_address($address_account);
			
			$name_compose = '';
			
			if (isset($row['address_name'])) {
				$row['address_name'] = preg_replace('/\s\s+/', ' ', $row['address_name']);
				$name_compose = ucwords(strtolower(trim($row['address_name'])));
				
				if (isset($row['address_last']) && !empty($row['address_last'])) {
					$row['address_last'] = preg_replace('/\s\s+/', ' ', $row['address_last']);
					$name_compose .= ' ' . ucwords(strtolower(trim($row['address_last'])));
				}
				
				if (!empty($name_compose)) {
					$name_gretting = '';
					
					if (isset($row['address_gender']) && !empty($row['address_gender'])) {
						switch ($row['address_gender']) {
							case 'Femenino':
								$name_by = 'a';
								break;
							case 'Masculino':
								$name_by = 'o';
								break;
							default:
								$name_gretting = $core->config['email_gretting'];
								break;
						}
					} else {
						if (strpos($name_compose, 'Sra.') !== false || strpos($name_compose, 'Srta.') !== false) {
							$name_by = 'a';
						} else if (strpos($name_compose, 'Sr.') !== false) {
							$name_by = 'o';
						} else {
							$name_gretting = $core->config['email_gretting'];
						}
					}
					
					if (empty($email['email_gretting'])) {
						$name_gretting = $core->config['email_gretting'];
					}
					
					if (!empty($name_gretting)) {
						$name_compose = $name_gretting . ' ' . $name_compose;
					} elseif (!empty($name_by)) {
						if (strpos($email['email_gretting'], '*') !== false) {
							$name_compose = str_replace('*', $name_by, $email['email_gretting']) . ' ' . $name_compose;
						}
					}
					
					if (!empty($name_compose)) {
						$name_compose .= ', ';
					}
				}
			}
			
			$emailer->assign_vars(array(
				'USERNAME' => $name_compose,
				'MESSAGE' => entity_decode($email['email_message']))
			);
			$emailer->send();
			$emailer->reset();
			
			$sql = 'UPDATE ?? SET address_sent = ?
				WHERE address_id = ?';
			sql_query(sql_filter($sql, $email['email_data'], time(), $row['address_id']));
			
			$i++;
			
			$sql = 'UPDATE _email SET email_last = ?
				WHERE email_id = ?';
			sql_query(sql_filter($sql, $i, $email['email_id']));
			
			$sent_to[] = $row['address_account'];
			
			sleep(1);
		}
		
		$sql = 'SELECT COUNT(address_id) AS total
			FROM ??
			WHERE address_sent = 0
			ORDER BY address_id';
		if (!sql_field(sql_filter($sql, $email['email_data']), 'total', 0)) {
			$sql = 'UPDATE _email SET email_active = 0, email_end = ?
				WHERE email_id = ?';
			sql_query(sql_filter($sql, time(), $email['email_id']));
			
			return $this->e('Finished sending ' . $i . ' emails.');
		}
		
		return $this->e('Processed ' . $i . ' emails.');
	}
	
	public function table() {
		return $this->method();
	}
	
	public function _table_home() {
		$v = $this->__(array('table'));
		
		if (empty($v['table'])) {
			exit;
		}
		
		$table_name = '_email_' . strtolower(trim($v['table']));
		
		$sql = 'CREATE TABLE IF NOT EXISTS ?? (
			address_id mediumint(5) NOT NULL AUTO_INCREMENT,
			address_name varchar(200) NOT NULL,
			address_last varchar(200) NOT NULL,
			address_account varchar(200) NOT NULL,
			address_gender varchar(200) NOT NULL,
			address_sent int(11) NOT NULL,
			PRIMARY KEY (address_id)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1';
		sql_query(sql_filter($sql, $table_name));
		
		$this->e('Table ' . $table_name . ' was created.');
	}
	
	function valid() {
		return $this->method();
	}
	
	function _valid_home() {
		global $user, $style;
		
		/*
		// the email to validate  
		$email = 'joe@gmail.com';  
		// an optional sender  
		$sender = 'user@example.com';  
		// instantiate the class  
		$SMTP_Valid = new SMTP_validateEmail();  
		// do the validation  
		$result = $SMTP_Valid->validate($email, $sender);  
		// view results  
		var_dump($result);  
		echo $email.' is '.($result ? 'valid' : 'invalid')."\n";  
		  
		// send email?   
		if ($result) {  
		  //mail(...);  
		}
		*/
		
		include(XFS . 'core/smtpvalidate.php');
		$SMTP_Valid = new SMTP_validateEmail();
		
		$sql = 'SELECT *
			FROM _email
			WHERE email_active = 1
			LIMIT 1';
		if (!$email = sql_fieldrow($sql)) {
			$this->e('No queue emails.');
		}
		
		$sql = 'SELECT *
			FROM ??
			ORDER BY address_id
			LIMIT ??, ??';
		$members = sql_rowset(sql_filter($sql, $email['email_data'], $email['email_last'], $email['email_batch']));
		
		$i = 0;
		foreach ($members as $row) {
			if (!preg_match('/^[a-z0-9\.\-_\+]+@[a-z0-9\-_]+\.([a-z0-9\-_]+\.)*?[a-z]+$/is', $row['address_account'])) {
				continue;
			}
			
			$result = $SMTP_Valid->validate($row['address_account'], 'clientes@claro.com.sv');
			
			// view results
			echo '<pre>';
			var_dump($result);
			echo '</pre>';
			
			flush();
			
			sleep(1);
			
			$i++;
		}
		
		exit;
		
		return;
	}
	
	function check() {
		$this->method();
	}
	
	function _check_home() {
		global $user;
		
		$v = $this->__(array('id' => 0));
		
		$sql = 'SELECT *
			FROM _email
			WHERE email_id = ?';
		if (!$email = sql_fieldrow(sql_filter($sql, $v['id']))) {
			$this->e('El registro de email no existe.');
		}
		
		foreach (w('start end') as $k) {
			$email['email_' . $k] = ($email['email_' . $k]) ? $user->format_date($email['email_' . $k]) : '';
		}
		
		foreach ($email as $k => $v) {
			if (is_numb($k)) unset($email[$k]);
		}
		
		$this->e($email);
	}
	
	function create() {
		$this->method();
	}
	
	function _create_home() {
		global $style;
		
		$v_fields = array('data', 'batch', 'gretting', 'from', 'from_address', 'subject', 'message');
		
		if (_button()) {
			$v = $this->__($v_fields);
			
			$sql = 'SELECT email_id
				FROM _email
				WHERE email_subject = ?
					AND email_message = ?';
			if (sql_fieldrow(sql_filter($sql, $v['subject'], $v['subject']))) {
				$this->e('El email ya esta programado para envio, no se puede duplicar.');
			}
			
			$v['active'] = 1;
			$v['data'] = '_email_' . $v['data'];
			$v['message'] = str_replace(array('&lt;', '&gt;', '&quot;'), array('<', '>', '"'), $v['message']);
			
			$sql = 'INSERT INTO _email' . sql_build('INSERT', prefix('email', $v));
			sql_query($sql);
			
			$this->e('El mensaje fue programado para envio de email.');
		}
		
		$tables = sql_rowset('SHOW TABLES', false, false, false, MYSQL_NUM);
		
		$i = 0;
		foreach ($tables as $table) {
			$table = $table[0];
			$search = '_email_';
			
			if (preg_match('#' . $search . '#i', $table)) {
				if (!$i) {
					$style->assign_block_vars('tables', array());
				}
				
				$style->assign_block_vars('tables.row', array(
					'TABLE' => str_replace($search, '', $table))
				);
				
				$i++;
			}
		}
		
		$sv = array();
		foreach ($v_fields as $field) {
			$sv[strtoupper($field)] = '';
		}
		
		$this->as_vars($sv);
	}
	
	function edit() {
		$this->method();
	}
	
	function _edit_home() {
		global $user, $style;
		
		$v = $this->__(array('id' => 0));
		
		$sql = 'SELECT *
			FROM _email
			WHERE email_id = ?';
		
		$email = sql_fieldrow(sql_filter($sql, $v['id']));
		if (!$email = sql_fieldrow(sql_filter($sql, $v['id']))) {
			$this->e('El registro de email no existe.');
		}
		
		$v_fields = array('data', 'batch', 'gretting', 'from', 'from_address', 'subject', 'message');
		
		if (_button()) {
			$v = array_merge($v, $this->__($v_fields));
			
			$v['data'] = '_email_' . $v['data'];
			$v['message'] = str_replace(array('&lt;', '&gt;', '&quot;'), array('<', '>', '"'), $v['message']);
			
			$sql = 'UPDATE _email SET ??
				WHERE email_id = ?';
			sql_query(sql_filter($sql, sql_build('UPDATE', ksql('email', $v)), $v['id']));
			
			$this->e('El mensaje programado fue actualizado.');
		}
		
		$tables = sql_rowset('SHOW TABLES', false, false, false, MYSQL_NUM);
		
		$i = 0;
		foreach ($tables as $table) {
			$table = $table[0];
			$search = '_email_';
			
			if (preg_match('#' . $search . '#i', $table)) {
				if (!$i) {
					$style->assign_block_vars('tables', array());
				}
				
				$style->assign_block_vars('tables.row', array(
					'TABLE' => str_replace($search, '', $table))
				);
				
				$i++;
			}
		}
		
		$sv = array();
		foreach ($v_fields as $field) {
			$sv[strtoupper($field)] = $email['email_' . $field];
		}
		
		$this->as_vars($sv);
	}
	
	function clear() {
		return $this->method();
	}
	
	function _clear_home() {
		global $user;
		
		$v = $this->__(array('id'));
		
		if ($v['id']) {
			$sql = 'SELECT *
				FROM _email
				WHERE email_id = ?';
			if (!$email = sql_fieldrow(sql_filter($sql, $v['id']))) {
				$this->e('El registro de email no existe.');
			}
			
			$sql = 'UPDATE _email SET email_active = 1, email_start = 0, email_end = 0, email_last = 0
				WHERE email_id = ?';
			sql_query(sql_filter($sql, $v['id']));
			
			$sql = 'UPDATE ?? SET address_sent = 0';
			sql_query(sql_filter($sql, $email['email_data']));
			
			$this->e('El registro de email fue reiniciado.');
		}
		
		$sql = 'SELECT email_id, email_subject
			FROM _email
			ORDER BY email_id';
		$emails = sql_rowset($sql);
		
		$response = '';
		foreach ($emails as $row) {
			$response .= '<a href="/jade/email/x1:clear.id:' . $row['email_id'] . '">' . $row['email_subject'] . '</a><br />';
		}
		
		$this->e($response);
	}
	
	function report() {
		return $this->method();
	}
	
	function _report_home() {
		$report = $this->implode('', @file('./mass.txt'));
		
		$list = explode("\n", $report);
		
		$a = '';
		foreach ($list as $i => $row) {
			$a .= ($i + 1) . ' > ' . $row . '<br />';
		}
		
		$this->e($a);
	}
	 
	function total() {
		return $this->method();
	}
	
	function _total_home() {
		$v = $this->__(array('id' => 0));
		
		$sql = 'SELECT *
			FROM _email
			WHERE email_id = ?';
		if (!$email = sql_fieldrow(sql_filter($sql, $v['id']))) {
			$this->e('El registro de email no existe.');
		}
		
		$sql = 'SELECT COUNT(address_id) AS total
			FROM ??';
		$total = sql_field(sql_filter($sql, $email['email_data']), 'total', 0);
		
		$this->e($total);
	}
}

?>
