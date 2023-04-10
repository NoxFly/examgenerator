<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


class AuthenticationService {

    protected $bLogged = false;
    /** @var Database $database */
    protected $database;

	protected $TOKEN_TTL = 86400 * 30 * 1000; // 1 day * 30 from seconds to ms
	protected $tokenSize = 26;

	protected $salt = 'NFAPI-';

	private $loginAdminQuery = 'SELECT
			ACC.mail, ACC.id as accountId,
			UNI.id as universityId, UNI.name as universityName
		FROM account ACC
		RIGHT JOIN university UNI ON (ACC.id_univ = UNI.id)
		WHERE ACC.mail = :mail';

	private $loginUserQuery = 'SELECT
			ACC.mail, ACC.id as accountId,
			USR.firstname, USR.lastname, USR.uuid, USR.id,
			UNI.id as universityId, UNI.name as universityName,
			TEA.id as teacherId,
			STU.id as studentId, STU.id_level as studentLevel, STU.id_year as studentYear
		FROM account ACC
		RIGHT JOIN university UNI ON (ACC.id_univ = UNI.id)
		LEFT JOIN user USR ON (USR.id_account = ACC.id)
		LEFT JOIN teacher TEA ON (TEA.id_user = USR.id)
		LEFT JOIN student STU ON (STU.id_user = USR.id)
		WHERE ACC.mail = :mail';


    /**
     * @param Database $db
     */
    function __construct($db) {
        session_start();

        $this->database = $db;
    }



	/**
	 * @return string
	 */
	private function generateRandomToken() {
		return $this->salt . generateRandomString($this->tokenSize);
	}

	/**
	 * @param array $query
	 * @return string
	 */
	public function getToken($query) {
		$headers = getallheaders();

		if(array_key_exists('X-Auth-Token', $headers)) {
			return $headers['X-Auth-Token'];
		}
		else if(array_key_exists('x-auth-token', $headers)) {
			return $headers['x-auth-token'];
		}
		else if(isset($query['api_key'])) {
			return $query['api_key'];
		}

		return NULL;
	}

	/**
	 * Changes the password for a given user.
	 * Ensure that the given accountId exists, or it will throw an error.
	 * @param int $accountId
	 * @param string $newPass
	 * @return string
	 */
	public function changePassword($accountId, $newPass=NULL) {
		if(!$newPass) {
			$newPass = generateRandomString(12, true);
		}

		$hash = password_hash($this->salt . $newPass, PASSWORD_DEFAULT);

		$this->database->query(
			'UPDATE `account`
			SET `password` = :pass WHERE `id` = :id',
			[
				'pass' => $hash,
				'id' => $accountId
			]
		);

		return $newPass;
	}

	/**
	 * @param int $accountId
	 * @param string $password
	 */
	public function verifyPasswordById($accountId, $password) {
		$realPass = $this->database->query(
			"SELECT password
			FROM account
			WHERE id = :accountId", [
				"accountId" => $accountId
			]
		)->fetchColumn();

