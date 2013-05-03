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

/*
echo $row['user_name']."<br/>";#teacher
echo $row['level_name']."<br/>";
echo $row['levelID'];
echo $row['title']."<br/>";
echo $row['subtitle']."<br/>";
echo $row['description']."<br/>";
echo $row['datetime_start']."<br/>";
echo $row['datetime_end']."<br/>";
echo $row['time']."<br/>";
echo $row['days_taught']."<br/>";
echo $row['season']."<br/>";
echo $row['year']."<br/>";
echo $row['price_cents']/100;
*/
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
	setSession($realkey, $realval);
	#echo ($realkey .'=>'. $realval .'<br/>');
}

$sql = "SELECT *
	FROM classes
		JOIN users
		JOIN levels
	WHERE classes.classID = ".getSession('classID')."
		AND users.userID = classes.teacherID
		AND levels.levelID = classes.levelID";

$results = $mysqli->query($sql);
$row_class = mysqli_fetch_array($results);

$time_str = explode(',', $row_class['time']);


$is_summer = $row_class['season'] == "Summer";
$is_sold_as_unit = $row_class['sold_as_unit'] == 1;
$is_adult_class = $row_class['levelID']==$ADULT_CLASS_ID;

if (!$is_adult_class) {
	if ($is_summer && !$is_sold_as_unit) {
		$str_days = pprintWeeks(getSession('session_days'), dayAndTimeArray($time_str));
	} else {
		$str_days = pprintDays(getSession('session_days'), dayAndTimeArray($time_str));
	}
} else {
	$str_days = pprintDays($row_class['days_taught'], dayAndTimeArray($time_str));
}

// the user selected a gaurdian from the select box before submitting
if (getSession('gaurdianID') != 0) {
	$sql_g = 'SELECT * 
		FROM gaurdians
		WHERE gaurdianID = '.getSession('gaurdianID');
	
	$results_g = $mysqli->query($sql_g);
	$row_g = mysqli_fetch_array($results_g);
	
	setSession('gaurdian_name',$row_g['gaurdian_name']);
	setSession('gaurdian_address',$row_g['gaurdian_address']);
	setSession('gaurdian_city',$row_g['gaurdian_city']);
	setSession('gaurdian_zip',$row_g['gaurdian_zip']);
	setSession('gaurdian_phone',$row_g['gaurdian_phone']);
	setSession('gaurdian_altphone',$row_g['gaurdian_altphone']);
}

// the user selected a student from the select box before submitting
if (getSession('studentID') != 0) {
	$sql_s = 'SELECT * 
		FROM students
		WHERE studentID = '.getSession('studentID');
	
	$results_s = $mysqli->query($sql_s);
	$row_s = mysqli_fetch_array($results_s);
	
	setSession('student_name',$row_s['student_name']);
	setSession('student_address',$row_s['student_address']);
	setSession('student_city',$row_s['student_city']);
	setSession('student_zip',$row_s['student_zip']);
	setSession('student_phone',$row_s['student_phone']);
	setSession('student_dob',$row_s['student_dob']);
	setSession('emergency_name',$row_s['emergency_name']);
	setSession('emergency_phone',$row_s['emergency_phone']);
}
?>

<div class="more-headline">
	<span class="more-headline-title">Confirm your registration details</span>
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
	    	<div>
	    		<?='<span class="overview-value"> '.getSession('student_name').' </span> will take '. $row_class['title'].' on 
					<span class="overview-value"> '.$str_days.'</span>.';?></div>
	    	<div>
	    		<?=!$is_adult_class && (!$is_summer || $is_sold_as_unit)?'Their first day of class is scheduled
	    			<span class="overview-value"> <span class="first-day-scheduled"> '.getSession('start_date').' </span> </span>':'';?>
    		</div>
    	</div>
	</div>
