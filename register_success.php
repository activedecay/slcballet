<?php 
if (!isset($SESSION_INCLUDED)) require 'session.php';
if (!isset($FUNCTIONS_INCLUDED)) require 'functions.php';

if (!getSession('logon')) {
	proceedTo('/register.php');
	exit;
}
require 'db_con.php';

$r_classid = @getSession('classID');

$oid = @getSession('userID');
$gid = 0;
$sid = 0;

$sql = "SELECT *
	FROM classes
		JOIN users
		JOIN levels
	WHERE classes.classID = ".$r_classid."
		AND users.userID = classes.teacherID
		AND levels.levelID = classes.levelID";

$results = $mysqli->query($sql);
$row = mysqli_fetch_array($results);

require 'constants.php';

$r_gid = @getSession('gaurdianID');
$r_sid = @getSession('studentID');
$r_gname = @getSession('gaurdian_name');
$r_gaddr = @getSession('gaurdian_address');
$r_gcity = @getSession('gaurdian_city');
$r_gzip = @getSession('gaurdian_zip');
$r_gphone = @getSession('gaurdian_phone');
$r_galtph = @getSession('gaurdian_altphone');
if (!$r_galtph) $r_galtph = "NULL";
$r_sname = @getSession('student_name');
$r_sdob = @getSession('student_dob');
$r_saddr = @getSession('student_address');
$r_scity = @getSession('student_city');
$r_szip = @getSession('student_zip');
$r_sphone = @getSession('student_phone');
$r_start = @getSession('start_date');
$r_ename = @getSession('emergency_name');
$r_ephone = @getSession('emergency_phone');
$r_session_count = @getSession('session_count');
$r_session_days = @getSession('session_days');

$time_str = explode(',', $row['time']);

$is_summer = $row['season'] == "Summer";
$is_sold_as_unit = $row['sold_as_unit'] == 1;
$is_adult_class = $row['levelID']==$ADULT_CLASS_ID;

if (!$is_adult_class) {
	if ($is_summer && !$is_sold_as_unit) {
		$str_days = pprintWeeks(getSession('session_days'), dayAndTimeArray($time_str));
	} else {
		$str_days = pprintDays(getSession('session_days'), dayAndTimeArray($time_str));
	}
	// students do not have a phone number
	$r_sphone = "NULL";
	
	if ($is_summer && !$is_sold_as_unit) {
		$r_start = "NULL";
	}
} else {
	$str_days = pprintDays($row['days_taught'], dayAndTimeArray($time_str));
	// adults have no start date, emergency stuff
	$r_start = "NULL";
	$r_sdob = '0000-00-00';
	$r_ename = "NULL";
	$r_ephone = "NULL";
	$r_session_count = 0;
	$r_session_days = '';
}

// unless the session is hacked, these should be escaped values. TODO is it safe to escape values twice?
if ($r_gid == 0 && ($r_gname && $r_gaddr && $r_gcity && $r_gzip && $r_gphone && $r_galtph)
		&& $stmt_g = $mysqli->prepare('INSERT INTO gaurdians (
			gaurdianID ,
			gaurdian_name ,
			gaurdian_address ,
			gaurdian_city ,
			gaurdian_zip ,
			gaurdian_phone ,
			gaurdian_altphone
			)
		VALUES (
			NULL,  ?,  ?,  ?,  ?,  ?,  ?
		)')){
	
	$stmt_g->bind_param("ssssss", $r_gname, $r_gaddr, $r_gcity, $r_gzip, $r_gphone, $r_galtph);
	$stmt_g->execute();
	$gid = $mysqli->insert_id;
	#printf("%s rows affected. gid=%s ", $stmt_g->affected_rows, $gid);
	$stmt_g->close();
} elseif ($r_gid != 0) {
	$gid = $r_gid;
} elseif ($row['levelID'] == $ADULT_CLASS_ID) {
	$gid = 'NULL';
} else {
	printf("Failed to insert guardian. ");
	exit;
}

