<?php 
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