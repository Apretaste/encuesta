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
use Framework\GoogleAnalytics;

class Service
{
	/**
	 * Main service
	 *
	 * @param Request $request
	 * @param Response $response
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
	 * SAVE PROFILE
	 *
	 * @param Request $request
	 * @param Response $response
	 * @throws FeedException
	 * @throws \Framework\Alert
	 * @throws \Kreait\Firebase\Exception\FirebaseException
	 * @throws \Kreait\Firebase\Exception\MessagingException
	 */
	public function _saveperfil(Request $request, Response &$response){
		Person::update($request->person->id, $request->input->data);
		$this->_main($request, $response);
	}

	/**
	 * Get the list of surveys opened
	 *
	 * @param Request $request
	 * @param Response $response
	 * @author salvipascual
	 */
	public function _lista(Request $request, Response $response)
	{
		// ensure your profile is completed
		if ($this->isProfileIncomplete($request->person)) {
			$this->_perfil($request, $response);
			return;
		}

		$sqlSurveys = "SELECT
				_survey.id as survey, _survey.* 
				FROM _survey
				WHERE active = 1 AND deadline >= CURRENT_DATE
				AND NOT EXISTS(SELECT survey_id FROM _survey_done C WHERE C.person_id = {$request->person->id} AND _survey.id = C.survey_id);";

		// get list of opened surveys
		$surveys = Database::query($sqlSurveys);

		// filter surveys
		$sql = [];
		foreach ($surveys as $key => $survey) {
			if (empty(trim($survey->filter)))
				$sql[] = "SELECT $key AS idx";
			else
				$sql[] = "SELECT ta{$key}.idx FROM (SELECT $key As idx) ta{$key} 
    						INNER JOIN (SELECT id FROM person WHERE id = {$request->person->id} AND ({$survey->filter})) tb{$key}";
		}

		$sql = implode(' UNION ', $sql);

		$filtered = $surveys;
		if(!empty(trim($sql))) {
			$idxs = Database::query($sql);
			$filtered = [];
			if (is_array($idxs)) {
				foreach ($idxs as $idx)
					$filtered[] = $surveys[$idx->idx];
			}
		}

		$surveys = $filtered;

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
		$response->setTemplate('list.ejs', ['surveys' => $filtered]);
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
			select _survey.id, _survey.title, _survey_done.inserted_date as completed,
			_survey.value from _survey_done
			inner join _survey on _survey.id = _survey_done.survey_id
			where person_id = {$request->person->id} order by inserted_date desc");

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
			    _survey_question.widget AS question_widget,
			    _survey_question.min_answers AS question_min_answers,
				_survey_answer.id AS answer,
				_survey_answer.title AS answer_title
			FROM _survey
			INNER JOIN _survey_question	ON _survey_question.survey = _survey.id
			LEFT JOIN _survey_answer ON _survey_answer.question = _survey_question.id
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
				$question->widget = $r->question_widget;
				$question->min_answers = $r->question_min_answers;
				$survey->questions[] = $question;
			}

			// create the answers for the question
			$answer = new stdClass();
			$answer->id = $r->answer;
			$answer->title = $r->answer_title;

