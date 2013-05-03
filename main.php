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

if (getSession('logon')) {
	
	// if user has just changed their password, update the roleID from the db
	require 'db_con.php';
	$sql = "SELECT
				userID,pass,roleID,user_name
			FROM users 
				WHERE userID='".getSession('userID')."'";
	
	$row = mysqli_fetch_assoc($mysqli->query($sql));
	setSession('roleID',$row['roleID']);
	
	if (getSession('roleID')==0) {
		proceedTo('/pass_change.php');
	}
	// user is okay to logon to main site
} else {
	// kick them back to the login page
	proceedTo('/register.php');
	exit;
}
?>
<html>
<head>
<title>Salt Lake City Ballet - Registration</title>

<link rel="stylesheet" type="text/css" href="css/reset.css" />
<link rel="stylesheet" type="text/css" href="css/site.css" />
<link rel="stylesheet" type="text/css" href="css/main.css" />
<link rel="stylesheet" type="text/css" href="css/slcb-theme/jquery-ui-1.8.16.custom.css" />
<script type="text/javascript" src="js/jquery-1.6.2.min.js"></script>
<script type="text/javascript" src="js/jquery-ui-1.8.16.custom.min.js"></script>
<script type="text/javascript" src="js/jquery.md5.js"></script>
<script type="text/javascript" src="js/validate.js"></script>
<script type="text/javascript">

var adult_level_id = <?php require 'constants.php'; echo $ADULT_CLASS_ID?>;//adult
var childrens_level_id = <?php require 'constants.php'; echo $CHILDRENS_ACADEMY_ID?>;//childrens

// what happens when the page first loads
$(function(){
	navWidth = 340;//$('.lhn').width();
	// click handlers (main buttons)
	$('#logout').button().click(function(){location.replace("exit.php");});
	$('#reports').button().click(function(){location.assign("reports.php");});
	$('#data_entry').button().click(function(){location.assign("data_entry.php");});
	$('#show-my-students').button().click(loadOverview);
	$(".register-for-more").button().click(loadLevelNav);

	$('#sel_filterLevel').change(updateFilter);

	<?php
	$sql_has_enrollments = "SELECT *
		FROM users u
			JOIN students s 
				ON u.userID = s.ownerID
			JOIN enrollments e
				ON s.studentID = e.studentID
		WHERE u.userID = ".getSession('userID');
	
	$results_e = $mysqli->query($sql_has_enrollments);
	
	if (mysqli_fetch_array($results_e)) {
		$user_has_students = true;
		// load a different page if the user has students
		echo 'loadOverview();';
	} else {
		$user_has_students = false;
		// load the nav if they have no students
		echo 'loadLevelNav();';
	}
	?>
});

function updateFilter() {
	// filter lhn by level
	$(this).val() == 'all'? $('.class-level').show():$('.class-level').hide();
	$('.class-level-' + $(this).val()).show();

	// do the same for overview
	$('.overview-class-level-' + $(this).val()).closest('.class-level').show();

	// update the portlets on the lhn
	if ($(".main").is(":visible")) 
		$(".portlet-description").hide();
	else
		$(".portlet-description").show();
}

function showMain(){
	if ($('.main').is(":visible")) return;
	
	$('.main').css({width:'auto',left:$(window).width()});
	$('.main').show().animate({left:navWidth});
	$('.lhn').animate({width:navWidth},function(){$(this).css('');});
	$('.portlet-description').hide('fade',190);

	return false;
}

function hideMain(){
	if (!($('.main').is(":visible"))) return;
	
	$('.main').animate({left:$(window).width()},function(){$('.main').hide();});
	$('.lhn').animate({width:$(window).width()},function(){$(this).css({right:0,width:'auto'});
	$('.portlet-description').show('fade',1500);});
	$('.portlet-more').show();
	$(".main .scrollpanel").fadeOut().html("");

	return false;
}