if ($r_sid == 0 && ($gid && $oid && $r_sname && $r_sdob && $r_saddr && $r_scity && $r_szip && $r_sphone && $r_ename && $r_ephone)){
	if ($is_adult_class) {
		if ($stmt_s = $mysqli->prepare('INSERT INTO students (
												studentID,
												gaurdianID,
												ownerID,
												student_name,
												student_dob,
												student_address,
												student_city,
												student_zip,
												student_phone,
												emergency_name,
												emergency_phone
												)
											VALUES (
												NULL,NULL,?,?,NULL,?,?,?,?,NULL,NULL
											)')) {
			// doit;
			$stmt_s->bind_param("dsssss", $oid, $r_sname, $r_saddr, $r_scity, $r_szip, $r_sphone);
		}
	} else {
		if ($stmt_s = $mysqli->prepare('INSERT INTO students (
										studentID,
										gaurdianID,
										ownerID,
										student_name,
										student_dob,
										student_address,
										student_city,
										student_zip,
										student_phone,
										emergency_name,
										emergency_phone
										)
									VALUES (
										NULL,?,?,?,?,?,?,?,?,?,?
									)')) {
			// doit;
			$stmt_s->bind_param("ddssssssss", $gid, $oid, $r_sname, $r_sdob, $r_saddr, $r_scity, $r_szip, $r_sphone, $r_ename, $r_ephone);
		}
	}
	
	$stmt_s->execute();
	$sid = $mysqli->insert_id;
	#printf("%s rows affected. ", $stmt_s->affected_rows);
	$stmt_s->close();
} elseif ($r_sid != 0) {
	$sid = $r_sid;
} else {
	if (!$gid){
		printf("No guardian id. ");
	}
	printf("Failed to insert student. ");
	exit;
}

if (($sid && $r_classid && 
		($is_adult_class || ($r_session_count && $r_session_days && $r_start))) 
		&& $stmt_e = $mysqli->prepare('INSERT INTO enrollments (
			enrollmentID,
			studentID,
			classID,
			session_count,
			session_days,
			start_date,
			discountID,
			paid
			)
		VALUES (
			NULL,?,?,?,?,?,NULL,0
		)')){

	$stmt_e->bind_param("dddss", $sid, $r_classid, $r_session_count, $r_session_days, $r_start);
	$stmt_e->execute();
	$eid = $mysqli->insert_id;
	#printf("%s rows affected.\n", $stmt_e->affected_rows);
	$stmt_e->close();
} else {
	if (!$sid){
		printf("No student id. ");
	}
	if (!$r_classid){
		printf("No class id. ");
	}
	if (!$r_session_count) {
		printf("No session count.");
	}
	if (!$r_session_days) {
		printf("No session days.");
	}
	if (!$r_start) {
		printf("No start date.");
	}
	printf("Failed to insert enrollment. ");
	exit;
}

?>

<div class="more-headline">
	<span class="more-headline-title">Congratulations!</span>
	<span class="more-headline-subbutton"><button class="continue">Continue</button></span>
	<span class="more-headline-sublnk"></span>
</div>
<div class="register-section">
	<div class="register-section-header">
	Class Information
	</div>
	<div class="register-section-content">
		<div class="register-paragraph">
	    	<div><?=$r_sname.' has been registered to take '. $row['title'].' on '.$str_days.'.';?></div>
	    	<div><?=$is_adult_class || ($is_summer && !$is_sold_as_unit) ? '' :
					'Their first day of class is scheduled for <span class="first-day-scheduled">'.$r_start.'</span>';?>
			</div>
		</div>
	</div>
</div>
<div class="register-section">
	<div class="register-section-header">
	Payment
	</div>
	<div class="register-section-content">
		<div class="register-paragraph">
	    	To arrange for payment, take your <span class="conf_number">confirmation number (<?=$eid?>)</span> to the Salt Lake City Ballet at your earliest convenience.
	    	<br/>
	    	<br/>
	    	Thank you,
	    	<br/>
	    	<br/>
	    	Terry &amp; Hillary
		</div>
	</div>
</div>