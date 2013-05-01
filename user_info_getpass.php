<?php

if (!isset($SESSION_INCLUDED)) require 'session.php';
if (!isset($FUNCTIONS_INCLUDED)) require 'functions.php';
if (!getSession('logon')) {
	if (!preg_match('/^\/register.php/',$_SERVER['REQUEST_URI'])) {
		proceedTo('/register.php');
		exit;
	}
}
require 'db_con.php';

// confirm that the user's password matches what's in the db
// it is required that you set these variables
//$user = 'hillary';
//$pass = '???????';

$query = "SELECT 
			userID,pass,roleID,user_name
		FROM users 
			WHERE user_name='".$user."'";

$results = mysqli_fetch_assoc($mysqli->query($query));
$salthash = $results['pass'];

// The first 64 characters of the hash is the salt
//$salt = substr($result['hash'], 0, 64);
$salt = substr($salthash, 0, 64);
$hash = substr($salthash, 64);

$saltpass = $salt . $pass;

// Hash the password as we did before
for ( $i = 0; $i < 373723; $i ++ )
{
    $saltpass = hash('sha256', $saltpass);
}

$saltpass = $salt . $saltpass;
 
// the user has the right password.
if ($salthash == $saltpass)
{
	$password_validated = true;
	setSession('logon',true);
	setSession('user_name',$results['user_name']);
	setSession('userID',$results['userID']);
	setSession('roleID',$results['roleID']);
}
?>
