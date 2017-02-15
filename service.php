<?php

/**
 * Apretaste
 * 
 * Service ENCUESTA
 * 
 * @version 1.0
 *
 */
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
		$survey_id = intval(trim($request->query));

		if ($survey_id === 0) return $this->defaultResponse($request);

		return $this->surveyResponse($request, $survey_id);
	}

	/**
	 * Subservice Responder
	 *
	 * @param Request $request			
	 * @return Response
	 */
	public function _responder(Request $request)
	{
		$connection = new Connection();
		$answerID = intval(trim($request->query));

		// check if the answer is valid
		$answer = $connection->deepQuery("
			SELECT *, 
				(SELECT survey 
				 FROM _survey_question 
				 WHERE _survey_question.id = _survey_answer.question
				) AS survey_id 
			FROM _survey_answer 
			WHERE id = $answerID");
		if ($answer == false || ! isset($answer[0]) || empty($answer)) return new Response();

		// check if person hasen't responded that question already
		$r = $connection->deepQuery("
			SELECT * 
			FROM _survey_answer_choosen 
			WHERE email = '{$request->email}' 
			AND answer = $answerID;");
		if (isset($r[0])) return new Response();

		// insert the answer into the database
		$surveyID = $answer[0]->survey_id;
		$questionID = $answer[0]->question;
		$connection->deepQuery("INSERT INTO _survey_answer_choosen (email,survey,question,answer) VALUES ('{$request->email}',$surveyID,$questionID,$answerID)");

		// if that question answers the whole survey, add $ and send confirmation
		if ($this->isSurveyComplete($request->email, $surveyID))
		{
			// get the credit to add
			$survey = $connection->deepQuery("SELECT title,value FROM _survey WHERE id='$surveyID'");
			$credit = $survey[0]->value;
			$title = $survey[0]->title;

			// add credit to the user account
			$connection->deepQuery("UPDATE person SET credit=credit+$credit WHERE email='{$request->email}'");

			// add the counter of times the survey was answered
			$connection->deepQuery("UPDATE _survey SET answers=answers+1 WHERE id='$surveyID'");

			// send completion answer
			$response = new Response();
			$response->setResponseSubject("Ha completado una encuesta");
			$response->createFromTemplate("completed.tpl", array("title"=>$title, "credit"=>$credit));
			return $response;
		}

		return new Response();
	}

	/**
	 * Check if the survey is completed
	 * 
	 * @author salvipascual
	 * @param String $email
	 * @param String $surveyID
	 * @return Boolean, true if survey is 100% completed
	 * */
	private function isSurveyComplete($email, $surveyID)
	{
		$connection = new Connection();
		$res = $connection->deepQuery("
			SELECT * FROM
			(SELECT COUNT(survey) as total FROM _survey_question WHERE survey='$surveyID') A,
			(SELECT COUNT(answer) as answers FROM _survey_answer_choosen WHERE survey='$surveyID' AND email='$email') B");

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
		$connection = new Connection();
		$surveys = $connection->deepQuery($opened);
		$finished = $connection->deepQuery($finished);

		// send response to the user
		$response = new Response();
		$response->setResponseSubject(count($surveys) > 0 ? "Encuestas activas" : "No tienes encuestas que responder");
		$response->createFromTemplate('basic.tpl', array('surveys' => $surveys, 'finished' => $finished));
		return $response;
	}

	/**
	 * Return Survey response
	 *
	 * @param Request $request			
	 * @param integer $survey_id			
	 * @return Response
	 */
	private function surveyResponse($request, $survey_id)
	{
		$survey = $this->getSurveyDetails($request->email, $survey_id);

		// do not process invalid responses
		if ($survey == false || ! isset($survey[0]) || empty($survey)) return new Response();

		$newsurvey = new stdClass();
		$newsurvey->id = $survey[0]->survey;
		$newsurvey->title = $survey[0]->survey_title;
		$newsurvey->details = $survey[0]->survey_details;
		$newsurvey->questions = array();

		foreach ($survey as $r)
		{
			if ( ! isset($newsurvey->questions[$r->question]))
			{
				$obj = new stdClass();
				$obj->id = $r->question;
				$obj->title = $r->question_title;
				$obj->answers = array();
				$obj->selectable = true;
				$newsurvey->questions[$r->question] = $obj;
			}

			$obj = new stdClass();
			$obj->id = $r->answer;
			$obj->title = $r->answer_title;
			$obj->choosen = $r->choosen == '1' ? true : false;

			if ($obj->choosen) $newsurvey->questions[$r->question]->selectable = false;

			$newsurvey->questions[$r->question]->answers[] = $obj;
		}

		$response = new Response();
		$response->setResponseSubject('Encuesta: ' . $newsurvey->title);
		$response->createFromTemplate('survey.tpl', array('survey' => $newsurvey));
		return $response;
	}

	/**
	 * Return details of survey
	 *
	 * @param string $email			
	 * @param integer $survey_id			
	 * @return Array
	 */
	private function getSurveyDetails ($email, $survey_id)
	{
		$sql = "
			SELECT
				_survey.id AS survey,
				_survey.title AS survey_title,
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
			WHERE _survey.id = $survey_id
			ORDER BY _survey_question.id, _survey_answer.id";

		$connection = new Connection();
		return $connection->deepQuery($sql);
	}
}