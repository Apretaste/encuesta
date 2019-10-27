<?php

use Apretaste\Money;

class Service
{
	/**
	 * Main service
	 *
	 * @author salvipascual
	 */
	public function _main(Request $request, Response $response)
	{
		// redirect to the list of surveys opened
		$this->_lista($request, $response);
	}

	/**
	 * Edit the person's profile
	 *
	 * @author salvipascual
	 */
	public function _perfil(Request $request, Response $response)
	{
		// prepare response for the view
		return $response->setTemplate('profile.ejs', ["profile" => $request->person]);
	}

	/**
	 * Get the list of surveys opened
	 *
	 * @param \Request  $request
	 * @param \Response $response
	 *
	 * @return \Response
	 * @throws \Exception
	 * @author salvipascual
	 */
	public function _lista(Request $request, Response $response)
	{
		// ensure your profile is completed
		if ($this->isProfileIncomplete($request)) {
			return $this->_perfil($request, $response);
		}

		// subqueries for the opened surveys
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
		$surveys = Connection::query("
			SELECT
				survey,
				survey_title AS title,
				survey_deadline AS deadline,
				survey_value AS value
			FROM ($sql_survey_datails) AS subq
			WHERE coalesce(($sql_survey_total_questions),0) > coalesce(($sql_survey_total_choosen),0);");

		// message if there are not opened surveys
		if (empty($surveys)) {
			return $response->setTemplate('message.ejs', [
				"header" => "No hay encuestas",
				"icon"   => "sentiment_very_dissatisfied",
				"text"   => "Lo siento pero no tenemos ninguna encuesta para usted en este momento. Estamos trabajamos en agregar encuestas a nuestra lista, por favor vuelva a revisar en unos días. Muchas gracias por estar pendiente.",
				"button" => ["href" => "ENCUESTA TERMINADAS", "caption" => "Ver Terminadas"],
			]);
		}

		// send response to the user
		$response->setTemplate('list.ejs', ['surveys' => $surveys]);
	}

	/**
	 * Display a list of previous surveys
	 *
	 * @return void
	 * @author salvipascual
	 */
	public function _terminadas(Request $request, Response $response)
	{
		// ensure your profile is completed
		if ($this->isProfileIncomplete($request)) {
			return $this->_perfil($request, $response);
		}

		//get the list of surveys answered
		$completed = Connection::query("
			SELECT person_id, responses, total, C.title, C.value, A.inserted
			FROM (SELECT person_id, survey, COUNT(survey) AS responses, MAX(date_choosen) AS inserted FROM _survey_answer_choosen WHERE person_id='{$request->person->id}' GROUP BY survey) A
			LEFT JOIN (SELECT survey, COUNT(survey) AS total FROM _survey_question GROUP BY survey) B
			ON A.survey = B.survey
			LEFT JOIN (SELECT * FROM _survey) C
			ON A.survey = C.id
			WHERE responses = total");

		// message if there are not opened surveys
		if (empty($completed)) {
			return $response->setTemplate('message.ejs', [
				"header" => "No ha completado encuestas",
				"icon" => "sentiment_neutral",
				"text" => "Usted aún no ha completado ninguna encuesta. Cuando responda por primera vez se agregará a esta lista.",
				"button" => ["href" => "ENCUESTA", "caption" => "Ver Encuestas"],
			]);
		}

		// encode as UTF-8
		foreach ($completed as $survey) {
			$survey->title = utf8_encode($survey->title);
		}

		// send response to the user
		$response->setCache("day");
		$response->setTemplate('completed.ejs', ['surveys' => $completed]);
	}

	/**
	 * Display a survey to answer it
	 *
	 * @return void
	 * @author salvipascual
	 */
	public function _ver(Request $request, Response $response)
	{
		// ensure your profile is completed
		if ($this->isProfileIncomplete($request)) {
			return $this->_perfil($request, $response);
		}

		// get the survey details
		$res = Connection::query("
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
				"header" => "¡Chócala! Ya respondió esta encuesta",
				"icon" => "pan_tool",
				"text" => "Usted ya respondió esta encuesta, y como agradecimiento se le agregaron §{$res[0]->survey_value} a su crédito. Muchas gracias por su participación.",
				"button" => ["href" => "ENCUESTA", "caption" => "Ver Encuestas"],
			]);
		}

		// create a new Survey object
		$survey = new stdClass();
		$survey->id = $res[0]->survey;
		$survey->title = utf8_encode($res[0]->survey_title);
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
				$question->title = utf8_encode($r->question_title);
				$question->answers = [];
				$question->completed = false;
				$survey->questions[] = $question;
			}

