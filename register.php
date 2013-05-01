<?php
if (!isset($SESSION_INCLUDED)) require 'session.php';
if (!isset($FUNCTIONS_INCLUDED)) require 'functions.php';
if (!getSession('logon')) {
	if (!preg_match('/^\/register.php/',$_SERVER['REQUEST_URI'])) {
		proceedTo('/register.php');
		exit;
	}
}

if (getSession('logon')) {
	proceedTo('/main.php');
	exit;
}

require 'db_con.php';

// variables
$err = array();
$success = array();
$redirect = '';

if (@$_REQUEST['post']=='login')
{
	$user = ''; $pass = '';
	$user = @trim(mysqli_real_escape_string($mysqli,$_REQUEST['user'])); // trims whitespace
	$pass = @mysqli_real_escape_string($mysqli,$_REQUEST['pass']);
	if (@$user!='' && @$pass!='')
	{
		// check user creds
		include 'user_info_getpass.php'; // sets the session[logon]

		// the user is logged in of this is true:
		if (getSession('logon'))
		{
			// give the user his proper respects
			$success['overall'] = 'Logging you in...';
			$redirect = '/main.php';
		} else {
			// error logging in
			$err['overall']='Login failed; check your username and password and try again.';
		}
	}
	else
	{
		// empty username or password
		if (@$_REQUEST['user']=='')
		{
			$err['username']='You must supply a username.';
		}
		if (@$_REQUEST['pass']=='')
		{
			$err['password']='You must supply a password.';
		}
	}

	$msgs = array();
	$msgs['err']=$err;
	$msgs['success']=$success;
	$msgs['redirect']=$redirect;
	echo json_encode($msgs);
	exit;
}
elseif (@$_REQUEST['post']=='register')
{
	$user = ''; $email = '';
	$user = @trim(mysqli_real_escape_string($mysqli,$_REQUEST['user']));
	$email = @trim(mysqli_real_escape_string($mysqli,$_REQUEST['email']));

	if (@$user!='' && @$email!='')
	{

		// check username requirements
		if(!preg_match('/^[\w0-9@_\. ]+$/',$user))
		{
			$err['username']='Your username may only contain numbers, letters and underscores.';
		}
		elseif(!preg_match('/^[\w0-9@_\. ]{4,80}$/',$user))
		{
			$err['username']='Your username must be between 4 and 80 characters.';
		}
			
		// check for valid email
		if(!preg_match('/^[\w-]+(?:\.[\w-]+)*@((?:[\w-]+\.)+[a-zA-Z]{2,7}|([0-9]{1,3})(\.[0-9]{1,3}){3})$/i',$email))
		{
			$err['email']='Your email is invalid; check the spelling and try again.';
		}

		// if there are no errors, let's proceed with the creation of this user.
		if(!count($err))
		{
			// username and email are quite possibly valid
			$pass = substr(hash('md5',$user.$email.uniqid(mt_rand(),true)),24);
			$user_pass = $pass;

			// attempt to stick the user in the database
			require 'user_info_setpass.php';

			if (mysqli_affected_rows($mysqli) != -1)
			{
				// user was inserted, alert them via email of their new password
				$to = $email;
				$header = 'From: "SLC Ballet Registration" <registration@slcballet.com>';
				$sub = 'SLC Ballet Registration Sign Up';
				$msg =
'Dear '.$user.',

Thank you for choosing the Salt Lake City Ballet. You can begin registering for classes right away!

Your username is: '.$user.'
Your new password: '.$user_pass.'

See you in class,

Hillary & Terry
Directors';
				if (@mail($to,$sub,$msg,$header))
				{
					$success['overall'] = 'We sent an email to '.$email.' with your new password. <a href="" id="lnk_login_now">Log in now.</a>';
				}
				else
				{
					$err['overall'] = 'Sign up server has failed; contact the administrator.';
				}
			}
			else
			{
				$err['username'] = 'That username is already taken.';
			}
		}
	}
	else
	{
		// empty username or password
		if (@$_REQUEST['user']=='')
		{
			$err['username']='You must supply both fields.';
		}
		if (@$_REQUEST['email']=='')
		{
			$err['email']='You must supply both fields.';
		}
	}

	$msgs = array();
	$msgs['err']=$err;
	$msgs['success']=$success;
	echo json_encode($msgs);
	exit;
}
?>
<html>
<head>
<title>Salt Lake City Ballet - Registration</title>

