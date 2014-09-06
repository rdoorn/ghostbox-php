<?php

namespace Googleplus;

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ .'/vendor/google-api-php-client/src');

require_once('Google/Client.php');
require_once('Google/Service/Plus.php');
//require_once($_SERVER["DOCUMENT_ROOT"].'/../php/api/google-api-php-client/src/contrib/Google_PlusService.php');
//require_once($_SERVER["DOCUMENT_ROOT"].'/../php/api/google-api-php-client/src/contrib/Google_Oauth2Service.php');

class googleplusHandler {
    private $client;
    private static $database;
    private static $user_by_uid;

    public function __construct() {
        self::$database = new \databaseHelper();
        $this->client = new \Google_Client();
        $this->client->setApplicationName(GOOGLEPLUS_APP_TITLE);
        $this->client->setClientId(GOOGLEPLUS_CLIENT_ID);
        $this->client->setClientSecret(GOOGLEPLUS_CLIENT_SECRET);
        $this->client->setRedirectUri(GOOGLEPLUS_LOGIN_URL);
        $this->client->setScopes(array('https://www.googleapis.com/auth/userinfo.email',
                 'https://www.googleapis.com/auth/plus.me')); 
    }

    // Check current session, and redirect if none
    public function loginCheck() {
        // 

        // alternative to fix php/lighttpd mistreating / in the code return
        $req = explode('?',urldecode($_SERVER['REQUEST_URI']));
        if (isset($req[1])) { 
            $res = explode('=',$req[1]);
            if ($res[0] == 'code') { $code = $res[1]; }
        }

        debug(4, __FUNCTION__.": session before: ".print_r($_SESSION,1));
        if (isset($code)) {
            debug(4, __FUNCTION__.": code is set");
            $this->client->authenticate($code);
            $_SESSION['gp_token'] = $this->client->getAccessToken();
            //print 'redirect: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
            //header('Location: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
            //$this->loginRedirect(GOOGLEPLUS_LOGIN_URL);
        }
        // If we have a access token, use it
        if (isset($_SESSION['gp_token']) && $_SESSION['gp_token']) {
            debug(4, __FUNCTION__.": gp_token is set");
            $this->client->setAccessToken($_SESSION['gp_token']);
        } else {
            debug(4, __FUNCTION__.": no auth, creating url");
            $authUrl = $this->client->createAuthUrl();
            $this->loginRedirect($authUrl);
        }
        if ($this->client->getAccessToken()) {
            debug(4, __FUNCTION__.": auth ok, get data");
            $_SESSION['gp_token'] = $this->client->getAccessToken();
            $token_data = $this->client->verifyIdToken()->getAttributes();

            // get the details using set session
            $details = $this->getDetails( $this->client );

            return $details;
        }
        debug(4, __FUNCTION__.": session after: ".print_r($_SESSION,1));
        

    }

    public static function logout() {
        unset($_SESSION['gp_token']);
    }

    // Redirect to login page
    public function loginRedirect( $url ) {
        header("Cache-control: private, no-cache");
        header("Status: 302 Moved");
        header("Location: ".$url );
        exit;
    }

    // Get users session
    public function getDetails( $client ) {
        $plus = new \Google_Service_Plus($client);        
        $profile = $plus->people->get('me');
        $details = array();
        $details['handlername'] = 'googleplus';
        $details['id'] = $profile->getId();
        $details['username'] = $profile->getName()->getGivenName();
        $details['displayname'] = $profile->getDisplayname();
        $details['email'] = $profile->getEmails()[0]->getValue();
        $details['image'] = explode('?',$profile->getImage()->getUrl())[0];
        $details['fields'] = array('id','image');

        return $details;
    }

    public static function profileImage( $uid , $size = 100 ) {
        if (!isset(self::$user_by_uid[$uid])) {
            if (!self::$database) { self::$database = new \databaseHelper(); }
            $gp = self::$database->query("SELECT image from googleplus where uid=?", $uid);
            if ($gp) {
                self::$user_by_uid[$uid] = $gp[0];
            }
        }
        if (isset(self::$user_by_uid[$uid])) {
            return self::$user_by_uid[$uid]->image.'?sz='.$size;
        }        
    }

}



?>