function makeLoadMore(row){
	var classID = row;
	return function (){

		$('.more-info-datetime-start').text(
				$.datepicker.formatDate("MM d, yy", $.datepicker.parseDate('yy-mm-dd',$('.more-info-datetime-start').text().replace(/^\s*|\s*$/g,''))));
		$('.more-info-datetime-end').text(
				$.datepicker.formatDate("MM d, yy", $.datepicker.parseDate('yy-mm-dd',$('.more-info-datetime-end').text().replace(/^\s*|\s*$/g,''))));
		
		// add styles
		$(".headline-cancel span:first-child").addClass("ui-icon ui-icon-close");
		$(".main .scrollpanel .more-headline").addClass("ui-corner-all ui-widget-header");
		$(".more-img").addClass("ui-widget ui-widget-content ui-helper-clearfix ui-corner-all");

		// click handlers
		$(".cancel").click(hideMain);
		$(".continue").button({ icons: {secondary:"ui-icon-triangle-1-e"} }).click(function(){
			$(".main .scrollpanel").html("").show().load("register_now.php?classID=" + classID,makeLoadRegister(classID));
		});

		// fade in effects
		$(".main .scrollpanel .more-img").fadeIn(function(){
		$(".main .scrollpanel .more-first").fadeIn(function(){
		$(".main .scrollpanel .more-second").fadeIn(function(){
		$(".main .scrollpanel .more-third").fadeIn(function(){});});});});
	};
}

enabled_gaurdian_inputs = true;
enabled_student_inputs = true;

// when the user clicks register. REQUEST{classId:num}
function makeLoadRegister(row){
	var classID = row;
	return function () {
		// show the links that could have been hidden
		$(".portlet-more").show();
		
		// add text
		$(".register-label").append("<span/>").find("span").html(":");

		// add styles
		$(".main .scrollpanel .more-headline").addClass("ui-corner-all ui-widget-header");
		$(".headline-cancel span:first-child").addClass("ui-icon ui-icon-close");
		$(".register-section").addClass("ui-widget ui-widget-content ui-helper-clearfix ui-corner-all");
		$(".register-section-header").addClass("ui-widget-header ui-corner-all");

		// make datepicker
		$("input[name='student_datepicker']").datepicker({
			changeMonth: true,
			changeYear: true,
			yearRange: "-30:+00",
			dateFormat: "d MM, yy",
			altField: "#alt_dob",
			altFormat: "yy-mm-dd",
			onClose:student_datepicker
		});
		$("input[name='start_datepicker']").datepicker({
			changeMonth: true,
			changeYear: true,
			yearRange: "0:+01",
			dateFormat: "d MM, yy",
			altField: "#alt_start",
			altFormat: "yy-mm-dd",
			onClose:start_datepicker,
			beforeShowDay: picker_days_available
		});
		// image datepicker
		$(".register-calendar-dob").click(function(){$("input[name='student_datepicker']").datepicker("show");});
		$(".register-calendar-start-date").click(function(){$("input[name='start_datepicker']").datepicker("show");});
        $('[name=start_datepicker]').attr('disabled',true);
        $('[name=student_datepicker]').attr('disabled',true);
        $('.blob-start-date').click(function(){$("input[name='start_datepicker']").datepicker("show");});
		$('.blob-dob').click(function(){$("input[name='student_datepicker']").datepicker("show");});
		
		// default selection
		$("select option[value='Utah']").attr("selected","selected");
		//$(".xss").load("load_registration.php");

		// click handlers
		$(".cancel").click(hideMain);
		$(".continue").button({ icons: {secondary:"ui-icon-triangle-1-e"} }).click(function(){
			// set registration errors
			checkAllInput();
			// if there are any registration form errors, they will appear in this list. also check that they're attending a class.
			if ($('.register-error').length == 0 && getAttendingValidation()) {
				// assume that all forms are valid here
				var req = "";// request query params string builder
				var delim = "?";// used to create query params, values={?,&}
				var g_pattern = /gaurdian/gi;
				var s_pattern = /student/gi;
				$('input').each(
					function(){
						// input[name]=input.value
						if ($(this).attr('name') == "student_datepicker" ||
								$(this).attr('name') == "start_datepicker" ||
								$(this).attr('type') == "checkbox" || 
								(!enabled_gaurdian_inputs && $(this).attr('name').match(g_pattern)) ||
								(!enabled_student_inputs && $(this).attr('name').match(s_pattern)) ) 
							return true;
						req +=delim + $(this).attr('name') + "=" + escape($(this).val());
						if (delim == "?") delim="&";
					}
				);
				
				// gaurdian is 0 then create new, otherwise use the gaurdian id to register
				if ($('#pan_has_gaurdians select option:selected').val() != undefined) {
					req += delim+'gaurdianID=' + $('#pan_has_gaurdians select option:selected').val();
					if (delim == "?") delim="&";
				} else {
					req += delim+'gaurdianID=0';
					if (delim == "?") delim="&";
				}
				// student is 0 then create new, otherwise use the student id to register
				if ($('#pan_has_students select option:selected').val() != undefined) {
					req += '&studentID=' + $('#pan_has_students select option:selected').val();
				} else {
					req += '&studentID=0';
				}
				req += '&classID=' + classID;
				req += '&session_count=' + $("#attending input[type=checkbox]:checked").length;
				req += '&session_days=';
				comma_str = "";
				$("#attending input[type=checkbox]:checked").each(function(){
					req += comma_str + escape($(this).attr('name'));
					comma_str = ",";// first element is not comma'd
				});
				// what to load when the information is gathered from registration
				$(".main .scrollpanel").html("").show().load("register_confirm.php" + req,loadConfirm);
			}
		});
		// copy some inputs from student to gaurdian 
		$("#click_here_same").click(function(e){
			e.preventDefault();
			// copy input
			copyInput("student_address","gaurdian_address");
			copyInput("student_city","gaurdian_city");
			copyInput("student_zip","gaurdian_zip");
			// copy state, but ignore state errors
			stud_state = $("select[name='student_state'] option:selected").val();
			$("select[name='gaurdian_state'] option[value='" + stud_state + "']").attr("selected","selected");

			// check errors
			gaurdian_address();
			gaurdian_city();
			gaurdian_zip();
		});
		
		// gaurdian select click handler
		$('#pan_has_gaurdians select').change(gaurdianFilterChanged);
		// student select click handler 
		$('#pan_has_students select').change(studentFilterChanged);
		
		// attending checkboxes click handler
		$("#attending input[type=checkbox]").click(checkAttendingTotal);
		$("#attending input[type=checkbox]").click(function(){
			// checkboxes should clear date picker values in case the date is stale based on which days they checked.
			$('[name=start_datepicker]').val('');
			$('[name=start_date]').val('');
		});

		// blur function handlers
		$("[name=gaurdian_name]").blur(gaurdian_name);        
		$("[name=gaurdian_address]").blur(gaurdian_address);
		$("[name=gaurdian_city]").blur(gaurdian_city);
		$("[name=gaurdian_zip]").blur(gaurdian_zip);
		$("[name=gaurdian_phone]").blur(gaurdian_phone);
		$("[name=gaurdian_altphone]").blur(gaurdian_altphone);
		$("[name=student_name]").blur(student_name);
		$("[name=student_datepicker]").blur(student_dob);
		$("[name=start_datepicker]").blur(start_date);
		$("[name=student_address]").blur(student_address);
		$("[name=student_city]").blur(student_city);
		$("[name=student_zip]").blur(student_zip);
		$("[name=student_phone]").blur(student_phone);
		$("[name=emergency_name]").blur(emergency_name);
		$("[name=emergency_phone]").blur(emergency_phone);

		// validations for attending checkboxes
		checkAttendingTotal();
	};
}

