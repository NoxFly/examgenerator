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


class UserDetailsController extends ApiController {
	function __construct() {
        $this->guard = new ApiPrivilegesGuard([
            'GET' 		=> UserType::ADMIN | UserType::TEACHER | UserType::STUDENT,
			'POST' 		=> UserType::ADMIN | UserType::TEACHER | UserType::STUDENT,
			'PUT' 		=> UserType::ADMIN,
			'DELETE' 	=> UserType::ADMIN
        ]);
    }
	
	/**
     * Affiche les détails d'un utilisateur (étudiant ou enseignant).
	 * - Ses identifiants
	 * - Ses informations de base (nom, prénom, mail, ...)
	 * - Si c'est un enseignant, affiche la liste des matières dont il est le référent.
	 * - Si c'est un étudiant, affiche les informations du niveau auquel il est assigné pour cette année.
	 * Si l'utilisateur demandé n'existe pas ou n'est pas dans la même université, renvoie code 404.
	 * 
	 * Codes de retour possibles :
     * - 200 OK
	 * - 400 Les paramètres donnés sont incomplets ou mal formés
	 * - 401 Utilisateur non authentifié
     * - 404 L'utilisateur demandé n'existe pas pour l'université demandé
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function GET($req, $res) {
		$this->sendData($req, $res, 'getDetails');
	}

	/**
     * Modifie les informations de l'utilisateur.
	 * - Mail / MDP
	 * - Nom / Prénom / id étudiant / enseignant
	 * Si l'utilisateur n'existe pas ou n'est pas dans la même université : code 404.
	 * Si le nouveau mail commence par 'admin@' : code 400.
	 * 
	 * Codes de retour possibles :
     * - 200 OK
	 * - 400 Ne peut pas utiliser le mail administrateur sur un compte utilisateur
	 * - 401 Utilisateur non authentifié
	 * - 403 Ne peut pas utiliser le mail administrateur sur un compte utilisateur
     * - 404 L'utilisateur à modifer n'existe pas
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function POST($req, $res) {
		$this->sendData($req, $res, 'modifyUser');
	}

	/**
     * Créer un utilisateur.
	 * - Mail / MDP
	 * - Nom / Prénom / id étudiant / enseignant
	 * Si l'id ou le mail existe déjà : 403.
	 * Si le nouveau mail commence par 'admin@' : code 400.
	 * 
	 * Codes de retour possibles :
     * - 200 OK
	 * - 400 Ne peut pas utiliser le mail administrateur sur un compte utilisateur
	 * - 401 Utilisateur non authentifié
     * - 403 L'utilisateur à créer a le même id qu'un existant, ou son mail est déjà pris
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function PUT($req, $res) {
		$this->sendData($req, $res, 'createUser');
	}

	/**
     * Supprime de la base l'utilisateur ciblé.
	 * Tout ce qui est lié sera également supprimé.
	 * Si l'utilisateur ne fait pas parti de la même université, code 404.
	 * 
	 * Codes de retour possibles :
     * - 200 OK
	 * - 401 Utilisateur non authentifié
     * - 404 l'utilisateur n'existe pas
	 * - 500 Erreur pendant l'exécution de la requête dans la base de données
	 * 
     * @param stdClass $req
     * @param ApiSite $res
     */
	public function DELETE($req, $res) {
		$this->sendData($req, $res, 'deleteUser');
	}


    /* -------------------------------------------------------- */


