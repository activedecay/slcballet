<?php 
if (!isset($SESSION_INCLUDED)) require 'session.php';
if (!isset($FUNCTIONS_INCLUDED)) require 'functions.php';
if (!getSession('logon')) {
	proceedTo('/register.php');
	exit;
}
require 'constants.php';
require 'db_con.php';

$edit_type_str = getSession('editing');
$adult = getSession('editing_adult');

if ($edit_type_str == '') {
	printf('No edit type.');
	exit;
}

$sql = 'ERROR';

$student = false;

if ($edit_type_str == 'student') {
	$student = true;
	$studentID=@getSession('edit-studentID');
	$name=@getSession('edit-student_name');
	$address=@getSession('edit-student_address');
	$city=@getSession('edit-student_city');
	$zip=@getSession('edit-student_zip');
	if ($adult){
		$phone=@getSession('edit-student_phone');
	} else {
		$emergency_name=@getSession('edit-emergency_name');
		$emergency_phone=@getSession('edit-emergency_phone');
	}
	$sql =
'UPDATE slcballetadmin.students
SET
	student_name='.@getSession('edit-student_name').',
	student_address='.@getSession('edit-student_address').',
	student_city='.@getSession('edit-student_city').',
	student_zip='.@getSession('edit-student_zip').',
	student_phone='.@getSession('edit-student_phone').',
	emergency_name='.@getSession('edit-emergency_name').',
	emergency_phone='.@getSession('edit-emergency_phone').'
WHERE
	students.studentID ='.@getSession('edit-studentID');
}

$gaurdian = false;

if ($edit_type_str == 'gaurdian') {
	$gaurdian = true;
	$gaurdianID=@getSession('edit-gaurdianID');
	$name=@getSession('edit-gaurdian_name');
	$address=@getSession('edit-gaurdian_address');
	$city=@getSession('edit-gaurdian_city');
	$zip=@getSession('edit-gaurdian_zip');
	$phone=@getSession('edit-gaurdian_phone');
	$phone2=@getSession('edit-gaurdian_altphone');

	$sql = 
'UPDATE slcballetadmin.gaurdians SET 
	gaurdian_name='.@getSession('edit-gaurdian_name').',
	gaurdian_address='.@getSession('edit-gaurdian_address').',
	gaurdian_city='.@getSession('edit-gaurdian_city').',
	gaurdian_zip='.@getSession('edit-gaurdian_zip').',
	gaurdian_phone='.@getSession('edit-gaurdian_phone').',
	gaurdian_altphone='.@getSession('edit-gaurdian_altphone').'
WHERE
	gaurdians.gaurdianID ='.@getSession('edit-gaurdianID');
}

if ($sql == 'ERROR') {
	printf('Error, could not determine a value to edit.');
	exit;
}

// user was inserted, alert them via email of their new password
$to = 'registration@slcballet.com';
$header = 'From: "SLC Ballet Admin" <registration@slcballet.com>';
$sub = 'SLC Ballet Registration Request for Edit ';
$msg =
'Dear Directors,

A user has requested that you make a change. Please make the following changes to database right away.

'.$sql.'

Have a nice day,
Automated Email';

if (@mail($to,$sub,$msg,$header))
{
	$success = true;
} else {
	$success = false;
}

?>

<div class="more-headline">
	<span class="more-headline-title"><?php if ($success) {echo 'Your Request Was Sent!'; } else { echo 'Failed to Send Update Request'; }?></span>
	<span class="more-headline-subbutton"><button class="continue">Continue</button></span>
	<span class="more-headline-sublnk"></span>
</div>
<?php if ($success) {?>
<div class="register-section">
	<div class="register-section-header">
	Updated <?=$edit_type_str?> information will be available within two business days.
	</div>
	<div>
		<div>
			<span class="register-label"> Name </span> <?=$name?>
		</div>
		<?php // gaurdian and adult 
		if ($gaurdian || $adult) {?>
		<div>
			<span class="register-label"> Mailing Address </span> <?=$address?>
		</div>
		<?php } else {// student only ?>
		<div>
			<span class="register-label"> Home Address </span> <?=$address?>
		</div>
		<?php } #end student only?>
		<div>
			<span class="register-label"> City and Zip </span> <?=$city?>, <?=$zip?>
		</div>
		<?php // (not adult) student only 
		if ($student && !$adult) {?>
		<div>
			<span class="register-label"> Emergency Contact Name </span> <?=$emergency_name?> 
		</div>
		<div>
			<span class="register-label"> Emergency Contact Phone </span> <?=$emergency_phone?>
		</div>
		<?php }
		 // gaurdian only
		 if ($gaurdian) { ?>
		<div>
	    	<span class="register-label"> Home Phone </span> <?=$phone?>
		</div>
		<div>
			<span class="register-label"> Email </span> <?=$phone2?>
		</div>
		<?php } // adult only
		if ($adult) { ?>
		<div>
			<span class="register-label"> Phone </span> <?=$phone?>
		</div>
	</div>
</div>
<?php }
} # end success
else {
?>
<div class="register-section">
	<div class="register-section-header">
		Error
	</div>
	<div>
		<span> Your request was not successful; please try again later.</span>
	</div>
</div>
<?php } # end failed?>