<link rel="stylesheet" type="text/css" href="css/reset.css" />
<link rel="stylesheet" type="text/css" href="css/slcb-theme/jquery-ui-1.8.16.custom.css" />	
<link rel="stylesheet" type="text/css" href="css/site.css" />
<link rel="stylesheet" type="text/css" href="css/login.css" />
<script type="text/javascript" src="js/jquery-1.6.2.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.16.custom.min.js"></script>
<script type="text/javascript" src="js/jquery.md5.js"></script>

<script type="text/javascript">
	function make_err_clear_fun(p_elemtStr)
	{
		var elemtStr = p_elemtStr;
		return function(msgs){
			$('#txt_'+elemtStr+'_err_username').html('');
			$('#txt_'+elemtStr+'_err_password').html('');
			$('#txt_'+elemtStr+'_err_email').html('');
			$('#txt_'+elemtStr+'_err_overall').html('');
			$('#txt_'+elemtStr+'_success_overall').html('');
			$('#'+elemtStr+'_username').removeClass('ebx_error');
			$('#'+elemtStr+'_password').removeClass('ebx_error');
			$('#'+elemtStr+'_email').removeClass('ebx_error');
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
						// disable everything, and keep the button disabled
						$('#register_username').prop('disabled',true);
						$('#register_email').prop('disabled',true);

						options = {color:'#D4FFD2'};
						effect = 'highlight';

						$('#txt_'+elemtStr+'_success_'+str).html(msgs[m][str]);
						$('#txt_'+elemtStr+'_success_'+str).effect(effect,options).effect(effect,options);

						// dom node must be available before setting a click handler
						$('#lnk_login_now').click(singin);
					}
				}
				else if (m == 'err')
				{
					// don't put code here because there could be no messages in err
					for (str in msgs[m])
					{
						$(btn).button("option", "disabled", false); // enable the button...
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
	cb_login = make_err_display_cb('login','#btn_login');
	clear_err_login = make_err_clear_fun('login');
	cb_register = make_err_display_cb('register','#btn_register');
	clear_err_register = make_err_clear_fun('register');
	// login(): used on the login button to log users in 
	function login() {
		// special case for login to show the status of our checking their password 
		clear_err_login();
		$('#txt_login_success_overall').html('Checking username and password...');

		// disable the button so they don't keep clicking
		$('#btn_login').button("option", 'disabled', true);
		
		var username = $.trim($('#login_username')[0].value);
		var raw_password = $.trim($('#login_password')[0].value);// TODO md5 this raw password.
		$.get("register.php", { user: username, pass: raw_password, post: 'login' }, cb_login, "json");
	}
	// signup(): used on the signup button to add new users 
	function signup() {
		// disable the button so they don't keep clicking
		$('#btn_register').button('option', 'disabled', true);

		// grab the post parameters
		var username = $.trim($('#register_username')[0].value);
		var email = $.trim($('#register_email')[0].value);
		clear_err_register();
		$.get("register.php", { user: username, email: email, post: 'register' }, cb_register, "json");
	}
	// singin(): (mispelled signin) move the user back to the sign-in/login screen 
	function singin(e){
		e.preventDefault();
		$('#exists').show();
		$('#newbs').hide();
		
		// focus the next textbox we expect them to want to edit
		if ($.trim($('#register_username')[0].value))
		{
			$('#login_username')[0].value = $.trim($('#register_username')[0].value);
			$('#login_password').focus();
		}
		else 
		{
			$('#login_username').focus();
		}
		
		$('#lnk_signin').hide();
		$('#lnk_create').show();
		return false;
	}
	// create(): move the user to the create new user page 
	function create(e){
		e.preventDefault();
		$('#newbs').show();
		$('#exists').hide();

		// focus the right textbox...
		$('#register_username').focus();
		
		$('#lnk_signin').show();
		$('#lnk_create').hide();
	}
	
	</script>
</head>

<body class="paper">
	<script type="text/javascript">
		$(document).ready(function (){
			// LOGIN STATES: setup default state and events
			$('#lnk_create').click(create);
			$('#lnk_signin').click(singin);
			$('#lnk_signin').hide();
			$('#lnk_create').show();
			
			$('#btn_login').button().addClass('btn_login').click(login);
			$('#btn_register').button().addClass('btn_login').click(signup);
			
			$('#login_username').focus();
			$('#login_username').keypress(function(e){
				if (e.which == 13) { // enter key pressed
					$('#login_password').focus();
				}
			});
			$('#login_password').keypress(function(e){
				if (e.which == 13) { // enter key pressed
					$('#btn_login').click();
				}
			});
			$('#register_username').keypress(function(e){
				if (e.which == 13) { // enter key pressed
					$('#register_email').focus();
				}
			});
			$('#register_email').keypress(function(e){
				if (e.which == 13) { // enter key pressed
					$('#btn_register').click();
				}
			});
		});
	</script>
	<div id="wrapper">
		<div id="center">
			<!-- existing users -->
			<div class="logo"></div>
			<div id="exists">
				<div class="whereami">
					<h3 class="h1_header chopin">Online Registration</h3>
					<p class="p_whami">Log in to register for class,
						<br /> and to update your personal information.
					</p>
				</div>

				<div>
					<!-- username -->
					<p class="p_input">
						<span class="txt_label">Username:</span> <input
							id="login_username" type="text" class="ebx_input" />
					</p>
					<div id="txt_login_err_username" class="txt_ex"></div>

					<!-- password -->
					<p class="p_input">
						<span class="txt_label">Password:</span> <input
							id="login_password" type="password" class="ebx_input" />
					</p>
					<div id="txt_login_err_password" class="txt_ex"></div>

					<!-- login button -->
					<div class="btn_container">
						<input id="btn_login" type="button" value="Login" />
					</div>
				</div>
				<div id="txt_login_err_overall" class="err"></div>
				<div id="txt_login_success_overall" class="success"></div>
			</div>
			<!-- new users -->
			<div id="newbs">
				<div class="whereami">
					<h3 class="h1_header chopin">Patron Sign Up</h3>
					<p class="p_whami">Choose a username and our automated system
						<br />  will email your password.
					</p>
				</div>

				<div>
					<!-- username -->
					<p class="p_input">
						<span class="txt_label">Desired Username:</span> <input
							id="register_username" type="text" class="ebx_input" />
					</p>
					<div id="txt_register_err_username" class="txt_ex"></div>

					<!-- password -->
					<p class="p_input">
						<span class="txt_label">Email Address:</span> <input
							id="register_email" type="text" class="ebx_input" />
					</p>
					<div id="txt_register_err_email" class="txt_ex"></div>

					<!-- login button -->
					<div class="btn_container">
						<input id="btn_register" type="button" value="Sign Up" />
					</div>
				</div>
				<div id="txt_register_err_overall" class="err"></div>
				<div id="txt_register_success_overall" class="success"></div>
			</div>
			<div class="foot">
				<a id="lnk_create" href="" class="lnk_special"> Click here to create an account.</a> 
				<a id="lnk_signin" href="" class="lnk_special"> Click here to log in.</a>
			</div>
		</div>
	</div>
</body>
</html>
