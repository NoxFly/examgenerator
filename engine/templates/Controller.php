<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


require_once('Guard.php');


abstract class Controller {
    public $guard;

    function __construct() {
        $this->guard = new Guard();
    }

    /**
     * @param stdClass $req
     * @param Site $res
     */
    public function GET($req, $res) {}
    /**
     * @param stdClass $req
     * @param Site $res
     */
    public function POST($req, $res) {}
    /**
     * @param stdClass $req
     * @param Site $res
     */
    public function PUT($req, $res) {}
    /**
     * @param stdClass $req
     * @param Site $res
     */
    public function PATCH($req, $res) {}
    /**
     * @param stdClass $req
     * @param Site $res
     */
    public function OPTIONS($req, $res) {}
    /**
     * @param stdClass $req
     * @param Site $res
     */
    public function DELETE($req, $res) {}
}