	protected function getDetails($req, $res) {
		$db = $res->getDatabase();

		$where = NULL;
		$queryParams = [
			'univId' => $req->user->universityId
		];

		if(isset($req->params['id'])) {
			$where = 'U.id = :userId';
			$queryParams['userId'] = $req->params['id'];
		}
		else if(isset($req->params['name'])) {
			$where = 'U.firstname = :first AND U.last = :last';
			$fullname = explode(' ', $req->params['name']);
			
			if(count($fullname) !== 2) {
				throw new ApiException(400);
			}

			$queryParams['first'] = $fullname[0];
			$queryParams['last'] = $fullname[1];
		}


		$data = $db->query(
			"SELECT A.id as accountId, A.mail,
				U.firstname, U.lastname, U.uuid as userUUID, U.id as userId,
				T.id as teacherId, S.id as studentId
			FROM user U
				LEFT JOIN account A ON (U.id_account = A.id)
				LEFT JOIN teacher T ON (T.id_user = U.id)
				LEFT JOIN student S ON (S.id_user = U.id)
			WHERE id_univ = :univId
				AND $where",
			$queryParams
		)->fetchObject();

		if(!$data) {
			throw new ApiException(404);
		}

		$this->data = (object)array(
			'ids' => (object)array(
				'accountId' => $data->accountId,
				'userId' => $data->userId,
				'userUUID' => $data->userUUID,
				'studentId' => $data->studentId,
				'teacherId' => $data->teacherId
			),
			'mail' => $data->mail,
			'firstname' => $data->firstname,
			'lastname' => $data->lastname
		);

		if($this->data->ids->teacherId) {
			$yearId = $this->getYearId($req, $res);

			$data = $db->query(
				'SELECT C.name, C.id, Y.year
				FROM course C
					INNER JOIN teacherteaching TT ON (TT.id_course = C.id)
					LEFT JOIN universityyear Y ON (Y.id = TT.id_year)
				WHERE TT.id_year = :yearId
					AND TT.id_teacher = :teacherId',
				[
					'teacherId' => $this->data->ids->teacherId,
					'yearId' => $yearId
				]
				)->fetchAll(PDO::FETCH_ASSOC);

			$this->data->courses = $data;
		}

		if($this->data->ids->studentId) {
			$data = $db->query(
				'SELECT L.id as levelId, L.name as levelName,
					UC.id as cursusId, UC.name as cursusName,
					Y.id as yearId, Y.year as yearName
				FROM level L
					INNER JOIN universitycourse UC ON (UC.id = L.id_universitycourse)
					INNER JOIN student S ON (S.id_level = L.id)
					INNER JOIN universityyear Y ON (Y.id = S.id_year)
				WHERE S.id = :studentId',
				[
					'studentId' => $this->data->ids->studentId
				]
			)->fetchObject();

			$this->data->level = $data;
		}
	}

