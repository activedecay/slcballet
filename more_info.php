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


$realClassID = $mysqli->real_escape_string($_REQUEST['classID']);

$sql = "SELECT *
	FROM classes
		JOIN users
		JOIN levels
	WHERE classes.classID = ".$realClassID."
		AND users.userID = classes.teacherID
		AND levels.levelID = classes.levelID";

$results = $mysqli->query($sql);
$row = mysqli_fetch_array($results);
$time_str = explode(',', $row['time']);
/*
echo $row['user_name']."<br/>";#teacher
echo $row['level_name']."<br/>";
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
?>
<div class="more-headline">
	<span class="more-headline-title"><?= $row['title']; ?></span>
	<span class="more-headline-subbutton"><button class="continue">Register Now</button></span>
	<span class="more-headline-sublnk">
		<a href="" class="headline-cancel" >
			<span></span>
			<span class="cancel txt">Cancel</span>
		</a>
	</span>
</div>
<div class="more-img">
	<div class="more-img-inner lg_<?=$row['classID']?>">
	</div>
</div>
<div class="more-first">
	<div class="more-content">
		<span class="more-inline more-level"><?= $row['level_name']?></span> - 
		<?php $desc = explode('%~',$row['description']);
			echo $desc[0];
		?>
	</div>
</div>
<div class="more-second">
	<div class="more-content">
		<?= @$desc[1]; ?>
	</div>
</div>
<div class="more-third">
	<div class="more-content">
		<?= @$desc[2]; ?>
	</div>
	<div class="more-content more-when">
		<span class="more-headline-subtxt"><?='Taught by '.$row['user_name'] ?></span> - 
		
		
		<?php
			#str_replace('%~',' ',
			#		join('.',array_slice(
			#				explode('.', $row['description'], 5), 0, 4)))
			echo 'Classes start on '.pprintDays($row['days_taught'], dayAndTimeArray($time_str)),'.';
			
			echo '
			Semester begins <span class="more-info-datetime-start">'.$row['datetime_start'].'</span>,
			and the last class ends <span class="more-info-datetime-end">'. $row['datetime_end'].'</span>.';
			
		?> 
		
		
	</div>
	<div class="more-content more-price">
		<!-- <?= $row['season'].' '.$row['year'].' Semester Price: $'.($row['price_cents']/100); ?> -->
	</div>
</div>
	