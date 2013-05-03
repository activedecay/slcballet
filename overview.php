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
userID	6
user_name	test
pass	0a617c443ccd7d067124bebb0b7cc2cc650c1cd4d10a2bab9b...
email	admin@127.0.0.1
roleID	1
studentID	1
gaurdianID	3
ownerID	6
student_name	Test Student
student_dob	2011-11-01
student_address	123 Test Lane
student_city	Test City
student_zip	73571
student_phone	NULL
emergency_name	Test Emergency
emergency_phone	123 123 1236e
enrollmentID	1
studentID	1
classID	4
session_count	1
session_days	S
discountID	NULL
paid	0
classID	4
teacherID	2
levelID	1
title	Pre-Ballet
subtitle	If your child expresses love of movement and is ea...
description	Each forty-five minute class imparts the joy of mo...
datetime_start	2012-01-14
datetime_end	2012-05-25
time	10:30 AM
days_taught	S
season	Winter
year	2012
price_cents	10000
levelID	1
level_name	Pre-Ballet

		JOIN enrollments e
			ON s.studentID = e.studentID
		JOIN classes c
			ON c.classID = e.classID
		JOIN levels l
			ON l.levelID = c.levelID
	ORDER BY l.levelID ASC 
	
*/
if (!isset($SESSION_INCLUDED)) require 'session.php';
if (!isset($FUNCTIONS_INCLUDED)) require 'functions.php';
if (!getSession('logon')) {
	proceedTo('/register.php');
	exit;
}
require 'db_con.php';
require 'constants.php';

$sql_gaurdians_from_user = "SELECT DISTINCT g.gaurdianID
	FROM users u
		JOIN students s 
			ON u.userID = s.ownerID
		JOIN gaurdians g 
			ON g.gaurdianID = s.gaurdianID
	WHERE u.userID = ".getSession('userID');

$results_gfromu = $mysqli->query($sql_gaurdians_from_user);

?>
<div class="more-headline">
	<span class="more-headline-title">My Student Registrations</span>
	<span class="more-headline-subbutton"></span>
	<span class="more-headline-sublnk"></span>
</div>
<?php

