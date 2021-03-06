<?php
#    This file is part of SLC Ballet Registration Website.
#
#    SLC Ballet Registration Website is free software: you can redistribute it and/or modify
#    it under the terms of the GNU Affero General Public License as published by
#    the Free Software Foundation, either version 3 of the License, or
#    (at your option) any later version.
#
#    SLC Ballet Registration Website is distributed in the hope that it will be useful,
#    but WITHOUT ANY WARRANTY; without even the implied warranty of
#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#    GNU Affero General Public License for more details.
#
#    You should have received a copy of the GNU Affero General Public License
#    along with SLC Ballet Registration Website.  If not, see <http://www.gnu.org/licenses/>.

	$SESSION_INCLUDED=true;

	session_name('slcbreg');
	session_set_cookie_params(2*7*24*60*60);
	
	session_start();
	// default user no logged in
	if(!isset($_SESSION['logon'])) {
		$logon = setSession('logon',false);
	}
	if(!isset($_SESSION['user_name'])) {
		setSession('user_name','');
	}
	if(!isset($_SESSION['userID'])) {
		setSession('userID','');
	}
	if(!isset($_SESSION['roleID'])) {
		setSession('roleID',0);
	}
	
	function hasSession($session_var) {
		return isset($_SESSION[$session_var]);
	}
	
	function getSession($session_var) 
	{
		return $_SESSION[$session_var];
	}
	
	function setSession($session_var, $new_value)
	{
		$_SESSION[$session_var]=$new_value;
	}
?>