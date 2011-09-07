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
		'valid' => array()
	);
	
	function home() {
		global $user, $style;
		
		$sql = 'SELECT *
			FROM _email
			WHERE email_active = 1
			LIMIT 1';
		if (!$email = sql_fieldrow($sql)) {
			$this->e('No queue emails.');
		}
		
		set_time_limit(0);
		
		if (!$email['email_start']) {
			$sql = 'UPDATE _email SET email_start = ?
				WHERE email_id = ?';
			sql_query(sql_filter($sql, time(), $email['email_id']));
		}
		
		$sql = 'SELECT *
			FROM ??
			ORDER BY address_id
			LIMIT ??, ??';
		$members = sql_rowset(sql_filter($sql, TABLE, $email['email_last'], $email['email_batch']));
		
		$i = 0;
		foreach ($members as $row) {
			if (!preg_match('/^[a-z0-9\.\-_\+]+@[a-z0-9\-_]+\.([a-z0-9\-_]+\.)*?[a-z]+$/is', $row['address_account'])) {
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
			$emailer->email_address(trim($row['address_account']));
			
			/*$hi = 'o';
			if (strtolower($row['address_genre']) == 'Femenino') {
				$hi = 'a';
			}
			 * */
			
			$address_name = '';
			
			/*
			$address_name = 'Estimad' . $hi;
			
			if (!empty($row['address_name']) || !empty($row['address_last']))
			{
				if (!empty($row['address_name']))
				{
					$address_name .= ' ' . ucwords(strtolower($row['address_name']));
				}
				
				if (!empty($row['address_last']))
				{
					$address_name .= ' ' . ucwords(strtolower($row['address_last']));
				}
			}*/
			
			//$address_name .= ',';
			
			if (!empty($row['address_name'])) {
				$person = $row['address_name'];
				
				$hi = '';
				if (strpos($person, 'Sr.') !== false) {
					$hi = 'o';
				}
				
				if (strpos($person, 'Srta.') !== false || strpos($person, 'Sra.') !== false) {
					$hi = 'a';
				}
				
				if (empty($hi)) {
					$hi = 'o';
				}
				
				$address_name = 'Estimad' . $hi . ' ' . $person . ',';
			}
			
			$emailer->assign_vars(array(
				'USERNAME' => $address_name,
				'MESSAGE' => entity_decode($email['email_message']))
			);
			$emailer->send();
			$emailer->reset();
			
			$sql = 'UPDATE ?? SET address_sent = ?
				WHERE address_id = ?';
			sql_query(sql_filter($sql, $email['email_data'], time(), $row['address_sent']));
			
			$i++;
			
			$sql = 'UPDATE _email SET email_last = ?
				WHERE email_id = ?';
			sql_query(sql_filter($sql, $i, $email['email_id']));
			
			sleep(2);
		}
		
		if ($i == count($members)) {
			$sql = 'UPDATE _email SET email_active = 0, email_end = ?
				WHERE email_id = ?';
			sql_query(sql_filter($sql, time(), $email['email_id']));
		}
		
		return $this->e('Processed ' . $i . ' emails.');
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
			FROM ' . EMAIL_TABLE . '
			ORDER BY address_id
			LIMIT ??, ??';
		$members = sql_rowset(sql_filter($sql, $email['email_last'], $email['email_batch']));
		
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
			
			sleep(2);
			
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
		
		foreach (w('lastvisit start end') as $k) {
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
		
		if (_button()) {
			$v = $this->__(array('subject', 'message', 'lastvisit' => 0));
			
			$sql = 'SELECT email_id
				FROM _email
				WHERE email_subject = ?
					AND email_message = ?';
			if (sql_fieldrow(sql_filter($sql, $v['subject'], $v['subject']))) {
				$this->e('El email ya esta programado para envio, no se puede duplicar.');
			}
			
			$v['active'] = 1;
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
		
		$sv = array(
			'SUBJECT' => '',
			'MESSAGE' => '',
			'LASTVISIT' => ''
		);
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
		if (!$email = sql_fieldrow(sql_filter($sql, $v['id']))) {
			$this->e('El registro de email no existe.');
		}
		
		if (_button()) {
			$v = array_merge($v, $this->__(array('subject', 'message', 'lastvisit')));
			
			$vs = explode(' ', $v['lastvisit']);
			$v['lastvisit'] = mktime(0, 0, 0, $vs[1], $vs[0], $vs[2]);
			
			$sql = 'UPDATE _email SET ??
				WHERE email_id = ?';
			sql_query(sql_filter($sql, sql_build('UPDATE', ksql('email', $v)), $v['id']));
			
			$this->e('El mensaje programado fue actualizado.');
		}
		
		$lastvisit = $user->format_date($email['email_lastvisit'], 'j n Y');
		
		$sv = array(
			'SUBJECT' => $email['email_subject'],
			'MESSAGE' => $email['email_message'],
			'LASTVISIT' => $lastvisit
		);
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
				WHERE email_id = ' . (int) $v['id'];
			if (!$email = sql_fieldrow($sql)) {
				$this->e('El registro de email no existe.');
			}
			
			$sql = 'UPDATE _email SET email_active = 1, email_start = 0, email_end = 0, email_last = 0
				WHERE email_id = ?';
			sql_query(sql_filter($sql, $v['id']));
			
			$this->e('El registro de email fue reiniciado.');
		}
		
		$sql = 'SELECT email_id, email_subject
			FROM _email
			ORDER BY email_id';
		$emails = sql_rowset($sql);
		
		$response = '';
		foreach ($emails as $row) {
			$response .= '<a href="/nijad/email/x1:clear.id:' . $row['email_id'] . '">' . $row['email_subject'] . '</a><br />';
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
			FROM _email_' . $email['email_data'];
		$total = sql_field($sql, 'total', 0);
		
		$sql = 'SELECT COUNT(address_id) AS total
			FROM _email_address';
		$all = sql_field($sql, 'total', 0);
		
		$this->e($total . ' . ' . $all);
	}
}

?>
