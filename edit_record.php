<?php
if (!isset($SESSION_INCLUDED)) require 'session.php';
if (!isset($FUNCTIONS_INCLUDED)) require 'functions.php';
if (!getSession('logon')) {
	proceedTo('/register.php');
	exit;
}
require 'constants.php';
require 'db_con.php';

foreach($_REQUEST as $key => $val){
	$realkey = $mysqli->real_escape_string($key);
	$realval = $mysqli->real_escape_string($val);
	setSession('edit-'.$realkey, $realval);
	#echo ($realkey .'=>'. $realval .'<br/>'); // DO NOT ECHO IF post=submit
}

$err = array();
$success = array();
$redirect = '';

if (@$_REQUEST['post']=='submit') {
	$user = @getSession('user_name'); // trims whitespace
	$pass = @mysqli_real_escape_string($mysqli,$_REQUEST['pass']);
	if (@$user!='' && @$pass!='')
	{
		$password_validated = false;
		
		// check user creds
		include 'user_info_getpass.php'; // sets the session[logon]
	
		if ($password_validated) {
			// give the user their propers
			$success['success'] = 'true';
		} else {
			// error logging in
			$err['overall']='Incorrect password. Try again.';
		}
	}
	else {
		// empty password
		if (@$pass['pass']=='') {
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

$edit_type_str = '';

if (@$_REQUEST['studentID']) {
	$edit_type_str = 'Student';
	setSession('editing','student');
}
elseif (@$_REQUEST['gaurdianID']) {
	$edit_type_str = 'Guardian';
	setSession('editing','gaurdian');
}

if ($edit_type_str == '') {
	printf('No edit type.');
	exit;
}
?>

<div class="more-headline">
	<span class="more-headline-title">Edit <?=$edit_type_str?></span>
	<span class="more-headline-subbutton"><button class="continue edit-continue">Continue</button></span>
	<span class="more-headline-sublnk">
		<a href="" class="headline-cancel" >
			<span></span>
			<span class="cancel txt">Cancel</span>
		</a>
	</span>
</div>

<?php
if ($edit_type_str == 'Student') {
	// get the student to edit, but make sure that 
	// users are only allowed to edit their own students
	$sql_student = 'SELECT * 
			FROM students
			WHERE studentID = '.getSession('edit-studentID').' 
				AND ownerID = '.getSession('userID');
	$res_stud = $mysqli->query($sql_student);
	$row_student = mysqli_fetch_array($res_stud);
	if (!$row_student) {
		printf("Error, no results.");
		exit;
	}
	
	$student_is_adult = $row_student['gaurdianID'] == null;
	// the edit_success page needs to know that we're editing an adult student.
	setSession('editing_adult',$student_is_adult);
?>
<div class="register-section">
	<div class="register-section-header">
		Student Information
	</div>
	<div class="register-section-content">
		<div class="register-infos">
			<span class="register-label">Name</span>
			<input name="student_name" type="text" maxlength="100" />
		</div>
		<div class="register-infos">
			<?= $student_is_adult ? 
			'<span class="register-label">Mailing Address</span>
			<input name="student_address" type="text" maxlength="180" />'
			:
			'<span class="register-label">Home Address</span>
			<input name="student_address" type="text" maxlength="180" />';
			?>
		</div>
		<div class="register-infos">
			<span class="register-label">City</span>
			<input name="student_city" type="text" maxlength="80" />
		</div>
		<div class="register-infos register-infos-short">
			<span class="register-label">Zip</span>
			<input name="student_zip" type="text" maxlength="20" />
		</div>
		<?= $student_is_adult ?
		'<div class="register-infos register-infos-short">
			<span class="register-label">Phone</span>
			<input name="student_phone" type="text" maxlength="50" />
		</div>'
		:'';?>
	</div>
</div>
<div class="register-section" id="emergency-infos">
	<div class="register-section-header">
		Emergency Contact
	</div>
	<div class="register-section-content">
		<div class="register-infos">
			<span class="register-label">Name</span>
			<input name="emergency_name" type="text" maxlength="100" />
		</div>
		<div class="register-infos register-infos-short">
			<span class="register-label">Phone</span>
			<input name="emergency_phone" type="text" maxlength="50" />
		</div>
	</div>
</div>
<script>
	$("[name=student_name]").val('<?=$row_student['student_name']?>');
	$("[name=student_address]").val('<?=$row_student['student_address']?>');
	$("[name=student_city]").val('<?=$row_student['student_city']?>');
	$("[name=student_zip]").val('<?=$row_student['student_zip']?>');
	$("[name=student_phone]").val('<?=$row_student['student_phone']?>');
	$("[name=emergency_name]").val('<?=$row_student['emergency_name']?>');
	$("[name=emergency_phone]").val('<?=$row_student['emergency_phone']?>');
</script>
<?php 
}#end student
?>


<?php 
if ($edit_type_str == 'Gaurdian') {
	$sql_gaurdian = 'SELECT gaurdian_name, gaurdian_address, gaurdian_city, gaurdian_zip, gaurdian_phone, gaurdian_altphone
			FROM gaurdians g
				JOIN students s
					ON s.gaurdianID = g.gaurdianID
			WHERE g.gaurdianID = '.getSession('edit-gaurdianID').'
				AND ownerID = '.getSession('userID');
	
	$res_gaurdian = $mysqli->query($sql_gaurdian);
	if (!$res_gaurdian) {
		printf("Error, no results. gid ".getSession('edit-gaurdianID').", uid ".getSession('userID'));
        exit;
	}
	$row_gaurdian = mysqli_fetch_array($res_gaurdian);
	
?>
<div class="register-section">
	<div class="register-section-header">
		<div class="txt_gaurdian_info">
			Guardian Information
		</div>
	</div>
	<div id="gaurdian-infos" class="register-section-content">
		<div class="register-infos">
			<span class="register-label">Name</span>
			<input name="gaurdian_name" type="text" maxlength="100" />
		</div>
		<div id="hide_for_stored_gaurdian">
			<div class="register-infos">
				<span class="register-label">Mailing Address</span>
				<input name="gaurdian_address" type="text" maxlength="180" />
			</div>
			<div class="register-infos">
				<span class="register-label">City</span>
				<input name="gaurdian_city" type="text" maxlength="80" />
			</div>
			<div class="register-infos register-infos-short">
				<span class="register-label">Zip</span>
				<input name="gaurdian_zip" type="text" maxlength="20" />
			</div>
			<div class="register-infos register-infos-short">
				<span class="register-label">Home Phone</span>
				<input name="gaurdian_phone" type="text" maxlength="80" />
			</div>
			<div class="register-infos register-infos-short">
				<span class="register-label">Email</span>
				<input name="gaurdian_altphone" type="text" maxlength="50" />
			</div>
		</div>
	</div>
</div>
<script>
	$("[name=gaurdian_name]").val('<?=$row_gaurdian['gaurdian_name']?>');
	$("[name=gaurdian_address]").val('<?=$row_gaurdian['gaurdian_address']?>');
	$("[name=gaurdian_city]").val('<?=$row_gaurdian['gaurdian_city']?>');
	$("[name=gaurdian_zip]").val('<?=$row_gaurdian['gaurdian_zip']?>');
	$("[name=gaurdian_phone]").val('<?=$row_gaurdian['gaurdian_phone']?>');
	$("[name=gaurdian_altphone]").val('<?=$row_gaurdian['gaurdian_altphone']?>');
</script>
<?php 
}#end gaurdian
?>


<div class="register-section">
	<div class="register-section-header">
		<div class="txt_gaurdian_info">
			Confirmation
		</div>
	</div>
	<div class="register-section-content">
		<div class="register-paragraph">
			<div class="register-notice-gaurdians ui-state-highlight ui-corner-all"> 
				<p><span class="ui-icon ui-icon-notice" style="float: left; margin-right: .3em;"></span>
				Enter your password to confirm the changes.</p>
			</div>
		</div>
		<div class="register-infos">
			<span class="register-label"> Password </span>
			<input name="pass" type="password" />
		</div>
	</div>
</div>


<div class="more-headline">
	<span class="more-headline-title">Edit <?=$edit_type_str?></span>
	<span class="more-headline-subbutton"><button class="continue edit-continue">Continue</button></span>
	<span class="more-headline-sublnk">
		<a href="" class="headline-cancel" >
			<span></span>
			<span class="cancel txt">Cancel</span>
		</a>
	</span>
</div>