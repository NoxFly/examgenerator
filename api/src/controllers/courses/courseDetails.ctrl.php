<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


require_once(PATH_TEMPLATES . 'ApiController.php');
require_once(PATH_GUARDS . 'ApiPrivileges.guard.php');


class CourseDetailsController extends ApiController {
    function __construct() {
        $this->guard = new ApiPrivilegesGuard([
			'POST'      => UserType::ADMIN,
			'PUT'       => UserType::ADMIN,
			'DELETE'    => UserType::ADMIN
        ]);
    }

    /**
     * Modifie les détails d'une matière:
     * - son nom
     * 
     * Si la matière n'existe pas, code 404
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 401 Utilisateur non authentifié
     * - 404 La matière n'existe pas
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function POST($req, $res) {
        $this->sendData($req, $res, 'modifyCourse');
    }

    /**
     * Créer une matière.
     * Son nom doit être unqique : si une autre matière existante dans l'université
     * a le même nom, code 500
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 401 Utilisateur non authentifié
     * - 400 Champs manquants
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function PUT($req, $res) {
        $this->sendData($req, $res, 'createCourse');
    }

    /**
     * Supprime une matière.
     * Si la matière n'existe pas, code 404.
     * 
     * Codes de retour possibles :
     * - 200 OK
     * - 404 La matière n'existe pas
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
     * 
     * @param stdClass $req
     * @param ApiSite $res
     */
    public function DELETE($req, $res) {
        $this->sendData($req, $res, 'deleteCourse');
    }


    /* -------------------------------------------------------- */


    protected function modifyCourse($req, $res) {
        $name = $req->body['name']?? NULL;
        $levels = $req->body['levels']?? NULL;

        if(!$name && !$levels) {
            throw new ApiException(400, 'Missing fields');
        }

        $db = $res->getDatabase();
        $courseId = intval($req->params['courseId']);

        $course = $db->query(
            'SELECT id
            FROM course
            WHERE id_univ = :univId
                AND id = :courseId',
            [
                'univId' => $req->user->universityId,
                'courseId' => $courseId
            ]
        )->fetch();

        if(!$course) {
            throw new ApiException(404);
        }

        if($name) {
            $db->query(
                'UPDATE course
                SET name = :name
                WHERE id = :courseId',
                [
                    'courseId' => $courseId,
                    'name' => $name
                ]
            );
        }

        if($levels) {
            $levels = json_decode($levels);
            
            $yearId = $this->getYearId($req, $res);

            try {
                $db->beginTransaction();

                $db->query(
                    'DELETE FROM courselevelyear
                    WHERE id_course = :courseId
                    AND id_year = :yearId',
                    [
                        'courseId' => $courseId,
                        'yearId' => $yearId
                    ]
                );

                $values = '';
                $params = [];

                $l = count($levels) - 1;
                
                foreach($levels as $i => $level) {
                    $values .= "(:levelId$i, :courseId$i, :yearId$i)";
                    $params["levelId$i"] = $level;
                    $params["courseId$i"] = $courseId;
                    $params["yearId$i"] = $yearId;

                    if($i < $l) {
                        $values .= ', ';
                    }
                }


                // enhancement : verify that the levels exist for this univ and this year
                if($l > 0) {
                    $db->query(
                        "INSERT INTO courselevelyear (id_level, id_course, id_year) VALUES $values",
                        $params
                    );
                }

                $db->commit();
            }
            catch(ApiException $e) {
                $db->rollback();
                throw $e;
            }
            catch(Exception $e) {
                $db->rollback();
                throw new ApiException(500, $e->getMessage());
            }
        }
    }

    protected function createCourse($req, $res) {
        if(!isset($req->body['name'])) {
            throw new ApiException(400);
        }

		$name = trim($req->body['name']);

        if(mb_strlen($name) === 0) {
			throw new ApiException(400);
		}

        $res->getDatabase()->query(
            'INSERT INTO course (id_univ, name)
                VALUES (:univId, :name)',
            [
                'univId' => $req->user->universityId,
                'name' => $name
            ]
        );

		$this->data['courseId'] = $res->getDatabase()->getLastInsertedId();
    }

    protected function deleteCourse($req, $res) {
        $univId = $res->getDatabase()->query(
            'SELECT id FROM course
            WHERE id_univ = :univId
                AND id = :courseId',
            [
                'univId' => $req->user->universityId,
                'courseId' => $req->params['courseId']
            ]
        )->fetch();

        if(!$univId) {
            throw new ApiException(404);
        }

        $res->getDatabase()->query(
            'DELETE FROM course
            WHERE id = :courseId',
            [
                'courseId' => $req->params['courseId']
            ]
        );
    }
}