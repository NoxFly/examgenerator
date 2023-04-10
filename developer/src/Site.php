<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */


defined('_NOX') or die('401 Unauthorized');


require_once(PATH_ENGINE . 'Site.php');



class DeveloperSite extends Site {

    protected $content = '';
    protected $appName = '';
    protected $title = '';
    protected $template = '';
    protected $view = '';

    protected $httpStatus = 200;

    /** @var StdClass */
    public $req;
    /** @var StdClass */
    public $data;

    

    function __construct($config) {
        parent::__construct($config);

        $this->loadPage();
    }




    protected function onPageLoaded($loadResult) {
        if($loadResult !== 0) {
            $this->router->loadEndpoint('404', $this);
        }
    }
}