// create a container for each gaurdian, inside it are more containers for students and classes
while ($row_gfromu = mysqli_fetch_assoc($results_gfromu))
{
	$gaurdian = $row_gfromu['gaurdianID'];
	
	// get the gaurdian's full info, and then SELECT DISTINCT student ids
	$sql_g = "SELECT *
		FROM gaurdians
		WHERE gaurdianID=".$gaurdian;

	$row_oneGaurdian = mysqli_fetch_assoc($mysqli->query($sql_g));

	echo '
<div class="portlet">
	<div class="portlet-header">
		'.$row_oneGaurdian['gaurdian_name'].'
		<span>
			<a href="" class="headline-edit edit-gaurdianID-'.$gaurdian.'" >
				<span title="edit"></span>
			</a>
		</span>
	</div>
	<div class="portlet-content">
    	<div>
    		<span class="overview-label">Mailing Address</span> 
    		<span class="overview-value">'.$row_oneGaurdian['gaurdian_address'].'</span>
    	</div>
    	<div>
    		<span class="overview-label">City and Zip</span> 
    		<span class="overview-value">'.$row_oneGaurdian['gaurdian_city'].", ".$row_oneGaurdian['gaurdian_zip'].'</span>
    	</div>
    	<div>
    		<span class="overview-label">Home Phone</span> 
    		<span class="overview-value">'.$row_oneGaurdian['gaurdian_phone'].'</span>
    	</div>
    	<div>
    		<span class="overview-label">Email</span> 
    		<span class="overview-value">'.$row_oneGaurdian['gaurdian_altphone'].'</span>
    	</div>
    	<table>';
	
	$sql_students = "SELECT *
			FROM gaurdians g
				JOIN students s
					ON g.gaurdianID = s.gaurdianID
			WHERE g.gaurdianID=".$gaurdian;
	
	$results_students = $mysqli->query($sql_students);
	
	// loop setup for students
	$i = 0;
	$col_width = 2;
	while ($row_students = mysqli_fetch_assoc($results_students)) {
		if ($i == 0) echo '<tr>';
		echo '
		<td>
			<div class="portlet class-level">
				<div class="portlet-header">
					'.$row_students['student_name'].' 
					<span>					
						<a href="" class="headline-edit edit-studentID-'.$row_students['studentID'].'" >
							<span title="edit"></span>
						</a>
					</span>
				</div>
				<div class="portlet-content">';
		
		// all student info
		echo '
			    	<div>
			    		<span class="overview-label">Home Address</span> 
			    		<span class="overview-value">'.$row_students['student_address'].'</span>
			    	</div>
			    	<div>
			    		<span class="overview-label">City and Zip</span> 
			    		<span class="overview-value">'.$row_students['student_city'].', '.$row_students['student_zip'].'</span>
			    	</div>
					<div>
						<span class="overview-label">Emergency Contact Name</span> 
						<span class="overview-value">'.$row_students['emergency_name'].'</span>
					</div>
			    	<div>
			    		<span class="overview-label">Emergency Contact Phone</span> 
			    		<span class="overview-value">'.$row_students['emergency_phone'].'</span>
			    	</div>
			    	<div>
			    		<span class="overview-label overview-enrolled-in">Enrolled in the following</span>
			    	</div>
					<ul>';
		
		$sql_classes = 'SELECT * 
				FROM enrollments e
					JOIN classes c
						ON c.classID = e.classID
					JOIN levels l
						ON l.levelID = c.levelID
				WHERE e.studentID='.$row_students['studentID'];
		
		$results_classes = $mysqli->query($sql_classes);
		while ($row_classes = mysqli_fetch_assoc($results_classes)) {
			$time_str = explode(',', $row_classes['time']);
			
			// student's class info
            $summer = $row_classes['season'] == "Summer";
            $sold_as_unit = $row_classes['sold_as_unit'] == 1;
            if ($row_classes['levelID']!=$ADULT_CLASS_ID) {
				if (!$summer || $sold_as_unit) {
					$str_days = pprintDays($row_classes['session_days'], dayAndTimeArray($time_str));
				} else {
					$str_days = pprintWeeks($row_classes['session_days'], dayAndTimeArray($time_str));
				}
			} else 
				$str_days = pprintDays($row_classes['days_taught'], dayAndTimeArray($time_str));

			echo '
		    			<li class="overview-class-level overview-class-level-'.$row_classes['levelID'].'">
							<span class="overview-label">'.$row_classes['title'].'</span> <span class="overview-conf-num"> (conf. #'.$row_classes['enrollmentID'].') </span>
							<div class="overview-value-class"> 
								<div>
								
								';
								if ($row_classes['levelID']!=$ADULT_CLASS_ID && (!$summer || $sold_as_unit))
									echo '
									<div>First day of class: '.$row_classes['start_date'].'.</div>';
								
								echo '
									<div>Days taught: '.$str_days.'</div>
								</div> 
							</div>
						</li>';
		}
		
		echo '
					</ul>
				</div>
			</div>
		</td>';
		$i++;
		if ($i == $col_width) {
			$i = 0;
			echo '</tr>';
		}
	}
	
	echo '
		</table>
	</div>
</div>';
}

// find all the adult students and display them on the same level as the gaurdians above.
$sql_adults = 'SELECT studentID, student_name, student_address, student_city, student_zip, student_phone
	FROM users u
		JOIN students s 
			ON u.userID = s.ownerID
	WHERE s.gaurdianID IS NULL
		AND s.ownerID ='.getSession('userID');

$results_adults_from_user = $mysqli->query($sql_adults);

// Adult content
while ($row_afromu = mysqli_fetch_assoc($results_adults_from_user))
{
	echo '
<div class="portlet">
	<div class="portlet-header">
		'.$row_afromu['student_name'].'
		<span>
			<a href="" class="headline-edit edit-studentID-'.$row_students['studentID'].'" >
				<span title="edit"></span>
			</a>
		</span>
	</div>
	<div class="portlet-content">';

	// all Adult student info
	echo '
		<div>
			<span class="overview-label">Mailing Address</span> 
			<span class="overview-value">'.$row_afromu['student_address'].'</span>
		</div>
		<div>
			<span class="overview-label">City and Zip</span> 
			<span class="overview-value">'.$row_afromu['student_city'].', '.$row_afromu['student_zip'].'</span>
		</div>
		<div>
			<span class="overview-label">Phone</span> 
			<span class="overview-value">'.$row_afromu['student_phone'].'</span>
		</div>
		<div>
			<span class="overview-label overview-enrolled-in">Enrolled in the following</span>
		</div>
		<ul>';

	$sql_classes = 'SELECT *
					FROM enrollments e
						JOIN classes c
							ON c.classID = e.classID
						JOIN levels l
							ON l.levelID = c.levelID
					WHERE e.studentID='.$row_afromu['studentID'];
	
	$results_classes = $mysqli->query($sql_classes);
	while ($row_classes = mysqli_fetch_assoc($results_classes)) {
		$time_str = explode(',', $row_classes['time']);

		// the Adult student's class info
		$str_days = pprintDays($row_classes['days_taught'], dayAndTimeArray($time_str));
		echo '
			<li>
							<span class="overview-label">'.$row_classes['title'].'</span> <span class="overview-conf-num"> (conf. #'.$row_classes['enrollmentID'].') </span>
							<div class="overview-value-class"> 
								<div>
									<div>Days taught: '.$str_days.'</div>
								</div> 
							</div>
			</li>';
	}
	
	echo '
		</ul>
	</div>
</div>';
}?>
