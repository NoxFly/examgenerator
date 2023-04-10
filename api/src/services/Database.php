<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


class Database {
    protected $host = '';
    protected $port = 0;
    protected $database = '';
    protected $username = '';
    protected $password = '';

    protected $link = NULL;

    function __construct($config) {
        $this->host = $config['HOST'];
        $this->port = $config['PORT'];
        $this->database = $config['DATABASE'];
        $this->username = $config['USERNAME'];
        $this->password = $config['PASSWORD'];
    }


    public function connect() {
        if($this->link !== NULL) {
            echo 'Cannot connect to database : Already connected<br>';
            return true;
        }

		try {
            $this->link = new PDO("mysql:host=$this->host;port=$this->port;dbname=$this->database", $this->username, $this->password);

            $this->link->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
            $this->link->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            return true;
		}
        catch(PDOException $e) {
            print "[Database] Error : " . $e->getMessage() . "<br>";
			return false;
		}
    }

    public function disconnect() {
        $this->link->close();
        $this->link = NULL;
    }

    /**
     * @param string $req
     * @param array<string, mixed> $data
     */
    public function query($req, $data=[]) {
        if(!$this->link) {
            return [];
        }

        try {
            $res = $this->link->prepare($req);

            $result = $res->execute($data);

            if($result === false) {
                $errMsg = $this->link->errorInfo();
                throw new Exception('Failed to query (errno ' . intval($this->link->errorCode()) . ') -> ' . join('; ', $errMsg));
            }

            return $res;
        }
        catch(PDOException $e) {
            throw new ApiException(500, '[Database] PDO Error : ' . $e->getMessage());
        }
        catch(Exception $e) {
            throw new ApiException(500, '[Database] Error : ' . $e->getMessage());
        }
    }

    public function beginTransaction()
    {
        $this->link->beginTransaction();
    }

    public function commit()
    {
        $this->link->commit();
    }

    public function rollBack()
    {
        $this->link->rollBack();
    }

    public function getLastInsertedId() {
        return intval($this->link->lastInsertId());
    }
}