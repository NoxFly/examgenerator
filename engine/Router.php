<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


class RoutePath {
    /** @var string $path */
    public $path = '/';
    /** @var int $allowedMethods */
    public $methods = [];
    /** @var RoutePath[] $children */
    public $children = [];

    /**
     * @param string $path
     */
    function __construct($path) {
        $this->path = $path;
    }
}

class Router {
    protected $mw;

    // tree of routes and subroutes. A route/subroute can have - or not - a controller
    // if it has a controller, it is an endpoint.
    /** @var RoutePath $routes */
    protected $routes;

    /** @var array */
    protected $controllers = [];

    /**
     * @var string $protocol
     * @var string $rootUrl
     * @var string $basePath
     * @var string $baseDir
     * @var string $baseUrl
     * @var string $baseUri
     */
    protected $protocol, $rootUrl, $basePath, $baseDir, $baseUrl, $baseUri;
    protected $query = array();

    function __construct($oRoutes) {
        $_ = rtrim(str_replace('/', SEP, $_SERVER['DOCUMENT_ROOT']), SEP);

        if(defined('PATH_PUBLIC')) {
            $this->basePath = PATH_PUBLIC;
        }
        else if(defined('PATH_ROOT')) {
            $this->basePath = PATH_ROOT;
        }
        else {
            $this->basePath = realpath('.');
        }

		$this->protocol 	= (strpos($_SERVER['SERVER_PROTOCOL'], 'HTTPS') === 0)? 'https' : 'http';
        $this->baseDir      = str_replace($_, '', $this->basePath);
		$this->rootUrl 	    = str_replace('\\', '/', str_replace($_, '', PATH_ROOT));
        $this->baseUrl      = str_replace('\\', '/', $this->baseDir);
        $this->baseUri      = $this->protocol . '://' . $_SERVER['HTTP_HOST'] . $this->rootUrl;

        $aURI = explode('?', $_SERVER['REQUEST_URI']);
        $query = $aURI[1]?? '';

        $this->routes = new RoutePath('/');

        if($query) {
            $query = explode('&', $query);

            foreach($query as $v) {
                $kv = explode('=', $v);
                $this->query[$kv[0]] = $kv[1];
            }
        }

        $this->buildRouteTree($oRoutes);
    }


    protected function buildRouteTree($oRoutes) {
        foreach($oRoutes as $method => $routes) {
            foreach($routes as $route) {
                $r = '/';
                $c = '';

                $endpt = explode(' ', preg_replace('/\/+/', '/', preg_replace('/[\t\s]+/', ' ', $route)));

                if(count($endpt) === 1) {
                    $r = '/';
                    $c = $endpt[0];
                }
                else {
                    [$r, $c] = $endpt;
                }

                $c = str_replace('-', '', $c);

                // detect if the controller exists.
                // if not, do not proceed.
                $routeCtrlpath = PATH_SRC . SEP . 'controllers' . SEP . $c . '.ctrl.php';

                if(!file_exists($routeCtrlpath)) {
                    continue;
                }

                $ctrlNameIdx = strrpos($c, '/');

                if($ctrlNameIdx === false)
                    $ctrlNameIdx = -1;

                $routeCtrlClassName = substr($c, $ctrlNameIdx+1) . 'Controller';

                include_once($routeCtrlpath);

                if(!class_exists($routeCtrlClassName)) {
                    continue;
                }
                // ----

                // the controller exist : build the endpoint
                
                if($r[0] !== '/') {
                    $r = '/' + $r;
                }

                if(mb_strlen($r) === 1) {
                    $r = [$r];
                }
                else {
                    $r = explode('/', $r);
                    
                    $r[0] = '/';
                }
                
                $this->buildEndpoint($this->routes, $r, $routeCtrlClassName, $method);
            }
        }
    }

