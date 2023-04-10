<?php

/**
 * @copyright Copyrights (C) 2023 Dorian Thivolle All rights reserved.
 * @author Dorian Thivolle
 * @since 2023
 * @package uha.archi_web
 */

defined('_NOX') or die('401 Unauthorized');


require_once(PATH_ENGINE_TEMPLATES . 'Controller.php');


class HomeController extends Controller {
    /**
     * @param stdClass $req
     * @param DeveloperSite $res
     */
    public function GET($req, $res) {
        $data = [
            'tabs' => []
        ];

        $config = parse_ini_file(PATH_API . 'conf/config.ini', true);
        $oEndpoints = $config['ROUTES']?? [];

        $api = $req->params['api']?? NULL;

        $endpoints = [];
        $tabs = [];

        foreach($oEndpoints as $method => $edpts) {
            foreach($edpts as $edpt) {
                $path = explode(' ', preg_replace('/\s+/', ' ', $edpt))[0];
                $root = preg_replace('/^([a-zA-Z0-9\-\s]+)\/?.*/', '$1', substr($path, 1));

                if($root !== '') {
                    array_push($tabs, $root);
                }

                if($api) {
                    $reg = '/^\/' . $api . '(\/[a-zA-Z0-9\-\s]+)*/';
                
                    if(preg_match($reg, $edpt)) {
                        $path = preg_replace('/:([a-zA-Z0-9\-\s]+)/', '{$1}', $path);
                        
                        if(!array_key_exists($path, $endpoints)) {
                            preg_match_all('/\{([a-zA-Z0-9\-\s]+)\}/', $path, $matches, PREG_OFFSET_CAPTURE);

                            $endpoints[$path] = [
                                'path' => $path,
                                'methods' => [],
                                'params' => array_splice($matches, 1)[0]
                            ];
                        }

                        array_push($endpoints[$path]['methods'], $method);
                    }
                }
            }
        }

        $tabs = array_unique($tabs);
        sort($tabs);

        $data = [
            'endpoints' => $endpoints,
            'tabs' => $tabs
        ];

        $res->render('home', $data);
    }
}