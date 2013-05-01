<?php
$FUNCTIONS_INCLUDED=true;
/**
 * called like, proceedTo("/haha.php");
 * @param unknown_type $loci
 */
function proceedTo($loci){
	if (!empty($_SERVER['HTTPS']) && ('on' == $_SERVER['HTTPS'])) {
		$uri = 'https://';
	} else {
		$uri = 'http://';
	}
	$uri .= $_SERVER['HTTP_HOST'];
	header('Location: '.$uri.$loci);
	exit;
}

function dayAndTimeArray($arr) {
	$ret = array();
	if (count($arr) == 1) {
		$ret[0] = $arr[0];
		return $ret;	
	}
	foreach ($arr as $dayTime) {
		$dayTime = trim($dayTime);
		$pos = strpos($dayTime, ' ');
		$day = trim(substr($dayTime, 0, $pos));
		$time = trim(substr($dayTime, $pos+1, strlen($dayTime)));
		$ret[$day] = $time;
	}
	return $ret;
}
/**
 * prints weeks instead of days
 */

function pprintWeeks($weeks,$daysArr) {
	$weeks = explode(',',$weeks);
	$span_day = '<span>%week</span>';
	$str_days ='';
	$comma="";
	$i = 0;
	foreach($weeks as $week) {
		$week = strtotime($week);
		$str_days .= $comma . date('F j', $week) . " - " . date('j', strtotime("+ 5 days", date($week)));
		if ($comma == "") {
			$comma = ", ";
		}
		$i++;
		if ($i == count($weeks) - 1) {
			$comma .= 'and ';
		}
	}
	$str_days = $str_days . " at " . $daysArr[0];
	return $str_days;
}

/** daysMin, string list like "Tu, Th" daysArr, array of days to times;
 returns: "Tuesday, and Thursday" */
function pprintDays($daysMin,$daysArr) {
	$arr_days = array ('Mo'=>'Monday','Tu'=>'Tuesday','We'=>'Wednesday','Th'=>'Thursday','Fr'=>'Friday','Sa'=>'Saturday','Su'=>'Sunday');
	$days_taught = explode(',',$daysMin);
	$span_day = '<span>%day</span>';
	$str_days ='';
	$comma="";
	$i = 0;
	foreach ($days_taught as $day) {
		$day = trim($day);
		$time = '';
		if (count($daysArr) != 1) {
			$time = $daysArr[$day];
		}
		$txt_day = $arr_days[$day].($time==''?'':' at '.$time);
		$str_days .= $comma . str_replace('%day', $txt_day, $span_day);
		if ($comma == "") {
			$comma = ", ";
		}
		$i++;
		if ($i == count($days_taught) - 1) {
			$comma .= 'and ';
		}
	}
	$str_days = $str_days.(count($daysArr) != 1 ? '' : ' at '.$daysArr[0]);
	return $str_days;
}

function decimalize($c) {
	if ($c == null)
	return "0";
	else
	return $c / 100;
}

function get_birthday($dob, $start) {
	$db = getdate(strtotime($dob));
	$sd = getdate(strtotime($start));
	return $sd["year"] - $db['year'] + ($sd['yday'] < $db['yday'] ? 0 : 1);
}

?>