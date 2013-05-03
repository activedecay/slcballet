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
	proceedTo('/register.php');
	exit;
}
require 'db_con.php';

if (getSession('roleID')!=0) {
	proceedTo('/main.php');
}

$err = array();
$success = array();
$redirect = '';

if (@$_REQUEST['post']=='change') {
	$pass = @mysqli_real_escape_string($mysqli,$_REQUEST['pass']);
	if ($pass != '') {
		if (strlen($pass) < 8) {
			$err['password']='Password must be at least 8 characters.';
		}
		
		if (!count($err)) {
			$user = getSession('user_name');
			include 'user_info_editpass.php';
			if (mysqli_affected_rows($mysqli) != -1) {
				$success['overall'] = 'Thank you!';
				$redirect = '/main.php';
			} else {
				$err['overall']='Database query error.';
			}
		}
		
		// takes the $pass and updates the user table.
		
	} else {
		$err['password']='You must supply a password.';
	}

	$msgs = array();
	$msgs['err']=$err;
	$msgs['success']=$success;
	$msgs['redirect']=$redirect;
	echo json_encode($msgs);
	exit;
}
?>
<html>
<head>
<title>Salt Lake City Ballet - Welcome - Change Password</title>

<link rel="stylesheet" type="text/css" href="css/reset.css" />
<link rel="stylesheet" type="text/css" href="css/slcb-theme/jquery-ui-1.8.16.custom.css" />	
<link rel="stylesheet" type="text/css" href="css/site.css" />
<link rel="stylesheet" type="text/css" href="css/login.css" />
<script type="text/javascript" src="js/jquery-1.6.2.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.16.custom.min.js"></script>


<script type="text/javascript">
function make_err_clear_fun(p_elemtStr)
{
	var elemtStr = p_elemtStr;
	return function(msgs){
		$('#txt_'+elemtStr+'_err_password').html('');
		$('#txt_'+elemtStr+'_err_password2').html('');
		$('#txt_'+elemtStr+'_err_overall').html('');
		$('#txt_'+elemtStr+'_success_overall').html('');
		$('#'+elemtStr+'_password').removeClass('ebx_error');
		$('#'+elemtStr+'_password2').removeClass('ebx_error');
		$('#btn_changePw').button().click(submit_pass_change);
	};
}
function make_err_display_cb(p_elemtStr,p_btn)
{
	var elemtStr = p_elemtStr;
	var btn = p_btn;
	return function(msgs)
	{
		$('#txt_login_success_overall').html('');
		for (m in msgs)
		{
			if (m == 'success')
			{
				// don't put code here because there could be no messages in success
				for (str in msgs[m])
				{
					$(btn).prop('disabled',true);
					$('#register_username').prop('disabled',true);
					$('#register_email').prop('disabled',true);
					options = {color:'#D4FFD2'};
					effect = 'highlight';
					$(btn).prop('disabled',false); // enable the button...
					$('#txt_'+elemtStr+'_success_'+str).effect(effect,options).effect(effect,options);
					$('#txt_'+elemtStr+'_success_'+str).html(msgs[m][str]);
				}
			}
			else if (m == 'err')
			{
				// don't put code here because there could be no messages in err
				for (str in msgs[m])
				{
					$(btn).prop('disabled',false); // enable the button...
					$('#txt_'+elemtStr+'_err_'+str).html(msgs[m][str]);
					$('#'+elemtStr+'_'+str).addClass('ebx_error');
				}
			}
		}
		if (msgs.redirect)
		{
			window.location.replace(msgs.redirect);
		}
	};
}

cb_change = make_err_display_cb('login','#btn_login');
clear_err_login = make_err_clear_fun('login');
function submit_pass_change() {
	if ($("#login_password").val() == $("#login_password2").val()) {
		// passwords match, send request to server.
		var raw_password = $.trim($('#login_password')[0].value);
		$.get("pass_change.php", { pass: raw_password, post: 'change' }, cb_change, "json");
	}
	else {
		$('#txt_login_err_password').html('Passwords don\'t match');
		$('#login_password').addClass('ebx_error');
	}
}
</script>

</head>

<body class="paper">
	<script type="text/javascript">
		$(document).ready(function (){
			$('#btn_changePw').button().click(submit_pass_change);
		});
	</script>
	<div id="wrapper">
		<div id="center">
			<!-- existing users -->
			<div class="logo"></div>
			<div id="passx">
				<div class="whereami">
					<h3 class="h1_header chopin">Online Registration</h3>
					<p class="p_whami">
						Change your password
					</p>
				</div>
				<div>
					<!-- password once -->
					<p class="p_input">
						<span class="txt_label">New Password:</span> <input
							id="login_password" type="password" class="ebx_input" />
					</p>
					<div id="txt_login_err_password" class="txt_ex"></div>

					<!-- password twice -->
					<p class="p_input">
						<span class="txt_label">New Password (repeat):</span> <input
							id="login_password2" type="password" class="ebx_input" />
					</p>
					<div id="txt_login_err_password2" class="txt_ex"></div>

					<!-- login button -->
					<div class="btn_container">
						<input id="btn_changePw" type="button" value="Continue"
							class="btn_login" />
					</div>
				</div>
				<div id="txt_login_err_overall" class="err"></div>
				<div id="txt_login_success_overall" class="success"></div>
			</div>
		</div>
	</div>
</body>
</html>