function picker_days_available(date) {
	var day = date.getDay();
	var ret = false;
	$('input[type=checkbox]:checked').each(function(){
		if ($(this).val()==day) {
			ret = true;
			return false; // exit each function
		}
	});
	return [ret];
}

function gaurdianFilterChanged(){
	// enable/disable all inputs
	enabled_gaurdian_inputs = $(this).val() == 0; // create new
	$("#gaurdian-infos input").attr('disabled',!enabled_gaurdian_inputs);
	
	if (enabled_gaurdian_inputs) {
		// the user would like to create a new gaurdian
		
		$('.gaurdian-register-warning').show();
		$('.gaurdian-register-okay').hide();
		
		$("#click_here_same").show();

		// show inputs
		$("#hide_for_stored_gaurdian").show();
		$("[name=gaurdian_name]").val("");
		
	} else {
		// the user is re-using a gaurdian
		
		$('.gaurdian-register-warning').hide();
		$('.gaurdian-register-okay').show();
		
		$("#click_here_same").hide();
		
		// hide inputs
		$("#hide_for_stored_gaurdian").hide();

		// copy the gaurdian name from the select
		$("[name=gaurdian_name]").val($('#pan_has_gaurdians select option:selected').text());
		// fix validations for when they actually do click continue.
		$("[name=gaurdian_address]").val("");
		$("[name=gaurdian_city]").val("");
		$("[name=gaurdian_zip]").val("");
		$("[name=gaurdian_phone]").val("");
		$("[name=gaurdian_altphone]").val("");

		// allow the user to proceed if they entered bad input before selecting a gaurdian from the drop down.
		errorCleared('gaurdian_name');
		errorCleared('gaurdian_address');
		errorCleared('gaurdian_city');
		errorCleared('gaurdian_zip');
		errorCleared('gaurdian_phone');
	}
}

