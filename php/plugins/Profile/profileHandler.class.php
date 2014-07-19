<?php

namespace Profile;

class profileHandler {
    private $database;
    private static $profile;

    public function __construct() {
        $this->database = new \databaseHelper();
    }

    public static function getProfileId( $request ) {
        if (isset($request->getRequestVars()[0])) {
            try {
                if (!isset(self::$profile[ strtolower($request->getRequestVars()[0]) ])) {
                    $user = new \userHelper();
                    self::$profile[ strtolower($request->getRequestVars()[0]) ] = $user->getUserByUsername( strtolower($request->getRequestVars()[0]) );
                }
                return self::$profile[ strtolower($request->getRequestVars()[0]) ];
            } catch ( \Exception $e ) {
                throw new \Exception("Invalid user: ".$request->getRequestVars()[0], 404);    
            }
        } else {
            throw new \Exception("No profile requested", 404);
        }
    }
/*
    public static function trimName( $name ) {
        if (strlen($name)> 20) { $name = substr($name,0,17)."..."; }
        return $name;
    }
*/
    public static function trimName($string, $limit) { 
        $string = self::trimString($string, $limit, ".");
        $string = self::trimString($string, $limit, ",");
        $string = self::trimString($string, $limit, " ");
        return $string;
    }

    public static function trimString($string, $limit, $break=" ", $pad="...") { 
            // return with no change if string is shorter than $limit 
        if(strlen($string) <= $limit) return $string; 

        // get max length
        $string = substr($string, 0, $limit); 
        if(false !== ($breakpoint = strrpos($string, $break))) { 
                $string = substr($string, 0, $breakpoint); 
        } 
        return $string . $pad; 
    }

}

?>