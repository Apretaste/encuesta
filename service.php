<?php

use Apretaste\Money;

class EncuestaService extends ApretasteService
{
	/**
	 * Main service
	 *
	 * @author salvipascual
	 */
	public function _main()
	{
		// redirect to the list of surveys opened
		$this->_lista();
	}

	/**
	 * Edit the person's profile
	 *
	 * @author salvipascual
	 */
	public function _perfil()
	{
		// prepare response for the view
		return $this->response->setTemplate('profile.ejs', ["profile" => $this->request->person]);
	}

	/**
	 * Get the list of surveys opened
	 *
	 * @return void
	 * @author salvipascual
	 */
	public function _lista()
	{
		// ensure your profile is completed
		if ($this->isProfileIncomplete()) return $this->_perfil();

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
				SELECT COUNT(_survey_answer_choosen.answer) as total, (
					SELECT _survey_question.survey
					FROM _survey_question
					WHERE _survey_question.id = (
						SELECT _survey_answer.question
						FROM _survey_answer
						WHERE _survey_answer.id = _survey_answer_choosen.answer)
					) as survey_id
				FROM _survey_answer_choosen
				WHERE _survey_answer_choosen.person_id = '{$this->request->person->id}'
				GROUP BY survey_id
			) AS subq2
			WHERE survey_id = subq.survey";

		// get list of opened surveys
		$surveys = Connection::query("
			SELECT
				survey,
				survey_title as title,
				survey_deadline as deadline,
				coalesce(($sql_survey_total_choosen),0) / ($sql_survey_total_questions) * 100 as completion,
				survey_value as value
			FROM ($sql_survey_datails) as subq
			WHERE coalesce(($sql_survey_total_questions),0) > coalesce(($sql_survey_total_choosen),0);");

		// message if there are not opened surveys
		if (empty($surveys)) {
			return $this->response->setTemplate('message.ejs', [
				"header" => "No hay encuestas",
				"icon"   => "sentiment_very_dissatisfied",
				"text"   => "Lo siento pero no tenemos ninguna encuesta para usted en este momento. Estamos trabajamos en agregar encuestas a nuestra lista, por favor vuelva a revisar en unos días. Muchas gracias por estar pendiente.",
				"button" => ["href" => "ENCUESTA TERMINADAS", "caption" => "Ver Terminadas"],
			]);
		}

		// send response to the user
		$this->response->setTemplate('list.ejs', ['surveys' => $surveys]);
	}

