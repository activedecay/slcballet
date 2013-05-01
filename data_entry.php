<?php
if (!isset($SESSION_INCLUDED)) require 'session.php';
if (!isset($FUNCTIONS_INCLUDED)) require 'functions.php';
if (!getSession('logon') || !(getSession('roleID') >= 2)) {
	proceedTo('/register.php');
	exit;
}
require 'db_con.php';

$msgs = array();

if (@$_REQUEST['post']=='payments') {
	$r_eid = @$_REQUEST['enrollmentID'];
	$r_amt = @$_REQUEST['amount_cents'];
	$r_dt = trim(@$_REQUEST['datetime']).':00';
	# requesting to submit a payment
	$stmt = $mysqli->prepare('INSERT INTO paylog (
		paymentID ,
		enrollmentID ,
		amount_cents ,
		datetime
		)
		VALUES (
		NULL, ?,  ?,  ?
		)');

	if (!($r_eid && $r_amt && $r_dt)) {
		$msgs['error'] = "Not enough data";
	} else if (!$stmt) {
		$msgs['error'] = "Bad query";
	} else {
		$stmt->bind_param("dds", $r_eid, $r_amt, $r_dt);
		$stmt->execute();
		$id = $mysqli->insert_id;
		if (!$id) {
			$msgs['error'] = "There was an error. Bad input? Try again.";
		}
		else {
			$msgs['success'] = "Success! ".$id;
		}
		$stmt->close();
	}
	
	echo json_encode($msgs);
	exit;
} else if (@$_REQUEST['post']=='directortime') {
	$r_uid = @$_REQUEST['userID'];
	$r_tid = @$_REQUEST['timetypeID'];
	$r_date = @$_REQUEST['date'];
	$r_dhours = @$_REQUEST['duration_hours'];
	$r_notes = @$_REQUEST['notes'];

	# requesting to submit a payment
	$stmt = $mysqli->prepare('INSERT INTO directortime (
		timelogID,
		directorID,
		type,
		date,
		duration_hours,
		notes
		)
		VALUES (
		NULL, ?,?,?,?,?
		)');
	
	if (!(/*$r_notes && */$r_dhours && $r_date && $r_tid && $r_uid)) {
		$msgs['error'] = "Not enough data";
	} else if (!$stmt) {
		$msgs['error'] = "Bad query";
	} else {
		$stmt->bind_param("ddsds", $r_uid, $r_tid, $r_date, $r_dhours, $r_notes);
		$stmt->execute();
		$id = $mysqli->insert_id;
		if (!$id) {
			$msgs['error'] = "There was an error. Bad input? Try again.";
		}
		else {
			$msgs['success'] = "Success! ".$id;
		}
		$stmt->close();
	}
	
	echo json_encode($msgs);
	exit;
} else if (@$_REQUEST['post']=='attendance') {
	
	$studentIDs = explode(',', @$_REQUEST['studentIDs']);
	$classID = @$_REQUEST['classID'];
	$sessionID = @$_REQUEST['sessionID'];

	# requesting to submit a payment
	$stmt = $mysqli->prepare('INSERT INTO attendance (
		attendanceID,
		studentID,
		classID,
		sessionID
		)
		VALUES (
		NULL, ?,?,?
		)');
	
	if (!(count($studentIDs) && $classID && $sessionID)) {
		$msgs['error'] = "Not enough data";
	} else if (!$stmt) {
		$msgs['error'] = "Bad query";
	} else {
		$all_ids = '';
		$comma = '';
		foreach ($studentIDs as $studentID) {
			$stmt->bind_param("ddd", $studentID, $classID, $sessionID);
			$stmt->execute();
			$id = $mysqli->insert_id;
			if (!$id) {
				$msgs['error'] = 'There was an error. Bad input? Try again.';
				break;
			}
			else {
				$all_ids .= $comma.$id;
				if ($comma == '') $comma = ', ';
			}
		}
		$msgs['success'] = 'Success! ' . $all_ids;
		$stmt->close();
	}
	
	echo json_encode($msgs);
	exit;
} else if (@$_REQUEST['post']=='studiosessions') {

	$userID = @$_REQUEST['userID'];
	$studioID = @$_REQUEST['studioID'];
	$datetime = trim(@$_REQUEST['datetime']).':00';
	$duration_hours = @$_REQUEST['duration_hours'];

	# requesting to submit a payment
	$stmt = $mysqli->prepare('INSERT INTO studiosessions (
		sessionID,
		userID,
		studioID,
		datetime,
		duration_hours
		)
		VALUES (
			NULL, ?,?,?,?
		)');
	
	if (!($userID && $studioID && $datetime && $duration_hours)) {
		$msgs['error'] = "Not enough data";
	} else if (!$stmt) {
		$msgs['error'] = "Bad query";
	} else {
		$stmt->bind_param("ddsd", $userID, $studioID, $datetime, $duration_hours);
		$stmt->execute();
		$id = $mysqli->insert_id;
		if (!$id) {
			$msgs['error'] = "There was an error. Bad input? Try again.";
		}
		else {
			$msgs['success'] = "Success! ".$id;
		}
		$stmt->close();
	}
	
	echo json_encode($msgs);
	exit;
}
?>

