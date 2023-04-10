<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


require_once(PATH_ENGINE_TEMPLATES . 'Controller.php');
require_once(PATH_GUARDS . 'Teacher.guard.php');


class ExamStepController extends Controller {
    function __construct() {
        $this->guard = new TeacherGuard();
    }

    /**
     * @param stdClass $req
     * @param WebSite $req
     */
    public function GET($req, $res) {
        $step = $req->params['step'];

        $data = $this->getStepData($req, $res, $step);

        $res->sendFile("board/exam/steps/step$step", $data);
    }

    /**
     * @param stdClass $req
     * @param WebSite $req
     */
    protected function getStepData($req, $res, $step) {
        $m = 'step' . $step;

        if(!method_exists($this, $m)) {
            $res->redirect(404);
        }

        return $this->{$m}($req, $res);
    }

    // -------------------------

    /**
     * @param stdClass $req
     * @param WebSite $req
     */
    protected function step0($req, $res) {
        $data = [];

        $data['courses'] = $res->api()->fetch('GET', 'courses');
        $data['courses'] = $data['courses']['asReferent'];

        return $data;
    }

    protected function step1($req, $res) {
        $data = [];

        $courseId = $req->query['course'];

        $data['course'] = $res->api()->fetch('GET', "courses/$courseId");
        $data['chapters'] = $res->api()->fetch('GET', "courses/$courseId/chapters");

        foreach($data['chapters'] as $i => $chapter) {
            try {
                $chapterId = $chapter['id'];

                $questions = $res->api()->fetch('GET', "courses/$courseId/chapters/$chapterId/questions");
                $qCount = count($questions);
                $qTextCount = count(array_filter($questions, function($q) { return $q['type'] === 0; }));
                $qMcqCount = $qCount - $qTextCount;

                $data['chapters'][$i]['questionsCount'] = $qCount;
                $data['chapters'][$i]['questionsTextCount'] = $qTextCount;
                $data['chapters'][$i]['questionsMcqCount'] = $qMcqCount;
            }
            catch(ApiException $e) {
                $data['chapters'][$i]['questionsCount'] = 0;
                $data['chapters'][$i]['questionsTextCount'] = 0;
                $data['chapters'][$i]['questionsMcqCount'] = 0;
            }
        }

        return $data;
    }

    protected function step2($req, $res) {
        
    }

    protected function step3($req, $res) {
        $data = [
            'levels' => []
        ];

        $data['levels'] = $res->api()->fetch('GET', 'courses/' . $req->query['course'])['levels'];

        return $data;
    }
}