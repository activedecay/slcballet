<?php 
if (!isset($SESSION_INCLUDED)) require 'session.php';
if (!isset($FUNCTIONS_INCLUDED)) require 'functions.php';

if (!getSession('logon')) {
	proceedTo('/register.php');
	exit;
}
require 'db_con.php';
require 'constants.php';

$realClassID = $mysqli->real_escape_string($_REQUEST['classID']);

$sql_class_level = "SELECT l.levelID,title,time,days_taught,season,year,datetime_start,datetime_end,sold_as_unit
	FROM classes c
		JOIN users u
			ON u.userID = c.teacherID
		JOIN levels l
			ON l.levelID = c.levelID
	WHERE c.classID = ".$realClassID;

$results = $mysqli->query($sql_class_level);
$class_row = mysqli_fetch_array($results);



// TODO BEGIN DUPLICATION
// find the gaurdians to create a select-option listbox.
$sql_gaurdians_from_user = 'SELECT DISTINCT g.gaurdianID, gaurdian_name
	FROM users u
		JOIN students s 
			ON u.userID = s.ownerID
		JOIN gaurdians g 
			ON g.gaurdianID = s.gaurdianID
	WHERE u.userID = '.getSession('userID');

// find students to create a select-option listbox.
$sql_students_from_user = 'SELECT DISTINCT s.studentID, student_name
	FROM users u
		JOIN students s
			ON u.userID = s.ownerID
	WHERE u.userID = '.getSession('userID').'
	ORDER BY s.student_name';

$results_gfromu = $mysqli->query($sql_gaurdians_from_user);
$results_sfromu = $mysqli->query($sql_students_from_user);

$select_tag = '<select>%opt</select>';
$option_tag = '<option value="%id">%name</option>';
$gaurdian_option_str = '';
$student_option_str = '';
$has_gaurdians = false;
$has_students = false;

while ($row_gaurdian = mysqli_fetch_array($results_gfromu)) {
	if (!$has_gaurdians) {
		$opt = str_replace("%id", '0', $option_tag);
		$gaurdian_option_str .= str_replace("%name", 'Create New', $opt);
		$has_gaurdians = true;
	}
	$opt = str_replace("%id", $row_gaurdian['gaurdianID'], $option_tag);
	$gaurdian_option_str .= str_replace("%name", $row_gaurdian['gaurdian_name'], $opt);
}
// final select option string for insert to html
$select_gaurdian_str = str_replace("%opt", $gaurdian_option_str, $select_tag);

while ($row_student = mysqli_fetch_array($results_sfromu)) {
	if (!$has_students) {
		$opt = str_replace("%id", '0', $option_tag);
		$student_option_str .= str_replace("%name", 'Create New', $opt);
		$has_students = true;
	}
	$opt = str_replace("%id", $row_student['studentID'], $option_tag);
	$student_option_str .= str_replace("%name", $row_student['student_name'], $opt);
}
$select_student_str = str_replace("%opt", $student_option_str, $select_tag);
// TODO END DUPLICATION



// create the days string
$time_str = explode(',', $class_row['time']);
$str_days = pprintDays($class_row['days_taught'], dayAndTimeArray($time_str));

