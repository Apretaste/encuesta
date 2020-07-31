<?php

use Apretaste\Money;
use Apretaste\Level;
use Apretaste\Person;
use Apretaste\Amulets;
use Apretaste\Request;
use Apretaste\Response;
use Apretaste\Challenges;
use Apretaste\Notifications;
use Framework\Database;

class Service
{
	/**
	 * Main service
	 *
	 * @paramRequest$request
	 * @paramResponse$response
	 *
	 * @throws \Exception
	 * @author salvipascual
	 */
	public function _main(Request $request, Response &$response)
	{
		// redirect to the list of surveys opened
		$this->_lista($request, $response);
	}

	/**
	 * Edit the person's profile
	 *
	 * @author salvipascual
	 */
	public function _perfil(Request $request, Response &$response)
	{
		// create content array
		$content = [
			'gender_selected' => $request->person->gender ?? '',
			'year_selected' => $request->person->yearOfBirth ?? '',
			'race_selected' => $request->person->skin ?? '',
			'occupation_selected' => $request->person->occupation ?? '',
			'education_selected' => $request->person->education ?? '',
			'province_selected' => $request->person->provinceCode ?? '',
			'marital_selected' => $request->person->maritalStatus ?? ''
		];

		// prepare response for the view
		$response->setTemplate('profile.ejs', $content);
	}

