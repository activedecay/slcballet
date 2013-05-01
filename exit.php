<?php 
require 'session.php';
session_unset();
session_destroy();

require 'functions.php';
proceedTo('/register.php');
?>