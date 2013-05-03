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