// find the right value for checkbox_str
$chkbox_str ='';
$is_summer = $class_row['season']=='Summer';
$is_childrens_academy = $class_row['levelID']==$CHILDRENS_ACADEMY_ID;
$weeks_cnt = $is_childrens_academy ? "3 or 6" : "2, 3, 4 or 6";
$sold_as_unit = $class_row['sold_as_unit'] == 1;
$val = '';
$week = '';
$comma = '';
$dash = '';
if ($is_summer && !$sold_as_unit) {
	
	$chkbox = '<input type="checkbox" name="%v" value="%v" id="%v"><label for="%v">%week</label></input>';
	
	$start = strtotime($class_row['datetime_start']);
	$end = strtotime($class_row['datetime_end']);
	
	$i = 0;
	while ($end > $start) {
		// check for Canada Trip date
		if ($start == strtotime("Jun 25 12")) {
        // children aren't going to canada
        //if ($is_childrens_academy) {
            // post loop necessity
            $start = strtotime("+ 1 week", date($start));
            continue;
        //}
        // $val = "2012-06-25";
        // $week = "Canada Trip <a href='javascript:;' title='Registration and schedule on a separate form'>*Info</a>";
		} elseif ($start == strtotime("2012-07-02")) {
			// children aren't going to school over the holiday
			if ($is_childrens_academy) {
				// post loop necessity
				$start = strtotime("+ 1 week", date($start));
				continue;
			}
		}
		
		// post loop necessity
		$i++;
		if ($is_childrens_academy) {
			$val .= $dash . date('Y-m-d', $start);
			$week .= $comma . date('F j', $start) . " - " . date('j', strtotime("+ 5 days", date($start)));
			$comma = ($i == 2 ? ", and " : ", "); // TODO limits choices to 3 weeks
			$dash = ",";
			if ($i == 3) {
				$str_1 = str_replace('%v', $val, $chkbox);
				$chkbox_str .= str_replace('%week', $week, $str_1) . "<br/>";
				$i = 0;
				$comma = "";
				$val = '';
				$week = '';
				$dash = '';
			}
		} else {
			$val = date('Y-m-d', $start);
			$week = date('F j', $start) . " - " . date('j', strtotime("+ 5 days", date($start)));
			$str_1 = str_replace('%v', $val, $chkbox);
			$chkbox_str .= str_replace('%week', $week, $str_1) . "<br/>";
		}
		
		$start = strtotime("+ 1 week", date($start));
	}
} else {
	
	$days_taught = explode(',',$class_row['days_taught']);
	$arr_days = array ('Mo'=>'Monday','Tu'=>'Tuesday','We'=>'Wednesday','Th'=>'Thursday','Fr'=>'Friday','Sa'=>'Saturday','Su'=>'Sunday');
	$arr_vals = array ('Mo'=>'1','Tu'=>'2','We'=>'3','Th'=>'4','Fr'=>'5','Sa'=>'6','Su'=>'0');
	// create the days checkboxes
	$chkbox = '<input type="checkbox" name="%s" value="%v" id="%s"><label for="%s">%day</label></input>';
	foreach ($days_taught as $day) {
		$day = trim($day);
		$txt_day = $arr_days[$day];
		$txt_val = $arr_vals[$day];
		$str_1 = str_replace('%s', $day, $chkbox);
		$str_2 = str_replace('%v', $txt_val, $str_1);
		$chkbox_str .= str_replace('%day', $txt_day, $str_2);
	}
}


?>
<div class="more-headline">
	<span class="more-headline-title"><?= $class_row['title']; ?></span>
	<span class="more-headline-subbutton"><button class="continue">Continue</button></span>
	<span class="more-headline-sublnk">
		<a href="" class="headline-cancel" >
			<span></span>
			<span class="cancel txt">Cancel</span>
		</a>
	</span>
</div>

<div class="register-section">
	<div class="register-section-header">
	Class Information
	</div>
	<div class="register-section-content">
		<div class="register-paragraph">
	    	<div><?= $class_row['title'].' is held '.$str_days.'.';?></div>
	    	<?php if ($class_row['levelID']!=$ADULT_CLASS_ID) { ?>
	    		<?php if (!$is_summer || $sold_as_unit) {?>
	    	<div> Check the days your student will attend, then pick the starting date of their first class. </div>
	    		<?php } else {?>
	    	<div> Check the <?=$weeks_cnt?> weeks your student will attend. 
	    			<?php if (!$is_childrens_academy) {?>
	    				You can choose any week; they don't have to be consecutive. </div>
	    			<?php } else { ?>
	    				You can choose from the two options below; each is three consecutive weeks. </div>
	    			<?php } ?>
	    		<?php } // end if is_summer?>
    	</div>
    	<div class="register-infos">
			<span class="register-label">Choose</span>
	    	<div id="attending"><?=$chkbox_str?></div>
		</div>
			<?php if (!$is_summer || $sold_as_unit) {?>
    	<div class="register-infos" style="position:relative;">
			<span class="register-label">Start Date</span>
			<input name="start_datepicker" style="background:white;border:1px solid gray;" /><span class="register-calendar-start-date calendar-month"></span>
			<input name="start_date" id="alt_start" type="hidden"/>
			<div style="
			    position: absolute;
			    left: 0;
			    top: 0;
			    right: 16px;
			    bottom: 0;" class="blob-start-date blob" ></div>
		</div>
			<?php } ?>
		<?php } else echo '</div>'; # end if student, start date picker ?>
	</div>
