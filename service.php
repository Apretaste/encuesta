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
     * Function executed when the service is called
     *
     * @param Request $request            
     * @return Response
     *
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
    public function _responder (Request $request)
    {
        $db = new Connection();
        
        $credit_plus = 0.5;
        
        $answer_id = intval(trim($request->query));
        
        $sql = "SELECT *, 
                    (SELECT survey 
                     FROM _survey_question 
                     WHERE _survey_question.id = _survey_answer.question
                    ) AS survey_id 
                FROM _survey_answer 
                WHERE id = $answer_id;";
        
        $answer = $db->deepQuery($sql);
        
        if ($answer == false || ! isset($answer[0]) || empty($answer)) {
            $response = $this->defaultResponse($request);
            $response->content['credit_plus'] = 0;
            $response->setResponseSubject('No existe la respuesta seleccionada');
            return $response;
        }
        
        $sql = "SELECT 
                    * 
                FROM _survey_answer_choosen 
                WHERE email = '{$request->email}' 
                AND answer = $answer_id;";
        
        $r = $db->deepQuery($sql);
        
        if ($answer !== false && isset($r[0])) {
            return new Response();
        }
        
        $sql = "INSERT INTO _survey_answer_choosen (email, answer) VALUES ('{$request->email}',$answer_id);";
        
        $r = $db->deepQuery($sql);
        
        // Check if survey are completed and set credit to user
        
        $detalis = $this->getSurveyDetails($request->email, $answer[0]->survey_id);
        
        $questions = array();
        $total_choosen = 0;
        foreach ($detalis as $detail) {
            $questions[$detail->question] = true;
            if ($detail->choosen === '1') $total_choosen ++;
        }
        
        $total_questions = count($questions);
        
        $response = $this->surveyResponse($request, $answer[0]->survey_id);
        $response->setResponseSubject('Gracias por responder la encuesta');
        $response->content['credit_plus'] = 0;
        if ($total_choosen === $total_questions) {
            $sql = "UPDATE person SET credit = credit + $credit_plus WHERE email = '{$request->email}';";
            $db->deepQuery($sql);
            $response->setResponseSubject("Has completado la encuesta #{$answer[0]->survey_id} y has ganado $" . number_format($credit_plus, 2) . " de credito");
            $response->content['credit_plus'] = $credit_plus;
            return $response;
        }
        return new Response();
    }

    /**
     *
     * @param Request $request            
     * @return Response
     */
    private function defaultResponse ($request)
    {
        $db = new Connection();
        
        $sql_survey_datails = '
            SELECT
                _survey.id AS survey,
                _survey.title AS survey_title,
                _survey.deadline as survey_deadline
            FROM
                _survey
            WHERE _survey.active = 1 AND _survey.deadline >= CURRENT_DATE';
        
        $sql_survey_total_questions = "
        SELECT 
            Count(_survey_question.id) AS total
        FROM _survey_question 
        WHERE _survey_question.survey =  subq.survey
        GROUP BY _survey_question.survey";
        
        $sql_survey_total_choosen = "
        SELECT total FROM (
            SELECT 
                count(_survey_answer_choosen.answer) as total,
                (SELECT _survey_question.survey 
                 FROM _survey_question 
                 WHERE _survey_question.id = (SELECT _survey_answer.question 
                                              FROM _survey_answer 
                                              WHERE _survey_answer.id = _survey_answer_choosen.answer)
                ) as survey_id
            FROM _survey_answer_choosen
            WHERE _survey_answer_choosen.email = '{$request->email}'
            GROUP BY survey_id
        ) AS subq2 
        WHERE survey_id = subq.survey";
        
        $sql = "
        SELECT survey, survey_title as title, survey_deadline as deadline, coalesce(($sql_survey_total_choosen),0) / ($sql_survey_total_questions) * 100 as completion
        FROM ($sql_survey_datails) as subq
        WHERE coalesce(($sql_survey_total_questions),0) > coalesce(($sql_survey_total_choosen),0);";
        
        $surveys = $db->deepQuery($sql);
        
        $response = new Response();
        $response->setResponseSubject(count($surveys) > 0 ? "Encuestas activas" : "No tienes encuestas que responder");
        $response->createFromTemplate('basic.tpl', array(
                'surveys' => $surveys,
                'no_surveys' => count($surveys) === 0
        ));
        
        return $response;
    }

    /**
     * Return Survey response
     *
     * @param Request $request            
     * @param integer $survey_id            
     * @return Response
     */
    private function surveyResponse ($request, $survey_id)
    {
        $survey = $this->getSurveyDetails($request->email, $survey_id);
        
        if ($survey == false || ! isset($survey[0]) || empty($survey)) {
            $response = $this->defaultResponse($request);
            $response->setResponseSubject("No encontramos la encuesta solicitada");
            $response->createFromText("El n&uacute;mero de Encuesta solicitada no existe en Apretaste. Por favor verif&iacute;calo o escribe simplemente la palabra ENCUESTA en el asunto para obtener una lista de las encuestas activas.");
            return $response;
        }
        
        $newsurvey = new stdClass();
        $newsurvey->id = $survey[0]->survey;
        $newsurvey->title = $survey[0]->survey_title;
        $newsurvey->questions = array();
        
        foreach ($survey as $r) {
            if (! isset($newsurvey->questions[$r->question])) {
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
        
        $survey = $newsurvey;
        
        $response = new Response();
        $response->setResponseSubject('Encuesta: ' . $survey->title);
        $response->createFromTemplate('survey.tpl', array(
                'survey' => $survey,
                'credit_plus' => 0
        ));
        
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
            _survey.active AS survey_active,
            _survey_question.id AS question,
            _survey_question.title AS question_title,
            _survey_answer.id AS answer,
            _survey_answer.title AS answer_title,
            (SELECT count(*) 
             FROM _survey_answer_choosen 
             WHERE email = '$email' 
             AND answer = _survey_answer.id
            ) AS choosen
        FROM
        _survey
        INNER JOIN _survey_answer
        INNER JOIN _survey_question 
            ON _survey_question.survey = _survey.id 
            AND _survey_answer.question = _survey_question.id
        WHERE _survey.id = $survey_id
        ORDER BY _survey_question.id, _survey_answer.id";
        
        $db = new Connection();
        return $db->deepQuery($sql);
    }
}