<?php

// for GET requests -H "Accept: application/json"
// for PUT/POST requests -H "Content-Type: application/json"

// Curl test:
// curl -X GET -H "Accept: application/json" -d '' http://icarus.ghostbox.org/members
// curl -X POST -H "Content-Type: application/json" -d '{x:y}' http://icarus.ghostbox.org/members

class httpRequest {
    private static $http_method;
    private static $http_accept;
    private static $request_resource;
    private static $request_data;
    private static $request_path;
    private static $request_vars;
    private static $source_ip;

    public function __construct() {
        self::$http_method = strtolower($_SERVER['REQUEST_METHOD']);
        if (isset($_SERVER['HTTP_ACCEPT'])) {
        self::$http_accept = (strpos($_SERVER['HTTP_ACCEPT'], 'json')) ? 'json' : 'html';
        } else { self::$http_accept = 'html'; } // this is mostlikely bots
        self::$request_vars=array_filter(explode('/',explode('?',urldecode($_SERVER['REQUEST_URI']))[0]));
        self::$request_path=self::$request_vars;
        self::$request_resource=array_shift(self::$request_vars);
        self::$request_data=self::processData();

        self::$source_ip = (isset($_SERVER["HTTP_X_FORWARDED_FOR"])?$_SERVER["HTTP_X_FORWARDED_FOR"]:$_SERVER["REMOTE_ADDR"]);
        if (substr(self::$source_ip,0,7) == "127.0.0") { self::$source_ip = (isset($_SERVER["REMOTE_ADDR"])?$_SERVER["REMOTE_ADDR"]:""); }

    }

    private function processData() {
        global $_GET, $_POST;
        $data = array();
        switch (self::$http_method)
        {
            case 'get':
                $data = $_GET;
                break;
            case 'post':
                $data = $_POST;
                break;
            case 'put':
                //parse_str(file_get_contents('php://input'), $put_vars);
                //$data = $put_vars;
                $data = json_decode(file_get_contents('php://input'));
                break;
            default:
        }
        return $data;
    }

    public function getResource() {
        return self::$request_resource;
    }

    public function getRequestVars() {
        return self::$request_vars;
    }

    public function getData() {
        return self::$request_data;
    }

    public function getRequestMethod() {
        return self::$http_method;
    }

    public function getHttpAccept() {
        return self::$http_accept;
    }

    public function getPath( $offset = 0 ) {
        return array_slice(self::$request_path, $offset);
    }

    public function __toString() {
        return $this;
    }

    public static function sourceIP() {
        return self::$source_ip;
    }
}

?>