<html>
<head><title>Data Entry</title>

<link rel="stylesheet" type="text/css" href="css/reset.css" />
<link rel="stylesheet" type="text/css" href="css/site.css" />
<link rel="stylesheet" type="text/css" href="css/main.css" />
<link rel="stylesheet" type="text/css" href="css/slcb-theme/jquery-ui-1.8.16.custom.css" />
<script type="text/javascript" src="js/jquery-1.6.2.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.16.custom.min.js"></script>
<script type="text/javascript" src="js/jquery.md5.js"></script>
<script type="text/javascript" src="js/validate.js"></script>
<script type="text/javascript" src="js/datetimepicker.js"></script>
<script type="text/javascript">
$(function(){
	$("#re-button").button().click(function(){location.assign("reports.php");}); 
	
	$('.de-entry-continer').addClass("ui-widget-content ui-corner-all");
	$('.reload').addClass('ui-widget');
	
	$('.de_attr').append("<span>:</span>");
	
	$("#attendance .dt").datepicker({dateFormat:"yy-mm-dd", 
			onClose:on_close_attendance_datepicker});
	$("#payments .dt").datetimepicker({dateFormat:"yy-mm-dd"});
	$("#directortime .dt").datepicker({dateFormat:"yy-mm-dd"});
	$("#studiosessions .dt").datetimepicker({dateFormat:"yy-mm-dd"});
	
	$("#payments [type=submit]").button().click(payments_submit);
	$("#directortime [type=submit]").button().click(directortime_submit);
	$("#attendance [type=submit]").button().click(attendance_submit);
	$("#studiosessions [type=submit]").button().click(studiosessions_submit);
	
	$(".errors").dialog().find("div").addClass("ui-state-error ui-corner-all");
	$(".infos").dialog();

	//$("#attendance .data-entry-chbx").click(function(){});
	$("#attendance select[name=classID]").change(classChanged);
	$("#attendance select[name=ssn]").change(hideOptions);
	$("#attendance .all-everybody-chbx").click(function(){	
		$("#attendance .student-chbx").attr("checked", $(this).attr("checked")=="checked");
	});
	$("#attendance .student-chbx").click(function(){
		var slctd_class = $(this).attr("classid");
		// count number checked and set everybody checkbox 
		$("#attendance .all-everybody-chbx").attr("checked", $("#attendance .cid-"+slctd_class+" [type=checkbox]:checked").length ==
				$("#attendance .cid-"+slctd_class+" [type=checkbox]").length);
	});
	$("#attendance .class-chbx-container").hide();
	
	//<span class="data-entry-chbx">type="checkbox" class="student-chbx"
	//<div class="class-chbx-container cid-%cid">%chks</div>
	//<div><input class="all-everybody-chbx" />%all</div>
});
function on_close_attendance_datepicker(d) {
	//$('select[name=sessionID] option').hide();
	$('select[name=sessionID] option').each(function(){
		
		myDate = Date.parse($($(this)[0]).attr("dateval"));
		//console.info($($(this)[0]).attr("dateval"));
		console.info(myDate);
		chosenDate = Date.parse(d);
		if (chosenDate >= myDate) {
			$($(this)[0]).remove();
		}
	});
}
// when a class changes, show the student checkboxes.
function classChanged(){
	// uncheck every damn thing
	$(".student-chbx").attr("checked", false);
	// hide everything
	$("#attendance .class-chbx-container").hide();
	$("#attendance .all-everybody-chbx").attr("checked", false);
	$(this).find(':selected').each(function(){
		var selctd_class = $(this).val();
		// show only one
		$("#attendance .cid-"+selctd_class).show();
	});
}

