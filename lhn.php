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

$sql = "SELECT * 
	FROM classes c 
		JOIN levels l 
			ON c.levelID=l.levelID
	WHERE c.active = 1	 
	ORDER BY l.levelID ASC";
$results = $mysqli->query($sql);

while ($row = mysqli_fetch_array($results,MYSQLI_ASSOC))
{
	echo '<div class="portlet class-level class-level-'.$row['levelID'].'">
	  <div class="portlet-header">
		'.$row['title'].'
		</div>
		<div class="portlet-content">
			<div class="portlet-img">
				<div class="portlet-img-inner sm_'.$row['classID'].'"></div>
			</div>
			<div class="portlet-wonderful">'.$row['subtitle'].'</div>
		  	<div class="portlet-description">'.str_replace('%~',' ',join('.',array_slice(explode('.', $row['description'], 5), 0, 4))).' ... </div>
		</div>
		<div class="portlet-button-panel">
			<a href="" class="portlet-more" id="portlet-more-'.$row['classID'].'">More Info</a>
			<button class="portlet-register" id="portlet-register-'.$row['classID'].'">Register Now</button>
		</div>
	</div>
	
	<script type="text/javascript">
		$("#portlet-more-'.$row['classID'].'").click(function(){
		
			$(".portlet-more").show();
			$(this).hide();
			
			$(".main .scrollpanel").html("").show().load("more_info.php?classID='.$row['classID'].'",makeLoadMore('.$row['classID'].'));
		});
		
		$("#portlet-register-'.$row['classID'].'").click(function(){
			$(".main .scrollpanel").html("").show().load("register_now.php?classID='.$row['classID'].'",makeLoadRegister('.$row['classID'].'));
		});
	</script>';
}?>