</div>
<div class="register-section">
	<div class="register-section-header">
		<div class="txt_student_info">Student Information</div>
		<div id="pan_has_students" class="pan_has_students">
		
		<?php if ($has_students) {
			echo '<span class="txt_select_student">Select Student</span>'.$select_student_str;
		}?>
		
		</div>
	</div>
	<div id="student-infos" class="register-section-content">
		
		<?php if ($has_students) {?>
		
		<div id="coax_into_using_gaurdian_widget" class="register-paragraph">
			<div>
				Choose a student from the menu above.
				If this is a new student select "Create New" and proceed below.
			</div>
			<div class="ui-widget student-register-warning">
				<div class="register-notice-gaurdians ui-state-error ui-corner-all">
					<p><span class="ui-icon ui-icon-notice" style="float: left; margin-right: .3em;"></span>
					In order to maintain distinct records for each student, please do not enter information for the same student twice.</p>
				</div>
			</div>
			<div class="ui-widget student-register-okay" style="display:none;">
				<div class="register-notice-gaurdians ui-state-highlight ui-corner-all">
					<p><span class="ui-icon ui-icon-check" style="float: left; margin-right: .3em;"></span>
					This student's information has already been recorded.</p>
				</div>
			</div>
		</div>
		
		<?php } # end if has students ?>
		
		<div class="register-infos">
			<span class="register-label">Name</span>
			<input name="student_name" type="text" maxlength="100" />
		</div>
		<div class="hide_for_stored_student">
			<?=($class_row['levelID']!=$ADULT_CLASS_ID) ?'
			<div class="register-infos" style="position:relative;">
				<span class="register-label">Date of Birth</span>
				<input name="student_datepicker" style="background:white;border:1px solid gray;" /><span class="register-calendar-dob calendar-month"></span>
				<input name="student_dob" id="alt_dob" type="hidden"/>
				<div style="
				    position: absolute;
				    left: 0;
				    top: 0;
				    right: 16px;
				    bottom: 0;" class="blob-dob blob" />
			</div>
			':'';?>
			<div class="register-infos">
				<?=($class_row['levelID']!=$ADULT_CLASS_ID) ?'
				<span class="register-label">Mailing Address</span>
				<input name="student_address" type="text" maxlength="180" />
				':'
				<span class="register-label">Home Address</span>
				<input name="student_address" type="text" maxlength="180" />';?>
			</div>
			<div class="register-infos">
				<span class="register-label">City</span>
				<input name="student_city" type="text" maxlength="80" />
				<!-- <span class="register-label state">State</span>
				<select name="student_state">
				  <option value="Alabama">Alabama</option>
				  <option value="Alaska">Alaska</option>
				  <option value="Arizona">Arizona</option>
				  <option value="Arkansas">Arkansas</option>
				  <option value="California">California</option>
				  <option value="Colorado">Colorado</option>
				  <option value="Connecticut">Connecticut</option>
				  <option value="Delaware">Delaware</option>
				  <option value="Florida">Florida</option>
				  <option value="Georgia">Georgia</option>
				  <option value="Hawaii">Hawaii</option>
				  <option value="Idaho">Idaho</option>
				  <option value="Illinois">Illinois</option>
				  <option value="Indiana">Indiana</option>
				  <option value="Iowa">Iowa</option>
				  <option value="Kansas">Kansas</option>
				  <option value="Kentucky">Kentucky</option>
				  <option value="Louisiana">Louisiana</option>
				  <option value="Maine">Maine</option>
				  <option value="Maryland">Maryland</option>
				  <option value="Massachusetts">Massachusetts</option>
				  <option value="Michigan">Michigan</option>
				  <option value="Minnesota">Minnesota</option>
				  <option value="Mississippi">Mississippi</option>
				  <option value="Missouri">Missouri</option>
				  <option value="Montana">Montana</option>
				  <option value="Nebraska">Nebraska</option>
				  <option value="Nevada">Nevada</option>
				  <option value="New Hampshire">New Hampshire</option>
				  <option value="New Jersey">New Jersey</option>
				  <option value="New Mexico">New Mexico</option>
				  <option value="New York">New York</option>
				  <option value="North Carolina">North Carolina</option>
				  <option value="North Dakota">North Dakota</option>
				  <option value="Ohio">Ohio</option>
				  <option value="Oklahoma">Oklahoma</option>
				  <option value="Oregon">Oregon</option>
				  <option value="Pennsylvania">Pennsylvania</option>
				  <option value="Rhode Island">Rhode Island</option>
				  <option value="South Carolina">South Carolina</option>
				  <option value="South Dakota">South Dakota</option>
				  <option value="Tennessee">Tennessee</option>
				  <option value="Texas">Texas</option>
				  <option value="Utah">Utah</option>
				  <option value="Vermont">Vermont</option>
				  <option value="Virginia ">Virginia </option>
				  <option value="Washington">Washington</option>
				  <option value="West Virginia">West Virginia</option>
				  <option value="Wisconsin">Wisconsin</option>
				  <option value="Wyoming ">Wyoming </option>
				</select> -->
			</div>
			<div class="register-infos register-infos-short">
				<span class="register-label">Zip</span>
				<input name="student_zip" type="text" maxlength="20" />
			</div>
			<?=($class_row['levelID']!=$ADULT_CLASS_ID) ?
			'' :
			'<div class="register-infos register-infos-short">
				<span class="register-label">Phone</span>
				<input name="student_phone" type="text" maxlength="50" />
			</div>';?>
		</div>
	</div>