function studentFilterChanged(){
	// enable/disable all inputs
	enabled_student_inputs = $(this).val() == 0;
	$("#student-infos").find("input").attr('disabled',!enabled_student_inputs); 
						
	if (enabled_student_inputs) {
		// the user would like to create a new student
		
		$('.student-register-warning').show();
		$('.student-register-okay').hide();
		
		// show inputs
		$(".hide_for_stored_student").show();
		$("[name=student_name]").val("");
		
	} else {
		// the user is re-using a student
		
		$('.student-register-warning').hide();
		$('.student-register-okay').show();
		
		// hide inputs
		$(".hide_for_stored_student").hide();

		// copy the student name from the select
		$("[name=student_name]").val($('#pan_has_students select option:selected').text());
		// fix validations for when they actually do click continue.
		$("[name=student_address]").val("");
		$("[name=student_city]").val("");
		$("[name=student_zip]").val("");
		$("[name=student_phone]").val("");
		$("[name=student_altphone]").val("");
		$("[name=student_dob]").val("");
		$("[name=student_datepicker]").val("");
		$("[name=emergency_name]").val("");
		$("[name=emergency_phone]").val("");

		// allow the user to proceed if they entered bad input before selecting a student from the drop down.
		errorCleared('student_name');
		errorCleared('student_address');
		errorCleared('student_city');
		errorCleared('student_zip');
		errorCleared('student_phone');
		errorCleared('student_dob');
	}
}

//checks only input fields that appear on the page.
function checkAllInput() {
	start_date();
	// skip some validations
	if (enabled_student_inputs){
		student_name();
		student_city();
		student_address();
		student_zip();
		student_dob();
		student_phone();
		emergency_phone();
		emergency_name();
	}
	// skip some validations
	if (enabled_gaurdian_inputs){
		gaurdian_name();
		gaurdian_address();
		gaurdian_city();
		gaurdian_zip();
		gaurdian_phone();
		gaurdian_altphone();
	}
}

function copyInput(stud,gard){
	$('input[name=' + gard + ']').val($('input[name=' + stud + ']').val());
}

// checks if any attending checkboxes are checked and enables the button
function checkAttendingTotal() {
	var enabled_attending = getAttendingValidation()
	$(".continue").button("option", "disabled", !enabled_attending);
}

function getAttendingValidation() {
	var enabled_attending = true;
	if (adult_level_id != register_now_level_id) {

		if (register_now_season == "Summer" && childrens_level_id != register_now_level_id && !register_now_sold_as_unit) {
			// summer, non-childrens_academy have to pick an even number or 3
			enabled_attending = $("#attending input[type=checkbox]:checked").length != 0 
				&& ($("#attending input[type=checkbox]:checked").length == 3
					|| $("#attending input[type=checkbox]:checked").length % 2 == 0);
		} else {
			// other class levels have to check any box
			enabled_attending = $("#attending input[type=checkbox]:checked").length != 0;
		}
	} // else adult class should automatically be enabled.
	return enabled_attending;
}

/** called when the user clicks continue from register now */
function loadConfirm() {
	// add text
	$(".register-label").append("<span/>").find("span").html(":");
	$('.first-day-scheduled').text($.datepicker.formatDate("d MM, yy", $.datepicker.parseDate('yy-mm-dd',$('.first-day-scheduled').text().replace(/^\s*|\s*$/g,''))));
	
	// add styles
	$(".main .scrollpanel .more-headline").addClass("ui-corner-all ui-widget-header");
	$(".headline-cancel span:first-child").addClass("ui-icon ui-icon-close");
	$(".register-section").addClass("ui-widget ui-widget-content ui-helper-clearfix ui-corner-all");
	$(".register-section-header").addClass("ui-widget-header ui-corner-all");

	// click handlers
	$(".cancel").click(hideMain);
	$(".continue").button({ icons: {secondary:"ui-icon-triangle-1-e"} }).click(function(){
		// check that something is checked first before letting them proceed
		if ($("#agreements input[type=checkbox]:checked").length == totalCheckedRequired) {
			$(".main .scrollpanel").html("").show().load("register_success.php",loadSuccess);
		}
	});

	// diables the continue button if there have not been enough check marks clicked.
	checkRequiredTotal();
	$("input[type=checkbox]").click(checkRequiredTotal);
}