			// create the answers for the question
			$answer = new stdClass();
			$answer->id = $r->answer;
			$answer->title = utf8_encode($r->answer_title);
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
	 * @author salvipascual
	 * @return void
	 * @throws \Exception
	 */
	public function _responder(Request $request, Response $response)
	{
		// do not continue if data is not passed
		if (empty($request->input->data->answers)) {
			return;
		}

		// get the question IDs for the answers received
		$answers = implode(",", $request->input->data->answers);
		$questions = Connection::query("SELECT question FROM _survey_answer WHERE id IN ($answers)");

		// get the survey
		$survey = Connection::query("
			SELECT A.id, A.value, A.title
			FROM _survey A
			JOIN _survey_question B
			ON A.id = B.survey
			WHERE B.id = {$questions[0]->question}")[0];

		if (!isset($survey->id)) {
			return $response->setTemplate('message.ejs', [
				"header" => "Encuesta no encontrada",
				"icon" => "sentiment_very_dissatisfied",
				"text" => "Hubo un error procesando su respuesta. Es posible que la app esté desincronizada. Por favor abra los ajustes y borre los datos guardados. Si el problema persiste, contacte al soporte técnico.",
				"button" => ["href" => "ENCUESTA", "caption" => "Ver Encuestas"],
			]);
		}

		if ($this->isSurveyComplete($survey->id, $request->person->id)) {

			Challenges::complete("view-current-contests", $request->person->id);

			return $response->setTemplate('message.ejs', [
				"header" => "Encuesta completada",
				"icon" => "sentiment_very_satisfied",
				"text" => "Esta encuesta ha sido completada por usted anteriormente y se le ha asignado el crédito. No es necesario hacer nada mas. ¡Gracias!",
				"button" => ["href" => "ENCUESTA", "caption" => "Ver Encuestas"],
			]);
		}

		// prepare the data to be sent in one large query
		$values = [];
		for ($i = 0, $iMax = count($request->input->data->answers); $i < $iMax; $i++) {
			$questionID = $questions[$i]->question;
			$answerID = $request->input->data->answers[$i];
			$values[] = "('{$request->person->id}', '{$request->person->email}', {$survey->id}, $questionID, $answerID)";
		}
		$values = implode(",", $values);

		// replace all old answers by the new answers in one query
		Connection::query("
			START TRANSACTION;
			DELETE FROM _survey_answer_choosen WHERE person_id = '{$request->person->id}' AND survey = '{$survey->id}';
			INSERT INTO _survey_answer_choosen (person_id, email, survey, question, answer) VALUES $values;
			COMMIT;");

		// add § for the user if all questions were completed
		if ($this->isSurveyComplete($survey->id, $request->person->id)) {
			Money::transfer(Money::BANK, $request->person->id, $survey->value, "ENCUESTA {$survey->id}", "Ha ganado §{$survey->value} por contestar la encuesta {$survey->title}");
			Connection::query("UPDATE _survey SET answers=answers+1 WHERE id='{$survey->id}'");
		}

		/* @NOTE: REFERRED CREDITS CLOSED DOWN FOR NOW

				// if there is a referred, add it to the table and grant credits
				if (!empty($request->input->data->friend)) {
					$friend = Utils::getPerson($request->input->data->friend);
					if ($friend && $friend->id !== $request->person->id) {
						// amount of referred credits
						$credit = 1;

						// add credits to the friend
						Money::transfer(Money::BANK, $friend->id, $credit, 'ENCUESTA REFERIR', "Ha ganado §$credit por referir a @{$request->person->username} a nuestra encuesta. Gracias!");

						// add refer record to the table
						Connection::query("INSERT INTO _survey_referred (person_id, survey_id, referred, credit) VALUES ({$request->person->id}, {$survey->id}, '{$friend->email}', $credit)");
					}
				}
		*/
	}

	/**
	 * Check if the survey is completed
	 *
	 * @param String $surveyID
	 * @param String $personID
	 * @return Boolean, true if survey is 100% completed
	 * @author salvipascual
	 */
	private function isSurveyComplete($surveyID, $personID)
	{
		$res = Connection::query("
			SELECT * FROM
			(SELECT COUNT(survey) as total FROM _survey_question WHERE survey='$surveyID') A,
			(SELECT COUNT(answer) as answers FROM _survey_answer_choosen WHERE survey='$surveyID' AND person_id='$personID') B");

		return $res[0]->total === $res[0]->answers;
	}

	/**
	 * Check if the profile is completed or not
	 *
	 * @return Boolean, true if profile is incomplete
	 * @author salvipascual
	 */
	private function isProfileIncomplete($request)
	{
		return $request->person->age < 5 || $request->person->age > 130
			|| empty($request->person->province)
			|| empty($request->person->skin)
			|| empty($request->person->highest_school_level);
	}
}
