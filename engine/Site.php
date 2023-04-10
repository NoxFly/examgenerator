<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */


defined('_NOX') or die('401 Unauthorized');


require_once(PATH_ENGINE . 'Router.php');


abstract class Site {

    /** @var Router $router */
    protected $router;

    protected $httpStatus = 200;


    /** @var StdClass */
    public $req;
    /** @var StdClass */
    public $data;

    protected $content = '';
    protected $appName = '';
    protected $title = '';
    protected $template = '';
    protected $view = '';


    function __construct($config) {
        $this->req = (object)array();
        $this->data = (object)array();

        $this->req->env = $config['ENV']?? 'production';

        if($this->req->env === 'dev') {
            $this->req->env = 'development';
        }

        elseif($this->req->env === 'prod') {
            $this->req->env = 'production';
        }

        // enable error output if in development mode
        if($this->req->env === 'development') {
            ini_set('display_errors', 1);
            error_reporting(E_ALL);
        }
        else {
            ini_set('display_errors', 0);
            error_reporting(0);
        }

        $this->appName = $config['APP']['application_name']?? 'Unamed Application';
        $this->template = $config['APP']['template_page']?? NULL;


        // instantiate services
        $this->router = new Router($config['ROUTES']);
    }

    protected function loadPage() {
        $page = '';

        $queryString = $_SERVER['QUERY_STRING'];

        if(true || !empty($queryString)) {
            $queryIndex = strpos($_SERVER['REQUEST_URI'], '?');

            if($queryIndex !== false) {
                $queryParams = substr($_SERVER['REQUEST_URI'], $queryIndex+1);
                $queryString = str_replace($queryParams, '', $queryString);
            }

            $page = $queryString;

			// if url ends with / : redirects without it
			if(substr($page, -1) == '/') {
                $newPage = '/' . rtrim($page, '/');
                $this->redirect($newPage);
			}
		}

        // tries to load the requested endpoint
        $this->onPageLoaded($this->router->loadEndpoint($page, $this));
    }

    /**
     * @param int $loadResult
     */
    abstract protected function onPageLoaded($loadResult);





    /* -------------------------- GETTERS -------------------------- */


    /**
     * @return Router
     */
    public function getRouter() {
        return $this->router;
    }

    /**
     * @return string
     */
    public function getTitle() {
        return $this->appName . (($this->title === '')? '' : ' - ' . $this->title);
    }

    /**
     * @return string
     */
    public function getAppName() {
        return $this->appName;
    }

    /**
     * @return string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * @param string $endUrl
     * @return string
     */
    public function url($endUrl) {
        return $this->router->getUrl($endUrl);
    }


    

    /* -------------------------- CONTENT CREATION & INCLUDES -------------------------- */

    protected function createContent() {
		ob_start();
		$this->includePage($this->view);
		$this->content = ob_get_clean();
    }


    /**
     * @param string $url
     */
    protected function include($url) {
        if(file_exists($url)) {
            return include($url);
        }

        return '';
    }

    /**
     * @param string $url
     */
    protected function require($url) {
        if(file_exists($url)) {
            return require($url);
        }

        return '';
    }



    /**
     * @param string $url
     */
    public function includePage($url) {
        return $this->include(PATH_VIEWS . SEP . $url . '.php');
    }

    /**
     * @param string $url
     */
    public function includeComponent($url) {
        return $this->require(PATH_PUBLIC . SEP . "components" . SEP . $url);
    }
    
    /**
     * @param string $url
     */
    public function requireComponent($url) {
        return require(PATH_PUBLIC . SEP . "components" . SEP . $url);
    }



    /**
     * @param string $filename
     */
    public function includeCSS($filename) {
        echo "<link rel='stylesheet' type='text/css' href='asset/css/$filename.css'>";
    }

    /**
     * @param string $filename
     */
    public function includeJS($filename) {
        echo "<script src='js/$filename.js' type='module'></script>";
    }




    /* -------------------------- RENDERING -------------------------- */

    /**
     * @param int $status
     * @return Site
     */
    public function status($status) {
        $this->httpStatus = $status;
        return $this;
    }


    /**
     * @param string $url
     */
    public function redirect($url) {
        http_response_code($this->httpStatus);
        header('location: ' . $this->url($url));
        die(0);
    }


    /**
     * @param string $data
     */
    public function send($str) {
        http_response_code($this->httpStatus);
        echo "$str";
    }


    /**
     * @param mixed $data
     */
    public function json($data) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($this->httpStatus);
        echo json_encode($data);
    }
    

    /**
     * @param string $viewPath
     * @param object $data
     */
    public function render($viewPath, $data=NULL) {
        if(!$data) {
            $data = array();
        }
        
        $this->data = $data;

        $this->view = $viewPath;

        //

        http_response_code($this->httpStatus);

        //

        $this->createContent();
        
        if(preg_match('#<h1>(.*)</h1>#', $this->content, $aMatches) && !empty($aMatches[1])) {
			$title = trim($aMatches[1]);

            if($title !== $this->appName) {
                $this->title = $title;
            }
		}


        //

        $this->includeComponent($this->template);
    }
}