//
// ON LOAD FUNCTIONS
//

$(document).ready(function(){
	$('select').formSelect();
});

//
// FUCTIONS FOR THE SERVICE
//

// formats a date and time
function formatDateTime(dateStr) {
	var months = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
	var date = new Date(dateStr);
	var month = date.getMonth();
	var day = date.getDate().toString().padStart(2, '0');
	var hour = (date.getHours() < 12) ? date.getHours() : date.getHours() - 12;
	var minutes = date.getMinutes();
	if (minutes < 10) minutes = '0' + minutes;
	var amOrPm = (date.getHours() < 12) ? "am" : "pm";
	return day + ' de ' + months[month] + ' a las ' + hour + ':' + minutes + amOrPm;
}

// formats a date and time
function formatDate(dateStr) {
	var months = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
	var date = new Date(dateStr);
	var month = date.getMonth();
	var day = date.getDate().toString().padStart(2, '0');
	return day + ' de ' + months[month] + ' del ' + date.getFullYear();
}

// get list of years fort the age
function getYears() {
	var year = new Date().getFullYear();
	var years = [];
	for (let i=year-15; i>=year-90; i--) years.push(i);
	return years;
}

// submit the profile informacion 
function submitProfileData() {
	// get the array of fields and  
	var fields = ['gender','year_of_birth','skin','highest_school_level','province'];

	// create the JSON of data
	var data = new Object;
	fields.forEach(function(field) {
		var value = $('#'+field).val().trim();
		if(value) data[field] = value;
	});

	// don't let you pass without filling all the fields
	if(Object.keys(data).length < 5) {
		M.toast({html: 'Por favor complete todos los campos antes de continuar'});
		return false;
	}

	// save information in the backend
	apretaste.send({
		"command": "PERFIL UPDATE",
		"data": data,
		"redirect": false,
		callback: {
			name: "callbackReloadEncuesta",
			data: {}
		}
	});
}

// submit a survey once completed
function submitSurvey() {
	// variable to save the ID of the responses
	var answers = [];

	$('.question').each(function() {
		// check if the item was checked and return the answer ID
		var item = $(this).find("input[name='"+this.id+"']:checked").val();
		answers.push(item);

		// if no checked, scroll to it and clean the responses
		if(item == undefined) {
			// display a message
			M.toast({html: 'Por favor responda todas las preguntas'});

			// scroll to the question
			$("html, body").animate({scrollTop: $(this).offset().top - 100}, 1000);

			// clean the responses list to stop sending
			answers = [];
			return false;
		}
	});

  M.toast({html: 'Has dado ' + answers.length + ' respuestas...'});

  if(answers.length) {

    M.toast({html: 'Enviando su encuesta...'});

    // send information to the backend
		apretaste.send({
			"command": "ENCUESTA RESPONDER",
			"data": {answers: answers},
			"redirect": false
		});

		// display the DONE message
		$('#list').hide();
		$('#msg').show();
	}
}

//
// CALLBACKS
//

// redirect to the survey page
function callbackReloadEncuesta() {
	apretaste.send({command: "ENCUESTA"});
}

//
// PROTOTYPES
//

String.prototype.replaceAll = function(search, replacement) {
	return this.split(search).join(replacement);
};

String.prototype.firstUpper = function() {
	return this.charAt(0).toUpperCase() + this.substr(1).toLowerCase();
};