	/**
	 * Get the list of surveys opened
	 *
	 * @paramRequest$request
	 * @paramResponse$response
	 *
	 * @throws \Framework\Alert
	 * @author salvipascual
	 */
	public function _lista(Request $request, Response $response)
	{
		// ensure your profile is completed
		if ($this->isProfileIncomplete($request->person)) {
			$this->_perfil($request, $response);
			return;
		}

		// subqueries for the opened surveys
		$sql_survey_datails = '
			SELECT
				_survey.id AS survey,
				_survey.title AS survey_title,
				_survey.deadline as survey_deadline,
				_survey.value as survey_value
			FROM _survey
			WHERE _survey.active = 1 AND _survey.deadline >= CURRENT_DATE';

		$sql_survey_total_questions = '
			SELECT COUNT(_survey_question.id) AS total
			FROM _survey_question
			WHERE _survey_question.survey =  subq.survey
			GROUP BY _survey_question.survey';

		$sql_survey_total_choosen = "
			SELECT total FROM (
				SELECT COUNT(_survey_answer_choosen.answer) AS total, (
					SELECT _survey_question.survey
					FROM _survey_question
					WHERE _survey_question.id = (
						SELECT _survey_answer.question
						FROM _survey_answer
						WHERE _survey_answer.id = _survey_answer_choosen.answer)
					) AS survey_id
				FROM _survey_answer_choosen
				WHERE _survey_answer_choosen.person_id = '{$request->person->id}'
				GROUP BY survey_id
			) AS subq2
			WHERE survey_id = subq.survey";

		// get list of opened surveys
		$surveys = Database::query("
			SELECT
				survey,
				survey_title AS title,
				survey_deadline AS deadline,
				survey_value AS value
			FROM ($sql_survey_datails) AS subq
			WHERE coalesce(($sql_survey_total_questions),0) > coalesce(($sql_survey_total_choosen),0);");

		// message if there are not opened surveys
		if (empty($surveys)) {
			$response->setTemplate('message.ejs', [
				'header' => 'No hay encuestas',
				'icon' => 'sentiment_very_dissatisfied',
				'text' => 'Lo siento pero no tenemos ninguna encuesta para usted en este momento. Estamos trabajamos en agregar encuestas a nuestra lista, por favor vuelva a revisar en unos días. Muchas gracias por estar pendiente.',
				'button' => ['href' => 'ENCUESTA TERMINADAS', 'caption' => 'Ver Terminadas'],
			]);
			return;
		}

		// send response to the user
		$response->setTemplate('list.ejs', ['surveys' => $surveys]);
	}

	/**
	 * Display a list of previous surveys
	 *
	 * @param Request $request
	 * @param Response $response
	 * @author salvipascual
	 */
	public function _terminadas(Request $request, Response $response)
	{
		// ensure your profile is completed
		if ($this->isProfileIncomplete($request->person)) {
			$this->_perfil($request, $response);
			return;
		}

		//get the list of surveys answered
		$completed = Database::query("
			SELECT person_id, responses, total, C.title, C.value, A.completed
			FROM (SELECT person_id, survey, COUNT(survey) AS responses, MAX(date_choosen) AS completed FROM _survey_answer_choosen WHERE person_id='{$request->person->id}' GROUP BY survey) A
			LEFT JOIN (SELECT survey, COUNT(survey) AS total FROM _survey_question GROUP BY survey) B
			ON A.survey = B.survey
			LEFT JOIN (SELECT * FROM _survey) C
			ON A.survey = C.id
			WHERE responses = total");

		// message if there are not opened surveys
		if (empty($completed)) {
			return $response->setTemplate('message.ejs', [
				'header' => 'No ha completado encuestas',
				'icon' => 'sentiment_neutral',
				'text' => 'Usted aún no ha completado ninguna encuesta. Cuando responda por primera vez se agregará a esta lista.',
				'button' => ['href' => 'ENCUESTA', 'caption' => 'Ver Encuestas'],
			]);
		}

		// send response to the user
		$response->setCache('day');
		$response->setTemplate('completed.ejs', ['surveys' => $completed]);
	}

	/**
	 * Display a survey to answer it
	 *
	 * @param Request $request
	 * @param Response $response
	 * @author salvipascual
	 */
	public function _ver(Request $request, Response $response)
	{
		// ensure your profile is completed
		if ($this->isProfileIncomplete($request->person)) {
			return $this->_perfil($request, $response);
		}

		// get the survey details
		$res = Database::query("
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
				(SELECT COUNT(person_id)
					FROM _survey_answer_choosen
					WHERE person_id = '{$request->person->id}'
					AND answer = _survey_answer.id
				) AS choosen
			FROM _survey
			INNER JOIN _survey_answer
			INNER JOIN _survey_question
			ON _survey_question.survey = _survey.id
			AND _survey_answer.question = _survey_question.id
			WHERE _survey.id = {$request->input->data->id}
			ORDER BY _survey_question.id, _survey_answer.id");

		// do not process invalid responses
		if (empty($res) || !isset($res[0])) {
			return;
		}

		// message if the survey was already completed
		if ($this->isSurveyComplete($res[0]->survey, $request->person->id)) {
			return $response->setTemplate('message.ejs', [
				'header' => '¡Genial! Ya respondió esta encuesta',
				'icon' => 'thumb_up',
				'text' => "Usted ya respondió esta encuesta y como agradecimiento le agregamos §{$res[0]->survey_value} a su crédito. Recuerde que sus respuestas contribuirán a construir una mejor Cuba para todos. Muchas gracias por su participación.",
				'button' => ['href' => 'ENCUESTA', 'caption' => 'Otras encuestas'],
			]);
		}

		// create a new Survey object
		$survey = new stdClass();
		$survey->id = $res[0]->survey;
		$survey->title = $res[0]->survey_title;
		$survey->details = $res[0]->survey_details;
		$survey->value = $res[0]->survey_value;
		$survey->questions = [];

		// create the list of questions
		foreach ($res as $r) {
			// create the question if it does not exist
			$question = end($survey->questions);

			if (empty($question) || $question->id != $r->question) {
				$question = new stdClass();
				$question->id = $r->question;
				$question->title = $r->question_title;
				$question->answers = [];
				$question->completed = false;
				$survey->questions[] = $question;
			}

			// create the answers for the question
			$answer = new stdClass();
			$answer->id = $r->answer;
			$answer->title = $r->answer_title;
			$answer->choosen = $r->choosen == '1';

			// mark question as completed
			if ($answer->choosen) {
				$question->completed = true;
			}

			// assign the answer to the question
			$question->answers[] = $answer;
		}

		// send response to the view
		$response->setTemplate('survey.ejs', ['survey' => $survey]);
	}

	/**
	 * Responds a survey
	 *
	 * @return void
	 * @throws \Exception
	 * @author salvipascual
	 */
	public function _responder(Request $request, Response &$response)
	{
		// do not continue if data is not passed
		if (empty($request->input->data->answers)) {
			return;
		}

		// get the question IDs for the answers received
		$answers = implode(',', $request->input->data->answers);
		$questions = Database::query("SELECT question FROM _survey_answer WHERE id IN ($answers)");

		// get the survey
		$survey = Database::query("
			SELECT A.id, A.value, A.title
			FROM _survey A
			JOIN _survey_question B
			ON A.id = B.survey
			WHERE B.id = {$questions[0]->question}")[0];

		if (!isset($survey->id)) {
			$response->setTemplate('message.ejs', [
				'header' => 'Encuesta no encontrada',
				'icon' => 'sentiment_very_dissatisfied',
				'text' => 'Hubo un error procesando su respuesta. Es posible que la app esté desincronizada. Por favor abra los ajustes y borre los datos guardados. Si el problema persiste, contacte al soporte técnico.',
				'button' => ['href' => 'ENCUESTA', 'caption' => 'Ver Encuestas'],
			]);
			return;
		}

		// do not let the user get double credits
		if ($this->isSurveyComplete($survey->id, $request->person->id)) {
			$response->setTemplate('message.ejs', [
				'header' => 'Encuesta completada',
				'icon' => 'sentiment_very_satisfied',
				'text' => 'Esta encuesta ha sido completada por usted anteriormente y se le ha asignado el crédito. No es necesario hacer nada mas. ¡Gracias!',
				'button' => ['href' => 'ENCUESTA', 'caption' => 'Ver Encuestas'],
			]);
			return;
		}

		// prepare the data to be sent in one large query
		$values = [];
		for ($i = 0, $iMax = count($request->input->data->answers); $i < $iMax; $i++) {
			$questionID = $questions[$i]->question;
			$answerID = $request->input->data->answers[$i];
			$values[] = "('{$request->person->id}', '{$request->person->email}', {$survey->id}, $questionID, $answerID)";
		}
		$values = implode(',', $values);
		$startTime = $request->input->data->startTime ?? date("Y-m-d h:i:s");

		// replace all old answers by the new answers in one query
		Database::query("
			START TRANSACTION;
			DELETE FROM _survey_answer_choosen WHERE person_id = '{$request->person->id}' AND survey = '{$survey->id}';
			INSERT INTO _survey_answer_choosen (person_id, email, survey, question, answer) VALUES $values;
			DELETE FROM _survey_done WHERE person_id = '{$request->person->id}' AND survey_id = '{$survey->id}';
			INSERT INTO _survey_done (survey_id, person_id, country, province, city, start_time) 
			    SELECT {$survey->id}, id, country, province, city, '$startTime' as start_time FROM person
			    WHERE id = {$request->person->id}; 
			COMMIT;");

		// add § for the user if all questions were completed
		if ($this->isSurveyComplete($survey->id, $request->person->id)) {
			$msg = '';

			// double credits if you are level Esmeralda or higer
			if ($request->person->levelCode >= Level::ESMERALDA) {
				$survey->value *= 2;
				$msg .= 'Gracias a su nivel, los créditos se han duplicado. ';
			}

			// run powers for amulet ENCUESTAX2
			if (Amulets::isActive(Amulets::ENCUESTAX2, $request->person->id)) {
				$survey->value *= 2;
				$msg .= 'Los poderes del amuleto del Druida duplicaron los créditos. ';
			}

			// run powers for amulet ENCUESTAS
			if (Amulets::isActive(Amulets::ENCUESTAS, $request->person->id)) {
				// calculate a random number
				$seed = rand(1, 6);

				// 3 tickets para la rifa
				if ($seed === 1) {
					Database::query("INSERT INTO ticket (origin,person_id) VALUES ('AMULET',{$request->person->id}),('AMULET',{$request->person->id}),('AMULET',{$request->person->id})");
					$msg .= 'Los poderes del amuleto del Druida te regalan 3 tickets para la rifa';
				} // 1 ticket para la rifa
				elseif ($seed === 2) {
					Database::query("INSERT INTO ticket (origin,person_id) VALUES ('AMULET',{$request->person->id})");
					$msg .= 'Los poderes del amuleto del Druida te regalan 1 ticket para la rifa';
				} // 3 flores
				elseif ($seed === 3) {
					Database::query("UPDATE _piropazo_people SET flowers=flowers+3 WHERE id_person={$request->person->id}");
					$msg .= 'Los poderes del amuleto del Druida te regalan 3 flores para Piropazo';
				} // 1 flor
				elseif ($seed === 4) {
					Database::query("UPDATE _piropazo_people SET flowers=flowers+1 WHERE id_person={$request->person->id}");
					$msg .= 'Los poderes del amuleto del Druida te regalan 1 flor para Piropazo';
				} // 3 corazones
				elseif ($seed === 5) {
					Database::query("UPDATE _piropazo_people SET crowns=crowns+3 WHERE id_person={$request->person->id}");
					$msg .= 'Los poderes del amuleto del Druida te regalan 3 corazones para Piropazo';
				} // 1 de crédito
				else {
					$survey->value++;
					$msg .= 'Los poderes del amuleto del Druida te regalan §1 de crédito';
				}
			}

			// transfer the funds
			Money::send(
				Money::BANK,
				$request->person->id,
				$survey->value,
				'Encuesta completada'
			);

			// add a new response to the counter
			Database::query("UPDATE _survey SET answers=answers+1 WHERE id='{$survey->id}'");

			// notify the user
			$msg = "Ha ganado §{$survey->value} por contestar la encuesta {$survey->title}. $msg";

			Notifications::alert($request->person->id, $msg, 'attach_money', '{"command":"ENCUESTA TERMINADAS"}');

			// complete the challenge
			Challenges::complete('fill-survey', $request->person->id);

			// add the experience
			Level::setExperience('FINISH_SURVEY', $request->person->id);
		}
	}

	/**
	 * Check if the survey is completed
	 *
	 * @param String $surveyID
	 * @param String $personID
	 *
	 * @return Boolean, true if survey is 100% completed
	 * @throws \Framework\Alert
	 * @author salvipascual
	 */
	private function isSurveyComplete($surveyID, $personID)
	{
		$res = Database::query("
			SELECT * FROM
			(SELECT COUNT(survey) as total FROM _survey_question WHERE survey='$surveyID') A,
			(SELECT COUNT(answer) as answers FROM _survey_answer_choosen WHERE survey='$surveyID' AND person_id='$personID') B");

		return $res[0]->total * 1 === $res[0]->answers * 1;
	}

	/**
	 * Check if the profile is completed or not
	 *
	 * @return Boolean, true if profile is incomplete
	 * @author salvipascual
	 */
	private function isProfileIncomplete(Person $person)
	{
		return $person->age < 5
			|| $person->age > 130
			|| empty($person->province)
			|| empty($person->gender)
			|| empty($person->skin)
			|| empty($person->maritalStatus)
			|| empty($person->occupation)
			|| empty($person->education);
	}
}