	/**
	 * Display a list of previous surveys
	 *
	 * @return void
	 * @author salvipascual
	 */
	public function _terminadas()
	{
		// ensure your profile is completed
		if ($this->isProfileIncomplete()) return $this->_perfil();

		//get the list of surveys answered
		$completed = Connection::query("
			SELECT person_id, responses, total, C.title, C.value, A.inserted
			FROM (SELECT person_id, survey, COUNT(survey) as responses, MAX(date_choosen) AS inserted FROM _survey_answer_choosen WHERE person_id='{$this->request->person->id}' GROUP BY survey) A
			LEFT JOIN (SELECT survey, COUNT(survey) as total FROM _survey_question GROUP BY survey) B
			ON A.survey = B.survey
			LEFT JOIN (SELECT * FROM _survey) C
			ON A.survey = C.id
			WHERE responses = total");

		// message if there are not opened surveys
		if (empty($completed)) {
			return $this->response->setTemplate('message.ejs', [
				"header" => "No ha completado encuestas",
				"icon"   => "sentiment_neutral",
				"text"   => "Usted aún no ha completado ninguna encuesta. Cuando responda por primera vez se agregará a esta lista.",
				"button" => ["href" => "ENCUESTA", "caption" => "Ver Encuestas"],
			]);
		}

		// send response to the user
		$this->response->setCache(12 * 60 * 60);
		$this->response->setTemplate('completed.ejs', ['surveys' => $completed]);
	}

	/**
	 * Display a survey to answer it
	 *
	 * @return void
	 * @author salvipascual
	 */
	public function _ver()
	{
		// ensure your profile is completed
		if ($this->isProfileIncomplete()) return $this->_perfil();

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
					WHERE person_id = '{$this->request->person->id}'
					AND answer = _survey_answer.id
				) AS choosen
			FROM _survey
			INNER JOIN _survey_answer
			INNER JOIN _survey_question
			ON _survey_question.survey = _survey.id
			AND _survey_answer.question = _survey_question.id
			WHERE _survey.id = {$this->request->input->data->id}
			ORDER BY _survey_question.id, _survey_answer.id");

		// do not process invalid responses
		if (empty($res) || !isset($res[0])) return;

		// message if the survey was already completed
		if ($this->isSurveyComplete($res[0]->survey)) {
			return $this->response->setTemplate('message.ejs', [
				"header" => "¡Chócala! Ya respondió esta encuesta",
				"icon"   => "pan_tool",
				"text"   => "Usted ya respondió esta encuesta, y como agradecimiento se le agregaron §{$res[0]->survey_value} a su crédito. Muchas gracias por su participación.",
				"button" => ["href" => "ENCUESTA", "caption" => "Ver Encuestas"],
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
		$this->response->setTemplate('survey.ejs', ['survey' => $survey]);
	}

	/**
	 * Responds a survey
	 *
	 * @author salvipascual
	 * @return void
	 * @throws \Exception
	 */
	public function _responder()
	{
		// do not continue if data is not passed
		if (empty($this->request->input->data->answers)) return;

		// get the question IDs for the answers received
		$answers = implode(",", $this->request->input->data->answers);
		$questions = Connection::query("SELECT question FROM _survey_answer WHERE id IN ($answers)");

		// get the survey
		$survey = Connection::query("
			SELECT A.id, A.value, A.title
			FROM _survey A
			JOIN _survey_question B
			ON A.id = B.survey
			WHERE B.id = {$questions[0]->question}")[0];

		if (!isset($survey->id)) {
			$this->simpleMessage("Encuesta no encontrada", "Los datos recibidos por ti no concuerdan con las encuestas que tenemos. Por favor, prueba borrar la cache de la aplicacion. Si el problema persiste contacte al soporte tecnico de A!");
			return;
		}

		if ($this->isSurveyComplete($survey->id)) {
			$this->simpleMessage("Encuesta completada", "La encuesta ha sido completada por usted anteriormente.");
			return;
		}

		// prepare the data to be sent in one large query
		$values = [];
		for ($i = 0, $iMax = count($this->request->input->data->answers); $i < $iMax; $i++) {
			$questionID = $questions[$i]->question;
			$answerID = $this->request->input->data->answers[$i];
			$values[] = "('{$this->request->person->id}', '{$this->request->person->email}', {$survey->id}, $questionID, $answerID)";
		}
		$values = implode(",", $values);

		// replace all old answers by the new answers in one query
		Connection::query("
			START TRANSACTION;
			DELETE FROM _survey_answer_choosen WHERE person_id = '{$this->request->person->id}' AND survey = '{$survey->id}';
			INSERT INTO _survey_answer_choosen (person_id, email, survey, question, answer) VALUES $values;
			COMMIT;");

		// add § for the user if all questions were completed
		if ($this->isSurveyComplete($survey->id)) {
			Money::transfer(Money::BANK, $this->request->person->id, $survey->value, "ENCUESTA {$survey->id}", "Ha ganado §{$survey->value} por contestar la encuesta {$survey->title}");
			Connection::query("UPDATE _survey SET answers=answers+1 WHERE id='{$survey->id}'");
		}

/* @NOTE: REFERRED CREDITS CLOSED DOWN FOR NOW 

		// if there is a referred, add it to the table and grant credits
		if (!empty($this->request->input->data->friend)) {
			$friend = Utils::getPerson($this->request->input->data->friend);
			if ($friend && $friend->id !== $this->request->person->id) {
				// amount of referred credits
				$credit = 1;

				// add credits to the friend
				Money::transfer(Money::BANK, $friend->id, $credit, 'ENCUESTA REFERIR', "Ha ganado §$credit por referir a @{$this->request->person->username} a nuestra encuesta. Gracias!");

				// add refer record to the table
				Connection::query("INSERT INTO _survey_referred (person_id, survey_id, referred, credit) VALUES ({$this->request->person->id}, {$survey->id}, '{$friend->email}', $credit)");
			}
		}
*/
	}

	/**
	 * Check if the survey is completed
	 *
	 * @param String $surveyID
	 *
	 * @return Boolean, true if survey is 100% completed
	 * @author salvipascual
	 */
	private function isSurveyComplete($surveyID)
	{
		$res = Connection::query("
			SELECT * FROM
			(SELECT COUNT(survey) as total FROM _survey_question WHERE survey='$surveyID') A,
			(SELECT COUNT(answer) as answers FROM _survey_answer_choosen WHERE survey='$surveyID' AND person_id='{$this->request->person->id}') B");

		return $res[0]->total === $res[0]->answers;
	}

	/**
	 * Check if the profile is completed or not
	 *
	 * @return Boolean, true if profile is incomplete
	 * @author salvipascual
	 */
	private function isProfileIncomplete()
	{
		return $this->request->person->age < 5 || $this->request->person->age > 130
			|| empty($this->request->person->province)
			|| empty($this->request->person->skin)
			|| empty($this->request->person->highest_school_level);
	}
}
