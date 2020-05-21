// values for the enums
var gender = {'M':'Hombre', 'F':'Mujer'};
var education = {'PRIMARIO':'Primario', 'SECUNDARIO':'Secundario', 'TECNICO':'Técnico', 'UNIVERSITARIO':'Universitario', 'POSTGRADUADO':'Postgraduado', 'DOCTORADO':'Doctorado', 'OTRO':'Otro'};
var province = {'PINAR_DEL_RIO':'Pinar del Río','LA_HABANA':'La Habana','ARTEMISA':'Artemisa','MAYABEQUE':'Mayabeque','MATANZAS':'Matanzas','VILLA_CLARA':'Villa Clara','CIENFUEGOS':'Cienfuegos','SANCTI_SPIRITUS':'Sancti Spiritus','CIEGO_DE_AVILA':'Ciego de Ávila','CAMAGUEY':'Camagüey','LAS_TUNAS':'Las Tunas','HOLGUIN':'Holguín','GRANMA':'Granma','SANTIAGO_DE_CUBA':'Santiago de Cuba','GUANTANAMO':'Guantánamo','ISLA_DE_LA_JUVENTUD':'Isla de la Juventud'};
var marital = {'SOLTERO':'Soltero', 'SALIENDO':'Saliendo', 'COMPROMETIDO':'Comprometido', 'CASADO':'Casado', 'DIVORCIADO':'Divorciado', 'VIUDO':'Viudo'};
var race = {'NEGRO':'Negro', 'BLANCO':'Blanco', 'MESTIZO':'Mestizo', 'OTRO':'Otro'};
var occupation = {'AMA_DE_CASA' :'Ama de casa', 'ESTUDIANTE':'Estudiante', 'EMPLEADO_PRIVADO':'Empleado Privado', 'EMPLEADO_ESTATAL':'Empleado Estatal', 'INDEPENDIENTE':'Trabajador Independiente', 'JUBILADO':'Jubilado', 'DESEMPLEADO':'Desempleado'};

// run when the service loads
$(document).ready(function () {
	$('select').formSelect();
	$('.tabs').tabs();
});

// get list of years for the age dropdown
function getYears() {
	var year = new Date().getFullYear();
	var years = [];

	for (var i = year - 18; i >= year - 90; i--) {
		years.push(i);
	}

	return years;
} 

// submit the profile informacion
function submitProfileData() {
	// get the array of fields
	var fields = ['gender', 'year_of_birth', 'skin', 'highest_school_level', 'province', 'marital_status', 'occupation'];

	// get the information for all the fields
	var data = {};
	for (var i = 0; i < fields.length; i++) {
		var field = fields[i];
		var value = $('#' + field).val().trim();
		if (value) data[field] = value;
	}

	// don't let you pass without filling all the fields
	if (Object.keys(data).length < 7) {
		M.toast({html: 'Por favor complete todos los campos antes de continuar'});
		return false;
	}

	// save information in the backend
	apretaste.send({
		"command": "PERFIL UPDATE",
		"data": data,
		"redirect": false
	});

	// re-open the survey
	apretaste.send({
		command: "ENCUESTA"
	});
}

// accept the terms of a survey
function acceptSurvey() {
	// get values for both checkboxes
	var acceptAdult = $('#accept-adult').prop('checked');
	var acceptParticipate = $('#accept-participate').prop('checked');

	// check if both are checked
	if(!acceptAdult || !acceptParticipate) {
		M.toast({html: 'Debe aceptar ambas opciones para continuar'});
		return;
	}

	// scroll top
	$("html, body").animate({
		scrollTop: $('#questions-section').offset().top - 100
	}, 1000); 

	// show the survey
	$('#consent-section').hide();
	$('#questions-section').show();
}

// submit a survey once completed
function submitSurvey() {
	// variable to save the ID of the responses
	var answers = [];

	$('.question').each(function () {
		// check if the item was checked and return the answer ID
		var item = $(this).find("input[name='" + this.id + "']:checked").val();
		answers.push(item); 

		// if no checked, scroll to it and clean the responses
		if (item == undefined) {
			// display a message
			M.toast({html: 'Por favor responda todas las preguntas'}); 

			// scroll to the question
			$("html, body").animate({
				scrollTop: $(this).offset().top - 100
			}, 1000); 

			// clean the responses list to stop sending
			answers = [];
			return false;
		}
	});

	// send information to the backend
	if (answers.length) {
		apretaste.send({
			command: "ENCUESTA RESPONDER",
			data: {answers: answers},
			redirect: false
		});

		// show the end message
		$('#questions-section').hide();
		$('#message-section').show();
	}
}

// Polyfill Functions

if (!Object.keys) {
	Object.keys = function () {
		'use strict';

		var hasOwnProperty = Object.prototype.hasOwnProperty,
			hasDontEnumBug = !{
				toString: null
			}.propertyIsEnumerable('toString'),
			dontEnums = ['toString', 'toLocaleString', 'valueOf', 'hasOwnProperty', 'isPrototypeOf', 'propertyIsEnumerable', 'constructor'],
			dontEnumsLength = dontEnums.length;

		return function (obj) {
			if (_typeof(obj) !== 'object' && (typeof obj !== 'function' || obj === null)) {
				throw new TypeError('Object.keys called on non-object');
			}

			var result = [],
				prop,
				i;

			for (prop in obj) {
				if (hasOwnProperty.call(obj, prop)) {
					result.push(prop);
				}
			}

			if (hasDontEnumBug) {
				for (i = 0; i < dontEnumsLength; i++) {
					if (hasOwnProperty.call(obj, dontEnums[i])) {
						result.push(dontEnums[i]);
					}
				}
			}

			return result;
		};
	}();
}