			// assign the answer to the question
			$question->answers[] = $answer;
		}

		GoogleAnalytics::event('survey_open', $survey->id);

		// send response to the view
		$response->setTemplate('survey.ejs', ['survey' => $survey]);
	}

	/**
	 * Responds a survey
	 *
	 * @param Request $request
	 * @param Response $response
	 * @author salvipascual
	 */
	public function _responder(Request $request, Response &$response)
	{

		// get the survey
		$survey = Database::queryFirst("
			SELECT A.id, A.value, A.title
			FROM _survey A
			WHERE A.id = {$request->input->data->survey}");

		if (!isset($survey->id)) {
			$response->setTemplate('message.ejs', [
				'header' => 'Encuesta no encontrada',
				'icon' => 'sentiment_very_dissatisfied',
				'text' => 'Hubo un error procesando su respuesta. Es posible que la app esté desincronizada. Por favor abra los ajustes y borre los datos guardados. Si el problema persiste, contacte al soporte técnico.',
				'button' => ['href' => 'ENCUESTA', 'caption' => 'Ver Encuestas'],
			]);
			return;
		}

		$request->input->data = get_object_vars($request->input->data);
		$cacheQuestions = [];
		//  (id, person_id, survey, question, answer, position, explanation)
		$values = [];
		foreach($request->input->data as $key => $value) {
			if (stripos($key,'question_') === 0) {
				$parts = explode('_', $key);

				// get question
				$questionId = $parts[1];
				if (!isset($cacheQuestions[$questionId])) {
					$cacheQuestions[$questionId] = Database::queryFirst("SELECT * FROM _survey_question WHERE id = $questionId");
				}
				$question = $cacheQuestions[$questionId];

				if ($question) {
					switch($question->widget) {
						case 'MULTIPLE':
						case 'RANDOM':
							$answerId = $value;
							$values[] = "(uuid(), '{$request->person->id}', {$survey->id}, $questionId, $answerId, 1, '')";
						break;
						case 'SEVERAL':
							$answerId = $parts[2];
							$values[] = "(uuid(), '{$request->person->id}', {$survey->id}, $questionId, $answerId, 1, '')";
							break;
						case 'RANKING':
							$answerId = $parts[2];
							$position = (int) $value;
							$values[] = "(uuid(), '{$request->person->id}', {$survey->id}, $questionId, $answerId, $position, '')";
							break;
						case 'FREE':
							$explanation = Database::escape($value);
							$values[] = "(uuid(), '{$request->person->id}', {$survey->id}, $questionId, 0, 1, '$explanation')";
							break;
					}
				}
			}
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

		$startTime = $request->input->data->startTime ?? date("Y-m-d h:i:s");
		$values = implode(',',  $values);

		// replace all old answers by the new answers in one query

		$sql = "
		START TRANSACTION;
		DELETE FROM _survey_response WHERE person_id = '{$request->person->id}' AND survey = '{$survey->id}';
		INSERT INTO _survey_response  (id, person_id, survey, question, answer, position, explanation) VALUES $values;
		DELETE FROM _survey_done WHERE person_id = '{$request->person->id}' AND survey_id = '{$survey->id}';
		INSERT INTO _survey_done (survey_id, person_id, country, province, city, start_time,
			year_of_birth, gender, eyes, skin, body_type, hair, highest_school_level, occupation, marital_status,
			sexual_orientation, religion) 
			SELECT {$survey->id}, id, country, province, city, '$startTime' as start_time, year_of_birth, gender, eyes,
			 skin, body_type, hair, highest_school_level, occupation, marital_status, sexual_orientation, religion
			 FROM person
			WHERE id = {$request->person->id}; 
		COMMIT;";

		Database::query($sql);

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
			Money::send(Money::BANK, $request->person->id, $survey->value, 'Encuesta completada');

			// add a new response to the counter
			Database::query("UPDATE _survey SET answers=answers+1 WHERE id='{$survey->id}'");

			// notify the user
			$msg = "Ha ganado §{$survey->value} por contestar la encuesta {$survey->title}. $msg";
			Notifications::alert($request->person->id, $msg, 'attach_money', '{"command":"ENCUESTA TERMINADAS"}');

			// submit to Google Analytics 
			GoogleAnalytics::event('survey_complete', $survey->id);

			// complete the challenge
			Challenges::complete('fill-survey', $request->person->id);

			// add the experience
			Level::setExperience('FINISH_SURVEY', $request->person->id);

			return $response->setTemplate('message.ejs', [
				'header' => '¡Genial! Ya respondió esta encuesta',
				'icon' => 'thumb_up',
				'text' => 'Usted ya respondió esta encuesta y como agradecimiento le agregamos §'.$survey->value.' a su crédito. Recuerde que sus respuestas contribuirán a construir una mejor Cuba para todos. Muchas gracias por su participación.',
				'button' => ['href' => 'ENCUESTA', 'caption' => 'Otras encuestas']
			]);
		}
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
		$res = Database::query("
			SELECT * FROM
			(SELECT COUNT(survey) as total FROM _survey_question WHERE survey='$surveyID') A,
			(SELECT COUNT(DISTINCT question) as answers FROM _survey_response WHERE survey='$surveyID' AND person_id='$personID') B");

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

	/**
	 * Event when start survey
	 *
	 * @param Request $request
	 * @param Response $response
	 */
	public function _start(Request $request, Response $response) {
		GoogleAnalytics::event('survey_start', $request->input->data->id);
	}
}