</div>

<?php if ($class_row['levelID']!=$ADULT_CLASS_ID) {
	echo '
<div class="register-section">
	<div class="register-section-header">
		<div class="txt_gaurdian_info">Guardian Information</div>
		<div id="pan_has_gaurdians" class="pan_has_gaurdians">';
	
	if ($has_gaurdians) {
		echo '<span class="txt_select_gaurdian">Select Gaurdian</span>'.$select_gaurdian_str;
	}
	
	echo '</div>
	</div>
	<div id="gaurdian-infos" class="register-section-content">';
	
	if ($has_gaurdians) {
		echo '
		<div id="coax_into_using_gaurdian_widget" class="register-paragraph">
			<div>
				Choose the guardian from the menu above. 
				If this is a new guardian select "Create New" and proceed below. 
			</div>
			<div class="ui-widget gaurdian-register-warning">
				<div class="register-notice-gaurdians ui-state-error ui-corner-all"> 
					<p><span class="ui-icon ui-icon-notice" style="float: left; margin-right: .3em;"></span>
					In order to maintain distinct records for each guardian, please do not enter information for the same guardian twice.</p>
				</div>
			</div>
			<div class="ui-widget gaurdian-register-okay" style="display:none;">
				<div class="register-notice-gaurdians ui-state-highlight ui-corner-all"> 
					<p><span class="ui-icon ui-icon-check" style="float: left; margin-right: .3em;"></span>
					This guardian\'s information has already been recorded.</p>
				</div>
			</div>
		</div>';
	}
	
	echo '
		<div class="register-infos">
			<span class="register-label">Name</span>
			<input name="gaurdian_name" type="text" maxlength="100" />
		</div>
		<a href="" id="click_here_same">Click here if same as Student\'s.</a>
		
		<div id="hide_for_stored_gaurdian">
			<div class="register-infos">
				<span class="register-label">Mailing Address</span>
				<input name="gaurdian_address" type="text" maxlength="180" />
			</div>
			<div class="register-infos">
				<span class="register-label">City</span>
				<input name="gaurdian_city" type="text" maxlength="80" />
				<!-- <span class="register-label state">State</span>
				<select name="gaurdian_state">
				  <option value="Alabama">Alabama</option>
				  <option value="Alaska">Alaska</option>
				  <option value="Arizona">Arizona</option>
				  <option value="Arkansas">Arkansas</option>
				  <option value="California">California</option>
				  <option value="Colorado">Colorado</option>
				  <option value="Connecticut">Connecticut</option>
				  <option value="Delaware">Delaware</option>
				  <option value="Florida">Florida</option>
				  <option value="Georgia">Georgia</option>
				  <option value="Hawaii">Hawaii</option>
				  <option value="Idaho">Idaho</option>
				  <option value="Illinois">Illinois</option>
				  <option value="Indiana">Indiana</option>
				  <option value="Iowa">Iowa</option>
				  <option value="Kansas">Kansas</option>
				  <option value="Kentucky">Kentucky</option>
				  <option value="Louisiana">Louisiana</option>
				  <option value="Maine">Maine</option>
				  <option value="Maryland">Maryland</option>
				  <option value="Massachusetts">Massachusetts</option>
				  <option value="Michigan">Michigan</option>
				  <option value="Minnesota">Minnesota</option>
				  <option value="Mississippi">Mississippi</option>
				  <option value="Missouri">Missouri</option>
				  <option value="Montana">Montana</option>
				  <option value="Nebraska">Nebraska</option>
				  <option value="Nevada">Nevada</option>
				  <option value="New Hampshire">New Hampshire</option>
				  <option value="New Jersey">New Jersey</option>
				  <option value="New Mexico">New Mexico</option>
				  <option value="New York">New York</option>
				  <option value="North Carolina">North Carolina</option>
				  <option value="North Dakota">North Dakota</option>
				  <option value="Ohio">Ohio</option>
				  <option value="Oklahoma">Oklahoma</option>
				  <option value="Oregon">Oregon</option>
				  <option value="Pennsylvania">Pennsylvania</option>
				  <option value="Rhode Island">Rhode Island</option>
				  <option value="South Carolina">South Carolina</option>
				  <option value="South Dakota">South Dakota</option>
				  <option value="Tennessee">Tennessee</option>
				  <option value="Texas">Texas</option>
				  <option value="Utah">Utah</option>
				  <option value="Vermont">Vermont</option>
				  <option value="Virginia ">Virginia </option>
				  <option value="Washington">Washington</option>
				  <option value="West Virginia">West Virginia</option>
				  <option value="Wisconsin">Wisconsin</option>
				  <option value="Wyoming ">Wyoming </option>
				</select> -->
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
				<input name="gaurdian_altphone" type="text" maxlength="180" />
			</div>
		</div>
	</div>
</div>

<div class="hide_for_stored_student">
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
</div>';
}?>

