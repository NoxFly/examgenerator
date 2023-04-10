<?php

/**
 * @copyright Copyrights (C) 2023 Jean-Charles Armbruster All rights reserved.
 * @author Jean-Charles Armbruster
 * @since 2023
 * @package uha.archi_web
 */

 defined('_NOX') or die('401 Unauthorized');


 require_once(PATH_TEMPLATES . 'ApiController.php');
 require_once(PATH_GUARDS . 'ApiPrivileges.guard.php');

 class CorrectController extends ApiController {
	function __construct() {
        $this->guard = new ApiPrivilegesGuard([
            'POST' => UserType::TEACHER
        ]);
    }

    /**
     * Corrige le sujet d'un étudiant donné en parametre,
     * prends en param dans le body :
     *  -questionsId : liste d'id des questions de l'exam a modifier(rajout de commentaire/edit des points) separé par des ','
     *  -comments : commentaires a la chaine séparé par ',' dans le même ordre que questionsId
     *  -points : nb de poins de la question séparé par ',' dans le même ordre que questionsId
     * 
     * renvois 400 si : questionsId null, comments && points null, comments || points != null et taille != de taille de questionsId
	 * 
	 * Codes de retour possibles :
     * - 200 OK
     * - 400 Autre erreur
	 * - 401 Utilisateur non authentifié
	 * - 403 N'a pas accès à cette ressource
     * - 500 Erreur pendant l'exécution de la requête dans la base de données
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function POST($req, $res) {
        // throw new ApiException(400, 'No questions id given');
        $this->sendData($req, $res, 'correctSubject');
    }

    /* -------------------------------------------------------- */

    protected function correctSubject($req, $res) {
        $examId = intval($req->params['examId']);
        $studentId = intval($req->params['studentId']);

        $fields = ['points', 'comment'];

        $request = "UPDATE answer SET ";
        $params = [
            'examId' => $examId,
            'studentId' => $studentId
        ];
        $ids = join(', ', array_keys($req->body));

        $counts = [];

        foreach($fields as $field) {
            $counts[$field]= 0;
        }

        foreach($req->body as $question) {
            foreach($fields as $field) {
                if(array_key_exists($field, $question)) {
                    $counts[$field]++;
                }
            }
        }

        foreach($fields as $i => $field) {
            if($counts[$field] === 0) {
                array_splice($fields, $i, 1);
            }
        }

        foreach($fields as $i => $field) {
            $r = "$field = CASE ";

            foreach($req->body as $questionId => $question) {
                if(array_key_exists($field, $question)) {
                    $v = $question[$field];

                    $qk = 'questionId_' . $i . '_' . $questionId;
                    $vk = 'value_' . $i . '_' . $questionId;

                    $r .= " WHEN id_question = :$qk THEN :$vk ";

                    $params[$qk] = $questionId;
                    $params[$vk] = $v;
                }
            }

            $r .= " END";

            if($i < count($fields)-1) {
                $r .= ',';
            }

            $request .= "$r ";
        }

        $request .= " WHERE id_question IN ($ids) AND id_exam = :examId AND id_student = :studentId";

        $db = $res->getDatabase();


        $db->query($request, $params);

        if($counts['points'] > 0) {
            $corrected = $db->query("SELECT DISTINCT 1 FROM answer WHERE id_exam = $examId AND points IS NULL")->fetchColumn();

            $b = 0;
            
            if (empty($corrected)) {
                $b = 1;
            }

            $db->query("UPDATE exam SET is_corrected='$b' WHERE id = $examId");
        }
    }
 }