function checkRequiredTotal() {
	 // enabled when all agreements are checked
	var enabled_agreements = $("#agreements input[type=checkbox]:checked").length == totalCheckedRequired;
	$(".continue").button("option", "disabled", !enabled_agreements);
}

// after the page that says they successfully created/edited a student
function loadSuccess() {
	// text
	if ($('.first-day-scheduled').text() != "")
		$('.first-day-scheduled').text($.datepicker.formatDate("d MM, yy", $.datepicker.parseDate('yy-mm-dd',$('.first-day-scheduled').text().replace(/^\s*|\s*$/g,''))));

	// toggle visibility of my students button
	$('#show-my-students').show();
	
	// add styles
	$(".main .scrollpanel .more-headline").addClass("ui-corner-all ui-widget-header");
	$(".headline-cancel span:first-child").addClass("ui-icon ui-icon-close");
	$(".register-section").addClass("ui-widget ui-widget-content ui-helper-clearfix ui-corner-all");
	$(".register-section-header").addClass("ui-widget-header ui-corner-all");

	// click hanlders
	$(".continue").button({ icons: {secondary:"ui-icon-triangle-1-e"} }).click(loadOverview);
}

function loadOverview() {
	$(".lhn .scrollpanel").html("").show().load("overview.php",overviewLoaded);
}

function overviewLoaded() {
	hideMain();

	// toggle visibility of my students button
	$('#show-my-students').show();

	// fix up the filter box
	$('select option[value=all]').attr('selected',true).each(updateFilter);

	// text
	$(".overview-label").append("<span/>").find("span").html(":");

	// add styles
	$(".lhn .scrollpanel .more-headline").addClass("ui-corner-all ui-widget-header");
	$(".headline-edit span:first-child").addClass("ui-icon ui-icon-pencil");
	$('.portlet').addClass('ui-widget ui-widget-content ui-helper-clearfix ui-corner-all')
		.find('.portlet-header').addClass('ui-widget-header ui-corner-all');

	// click handlers for edit pencil links
	$('.headline-edit').click(function(){
		showMain();

		// hide all gaurdian/student portlets
		$('.headline-edit').closest('.portlet').hide(); 

		// show this helper when they're editing.
		$('.lhn .scrollpanel').append('<div class="portlet ui-widget ui-widget-content ui-helper-clearfix ui-corner-all" id="edit-helper"/>');
		$('#edit-helper').html($("."+$(this).attr('class').substring("headline-edit ".length)).closest('.portlet').html())
			.find('.headline-edit').hide().end()
			.find('ul').remove().end()
			.find('.overview-enrolled-in').remove().end()
			.find('.conf-num').remove();

		// show the edit record page
		var edit_arr = $(this).attr("class").substring("headline-edit edit-".length).split("-");
		$(".main .scrollpanel").html("").show().load("edit_record.php?"+edit_arr[0]+"="+edit_arr[1],editLoaded);

		return false;
	});
}