function studiosessions_submit() {
	$("#studiosessions [type=submit]").button('option','disabled',true);
	
	var req = {};// request query params string builder

	$('#studiosessions input[name]').each(
		function(){
			req[$(this).attr('name')] = $(this).val();
		}
	);
	$("#studiosessions option:selected").each(function(){
		req[$(this).closest('select').attr('name')] = $(this).val();
	});
	// what to load when the information is gathered from registration
	$.get("data_entry.php", req, loadStudiosessions, "json");
}

function loadStudiosessions(msgs) {
	$('#studiosessions .result').effect('highlight', 3000);
	if (msgs) {
		if (msgs.success) {
			$('#studiosessions .result').text(msgs.success).addClass('result-success');
		}
		if (msgs.error) {
			$('#studiosessions .result').text(msgs.error).addClass('result-error');
		}
	}
	$("#studiosessions [type=submit]").button('option','disabled',false);
}

function attendance_submit() {
	$("#attendance [type=submit]").button('option','disabled',true);
	
	var req = {};// request query params string builder

	//<span class="data-entry-chbx">type="checkbox" class="student-chbx"
	//<div class="class-chbx-container cid-%cid">%chks</div>
	//<div><input class="all-everybody-chbx" />%all</div>

	var slctd_class = $("#attendance select[name=classID] option:selected").val();
	comma = "";
	value_str = "";
	$('#attendance .cid-'+slctd_class+' input[id]:checked').each(
		// for all the inputs that have an id, and are on the selected class's panel 
		function () {
			value_str += comma + $(this).val();
			if (comma == "") comma = ",";
		}
	);
	req["studentIDs"] = value_str;

	$("#attendance option:selected").each(function(){
		if ($(this).closest('select').attr('name') !=  "ssn")
			req[$(this).closest('select').attr('name')] = $(this).val();
	});
	$('#attendance input[name]').each(
		function(){
			req[$(this).attr('name')] = $(this).val();
		}
	);
	// what to load when the information is gathered from registration
	$.get("data_entry.php", req, loadAttendance, "json");
}

function loadAttendance(msgs) {
	$('#attendance .result').effect('highlight', 3000);
	if (msgs) {
		if (msgs.success) {
			$('#attendance .result').text(msgs.success).addClass('result-success');
		}
		if (msgs.error) {
			$('#attendance .result').text(msgs.error).addClass('result-error');
		}
	}
	$("#attendance [type=submit]").button('option','disabled',false);
}

function directortime_submit() {
	$("#directortime [type=submit]").button('option','disabled',true);
	
	var req = {};// request query params string builder

	req['post'] = $('#directortime input[name=post]').val();
	req['date'] = $('#directortime input[name=date]').val();
	req['timetypeID'] = $('#directortime input[type=radio]:checked').val();
	req['duration_hours'] = $('#directortime input[name=duration_hours]').val();

	$("#directortime option:selected").each(function(){
		req[$(this).closest('select').attr('name')] = $(this).val();
	});
	$("#directortime textarea").each(function(){
		req[$(this).attr('name')] = $(this).val();
	});
	// what to load when the information is gathered from registration
	$.get("data_entry.php", req, loadDirectortime, "json");
}

function loadDirectortime(msgs) {
	$('#directortime .result').effect('highlight', 3000);
	if (msgs) {
		if (msgs.success) {
			$('#directortime .result').text(msgs.success).addClass('result-success');
		}
		if (msgs.error) {
			$('#directortime .result').text(msgs.error).addClass('result-error');
		}
	}
	$("#directortime [type=submit]").button('option','disabled',false);
}

function payments_submit() {
	$("#payments [type=submit]").button('option','disabled',true);
	
	var req = {};// request query params string builder

	$('#payments input[name]').each(
		function(){
			req[$(this).attr('name')] = $(this).val();
		}
	);
	$("#payments option:selected").each(function(){
		req[$(this).closest('select').attr('name')] = $(this).val();
	});
	// what to load when the information is gathered from registration
	$.get("data_entry.php", req, loadPayments, "json");
}

function loadPayments(msgs) {
	$('#payments .result').effect('highlight', 3000);
	if (msgs) {
		if (msgs.success) {
			$('#payments .result').text(msgs.success).addClass('result-success');
		}
		if (msgs.error) {
			$('#payments .result').text(msgs.error).addClass('result-error');
		}
	}
	$("#payments [type=submit]").button('option','disabled',false);
}

