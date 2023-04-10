<?php

/**
 * @copyright Copyrights (C) 2023 Jean-Charles Armbruster All rights reserved.
 * @author Jean-Charles Armbruster
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


require_once(PATH_ENGINE_TEMPLATES . 'Controller.php');
require_once(PATH_GUARDS . 'User.guard.php');


class CourseController extends Controller {
    function __construct()
    {
        $this->guard = new UserGuard();
    }

    /**
     * @param stdClass $req
     * @param WebSite $req
     */
    public function GET($req, $res)
    {
        $courseId = $req->params["id"];

        $chapters = $res->api()->fetch("GET", "/courses/$courseId/chapters");
        $courses = $res->api()->fetch("GET", "/courses");

        $name = NULL;

        //                  'asXXXX' => [0=>{id, name},1=>{id, name}]
        foreach($courses as $type => $arrValue) {
            if(($i = array_search($courseId, array_column($arrValue, 'id'))) !== false) {
                $name = $arrValue[$i]['name'];
            }
        }
        

        if($name === NULL) {
            $res->redirect("/404");
        }

        $data = [
            'courseName' => $name,
            'chapters' => $chapters
        ];

        if(bitwiseAND(UserType::STUDENT, $_SESSION['privileges'])) {
            $exam = $res->api()->fetch('GET', "/courses/$courseId/exams"); // récup l'examen potentiel de cette matière

            if(!empty($exam)) { // si l'exam existe
                /*
                dans le cas de plusieurs exams ouverts
                $data['examId'] = $exam[0]["id"]; // ... // rajouter l'id de l'exam dans le data
                */
                foreach ($exam as $examDatas)
                {
                    if ($examDatas["repondu"] === 0)
                    {
                        $data['examId'] = $examDatas["id"];
                        break;
                    }
                }
            }
        }


        $participants = $res->api()->fetch('GET', "courses/$courseId/students");
        $referent = $res->api()->fetch('GET', "courses/$courseId/referent");

        $data['participants'] = $participants;
        $data['referent'] = $referent;
        
        $res->render("board/course/courseDetails", $data);
    }
}