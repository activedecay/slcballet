function student_dob(){
	if ($("input[name=student_dob]").val() == "") {
		errorCleared('student_dob');
		errorGenerated('student_dob','Student\'s DOB can\'t be blank.');
	} else {
		errorCleared('student_dob');
	}
}
function student_datepicker(){
	if ($("input[name=student_datepicker]").val() == "") {
		errorCleared('student_dob');
		errorGenerated('student_dob','Student\'s DOB can\'t be blank.');
	} else {
		errorCleared('student_dob');
	}
}
function start_date(){
	if ($("input[name=start_date]").val() == "") {
		errorCleared('start_date');
		errorGenerated('start_date','Student\'s starting date can\'t be blank.');
	} else {
		errorCleared('start_date');
	}
}
function start_datepicker(){
	if ($("input[name=start_datepicker]").val() == "") {
		errorCleared('start_date');
		errorGenerated('start_date','Student\'s starting date can\'t be blank.');
	} else {
		errorCleared('start_date');
	}
}

// these are actually functions, used elsewhere
/*function*/var gaurdian_name = createBlankValidation('gaurdian_name','Guardian\'s name can\'t be blank.');
/*function*/var gaurdian_address = createBlankValidation('gaurdian_address','Guardian\'s address can\'t be blank.');
/*function*/var gaurdian_city = createBlankValidation('gaurdian_city','Guardian\'s city can\'t be blank.');
/*function*/var student_name = createBlankValidation('student_name','Student\'s name can\'t be blank.');
/*function*/var student_address = createBlankValidation('student_address','Student\'s address can\'t be blank.');
/*function*/var student_city = createBlankValidation('student_city','Student\'s city can\'t be blank.');
/*function*/var emergency_name = createBlankValidation('emergency_name','Emergency contact\'s name can\'t be blank.');

/*function*/var student_zip = createZipValidation('student_zip','Student');
/*function*/var gaurdian_zip = createZipValidation('gaurdian_zip','Guardian');

/*function*/var student_phone = createPhoneValidation("student_phone", "Student");
/*function*/var emergency_phone = createPhoneValidation("emergency_phone", "Emergency contact");
/*function*/var gaurdian_phone = createPhoneValidation("gaurdian_phone", "Guardian");

/*function*/var gaurdianAltPhoneIsEmail = createEmailValidation("gaurdian_altphone", "Guardian");

function gaurdian_altphone() {
	if ($("input[name=gaurdian_altphone]").val() == "") {
		errorCleared("gaurdian_altphone");
		return; // valid if left blank
	} else {
		gaurdianAltPhoneIsEmail();
	}
}

function createEmailValidation(query, display) {
	return function() {
		if (!$("input[name="+query+"]").length) {
			return;
		}
		if (/^[\w-]+(?:\.[\w-]+)*@((?:[\w-]+\.)+[a-zA-Z]{2,7}|([0-9]{1,3})(\.[0-9]{1,3}){3})$/i
				.test($("input[name="+query+"]").val())) {
			// this is an email!
			errorCleared(query);
		} else {
			errorCleared(query);
			errorGenerated(query,display+"'s email not a valid email address");// display message
		}
	};
}

//query, used to find the element; display, message to display if the query result is blank.
function createBlankValidation(query, display){
	return function() {
		if ($("input[name="+query+"]").val() == "") {
			errorCleared(query);
			errorGenerated(query,display);// display message
		} else {
			errorCleared(query);
		}
	};
}
//query, used to find the element; display, message to display if the query result is blank.
function createPhoneValidation(query, display) {
	return function() {
		if (!$("input[name="+query+"]").length) {
			return;
		}
		
		if ($("input[name="+query+"]").val() == "") {
			errorCleared(query);
			errorGenerated(query,display+"'s phone can't be blank.");
			return;
		}
		
		var onlyNumbers = /.*?(\d)[^\d]*/g;
		var numbersEntered = $("input[name="+query+"]").val().replace(onlyNumbers, "$1");
		if (numbersEntered.length < 10) {
			errorCleared(query);
			errorGenerated(query,'Use at least 10 digits; characters like "() - #ext" are allowed.');
			return;
		}
		
		errorCleared(query);
	};
}
//query, used to find the element; display, message to display if the query result is blank.
function createZipValidation(query, display) {
	return function() {
		if (!$("input[name="+query+"]").length) {
			return;
		}
		
		if ($("input[name="+query+"]").val() == "") {
			errorCleared(query);
			errorGenerated(query,display+"'s zip can't be blank.");
			return;
		}
		
		var onlyNumbers = /.*?(\d*)[^\d]*/g;
		var numbersEntered = $("input[name="+query+"]").val().replace(onlyNumbers, "$1");
		if (numbersEntered.length < 5) {
			errorCleared(query);
			errorGenerated(query,'Zip code should be least 5 digits; "-" characters are allowed.');
			return;
		}
		
		errorCleared(query);
	};
}

// valid, comes from server; msg, used to display messages from the server
function editing_password_valid(valid, msg){
	if (!valid) {
		errorCleared('pass');
		errorGenerated('pass',msg);
	} else {
		errorCleared('pass');
	}
}


// helper functions
function errorGenerated(input_name, error_text) {
	$("input[name="+input_name+"]").closest(".register-infos").addClass("register-error")
		.append('<br/><span class="register-sublabel">'+error_text+'</span>')
		.find(".register-label span").html("<span class='txt-register-error'>*</span>:");
}

function errorCleared(input_name) {
	$("input[name="+input_name+"]").closest(".register-infos").removeClass("register-error")
		.find('.register-sublabel').remove().end()
		.find('br').remove().end()
		.find(".register-label span").html(":");
}
