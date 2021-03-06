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

if (!is_array($lang) || empty($lang)) {
	$lang = array();
}

$lang += array(
	'ENCODING' => 'iso-8859-1',
	'DATE_FORMAT' => 'd M Y',
	'DATE_FORMAT_FULL' => 'd F Y',
	'YES' => 'Si',
	'NO' => 'No',
	
	'PAGE_HEADER' => 'Jade',
	'PAGE_HEADER_SUB' => 'Jade Email Server',
	
	'LOGIN' => 'Iniciar sesi&oacute;n',
	'LOGOUT' => 'Salir del sistema',
	'LOGGED_OUT' => 'Ha cerrado su sesi&oacute;n del sistema correctamente.',
	
	'INFORMATION' => 'Informaci&oacute;n',
	'CONTROL' => 'Panel de control',
	'PREFERENCES' => 'Preferencias de usuario',
	'SEARCH_CONTACT' => 'Buscar usuario',
	'USERS' => 'Usuarios',
	'GROUPS' => 'Grupos',
	'OPTIONS' => 'Ver',
	'EDIT' => 'Editar',
	'REMOVE' => 'Eliminar',
	'AGO' => 'hace ',
	'AGO_LESS_MIN' => 'menos de 1 minuto',
	'CONTROL_PANEL' => 'Panel de control',
	'SAVED' => 'La informaci&oacute;n fue guardada.',
	'PUBLIC' => 'P&uacute;blico',
	'PRIVATE' => 'Privado',
	'LOCATION' => 'Ubicaci&oacute;n',
	'ERROR' => 'Error',
	'HIDE' => 'Ocultar mensaje',
	'SHOW_DETAILS' => 'Mostrar detalles',
	'HIDE_DETAILS' => 'Ocultar detalles',
	'ELEMENTS' => 'elementos',
	'ELEMENT' => 'elemento',
	'SIR' => 'se&ntilde;or',
	'PRINT' => 'Imprimir',
	'LOADING' => 'Cargando...',
	'REPLY' => 'Respuesta',
	
	'PAGES_ON' => 'P&aacute;gina <strong>%d</strong> de <strong>%d</strong>',
	'PAGES_PREV' => 'P&aacute;gina anterior',
	'PAGES_NEXT' => 'P&aacute;gina siguiente',
	
	/* Display */
	'Smart_dates_ago' => 'Hace %s',
	'Smart_dates_at' => '@ %s',
	'Smart_dates_yesterday'	=> 'Ayer',
	/* Time strings */
	'Smart_dates_second' => 'segundo',
	'Smart_dates_seconds' => 'segundos',
	'Smart_dates_minute' => 'minuto',
	'Smart_dates_minutes' => 'minutos',
	'Smart_dates_hour' => 'hora',
	'Hours' => 'horas',
	
	'datetime_chars' => array('%d a&ntilde;o', '%d mes', '%d d&iacute;a', '%d hora', '%d minuto'),
	
	'datetime' => array(
		'Sunday'	=> 'Domingo',
		'Monday'	=> 'Lunes',
		'Tuesday'	=> 'Martes',
		'Wednesday'	=> 'Mi&eacute;rcoles',
		'Thursday'	=> 'Jueves',
		'Friday'	=> 'Viernes',
		'Saturday'	=> 'Sabado',
		
		'days' => array(
			'Domingo', 'Lunes', 'Martes', 'Mi&eacute;rcoles', 'Jueves', 'Viernes', 'Sabado'
		),

		'Sun'		=> 'Dom',
		'Mon'		=> 'Lun',
		'Tue'		=> 'Mar',
		'Wed'		=> 'Mie',
		'Thu'		=> 'Jue',
		'Fri'		=> 'Vie',
		'Sat'		=> 'Sab',

		'January'	=> 'Enero',
		'February'	=> 'Febrero',
		'March'		=> 'Marzo',
		'April'		=> 'Abril',
		'May'		=> 'Mayo',
		'June'		=> 'Junio',
		'July'		=> 'Julio',
		'August'	=> 'Agosto',
		'September' => 'Septiembre',
		'October'	=> 'Octubre',
		'November'	=> 'Noviembre',
		'December'	=> 'Diciembre',

		'Jan'		=> 'Ene',
		'Feb'		=> 'Feb',
		'Mar'		=> 'Mar',
		'Apr'		=> 'Abr',
		'Jun'		=> 'Jun',
		'Jul'		=> 'Jul',
		'Aug'		=> 'Ago',
		'Sep'		=> 'Sep',
		'Oct'		=> 'Oct',
		'Nov'		=> 'Nov',
		'Dec'		=> 'Dic',

		'TODAY'		=> 'Hoy',
		'YESTERDAY'	=> 'Ayer',
	)
);

?>