function editLoaded() {
	// add text
	$(".register-label").append("<span/>").find("span").html(":");
	
	// add styles
	$(".main .scrollpanel .more-headline").addClass("ui-corner-all ui-widget-header");
	$(".headline-cancel span:first-child").addClass("ui-icon ui-icon-close");
	$(".register-section").addClass("ui-widget ui-widget-content ui-helper-clearfix ui-corner-all");
	$(".register-section-header").addClass("ui-widget-header ui-corner-all");

	// click handlers
	$(".cancel").click(function () {
		hideMain();

		// delete edit-helper
		$('#edit-helper').remove();

		// hide left nav's main button
		$(".register-for-more").show();

		// show all gaurdian/student portlets
		$('.headline-edit').closest('.portlet').show();

		return false;
	});

	$(".continue").button({ icons: {secondary:"ui-icon-triangle-1-e"} }).click(function(){
		// validate submit
		checkAllInput();
		editing_password_valid(true); // pretend it's valid, to reset the ui
		$('.edit-continue').button("option", "disabled", true).removeClass('ui-state-hover');
		if ($('.register-error').length == 0) {
			// okay to submit password
			var request_obj = {post:'submit'};
	
			// grab all the info from the inputs and shove them into request obj
			$('input').each(function(){
				request_obj[$(this).attr('name')]=$(this).val();
			});
	
			// send the request to this page to confirm they have their password correct.
			// when we return show errors, or make the request to edit_success
			$.get("edit_record.php", request_obj, cbEdit, "json");
		}
	});

	// blur function handlers
	$("[name=gaurdian_name]").blur(gaurdian_name);
	$("[name=gaurdian_address]").blur(gaurdian_address);
	$("[name=gaurdian_city]").blur(gaurdian_city);
	$("[name=gaurdian_zip]").blur(gaurdian_zip);
	$("[name=gaurdian_phone]").blur(gaurdian_phone);
	$("[name=gaurdian_altphone]").blur(gaurdian_altphone);
	$("[name=student_name]").blur(student_name);
	$("[name=student_datepicker]").blur(student_dob);
	$("[name=student_address]").blur(student_address);
	$("[name=student_city]").blur(student_city);
	$("[name=student_zip]").blur(student_zip);
	$("[name=student_phone]").blur(student_phone);
	$("[name=emergency_name]").blur(emergency_name);
	$("[name=emergency_phone]").blur(emergency_phone);
}

// after the user submits their edits, we need to validate that the password was correct, and display error messages
function cbEdit(msgs) {
	$('#txt_login_success_overall').html('');
	for (m in msgs)
	{
		if (m === 'success')
		{
			// don't put code here because there could be no messages in success
			for (str in msgs[m]) // TODO hasOwnProperty
			{
				if (str === 'success') {
					$(".main .scrollpanel").html("").show().load("edit_success.php",loadSuccess);
					return;
				}
			}
		}
		else if (m === 'err')
		{
			// don't put code here because there could be no messages in err
			for (str in msgs[m])
			{
				editing_password_valid(false,msgs[m][str]);

				$('.edit-continue').button("option", "disabled", false); // enable the button so they can click it again
			}
		}
	}
}

function loadLevelNav() {
	$(".lhn .scrollpanel").html("").load("lhn.php",leftNavLoaded);
}

function leftNavLoaded() {

	$('select option[value=all]').attr('selected',true).each(updateFilter);

	$('.portlet').addClass('ui-widget ui-widget-content ui-helper-clearfix ui-corner-all')
		.find('.portlet-header')
		.addClass('ui-widget-header ui-corner-all')
		//.prepend('<span class="ui-icon ui-icon-plus"></span>')
		.end()
		.find('.portlet-img').addClass('ui-widget ui-widget-content ui-helper-clearfix ui-corner-all')
		.find('.portlet-img-inner').css({backgroundColor:'red'})
		.end();

	$('.portlet-more').click(function(){showMain();return false;});
	$('.portlet-register').button().click(function(){showMain();});
}
</script>
</head>

<body class="paper">
	<div class="page">
		<!-- header -->
		<div class="header">
			<div class="headline">
				<div class="headline-txt">Online Registration</div>
				Register now to take class today!
			</div>
			<div class="right_brain">
				<button class="register-for-more">Show Class List</button>
				<button id="show-my-students" style="<?php if (!$user_has_students) echo 'display:none;'?>"> Show My Students </button>
				<button id="logout"> Logout </button>
<?php if (getSession('roleID') >= 2) {?>
				<button id="reports"> Reports </button>
				<button id="data_entry"> Data Entry </button>
<?php } # admin role is 2?>
			</div>

			<div class="portlet filter_level">
				<div class="portlet-header">Search</div>
				<div class="portlet-content">
					<span class="txt_filter_level">Choose level to filter:</span>
					<select id="sel_filterLevel">
						<option value="all">Show all levels</option>
<?php 
$sql = "SELECT * FROM levels";
$results_l = $mysqli->query($sql);
while ($row_l = mysqli_fetch_array($results_l)) {
	echo '
						<option value="'.$row_l['levelID'].'">'.$row_l['level_name'].'</option>';
}
?>
					</select>
				</div>
			</div>
		</div>

		<!-- leftnav -->
		<div class="lhn">
			<div class="scrollpanel">

			</div>
		</div>

		<!-- main content -->
		<div class="main">
			<div class="scrollpanel">
				
			</div>
		</div>
		
		<div class="headline-shadow"></div>
	</div>
</body>
</html>
