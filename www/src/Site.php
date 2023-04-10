<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */


defined('_NOX') or die('401 Unauthorized');


require_once(PATH_ENGINE . 'Site.php');
require_once(PATH_SERVICES . 'Authentication.php');
require_once(PATH_SERVICES . 'Api.php');



class WebSite extends Site {

    /** @var AuthenticationService */
    protected $authService;

    /** @var ApiService */
    protected $apiService;



    function __construct($config) {
        parent::__construct($config);


        // instantiate services
        $this->authService = new AuthenticationService();
        $this->apiService = new ApiService($this->router->getBaseUri());

        $this->authService->refreshStatus($this);

        //
        $this->loadPage();
    }




    protected function onPageLoaded($loadResult) {
        if($loadResult !== 0) {
            $this->router->loadEndpoint('404', $this);
        }
    }



    /* -------------------------- GETTERS -------------------------- */


    /**
     * @return AuthenticationService
     */
    public function auth() {
        return $this->authService;
    }

    /**
     * @return ApiService
     */
    public function api() {
        return $this->apiService;
    }





    //
    /**
     * @param string $viewPath
     * @param object $data
     */
    public function sendFile($viewPath, $data) {
        http_response_code($this->httpStatus);
        $this->data = $data;
        $this->includePage($viewPath);
    }
}