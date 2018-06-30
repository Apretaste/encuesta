<?php

class Encuesta extends Service
{
	/**
	 * Get the list of surveys opened
	 *
	 * @param Request $request
	 * @return Response
	 */
	public function _main (Request $request)
	{
		// ensure your profile is completed
		$person = Utils::getPerson($request->email);
		if(
			$person->age < 5 || $person->age > 130 ||
			empty($person->sexual_orientation) ||
			empty($person->gender) ||
			empty($person->province) ||
			empty($person->skin) ||
			empty($person->marital_status) ||
			empty($person->highest_school_level) ||
			empty($person->occupation) ||
			empty($person->religion)
		) return $this->_perfil($request);

		// get the ID of the survey to open
		$res_id = intval(trim($request->query));

		// if no survey ID passed, show list of surveys
		if ($res_id === 0) return $this->defaultResponse($request);

		// else show the survey itself
		return $this->surveyResponse($request, $res_id);
	}

	/**
	 * Edit the person's profile
	 *
	 * @param Request $request
	 * @return Response
	 */
	public function _perfil (Request $request)
	{
		// get the person to edit profile
		$person = $this->utils->getPerson($request->email);
		if (empty($person)) return new Response();

		// get the person's province
		$person->province = str_replace("_", " ", $person->province);

		// get the person's gender
		if ($person->gender == 'M') $person->gender = "Masculino";
		if ($person->gender == 'F') $person->gender = "Femenino";

		// create date for the selects
		$options = new stdClass();

		// gender
		$options->gender = json_encode([
			["caption"=>"Masculino", "href"=>"PERFIL SEXO MASCULINO"],
			["caption"=>"Femenino", "href"=>"PERFIL SEXO FEMENINO"]
		]);

		// sexual orientation
		$options->sexual_orientation = json_encode([
			["caption"=>"Hetero", "href"=>"PERFIL ORIENTACION HETERO"],
			["caption"=>"Gay", "href"=>"PERFIL ORIENTACION HOMO"],
			["caption"=>"Bi", "href"=>"PERFIL ORIENTACION BI"]
		]);

		// skin
		$options->skin = json_encode([
			["caption"=>"Blanco", "href"=>"PERFIL PIEL BLANCO"],
			["caption"=>"Negro", "href"=>"PERFIL PIEL NEGRO"],
			["caption"=>"Mestizo", "href"=>"PERFIL PIEL MESTIZO"],
			["caption"=>"Otro", "href"=>"PERFIL PIEL OTRO"]
		]);

		// marital status
		$options->marital_status = json_encode([
			["caption"=>"Soltero", "href"=>"PERFIL ESTADO SOLTERO"],
			["caption"=>"Saliendo", "href"=>"PERFIL ESTADO SALIENDO"],
			["caption"=>"Comprometido", "href"=>"PERFIL ESTADO COMPROMETIDO"],
			["caption"=>"Casado", "href"=>"PERFIL ESTADO CASADO"]
		]);

		// highest school level
		$options->highest_school_level = json_encode([
			["caption"=>"Primario", "href"=>"PERFIL NIVEL PRIMARIO"],
			["caption"=>"Secundario", "href"=>"PERFIL NIVEL SECUNDARIO"],
			["caption"=>"Tecnico", "href"=>"PERFIL NIVEl TECNICO"],
			["caption"=>"Universitario", "href"=>"PERFIL NIVEl UNIVERSITARIO"],
			["caption"=>"Postgraduado", "href"=>"PERFIL NIVEl POSTGRADUADO"],
			["caption"=>"Doctorado", "href"=>"PERFIL NIVEl DOCTORADO"],
			["caption"=>"Otro", "href"=>"PERFIL NIVEl OTRO"]
		]);

		// occupation
		$options->occupation = json_encode([
			["caption"=>"Trabajador estatal", "href"=>"PERFIL PROFESION Trabajador estatal"],
			["caption"=>"Cuentapropista", "href"=>"PERFIL PROFESION Cuentapropista"],
			["caption"=>"Estudiante", "href"=>"PERFIL PROFESION Estudiante"],
			["caption"=>"Ama de casa", "href"=>"PERFIL PROFESION Ama de casa"],
			["caption"=>"Desempleado", "href"=>"PERFIL PROFESION Desempleado"],
		]);

		// province
		$options->province = json_encode([
			["caption"=>"Pinar del Rio", "href"=>"PERFIL PROVINCIA PINAR_DEL_RIO"],
			["caption"=>"La Habana", "href"=>"PERFIL PROVINCIA LA_HABANA"],
			["caption"=>"Artemisa", "href"=>"PERFIL PROVINCIA ARTEMISA"],
			["caption"=>"Mayabeque", "href"=>"PERFIL PROVINCIA MAYABEQUE"],
			["caption"=>"Matanzas", "href"=>"PERFIL PROVINCIA MATANZAS"],
			["caption"=>"Villa Clara", "href"=>"PERFIL PROVINCIA VILLA CLARA"],
			["caption"=>"Cienfuegos", "href"=>"PERFIL PROVINCIA CIENFUEGOS"],
			["caption"=>"Sancti Spiritus", "href"=>"PERFIL PROVINCIA SANCTI_SPIRITUS"],
			["caption"=>"Ciego de Avila", "href"=>"PERFIL PROVINCIA CIEGO_DE_AVILA"],
			["caption"=>"Camaguey", "href"=>"PERFIL PROVINCIA CAMAGUEY"],
			["caption"=>"Las Tunas", "href"=>"PERFIL PROVINCIA LAS_TUNAS"],
			["caption"=>"Holguin", "href"=>"PERFIL PROVINCIA HOLGUIN"],
			["caption"=>"Granma", "href"=>"PERFIL PROVINCIA GRANMA"],
			["caption"=>"Santiago de Cuba", "href"=>"PERFIL PROVINCIA SANTIAGO_DE_CUBA"],
			["caption"=>"Guantanamo", "href"=>"PERFIL PROVINCIA GUANTANAMO"],
			["caption"=>"Isla de la Juventud", "href"=>"PERFIL PROVINCIA ISLA_DE_LA_JUVENTUD"]
		]);

		// religion
		$options->religion = json_encode([
			["caption"=>"Cristianismo", "href"=>"PERFIL RELIGION CRISTIANISMO"],
			["caption"=>"Catolicismo", "href"=>"PERFIL RELIGION CATOLICISMO"],
			["caption"=>"Yoruba", "href"=>"PERFIL RELIGION YORUBA"],
			["caption"=>"Protestante", "href"=>"PERFIL RELIGION PROTESTANTE"],
			["caption"=>"Santero", "href"=>"PERFIL RELIGION SANTERO"],
			["caption"=>"Abakua", "href"=>"PERFIL RELIGION ABAKUA"],
			["caption"=>"Budismo", "href"=>"PERFIL RELIGION BUDISMO"],
			["caption"=>"Islam", "href"=>"PERFIL RELIGION ISLAM"],
			["caption"=>"Ateismo", "href"=>"PERFIL RELIGION ATEISMO"],
			["caption"=>"Agnosticismo", "href"=>"PERFIL RELIGION AGNOSTICISMO"],
			["caption"=>"Secularismo", "href"=>"PERFIL RELIGION SECULARISMO"],
			["caption"=>"Otra", "href"=>"PERFIL RELIGION OTRA"]
		]);

		// prepare response for the view
		$response = new Response();
		$response->setResponseSubject('Edite su perfil');
		$response->createFromTemplate('profile.tpl', ["person"=>$person, "options"=>$options]);
		return $response;
	}