	protected function modifyUser($req, $res) {
		$db = $res->getDatabase();

		$accountFields = [
			// param => sql field
			'mail' => 'mail'
		];

		$userFields = [
			// param => sql field
			'firstname' => 'firstname',
			'lastname' => 'lastname',
			'uuid' => 'uuid'
		];

		$roleField = $req->body['role']?? NULL;

		$stuLevel = $req->body['level']?? NULL;
		$stuYearId = $this->getYearId($req, $res);


		$univId = $req->user->universityId;
		$userId = $req->params['userId'];


		$user = $db->query(
			'SELECT A.id as accountId, A.id_univ as univId, A.mail,
				S.id as studentId, T.id as teacherId
			FROM account A
			RIGHT JOIN user U ON (U.id_account = A.id)
			LEFT JOIN student S ON (S.id_user = U.id)
			LEFT JOIN teacher T ON (T.id_user = U.id)
			WHERE U.id = :userId',
			[
				'userId' => $userId
			]
		)->fetch();

		if(!$user || $user['univId'] !== $univId) {
			throw new ApiException(404);
		}


		$values = '';
		$queryParams = [];

		$queryParams['accountId'] = $user['accountId'];

		foreach($accountFields as $f => $c) {
			if(isset($req->body[$f])) {
				if($f === 'password') {
					$psswd = password_hash($req->body[$f], PASSWORD_DEFAULT);
					$queryParams[$f] = ($psswd);
				}
				else if($f === 'mail') {
					if(strpos($c, 'admin@') !== false) {
						throw new ApiException(400);
					}

					$queryParams[$f] = $req->body[$f];
				}
				else {
					$queryParams[$f] = $req->body[$f];
				}

				$values .= "$c = :$f,";
			}
		}

		$len = mb_strlen($values);

		try {
			$db->beginTransaction();

			if($len > 0) {
				if($values[$len-1] === ',') {
					$values = rtrim($values, ',');
				}

				$db->query(
					"UPDATE account
					SET $values
					WHERE id = :accountId",
					$queryParams
				);
			}

			$values = '';
			$queryParams = [];

			$queryParams['userId'] = $userId;

			foreach($userFields as $f => $c) {
				if(isset($req->body[$f])) {
					$queryParams[$f] = $req->body[$f];
					$values .= "$c = :$f,";
				}
			}

			$len = mb_strlen($values);

			if($len > 0) {
				if($values[$len-1] === ',') {
					$values = rtrim($values, ',');
				}

				$db->query(
					"UPDATE user
					SET $values
					WHERE id = :userId",
					$queryParams
				);
			}

			$queryParams = [
				'userId' => $userId
			];

			$hasAlreadyUpdatedStudent = false;


			if($roleField) {
				// now : student
				if(bitwiseAND($roleField, UserType::STUDENT)) {
					// before : was not student : update
					if(!$user['studentId']) {
						$hasAlreadyUpdatedStudent = true;

						$stuQP = $queryParams;
						$fields = ['id_user'];
						$values = [':userId'];

						if($stuYearId) {
							array_push($fields, 'id_year');
							array_push($values, ':yearId');
							$stuQP['yearId'] = $stuYearId;
						}
			
						if($stuLevel) {
							array_push($fields, 'id_level');
							array_push($values, ':levelId');
							$stuQP['levelId'] = $stuLevel;
						}

						$fields = join(',', $fields);
						$values = join(',', $values);

						$db->query(
							"INSERT INTO student ($fields)
							VALUES ($values)",
							$stuQP
						);
					}
				}
				// XOR
				// now : not student
				else {
					// before : was student : update
					if($user['studentId']) {
						$db->query(
							'DELETE FROM student WHERE id_user = :userId',
							$queryParams
						);
					}
				}

				// now : teacher
				if(bitwiseAND($roleField, UserType::TEACHER)) {
					// before : was not teacher : update
					if(!$user['teacherId']) {
						$db->query(
							'INSERT INTO teacher (id_user)
							VALUES (:userId)',
							$queryParams
						);
					}
				}
				// XOR
				// now : not teacher
				else {
					// before : was teacher : update
					if($user['teacherId']) {
						$db->query(
							'DELETE FROM teacher WHERE id_user = :userId',
							$queryParams
						);
					}
				}
			}

			if(!$hasAlreadyUpdatedStudent) {
				$values = [];
				$queryParams = [
					'studentId' => $user['studentId']
				];
				$needsUpdate = false;

				if($stuYearId) {
					array_push($values, 'id_year = :yearId');
					$queryParams['yearId'] = $stuYearId;
					$needsUpdate = true;
				}

				if($stuLevel) {
					array_push($values, 'id_level = :levelId');
					$queryParams['levelId'] = $stuLevel;
					$needsUpdate = true;
				}

				$values = join(',', $values);

				if($needsUpdate) {
					$db->query(
						"UPDATE student
						SET $values
						WHERE id = :studentId",
						$queryParams
					);
				}
			}

			$db->commit();
		}
		catch(Exception $e) {
			$db->rollback();
			throw new ApiException(500, $e->getMessage());
		}


		$oldPass = $req->body['oldPassword']?? NULL;
		$newPass = $req->body['newPassword']?? NULL;

		if ($oldPass && $newPass)
		{
			
			$userId = $req->params['userId'];

			$accountId = $res->getDatabase()->query(
				"SELECT A.id
				FROM account A
				LEFT JOIN user U ON (U.id_account = A.id)
				WHERE U.id = :userId", [
					"userId" => $userId
				]
			)->fetchColumn();
			
			if(!$accountId) {
				throw new ApiException(404);
			}

			if(!$res->auth()->verifyPasswordById($accountId, $oldPass)) {
				throw new ApiException(403);
			}
			
			$res->auth()->changePassword($accountId, $newPass);
		}
	}

	protected function createUser($req, $res) {
		if(
			!isset($req->body['mail']) ||
			!isset($req->body['password']) ||
			!isset($req->body['firstname']) ||
			!isset($req->body['lastname']) ||
			!isset($req->body['uuid']) ||
			!isset($req->body['role'])
		) {
			throw new ApiException(400, 'Missing fields');
		}

		$db = $res->getDatabase();

		$univId = $req->user->universityId;
		$mail = trim($req->body['mail']);
		$password = trim($req->body['password']);
		$firstname = trim($req->body['firstname']);
		$lastname = trim($req->body['lastname']);
		$uuid = trim($req->body['uuid']);
		$role = trim($req->body['role']);

		if(mb_strlen($mail) === 0 || mb_strlen($password) === 0
			|| mb_strlen($firstname) === 0 || mb_strlen($lastname) === 0
			|| mb_strlen($uuid) === 0 || mb_strlen($role) === 0
		) {
			throw new ApiException(400);
		}

		$role = intval($role);

		$isTeacher = bitwiseAND($role, UserType::TEACHER);
		$isStudent = bitwiseAND($role, UserType::STUDENT);

		if(!$isTeacher && !$isStudent) {
			throw new ApiException(400);
		}

		if($isStudent) {
			if(isset($req->body['levelId']) && isset($req->body['year'])) {
				$r = $db->query(
					'SELECT L.id
					FROM level L
						LEFT JOIN universitycourse UC ON (UC.id = L.id_universitycourse)
					WHERE L.id = :levelId
						AND UC.id_univ = :univId',
					[
						'univId' => $univId,
						'levelId' => $req->body['levelId']
					]
				)->fetchColumn();

				if(!$r) {
					throw new ApiException(400, 'Unknown level id');
				}

				$req->body['year'] = $db->query(
					'SELECT id
					FROM universityyear
					WHERE id_univ = :univId
						AND year = :year',
					[
						'univId' => $univId,
						'year' => $req->body['year']
					]
				)->fetchColumn();

				if(!$req->body['year']) {
					throw new ApiException(400, 'Unknown university year');
				}
			}
		}

		$levelId = $req->body['levelId']?? NULL;
		$yearId = $req->body['year']?? NULL;


		$users = $db->query(
			'SELECT A.id
			FROM account A
				LEFT JOIN user U ON (U.id_account = A.id)
			WHERE (U.uuid = :userUUID OR A.mail = :mail)
				AND A.id_univ = :univId',
			[
				'userUUID' => $uuid,
				'mail' => $mail,
				'univId' => $univId
			]
		)->fetchAll();

		if(count($users) > 0) {
			throw new ApiException(400, 'Users with same uuid or mail exist');
		}

		try {
			$db->beginTransaction();

			$db->query(
				'INSERT INTO account (id_univ, mail, password) VALUES
					(:univId, :mail, :password)',
				[
					'univId' => $univId,
					'mail' => $mail,
					'password' => password_hash($password, PASSWORD_DEFAULT)
				]
			);

			$accountId = $db->getLastInsertedId();

			if(!$accountId) {
				throw new ApiException(500, 'Failed to create account #1');
			}

			$db->query(
				'INSERT INTO user (id_account, firstname, lastname, uuid) VALUES
					(:accountId, :first, :last, :uuid)',
				[
					'accountId' => $accountId,
					'first' => $firstname,
					'last' => $lastname,
					'uuid' => $uuid
				]
			);

			$userId = $db->getLastInsertedId();

			if(!$userId) {
				throw new ApiException(500, 'Failed to create account #2');
			}

			if($isTeacher) {
				$db->query(
					'INSERT INTO teacher (id_user) VALUES
						(:userId)',
					[
						'userId' => $userId
					]
				);
			}

			if($isStudent) {
				$db->query(
					'INSERT INTO student (id_user, id_level, id_year) VALUES
						(:userId, :levelId, :yearId)',
					[
						'userId' => $userId,
						'levelId' => $levelId,
						'yearId' => $yearId
					]
				);
			}

			$db->commit();

			$this->data = [
				'userId' => $userId
			];
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

	protected function deleteUser($req, $res) {
		$user = $res->getDatabase()->query(
			'SELECT A.id
			FROM user U
				INNER JOIN account A ON (A.id = U.id_account)
			WHERE U.id = :userId
				AND A.id_univ = :univId',
			[
				'userId' => $req->params['userId'],
				'univId' => $req->user->universityId
			]
		)->fetch();

		if(!$user) {
			throw new ApiException(404);
		}

		$res->getDatabase()->query(
			'DELETE FROM account
			WHERE id = :accountId',
			[
				'accountId' => $user['id']
			]
		);
	}
}