<div class="more-headline">
	<span class="more-headline-title"><?= $class_row['title']; ?></span>
	<span class="more-headline-subbutton"><button class="continue">Continue</button></span>
	<span class="more-headline-sublnk">
		<a href="" class="headline-cancel" >
			<span></span>
			<span class="cancel txt">Cancel</span>
		</a>
	</span>
</div>


<script>
var register_now_level_id = <?= $class_row['levelID']?>;
var register_now_season = "<?= $class_row['season']?>";
var register_now_sold_as_unit = "<?= $class_row['sold_as_unit']==1?>";
if (!<?=$has_gaurdians? 'true': 'false'?>) {
	$("[name=gaurdian_name]").val('<?=@getSession('gaurdian_name')?>');
	$("[name=gaurdian_address]").val('<?=@getSession('gaurdian_address')?>');
	$("[name=gaurdian_city]").val('<?=@getSession('gaurdian_city')?>');
	$("[name=gaurdian_zip]").val('<?=@getSession('gaurdian_zip')?>');
	$("[name=gaurdian_phone]").val('<?=@getSession('gaurdian_phone')?>');
	$("[name=gaurdian_altphone]").val('<?=@getSession('gaurdian_altphone')?>');
} else {
	// select the option from the session, and call the function to update the view
	$('#pan_has_gaurdians select option[value=<?=@getSession('gaurdianID')?>]').attr('selected',true).each(gaurdianFilterChanged);
}

if (!<?=$has_students? 'true': 'false'?>) {
	$("[name=student_name]").val('<?=@getSession('student_name')?>');
	$("[name=student_dob]").val('<?=@getSession('student_dob')?>');
	$("[name=student_datepicker]").val('<?=@getSession('student_dob')?>');
	$("[name=student_address]").val('<?=@getSession('student_address')?>');
	$("[name=student_city]").val('<?=@getSession('student_city')?>');
	$("[name=student_zip]").val('<?=@getSession('student_zip')?>');
	$("[name=student_phone]").val('<?=@getSession('student_phone')?>');
	$("[name=emergency_name]").val('<?=@getSession('emergency_name')?>');
	$("[name=emergency_phone]").val('<?=@getSession('emergency_phone')?>');
} else {
	// select the option from the session, and call the function to update the view
	$('#pan_has_students select option[value=<?=@getSession('studentID')?>]').attr('selected',true).each(studentFilterChanged);
}
	// never do this for start date because the day may not match up with what's chosen
	//$("[name=start_datepicker]").val('<?=@getSession('start_date')?>');
	//$("[name=start_date]").val('<?=@getSession('start_date')?>');
	
	var split = "<?=@getSession('session_days')?>".split(",");
	for (s in split) {
		$('input[name='+split[s].replace(/^\s*|\s*$/g, "")+']').attr("checked",true);// javascript trim
	}
</script>