</div>
<div class="register-section">
	<div class="register-section-header">
	Student Information
	</div>
	<div class="register-section-content">
		<div>
			<span class="register-label">Name</span> 
			<span class="overview-value"> <?= getSession('student_name');?>
			<?php if (!$is_adult_class) {
					echo '</span> <span>born</span> <span class="overview-value"> '.getSession('student_dob');
			}?>
			</span>
		</div>
    	<div>
    		<span class="register-label">Address</span> 
    		<span class="overview-value"> <?= getSession('student_address');?> </span>
    	</div>
    	<div>
    		<span class="register-label">City and Zip</span> 
    		<span class="overview-value"> <?= getSession('student_city').", ".getSession('student_zip');?> </span>
    	</div>
    	<?php if ($row_class['levelID']==$ADULT_CLASS_ID) {
			echo '
		<div>
			<span class="register-label">Phone</span> 
			<span class="overview-value"> '.getSession('student_phone').' </span>
		</div>
			';
		}?>
	</div>
</div>
<?php 
	if (!$is_adult_class) {
		echo '
<div class="register-section">
	<div class="register-section-header">
	Guardian Information
	</div>
	<div class="register-section-content">
		<div>
			<span class="register-label">Name</span> 
			<span class="overview-value"> '.getSession('gaurdian_name').' </span> 
		</div>
    	<div>
    		<span class="register-label">Address</span> 
    		<span class="overview-value"> '.getSession('gaurdian_address').' </span>
    	</div>
    	<div>
    		<span class="register-label">City and Zip</span> 
    		<span class="overview-value"> '.getSession('gaurdian_city').", ".getSession('gaurdian_zip').' </span>
    	</div>
    	<div>
    		<span class="register-label">Home Phone</span> 
    		<span class="overview-value"> '.getSession('gaurdian_phone').' </span>
    	</div>
    	<div>
    		<span class="register-label">Email</span> 
    		<span class="overview-value"> '.getSession('gaurdian_altphone').' </span>
    	</div>
	</div>
</div>
<div class="register-section">
	<div class="register-section-header">
	Emergency Contact Information
	</div>
	<div class="register-section-content">
		<div>
			<span class="register-label">Name</span> 
			<span class="overview-value"> '.getSession('emergency_name').' </span> 
		</div>
    	<div>
    		<span class="register-label">Phone</span> 
    		<span class="overview-value"> '.getSession('emergency_phone').' </span>
    	</div>
	</div>
</div>
		';
	}
?>
<div class="register-section">
	<div class="register-section-header">
		Policies and Agreements
	</div>
	<div class="register-section-content" id="agreements">
<?php 
	if (!$is_adult_class) {
		echo '
		<div>
			<input type="checkbox" id="chk_tp"/> 
			<label for="chk_tp">I certify that I have read, understand and agree to the contents of the <a href="doc/tuition-policy.pdf" target="_blank"> Tuition Policy document. </a> </label>
		</div>
		<div>
			<input type="checkbox" id="chk_cet"/> 
			<label for="chk_cet">I certify that I have read, understand and agree to the contents of the <a href="doc/emergency-consent.pdf" target="_blank"> Consent to Emergency Treatment document. </a> </label>
		</div>
		';
	}
?>
		<div>
			<input type="checkbox" id="chk_ir"/> 
			<label for="chk_ir">I certify that I have read, understand and agree to the contents of the <a href="doc/indemnification.pdf" target="_blank"> Indemnification and Release document. </a> </label>
		</div>
	</div>
</div>

<script>
var totalCheckedRequired = <?=$row_class['levelID']==$ADULT_CLASS_ID?'1':'3';?>;
</script>
 <div class="more-headline">
	<span class="more-headline-title">Confirm your registration details</span>
	<span class="more-headline-subbutton"><button class="continue">Continue</button></span>
	<span class="more-headline-sublnk">
		<a href="" class="headline-cancel" >
			<span></span>
			<span class="cancel txt">Cancel</span>
		</a>
	</span>
</div>
