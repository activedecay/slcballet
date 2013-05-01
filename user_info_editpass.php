<?php
// only the user whose ID is on the session can change their password

// you must set $user,$pass before coming in here.
//$user = "Bobby";
//$pass = "asdfasdf";
//$_SESSION['userID']=12;
if (!isset($SESSION_INCLUDED)) require 'session.php';
if (!isset($FUNCTIONS_INCLUDED)) require 'functions.php';
if (!getSession('logon')) {
	proceedTo('/register.php');
	exit;
}
require 'db_con.php';

// Create a 256 bit (64 characters) long random salt
$salt = hash('sha256', uniqid(mt_rand(), true) . 'slcbasdfllet rawks~' . strtolower($user));

// Prefix the password with the salt
$saltpass = $salt . $pass;
 
// Hash the salted password a bunch of times
for ( $i = 0; $i < 373723; $i ++ )
{
    $saltpass = hash('sha256', $saltpass);
}

// Prefix the hash with the salt so we can find it back later
$salthash = $salt . $saltpass;

$sql = "UPDATE users SET pass='".$salthash."', roleID=1 WHERE users.userID = ".getSession('userID');

$mysqli->query($sql);

?>