		return $realPass && password_verify($this->salt . $password, $realPass);
	}

	/**
	 * Tries to login the user that has the given login / password.
	 * 
	 * If it succeed, then generate a token and returns it. Adds a row in the database.
	 * If the token already exists :
	 * - if it expired, recreate it
	 * - if not, then don't re-generate it, just send it, and don't add a row.
	 * 
	 * @param string $login
	 * @param string $password
	 */
	public function login($login, $password) {
        $isAdmin = substr($login, 0, 6) === 'admin@';

		$res = $this->database->query(
            'SELECT password, token, UNIX_TIMESTAMP(C.created_at) as createdAt
            FROM account A
				LEFT JOIN connection C ON (C.id_account = A.id)
            WHERE mail = :mail',
            [
                'mail' => $login
            ]
        )->fetch();

        if(!$res || !password_verify($this->salt . $password, $res['password'])) {
            return NULL;
        }

		$query = $isAdmin? $this->loginAdminQuery : $this->loginUserQuery;

        $user = $this->database->query($query, [
            'mail' => $login
        ])->fetchObject();

        if($user === false) {
            return NULL;
        }

		//

		if($res['createdAt'] !== NULL && time() - $res['createdAt'] >= $this->TOKEN_TTL) {
			$this->logoutById($user->accountId);
			$res['createdAt'] = NULL;
			$res['token'] = NULL;
		}

		if($res['token'] === NULL) {
			$token = $this->generateRandomToken();

			$this->database->query(
				'INSERT INTO connection (id_account, token)
					VALUES (:id, :token)',
				[
					'id' => $user->accountId,
					'token' => $token
				]
			);
		}
		else {
			$token = $res['token'];
		}
		

		return [
			'token' => $token,
			'user' => $user
		];
	}

	/**
	 * Log out a user finding him by his id.
	 * 
	 * @param int $id
	 */
	public function logoutById($id) {
		$this->database->query(
			'DELETE FROM connection WHERE id_account = :id',
			[
				'id' => $id
			]
		);
	}

	/**
	 * Log out a user finding him by his token.
	 * 
	 * @param string $token
	 */
	public function logoutByToken($token) {
		$this->database->query(
			'DELETE FROM connection WHERE token = :token',
			[
				'token' => $token
			]
		);
	}

	/**
	 * @param int $id
	 * @param string $domain
	 * @param string $password
	 */
	public function createUnivAccount($id, $domain, $password) {
		// hash du psswd
		$psswd = password_hash($this->salt . $password, PASSWORD_DEFAULT);

		// on cree le compte admin
		$this->database->query(
			'INSERT INTO account (id_univ, mail, password)
			VALUES (:id, :mail, :password)',
			[
				'id' => $id,
				'mail' => "admin@$domain",
				'password' => $psswd
			]
		);
	}

	/**
	 * Verify the token validity of the user who does the request.
	 * If valid: auth him for this request, and returns his basic informations.
	 * If no valid: delete his token, return NULL.
	 * 
	 * @param mixed $query
	 */
    public function refreshStatus($query) {
		$anoObj = (object)array(
			'privileges' => UserType::ANONYMOUS
		);

		$token = $this->getToken($query);

		if(!$token) {
			$this->bLogged = false;
			
			return $anoObj;
		}

		$isAuth = $this->database->query(
			'SELECT token, UNIX_TIMESTAMP(created_at) as createdAt, id_account as id
			FROM connection
			WHERE token = :token',
			[
				'token' => $token
			]
		)->fetch();

		if(!$isAuth) {
			$this->bLogged = false;
			
			return $anoObj;
		}

		if(time() - $isAuth['createdAt'] >= $this->TOKEN_TTL) {
			// remove row from db : expired
			$this->logoutByToken($token);
			$this->bLogged = false;
			
			return $anoObj;
		}

		$user = $this->database->query(
			'SELECT
				A.id, A.id_univ as universityId, A.mail,
				U.id as userId, U.uuid as userUUID,
				S.id as studentId, T.id as teacherId
			FROM account A
				LEFT JOIN user U ON (U.id_account = A.id)
				LEFT JOIN student S ON (S.id_user = U.id)
				LEFT JOIN teacher T ON (T.id_user = U.id)
			WHERE A.id = :id',
			[
				'id' => $isAuth['id']
			]
		)->fetchObject();

		if(!$user) {
			// remove row from db : user no longer exists
			$this->logoutByToken($token);
			$this->bLogged = false;

			return $anoObj;
		}

		$user->privileges = UserType::NONE;

		if(substr($user->mail, 0, 6) === 'admin@') {
			$user->privileges = UserType::ADMIN;
		}
		else {
			if($user->teacherId !== NULL) {
				$user->privileges |= UserType::TEACHER;
			}


			if($user->studentId !== NULL) {
				$user->privileges |= UserType::STUDENT;
			}
		}

		$user->year = intval(date('Y'));

		if (intval(date('m')) < 8) {
			$user->year--;
		}

		$this->bLogged = true;

		return $user;
	}

	/**
	 * @param string $accountId
	 * @param array $query
	 */
	public function checkTokenValidity($accountId, $query=NULL) {
		$token = $this->getToken($query);

		if(!$token) {
			return false;
		}

		$res = $this->database->query(
			'SELECT UNIX_TIMESTAMP(created_at) as createdAt
			FROM connection
			WHERE id_account = :id
				AND token = :token',
			[
				'id' => $accountId,
				'token' => $token
			]
		)->fetch();

		if(!$res || time() - $res['createdAt'] >= $this->TOKEN_TTL) {
			$this->database->query(
				'DELETE FROM connection
				WHERE id_account = :id',
				[
					'id' => $accountId
				]
			);

			return false;
		}

		return true;
	}

	public function isAuthenticated() {
        return $this->bLogged;
    }
}