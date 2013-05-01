<?php
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