    /**
     * RECURSIVE method
     * @param RoutePath $o - The Route tree / subtree
     * @param string[] $r - The route to add
     * @param string $c - The controller's name to add to the built endpoint
     * @param string $m - The method of the built endpoint
     * @param int $rOffset - The route's fragment index to treat at this iteration
     */
    protected function buildEndpoint($o, $r, $c, $m, $rOffset=0) {
        $l = count($r) - 1;
        $frag = $r[$rOffset];
        $isEndpoint = $rOffset === $l;

        // endpoint reached
        if($isEndpoint) {
            // the path corresponds
            if($o->path === $frag) {
                // create the controller if not previously existing (for exampel if it already has another method activated)
                $o->methods[$m] = $c;

                if(!array_key_exists($c, $this->controllers)) {
                    $this->controllers[$c] = new $c();
                }
            }

            return;
        }

        $nextFrag = $r[$rOffset+1];

        // build the frag path for the next iteration
        if(!array_key_exists($nextFrag, $o->children)) {
            $o->children[$nextFrag] = new RoutePath($nextFrag);
        }

        $this->buildEndpoint($o->children[$nextFrag], $r, $c, $m, $rOffset+1);
    }

    /**
     * @return string
     */
    public function getRootUrl() {
        return $this->rootUrl;
    }

    /**
     * @return RoutePath
     */
    public function getRoutes() {
        return $this->routes;
    }

    /**
     * @return string
     */
    public function getBaseUri() {
        return $this->baseUri;
    }

    /**
     * @param string $page
     */
    public function getUrl($page) {
        if($page && mb_strlen($page) > 0 && $page[0] === '/') {
            $page = substr($page, 1);
        }

        return $this->baseUri . $page;
    }

    /**
     * @return array
     */
    public function getQuery() {
        return $this->query;
    }



    /**
     * @param string $page
     * @param Site $site
     * @return boolean
     */
    public function loadEndpoint($page, $site) {
        $method = $_SERVER['REQUEST_METHOD'];

        $fragRoute = ($page === '')? ['/'] : explode('/', '/' . $page);
        $fragRoute[0] = '/';
        $fl = count($fragRoute) - 1;

        $routes = $this->routes;
        $route = '';
        $params = array();
        $controller = NULL;

        foreach($fragRoute as $k => $frag) {
            // param
            if($routes->path[0] === ':') {
                $params[substr($routes->path, 1)] = $frag;
            }

            if($k === $fl) {
                $controller = $routes->methods[$method]?? NULL;
                break;
            }
            else {
                $nextFrag = $fragRoute[$k+1];

                if(isset($nextFrag)) {
                    $nextRoutes = $routes->children[$nextFrag]?? NULL;

                    if($nextRoutes === NULL) {
                        foreach($routes->children as $p => $v) {
                            $p = strval($p);

                            if($p[0] === ':') {
                                $nextRoutes = $routes->children[$p];
                                break;
                            }
                        }

                        if($nextRoutes === NULL) {
                            break;
                        }

                    }

                    $route .= '/' . $nextRoutes->path;
                    $routes = $nextRoutes;
                }
            }
        }

        
        if(!$controller) {
            return 1;
        }


        $oController = $this->controllers[$controller];


        $req = array(
            'method' => $method,
            'headers' => getallheaders(),
            'route' => $route,
            'page' => $page,
            'uri' => explode('?', $_SERVER['REQUEST_URI'])[0],
            'query' => $this->query,
            'params' => $params,
            'body' => [],
            'files' => $_FILES,
            'rootUrl' => $this->rootUrl,
            'baseUrl' => $this->baseUrl,
            'baseUri' => $this->baseUri
        );

        if($method === 'POST' || $method === 'PUT') {
            if(empty($$_)) {
                $bodyContent = file_get_contents('php://input')??'';
                
                if(mb_strlen($bodyContent) > 0) {
                    if($bodyContent[0] === '{') {
                        $req['body'] = json_decode($bodyContent, true);
                    }
                    else {
                        parse_str($bodyContent, $req['body']);
                    }
                }
            }
            else {
                $req['body'] = $$_;
            }
        }

        $site->req = (object)array_merge((array)$site->req, $req);

        
        if(!$oController->guard->canActivate($site->req, $site)) {
            return 2;
        }


        $oController->{$method}($site->req, $site);

        return 0;
    }
}