function hideOptions(){
	ssn = $(this).find("option:selected").val();
	//hide all classes for all seasons
	$('option[season]').hide();
	//show all classes of this season
	$('option[season='+ssn+']').show()
	
}
</script>



</head>
<body>
<table style="position:absolute;top:0;font-size:11px;">
	<tr>
		<td>12hr Time&nbsp;&nbsp;</td> <td>24hr Time</td>
	</tr>
	<?php for ($i=0;$i<12;$i++) echo '<tr><td>'.($i+1).':00PM</td>'.'<td>'.(($i+13)%24).':00</td></tr>';?>
</table>
<div style="text-align:center; margin: 30px;"><img src="../img/MastheadLogo-33.png"/> </div>
<div style="text-align:center; margin: 30px;"><span class="headline-txt" style="font-size:40px;">Data Entry</span></div>

<div id="re-button" style="position:absolute; top: 0; right: 0">Reports</div>

<div id="studiosessions" class="de-entry-continer">
	<h1 class="chopin report-heading">Studio Session</h1>
	<input type="hidden" name="post" value="studiosessions"/>
	
	<?php 
		$select_str = '<select name="userID"><option value="0">Select Teacher ...</option>%opt</select>';
		$option_tag = '<option value="%val">%opt</option>';
		$option_str = '';
		$sql = 'SELECT userID,user_name
			FROM users u 
			WHERE u.roleID>1';
		$results = $mysqli->query($sql);
		if ($results) {
			while ($row = mysqli_fetch_array($results)) {
				$str = $row['user_name'].' (id#'.$row['userID'].')';
				$option_str .= str_replace('%opt', $str, $option_tag);
				$option_str = str_replace('%val', $row['userID'], $option_str);
			}
		}
		$select_str = str_replace('%opt', $option_str, $select_str);
	?>
	<span class="de_attr">Teacher</span> <?=$select_str?> <br/>
	
	<?php 
		$select_str = '<select name="studioID"><option value="0">Select Studio ...</option>%opt</select>';
		$option_tag = '<option value="%val">%opt</option>';
		$option_str = '';
		$sql = 'SELECT studioID,name,description
			FROM studios';
		$results = $mysqli->query($sql);
		if ($results) {
			while ($row = mysqli_fetch_array($results)) {
				$str = $row['name'].' (id#'.$row['studioID'].') - '.$row['description'];
				$option_str .= str_replace('%opt', $str, $option_tag);
				$option_str = str_replace('%val', $row['studioID'], $option_str);
			}
		}
		$select_str = str_replace('%opt', $option_str, $select_str);
	?>
	<span class="de_attr">StudioID</span> <?=$select_str?> <br/>
	<span class="de_attr">Date and Time</span> <input name="datetime" class="dt"/> <br/>
	<span class="de_attr">Hours</span> <input name="duration_hours"/> example: 1.25 = 1hr 15min <br/>
	
	<div class="de-submit"><a href="" class="reload">Reload</a> <input type="submit"/></div> <span class="result"></span>
</div>
<div style="display:none;">Military time conversion cheat sheet:
	<div>T = 12-hr time; M = 24-hr time;</div>
	<div>T + 12 = M</div>
	<div>M - 12 = T -- for M &gt; 12</div>
	<div>*It may be helpful to <span style="font-weight:bold;">always</span> convert in one direction: take any T time and add 12 until you get the right M time.</div>
	<div>*12 is more difficult to add/subtract than 2, but relatively the same result is obtained by adding/subtracting 2.</div>
	<div>example:M=13 hours. 1 + 12 = 13 (T=1)</div>
</div>