	/**
	 * Subservice Responder
	 *
	 * @param Request $request
	 * @return Response
	 */
	public function _responder(Request $request)
	{
		$answerID = intval(trim($request->query));

		// check if the answer is valid
		$answer = Connection::query("
			SELECT *,
				(SELECT survey
				 FROM _survey_question
				 WHERE _survey_question.id = _survey_answer.question
				) AS survey_id
			FROM _survey_answer
			WHERE id = $answerID");
		if ($answer == false || ! isset($answer[0]) || empty($answer)) return new Response();

		$resID = $answer[0]->survey_id;
		$questionID = $answer[0]->question;

		// check if person hasen't responded that question already
		$r = Connection::query("
			SELECT *
			FROM _survey_answer_choosen
			WHERE email = '{$request->email}'
			AND question = {$questionID};");
		if (isset($r[0])){
			Connection::query("UPDATE _survey_answer_choosen SET answer={$answerID} WHERE question={$questionID} AND email='{$request->email}'");
			return new Response();
		} 

		// insert the answer into the database
		Connection::query("INSERT INTO _survey_answer_choosen (email,survey,question,answer) VALUES ('{$request->email}',$resID,$questionID,$answerID)");

		// if that question answered the whole survey, add §
		if ($this->isSurveyComplete($request->email, $resID))
		{
			// get the credit to add
			$res = Connection::query("SELECT title,value FROM _survey WHERE id='$resID'");
			$credit = $res[0]->value;
			$title = $res[0]->title;

			// add credit to the user account
			Connection::query("UPDATE person SET credit=credit+$credit WHERE email='{$request->email}'");

			// add the counter of times the survey was answered
			Connection::query("UPDATE _survey SET answers=answers+1 WHERE id='$resID'");
		}

		return new Response();
	}

	/**
	 * Check if the survey is completed
	 *
	 * @author salvipascual
	 * @param String $email
	 * @param String $resID
	 * @return Boolean, true if survey is 100% completed
	 * */
	private function isSurveyComplete($email, $resID)
	{
		$res = Connection::query("
			SELECT * FROM
			(SELECT COUNT(survey) as total FROM _survey_question WHERE survey='$resID') A,
			(SELECT COUNT(answer) as answers FROM _survey_answer_choosen WHERE survey='$resID' AND email='$email') B");

		return $res[0]->total === $res[0]->answers;
	}

	/**
	 *
	 *
	 * @param Request $request
	 * @return Response
	 */
	private function defaultResponse ($request)
	{
		// get list of opened surveys
		$sql_survey_datails = "
			SELECT
				_survey.id AS survey,
				_survey.title AS survey_title,
				_survey.deadline as survey_deadline,
				_survey.value as survey_value
			FROM _survey
			WHERE _survey.active = 1 AND _survey.deadline >= CURRENT_DATE";
		$sql_survey_total_questions = "
			SELECT COUNT(_survey_question.id) AS total
			FROM _survey_question
			WHERE _survey_question.survey =  subq.survey
			GROUP BY _survey_question.survey";
		$sql_survey_total_choosen = "
			SELECT total FROM (
				SELECT COUNT(_survey_answer_choosen.answer) as total, (
					SELECT _survey_question.survey
					FROM _survey_question
					WHERE _survey_question.id = (
						SELECT _survey_answer.question
						FROM _survey_answer
						WHERE _survey_answer.id = _survey_answer_choosen.answer)
					) as survey_id
				FROM _survey_answer_choosen
				WHERE _survey_answer_choosen.email = '{$request->email}'
				GROUP BY survey_id
			) AS subq2
			WHERE survey_id = subq.survey";
		$opened = "
			SELECT
				survey,
				survey_title as title,
				survey_deadline as deadline,
				coalesce(($sql_survey_total_choosen),0) / ($sql_survey_total_questions) * 100 as completion,
				survey_value as value
			FROM ($sql_survey_datails) as subq
			WHERE coalesce(($sql_survey_total_questions),0) > coalesce(($sql_survey_total_choosen),0);";

		//get the list of surveys answered
		$finished = "
			SELECT email, responses, total, C.title, C.value, A.inserted
			FROM (SELECT email, survey, COUNT(survey) as responses, MAX(date_choosen) AS inserted FROM _survey_answer_choosen WHERE email='{$request->email}' GROUP BY survey) A
			LEFT JOIN (SELECT survey, COUNT(survey) as total FROM _survey_question GROUP BY survey) B
			ON A.survey = B.survey
			LEFT JOIN (SELECT * FROM _survey) C
			ON A.survey = C.id
			WHERE responses = total";

		// run both queries
		$ress = Connection::query($opened);
		$finished = Connection::query($finished);

		// send response to the user
		$response = new Response();
		$response->setResponseSubject(count($ress) > 0 ? "Encuestas activas" : "No tienes encuestas que responder");
		$response->createFromTemplate('basic.tpl', array('surveys' => $ress, 'finished' => $finished));
		return $response;
	}

	/**
	 * Return Survey response
	 *
	 * @param Request $request
	 * @param integer $res_id
	 * @return Response
	 */
	private function surveyResponse($request, $res_id)
	{
		$res = $this->getSurveyDetails($request->email, $res_id);

		// do not process invalid responses
		if (empty($res) || ! isset($res[0])) return new Response();

		// create a new Survey object
		$survey = new stdClass();
		$survey->id = $res[0]->survey;
		$survey->title = $res[0]->survey_title;
		$survey->details = $res[0]->survey_details;
		$survey->value = $res[0]->survey_value;
		$survey->completed = true;
		$survey->questions = array();

		foreach ($res as $r)
		{
			// create the question if it does not exist
			if ( ! isset($survey->questions[$r->question]))
			{
				$question = new stdClass();
				$question->id = $r->question;
				$question->title = $r->question_title;
				$question->answers = array();
				$question->completed = false;
				$survey->questions[$r->question] = $question;
			}

			// create the answers for the question
			$answer = new stdClass();
			$answer->id = $r->answer;
			$answer->title = $r->answer_title;
			$answer->choosen = $r->choosen == '1';

			// mark question as completed if it was responded
			if ($answer->choosen) $survey->questions[$r->question]->completed = true;

			// assign the answer to the question
			$survey->questions[$r->question]->answers[] = $answer;
		}

		// if all questions were responded, mark the survey as completed
		foreach ($survey->questions as $q) {
			if(empty($q->completed)) {
				$survey->completed = false;
				break;
			}
		}

		// send response to the view
		$response = new Response();
		$response->setResponseSubject('Encuesta: ' . $survey->title);
		$response->createFromTemplate('survey.tpl', array('survey' => $survey));
		return $response;
	}

	/**
	 * Return details of survey
	 *
	 * @param string $email
	 * @param integer $res_id
	 * @return Array
	 */
	private function getSurveyDetails ($email, $res_id)
	{
		return Connection::query("
			SELECT
				_survey.id AS survey,
				_survey.title AS survey_title,
				_survey.value AS survey_value,
				_survey.details AS survey_details,
				_survey.active AS survey_active,
				_survey_question.id AS question,
				_survey_question.title AS question_title,
				_survey_answer.id AS answer,
				_survey_answer.title AS answer_title,
				(SELECT COUNT(email)
					FROM _survey_answer_choosen
					WHERE email = '$email'
					AND answer = _survey_answer.id
				) AS choosen
			FROM _survey
			INNER JOIN _survey_answer
			INNER JOIN _survey_question
			ON _survey_question.survey = _survey.id
			AND _survey_answer.question = _survey_question.id
			WHERE _survey.id = $res_id
			ORDER BY _survey_question.id, _survey_answer.id");
	}
}
