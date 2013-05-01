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

// get username and password values, salt and hash the password, stick into the database.
// $user = 'hillary';
// $pass = 'tinylady';

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

$sql = "INSERT INTO users (user_name,pass,email) VALUES ('".$user."','".$salthash."','".$email."')";
$mysqli->query($sql);

?>