<div id="attendance" class="de-entry-continer">
	<h1 class="chopin report-heading">Attendance</h1>
	<input type="hidden" name="post" value="attendance"/>
	<?php
		$select_str = '<select name="ssn"><option value="0">Select Season ...</option>%opt</select>';
		$option_tag = '<option value="%val">%opt</option>';
		$option_str = '';
		$sql = 'SELECT DISTINCT season,year
					FROM classes c';
		$results = $mysqli->query($sql);
		if ($results) {
			while ($row = mysqli_fetch_array($results)) {
				$str = $row['season'].$row['year'];
				$option_str .= str_replace('%opt', $str, $option_tag);
				$option_str = str_replace('%val', $str, $option_str);
			}
		}
		$select_str = str_replace('%opt', $option_str, $select_str);
	?>
	<span class="de_attr">Season</span> <?=$select_str?> <br/>
		
	<?php 
		$select_str = '<select name="classID"><option value="0">Select Class...</option>%opt</select>';
		$option_tag = '<option value="%val" season="%ssn">%opt</option>';
		$option_str = '';
		$sql = 'SELECT classID,title,l.level_name,season,year
			FROM classes c
			JOIN levels l
				ON c.levelID = l.levelID';
		$results = $mysqli->query($sql);
		if ($results) {
			while ($row = mysqli_fetch_array($results)) {
				$str = $row['classID'].' '.$row['title'].' '.$row['level_name'];
				$option_str .= str_replace('%opt', $str, $option_tag);
				$option_str = str_replace('%ssn', $row['season'].$row['year'], $option_str);
				$option_str = str_replace('%val', $row['classID'], $option_str);
			}
		}
		$select_str = str_replace('%opt', $option_str, $select_str);
	?>	
	<span class="de_attr">Class</span> <?=$select_str?> <br/>
	
	<?php 
		# for all classes, create a checkbox for each student
		$chbx_tag = '<span class="data-entry-chbx"><input classid="%cid" value="%sid" type="checkbox" id="%cid-s%sid" class="student-chbx"/> <label for="%cid-s%sid"> %txt </label> </span>';
		$chbx_continer = '<div class="class-chbx-container cid-%cid">%chks</div>'; # a place to put checkboxes
		$container_html = '<div style="padding-left: 240px;"><input id="all-everybody" type="checkbox" class="all-everybody-chbx"/> <label for="all-everybody">Select all </label> %all</div>';

		$sql = 'SELECT c.classID, s.studentID, student_name
			FROM classes c
			JOIN enrollments e ON c.classID=e.classID
			JOIN students s ON s.studentID=e.studentID
			ORDER BY c.classID';
		$results = $mysqli->query($sql);
		if ($results) {

			$classes = array(); # everything I ever cared about.
			
			# iter over all rows, for each student create a checkbox, if the class is different than last time, create a container
			while ($row = mysqli_fetch_array($results)) {

				$cid = $row['classID'];
				
				if (!@$classes[$cid]) {
					$classes[$cid] = array(); # students
				}
				
				$sid = $row['studentID'];
				$classes[$cid][$sid] = $row['student_name'];
				
				
				$last_cid = $cid; # store the last
			}
			
			# easy array math
			$containers_str = ''; # all the containers
			foreach ($classes as $clacid => $studs) {
				# each class gets a container
				$chbx_cont = str_replace('%cid', $clacid, $chbx_continer);
				
				#each student gets a checkbox
				$chbx_str = '';
				foreach ($studs as $stusid => $student_n) {
					$chbx_str .= str_replace('%sid', $stusid, $chbx_tag); # holds all students in this class
					$chbx_str = str_replace('%cid', $clacid, $chbx_str);
					$chbx_str = str_replace('%txt', $student_n, $chbx_str);
				}
				
				$chbx_cont = str_replace('%chks', $chbx_str, $chbx_cont);
				$containers_str .= $chbx_cont; 
			}
			
			$container_html = str_replace('%all', $containers_str, $container_html);
		}
	?>
	<span class="de_attr">Students</span> <?=$container_html?> <br/>

	<?php 
		$select_str = '<select name="sessionID"><option value="0">Select Session ...</option>%opt</select>';
		$option_tag = '<option value="%val" dateval="%dateval">%opt</option>';
		$option_str = '';
		$sql = 'SELECT user_name,sessionID,name,datetime
			FROM studiosessions s
			JOIN users u ON u.userID=s.userID
			JOIN studios sd ON sd.studioID=s.studioID';
		$results = $mysqli->query($sql);
		if ($results) {
			while ($row = mysqli_fetch_array($results)) {
				$result = strtotime($row['datetime']);
				$format = date('D, M j, Y H:i', $result);
				
				$str = $row['sessionID'].' '.$row['user_name'].'\'s '.$format.' '.' in '.$row['name'];
				$option_str .= str_replace('%opt', $str, $option_tag);
				$option_str = str_replace('%val', $row['sessionID'], $option_str);
				$option_str = str_replace('%dateval', $format, $option_str);
			}
		}
		$select_str = str_replace('%opt', $option_str, $select_str);
	?>
	<span class="de_attr">Session</span> <?=$select_str?> <br/>
	<span class="de_attr">Session Filter by Date</span> <input name="date" class="dt"/> <br/>
	
	<div class="de-submit"><a href="" class="reload">Reload</a> <input type="submit"/></div> <span class="result"></span>
	
