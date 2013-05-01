<?php
if (!isset($SESSION_INCLUDED)) require 'session.php';
if (!isset($FUNCTIONS_INCLUDED)) require 'functions.php';
if (!getSession('logon') || !(getSession('roleID') >= 2)) {
	proceedTo('/register.php');
	exit;
}
require 'db_con.php';
// example using fpdf
if(@$_REQUEST['action']=="getpdf" && @getSession('roleID') >= 2)
{
	include ('fpdf.php');
	$pdf = new FPDF();
	$pdf->AddFont('ProggyTinyTTSZ','','./fonts/ProggyTinyTTSZ.php');
	$pdf->SetFont('ProggyTinyTTSZ','',12);
	$pdf->AddPage('L');

	$pdf->Cell(0,5,"CLASS SIZE",0,1);
	$result=$mysqli->query("SELECT c.title,l.level_name,u.user_name,COUNT(e.classID) as total
						FROM classes c 
					 		JOIN users u
						 		ON c.teacherID=u.userID
						 	JOIN levels l
								ON l.levelID=c.levelID
						 	JOIN enrollments e
								ON e.classID=c.classID
						GROUP BY e.classID
						ORDER BY total DESC");
	// $pdf->Cell(0,4,'txt',$border,$ln,$align,$fill,$link);
	while( $row=mysqli_fetch_assoc($result))
	{
        foreach($row as $col) {
            $pdf->Cell($pdf->GetStringWidth($col)+2,5,$col,1);
        }
        $pdf->Ln();
	}
	$pdf->Ln(3);
	
	$pdf->Cell(0,5,"UNPAID STUDENTS",0,1);
	$result=$mysqli->query("SELECT s.student_name,c.title,SUM(c.price_cents) as total
					 	FROM students s
						 	JOIN enrollments e
						 		ON e.studentID=s.studentID
							JOIN classes c
						 		ON c.classID=e.classID
						WHERE e.paid=0
						GROUP BY s.studentID");
	#$pdf->Cell(0,4,'Printing line number '.$i,$border,$ln,$align,$fill,$link);
	while( $row=mysqli_fetch_assoc($result))
	{
		$cell = $row['student_name'];
		$pdf->Cell($pdf->GetStringWidth($cell)+2,4,$cell,1);
		$cell = $row['title'];
		$pdf->Cell($pdf->GetStringWidth($cell)+2,4,$cell,1);
		$cell = $row['total'];
		$pdf->Cell($pdf->GetStringWidth($cell/100)+2,4,$cell/100,1);
        $pdf->Ln();
	}
	$pdf->Output();
	exit;
}?>

<html>
<head>

<title></title>
<link rel="stylesheet" type="text/css" href="css/reset.css" />
<link rel="stylesheet" type="text/css" href="css/site.css" />
<link rel="stylesheet" type="text/css" href="css/main.css" />
<link rel="stylesheet" type="text/css" href="css/slcb-theme/jquery-ui-1.8.16.custom.css" />
<script type="text/javascript" src="js/jquery-1.6.2.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.16.custom.min.js"></script>
<script type="text/javascript" src="js/jquery.md5.js"></script>
<script type="text/javascript" src="js/validate.js"></script>
<script src="js/hic/highcharts.js"></script>
<script src="js/hic/modules/exporting.js"></script>
<script type="text/javascript">
var globalToDate;
var globalFromDate;
function closed_directortime_datepicker_fromdate(date) {
    location.assign("reports.php?from_date="+date+"&to_date="+globalToDate);
}
function closed_directortime_datepicker_todate(date) {
    location.assign("reports.php?to_date="+date+"&from_date="+globalFromDate);
}
$(function(){
	$("#de-button").button().click(function(){location.assign("data_entry.php");});

    try {
        var from_reg = /from_date=[^&]*/;
        var from_date = from_reg.exec(location.search);
        globalFromDate = from_date ? from_date[0].substring("from_date=".length) : "";
        $("[name=fromdate]").val(globalFromDate);

        var to_reg = /to_date=[^&]*/;
        var to_date = to_reg.exec(location.search);
        globalToDate = to_date ? to_date[0].substring("to_date=".length) : "";
        $("[name=todate]").val(globalToDate);
	} catch(f) {}

	$("[name=fromdate]").datepicker({
	    dateFormat:"yy-mm-dd",
        onClose:closed_directortime_datepicker_fromdate});
	$("[name=todate]").datepicker({
	    dateFormat:"yy-mm-dd",
        onClose:closed_directortime_datepicker_todate});

	$(".reports-get-pdf").button().click(getReports);
	//<div><button class="reports-get-pdf">Get PDF</button></div>
	$('.report-table-container table').each(function(){
		zebraRowsSelector($(this).find('tbody tr:odd td'), 'odd');
	});
	
	$('tbody tr').hover(function(){
		  $(this).find('td').addClass('hovered');
		}, function(){
		  $(this).find('td').removeClass('hovered');
	});

	//default each row to visible
  	$('tbody tr').show();

	$('.filter').keyup(function(event) {
		var tid = $(this).attr('class').substr(14);
		
		//if esc is pressed or nothing is entered
		if (event.keyCode == 27 || $(this).val() == '') {
			//if esc is pressed we want to clear the value of search box
			$(this).val('');
	
			//we want each row to be visible because if nothing
			//is entered then all rows are matched.
			$('#table-'+tid+' tbody tr').show();
		}

		//if there is text, lets filter
		else {
			filter('#table-'+tid+' tbody tr', $(this).val());
		}

		//reapply zebra rows
		$('#table-'+tid+' tbody td').removeClass('odd');
		zebraRows('#table-'+tid+' tbody tr:visible:odd td', 'odd');
  	});


	//grab all header rows
	$('.report-table-container table').each(function(){
		$(this).find('thead th').each(function(column) {
			$(this).addClass('sortable').click(function(){
	
				var tid = $(this).closest('table').attr('id').substr(6);
				
				var findSortKey = function($cell) {
					return $cell.find('.sort-key').text().toUpperCase() + ' ' + $cell.text().toUpperCase();
				};
				var sortDirection = $(this).is('.sorted-asc') ? -1 : $(this).is('.sorted-desc') ? 0 : 1;
	
				//step back up the tree and get the rows with data
				//for sorting
				var $rows = $(this).closest('table').find('tbody tr').get();
	
				//loop through all the rows and find
				$.each($rows, function(index, row) {
					row.sortKey = findSortKey($(row).children('td').eq(column));
				});
	
				//compare and sort the rows alphabetically
				$rows.sort(function(a, b) {
					if (a.sortKey < b.sortKey) return -sortDirection;
					if (a.sortKey > b.sortKey) return sortDirection;
					return 0;
				});
	
				//add the rows in the correct order to the bottom of the table
				$.each($rows, function(index, row) {
					$('#table-'+tid+' tbody').append(row);
					row.sortKey = null;
				});
	
				//identify the column sort order
				$('#table-'+tid+' th').removeClass('sorted-asc sorted-desc');
				var $sortHead = $('#table-'+tid+' th').filter(':nth-child(' + (column + 1) + ')');
				sortDirection == 1 ? $sortHead.addClass('sorted-asc') : sortDirection == -1 ? $sortHead.addClass('sorted-desc') : void(0);
	
				//identify the column to be sorted by
				$('#table-'+tid+' td').removeClass('sorted')
							.filter(':nth-child(' + (column + 1) + ')')
							.addClass('sorted');
	
				//reapply zebra rows
				$('#table-'+tid+' tbody td').removeClass('odd');
				zebraRows('#table-'+tid+' tbody tr:visible:odd td', 'odd');
			});
		});
	});
	
	$('#table-3 tr').each(
		function(a,b){
			var i = 0;
			$(this).find('td').each(
				function() {
					$(this).addClass('col' + i);
					i++;
				}
			);
			var i = 0;
			$(this).find('th').each(
				function() {
					var num = i;
					
					$(this).addClass('col' + i).append('<a style="color:blue;width:15px;height:16px;display:inline-block;background:red;" class="table-3-col-'+i+'">X</a>');
					$('.table-3-col-'+i).click( function() {
						
						console.info('hello');
						$('.col'+num).addClass('hide');
					});
					i++;
				}
			);
		}
	);

	createPieChart();
});
function getReports() {
	window.location = ("reports.php?action=getpdf");
}
//used to apply alternating row styles  
function zebraRows(selector, className)  
{  
	$(selector).removeClass(className).addClass(className);
}
function zebraRowsSelector(query, className) {
	query.removeClass(className).addClass(className);
}
//filter results based on query
function filter(selector, query) {
	query	=	$.trim(query); //trim white space
	query = query.replace(/ /gi, '|'); //add OR for regex query
	
	$(selector).each(function() {
		($(this).text().search(new RegExp(query, "i")) < 0) ? $(this).hide().removeClass('visible') : $(this).show().addClass('visible');
	});
}

function roundNumber(num, dec) {
	var result = Math.round(num*Math.pow(10,dec))/Math.pow(10,dec);
	return result;
}

function createPieChart() {
    var chart;
    $(document).ready(function() {

        // Radialize the colors
        Highcharts.getOptions().colors = $.map(Highcharts.getOptions().colors, function(color) {
            return {
                radialGradient: { cx: 0.5, cy: 0.3, r: 0.7 },
                stops: [
                    [0, color],
                    [1, Highcharts.Color(color).brighten(-0.3).get('rgb')] // darken
                ]
            };
        });

        // Build the chart
        chart = new Highcharts.Chart({
            chart: {
                renderTo: 'directortime',
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false
            },
            title: {
                text: 'Director\'s Time - Categories'
            },
            tooltip: {
                formatter: function() {
                    return '<b>'+ this.point.name +'</b>: '+ roundNumber(this.point.y, 2)+ ' hours';
                }
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: true,
                        color: '#000000',
                        connectorColor: '#000000',
                        formatter: function() {
                            return '<b>'+ this.point.name +'</b>: '+ roundNumber(this.percentage, 2) +' %';
                        }
                    }
                }
            },
            series: [{
                type: 'pie',
                name: 'times',
                data: [


    <?php
        if (@$_REQUEST['from_date'] != null || @$_REQUEST['to_date'] != null ) {
            $result=$mysqli->query("
                SELECT SUM( duration_hours ) AS hours, name, directorID
                FROM (SELECT * FROM directortime

                WHERE " .

                   ($_REQUEST['from_date'] != null
                   ? "date >= '".$_REQUEST['from_date']."'"
                   : "") .

                   ($_REQUEST['from_date'] != null && $_REQUEST['to_date'] != null
                   ? " and "
                   : "") .

                   ($_REQUEST['to_date'] != null ?
                   "date <= '".$_REQUEST['to_date']."'"
                   : "") .
                ") d
                JOIN directortimetype ON
                type = timetypeID
                GROUP BY type
                ORDER BY  `hours` DESC");
        } else {
            $result=$mysqli->query("
                SELECT SUM( duration_hours ) AS hours, name
                FROM  `directortime`
                JOIN directortimetype ON
                type = timetypeID
                GROUP BY type
                ORDER BY  `hours` DESC");
        }
        while ($row=mysqli_fetch_assoc($result)) {
    ?>
                    [ '<?=$row['name']?>',   <?=$row['hours']?>],
    <?php } # end while ?>
                ]
            }]
        });
    });

}
</script>

</head>
<body class="reports-body">

<div style="text-align:center; margin: 30px;"><img src="../img/MastheadLogo-33.png"/> </div>
<div style="text-align:center; margin: 30px;"><span class="headline-txt" style="font-size:40px;">Reports</span></div>

<div style="position:absolute; top: 0; right: 0">
	<button id="de-button">Data Entry</button></div>

<div class="report-table-container">

	<table id="table-0" style="display:inline-block">
		<thead>
			<th class="report-table-header">Director</th>
			<th class="report-table-header">Hours</th>
		</thead>
		<tbody>
    <?php
        if (@$_REQUEST['from_date'] != null || @$_REQUEST['to_date'] != null ) {
            $result=$mysqli->query("
                SELECT sum(duration_hours) as hours,user_name
                FROM
                (
                   SELECT duration_hours,directorID FROM `directortime` d

                   WHERE " .

                   ($_REQUEST['from_date'] != null
                   ? "date >= '".$_REQUEST['from_date']."'"
                   : "") .

                   ($_REQUEST['from_date'] != null && $_REQUEST['to_date'] != null
                   ? " and "
                   : "") .

                   ($_REQUEST['to_date'] != null ?
                   "date <= '".$_REQUEST['to_date']."'"
                   : "") .
                ") d
                JOIN users u on d.directorID=u.userID GROUP BY directorID");
        } else {
            $result=$mysqli->query("SELECT sum(duration_hours) as hours,user_name
                                    FROM `directortime` d
                                        JOIN users u on d.directorID=u.userID
                                    GROUP BY directorID");
        }
        while ($row=mysqli_fetch_assoc($result)) {
    ?>
            <tr>
                <td> <?=$row['user_name']?> </td>
                <td> <span style="padding:0 10px"> <?=$row['hours']?> </span> </td>
            </tr>
    <?php } # end while ?>
        </tbody>
    </table>
    Show Hours From: <input name="fromdate" class="dt"/> To: <input name="todate" class="dt"/>
</div>

<div id="directortime" style="min-width: 400px; height: 400px; margin: 0 auto"></div>

<div class="report-table-container">
	<div class="chopin report-heading">Class Size</div>
	<div class="report-filter">Filter: <input class="filter filter-1"/></div>
	<table id="table-1">
		<thead>
			<th class="report-table-header"># Students</th>
			<th class="report-table-header">Level</th>
			<th class="report-table-header">Class Title</th>
			<th class="report-table-header">Season/Year</th>
			<th class="report-table-header">Teacher</th>
		</thead>
		<tbody>
	<?php
	$result=$mysqli->query("SELECT c.title, l.level_name, u.user_name, c.season, c.year, COUNT(e.classID) as students_enrolled
							FROM classes c 
						 		JOIN users u
							 		ON c.teacherID = u.userID
							 	JOIN levels l
									ON l.levelID = c.levelID
							 	JOIN enrollments e
									ON e.classID = c.classID
							GROUP BY e.classID
							ORDER BY students_enrolled DESC");
	while($row=mysqli_fetch_assoc($result)) {
	?>
			<tr>
			<td><?=$row['students_enrolled']?></td>
			<td><?=$row['level_name']?></td>
			<td><?=$row['title']?></td>
			<td><?=$row['season']?>/<?=$row['year']?></td>
			<td><?=$row['user_name']?></td>
			</tr>
	<?php } # end while ?>
		</tbody>
	</table>
</div>

<div class="report-table-container">
	<div class="chopin report-heading">Student Itemized</div>
	<div class="report-filter">Filter: <input class="filter filter-3"/></div>
	<table id="table-3">
		<thead>
			<tr>
				<th class="report-table-header">Enrol-ID</th>
				<th class="report-table-header">Stu-ID</th>
				<th class="report-table-header">Student</th>
				<th class="report-table-header">dob</th>
				<th class="report-table-header">Age</th>
				<th class="report-table-header">Class</th>
				<th class="report-table-header">Season/Year</th>
				<th class="report-table-header">Session Days</th>
				<th class="report-table-header">Gaurdian</th>
				<th class="report-table-header">Phone</th>
				<th class="report-table-header">Email</th>
				<th class="report-table-header">Emergency</th>
				<th class="report-table-header">Cost</th>
				<th class="report-table-header">Paid</th>
				<th class="report-table-header">Unpaid</th>
			</tr>
		</thead>
		<tbody>
	<?php
$result = $mysqli->query("
	SELECT
		student_name,
		studentID,
		student_dob,
		user_name,
		email,
		title,
		price_cents,
		enroll_owed.enrollmentID,
		total_paid,
		start_date,
		session_days,
		level_name,
		year,
		season,
		gaurdian_name,
		gaurdian_phone,
		emergency_phone,
		gaurdian_altphone
		
	FROM
		(SELECT 
			student_name,
			student_dob,
			s.studentID,
			user_name,
			email,
			title,
			price_cents,
			enrollmentID,
			start_date,
			session_days,
			l.level_name,
			year,
			season,
			gaurdian_name,
			gaurdian_phone,
			emergency_phone,
			gaurdian_altphone
		FROM students s
			JOIN enrollments e ON e.studentID = s.studentID
			JOIN gaurdians g ON g.gaurdianID = s.gaurdianID
			JOIN classes c ON c.classID = e.classID
			JOIN levels l ON l.levelID = c.levelID
			JOIN users u ON s.ownerID = u.userID
		WHERE e.paid = 0)
		AS enroll_owed

	LEFT JOIN

		(SELECT 
			SUM(amount_cents) as total_paid,
			e.enrollmentID
		FROM enrollments e
			JOIN paylog p ON e.enrollmentID = p.enrollmentID
		GROUP BY e.enrollmentID)
		AS enroll_paid
				ON enroll_paid.enrollmentID = enroll_owed.enrollmentID
	");
	while($row = mysqli_fetch_assoc($result)) {
	?>
			<tr>
				<td><?=$row['enrollmentID']?></td>
				<td><?=$row['studentID']?></td>
				<td><?php
					$stu_name=explode(' ',  $row['student_name']);
					
					$str=array_shift($stu_name);
					echo implode(" ",$stu_name).", ".$str;
					?></td>
				<td><?=$row['student_dob']?></td>
				<td><?=get_birthday($row['student_dob'], $row['start_date'])?></td>
				<td><?=$row['title']?> <?=$row['level_name']?></td>
				<td><?=($row['season'].'/'.$row['year'])?></td>
				<td><?=$row['session_days']?></td>
				<td><?=$row['gaurdian_name']?> </td>
				<td><?=$row['gaurdian_phone']?> </td>
				<td><?=$row['gaurdian_altphone']?> </td>
				<td><?=$row['emergency_phone']?> </td>
				<td>$<?=decimalize($row['price_cents'])?></td>
				<td>$<?=decimalize($row['total_paid']==null?0:$row['total_paid'])?></td>
				<td>$<?=decimalize($row['price_cents']-$row['total_paid'])?></td>
			</tr>
	<?php } # end while ?>
		</tbody>
	</table>
</div>

<?php 
/* <div class="report-table-container">
	<div class="chopin report-heading">Student Totals Owed </div>
	<div class="report-filter">Filter: <input class="filter filter-2"/></div>
	<table id="table-2">
		<thead>
			<tr>
				<th class="report-table-header">Student</th>
				<th class="report-table-header">Total</th>
			</tr>
		</thead>
		<tbody>
	<?php
	$result=$mysqli->query("SELECT s.student_name,SUM(c.price_cents) as total_owed
					 	FROM students s
						 	JOIN enrollments e
						 		ON e.studentID=s.studentID
							JOIN classes c
						 		ON c.classID=e.classID
						WHERE e.paid=0
						GROUP BY s.studentID");# group by is used in conjunction with sum
	while($row=mysqli_fetch_assoc($result)) {
	?>
			<tr>
			<td><?=$row['student_name']?></td>
			<td>$<?=$row['total_owed']?></td>
			</tr>
	<?php } # end while ?>
		</tbody>
	</table>
</div>
 */
?>

<div class="report-table-container">
	<div class="chopin report-heading">Users</div>
	<div class="report-filter">Filter: <input class="filter filter-4"/></div>
	<table id="table-4">
		<thead>
			<tr>
				<th class="report-table-header">UserID</th>
				<th class="report-table-header">Email</th>
				<th class="report-table-header">User</th>
				<th class="report-table-header">Guardian Name</th>
				<th class="report-table-header">Address</th>
				<th class="report-table-header">Zip</th>
				<th class="report-table-header">Student Name</th>
			</tr>
		</thead>
		<tbody>
	<?php
	$result=$mysqli->query("
		select user_name,gaurdian_address,gaurdian_name,gaurdian_phone,gaurdian_altphone,email,gaurdian_zip,student_name,userID
		from users u
			join students s on userID=ownerID
			join gaurdians g on g.gaurdianID=s.gaurdianID");
	while($row=mysqli_fetch_assoc($result)) {
	?>
			<tr>
				<td><?=$row['userID']?></td>
				<td> <a href="mailto:<?=$row['email']?>"><?=$row['email']?></a> </td>
				<td><?=$row['user_name']?></td>
				<td><?=$row['gaurdian_name']?></td>
				<td><?=$row['gaurdian_address']?></td>
				<td><?=$row['gaurdian_zip']?></td>
				<td><?=$row['student_name']?></td>
			</tr>
	<?php } # end while ?>
		</tbody>
	</table>
</div>

<!--<div class="report-table-container">
	<div class="chopin report-heading">Tuition</div>
	<div class="report-filter">Filter: <input class="filter filter-5"/></div>
	<table id="table-5">
		<thead>
			<tr>
				<th class="report-table-header">To do ...</th>
			</tr>
		</thead>
		<tbody>
	<?php
	$result=$mysqli->query("
		select userID
		from users
			join students s 
				on userID=ownerID
			join enrollments e
				on e.enrollmentID=s.studentID");
	while($row=mysqli_fetch_assoc($result)) {
	?>
			<tr>
				<td><?=$row['userID']?></td>
			</tr>
	<?php } # end while ?>
		</tbody>
	</table>
</div>   -->

<div style="text-align:center; margin: 30px;"><img src="../img/MastheadLogo-33.png"/> </div>
<div style="text-align:center; margin: 30px;"><span class="headline-txt" style="font-size:40px;">Reports</span></div>

</body>
</html>