</div>

<div id="directortime" class="de-entry-continer">
	<h1 class="chopin report-heading">Director Time</h1>
	<input type="hidden" name="post" value="directortime"/>
	
	<?php 
		$select_str = '<select name="userID"><option value="0">Select Director ...</option>%opt</select>';
		$option_tag = '<option value="%val">%opt</option>';
		$option_str = '';
		$sql = 'SELECT userID,user_name
			FROM users u 
			WHERE u.roleID=3';
		$results = $mysqli->query($sql);
		if ($results) {
			while ($row = mysqli_fetch_array($results)) {
				$str = $row['user_name'].' (id#'.$row['userID'].')';
				$option_str .= str_replace('%opt', $str, $option_tag);
				$option_str = str_replace('%val', $row['userID'], $option_str);
			}
		}
		$select_str = str_replace('%opt', $option_str, $select_str);
	?>
	<span class="de_attr">Director</span> <?=$select_str?> <br/>
	<span class="de_attr">Date</span> <input name="date" class="dt"/> <br/>
	
	<?php 
		$option_tag = '<input id="%val-%opt" type="radio" name="timetypeID" value="%val"><label for="%val-%opt">%opt</label><br/>';
		$option_str = '';
		$sql = 'SELECT timetypeID,name
			FROM directortimetype';
		$results = $mysqli->query($sql);
		if ($results) {
			while ($row = mysqli_fetch_array($results)) {
				$str = $row['name'].' (id#'.$row['timetypeID'].')';
				$option_str .= str_replace('%opt', $str, $option_tag);
				$option_str = str_replace('%val', $row['timetypeID'], $option_str);
			}
		}
	?>
	<span class="de_attr">Category</span> <div style="padding-left:240px;"> <?=$option_str?> </div> <br/>
	
	<span class="de_attr">Hours</span> <input name="duration_hours"/> example: 1.25 = 1hr 15min <br/>
	<span class="de_attr">Notes</span> <textarea name="notes" rows="10" cols="40" maxlength="666" style="vertical-align:top;" ></textarea> <br/>
	
	<div class="de-submit"><a href="" class="reload">Reload</a> <input type="submit"/></div> <span class="result"></span>
</div>

<div id="payments" class="de-entry-continer">
	<h1 class="chopin report-heading">Payments</h1>
	<input type="hidden" name="post" value="payments"/>

	<?php 
		$select_str = '<select name="enrollmentID"><option value="0">Select Enrollment ...</option>%opt</select>';
		$option_tag = '<option value="%val">%opt</option>';
		$option_str = '';
		$sql = 'SELECT e.enrollmentID,student_name,title
			FROM enrollments e 
			JOIN students s ON e.studentID=s.studentID
			JOIN classes c ON c.classID=e.classID
			order by enrollmentID';
		$results = $mysqli->query($sql);
		if ($results) {
			while ($row = mysqli_fetch_array($results)) {
				$str = $row['enrollmentID'].' '.$row['student_name'].' - '.$row['title'];
				$option_str .= str_replace('%opt', $str, $option_tag);
				$option_str = str_replace('%val', $row['enrollmentID'], $option_str);
			}
		}
		$select_str = str_replace('%opt', $option_str, $select_str);
	?>
	<span class="de_attr">Enrollment</span> <?=$select_str?> <br/>
	
	<span class="de_attr">Amount Cents</span> <input name="amount_cents"/>  example: 100 = $1.00 <br/>
	<span class="de_attr">Date and Time</span> <input name="datetime" class="dt"/> <br/>
	
	<div class="de-submit"><a href="" class="reload">Reload</a> <input type="submit"/></div> <span class="result"></span>
</div>


<div style="text-align:center; margin: 30px;"><img src="../img/MastheadLogo-33.png"/> </div>
<div style="text-align:center; margin: 30px;"><span class="headline-txt" style="font-size:40px;">Data Entry</span></div>

</body>
</html>
