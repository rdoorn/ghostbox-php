<?php

namespace Facebook;

set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ .'/vendor/facebook-php-sdk-v4/src');

require_once('Facebook/GraphObject.php');
require_once('Facebook/FacebookSession.php');
require_once('Facebook/FacebookRedirectLoginHelper.php');
require_once('Facebook/FacebookRequest.php');
require_once('Facebook/FacebookSDKException.php');
require_once('Facebook/FacebookRequestException.php');
require_once('Facebook/FacebookAuthorizationException.php');
require_once('Facebook/FacebookResponse.php');
require_once('Facebook/GraphUser.php');
require_once('Facebook/GraphSessionInfo.php');

class facebookHandler {
    private static $database;
    private static $user_by_uid;

    // Setup Facebook session
    public function __construct() {
        self::$database = new \databaseHelper();
        FacebookSession::setDefaultApplication(FACEBOOK_APP_ID, FACEBOOK_APP_SECRET);
    }

    // Check current session, and redirect if none
    public function loginCheck() {
        $helper = new FacebookRedirectLoginHelper(FACEBOOK_LOGIN_URL);

        // see if a existing session exists
        if ( isset( $_SESSION ) && isset( $_SESSION['fb_token'] ) ) {
            // create new session from saved access_token
            $session = new FacebookSession( $_SESSION['fb_token'] );
            // validate the access_token to make sure it's still valid
            try {
                if ( !$session->validate() ) {
                    $session = null;
                }
            } catch ( Exception $e ) {
                // catch any exceptions
                $session = null;
            }
        } else {
        // no session exists        
            try {
                 $session = $helper->getSessionFromRedirect();
            } catch(FacebookRequestException $ex) {
                 throw new \Exception("Facebook reports error: ".$ex->getMessage(), 1);
            } catch(\Exception $ex) {
                throw new \Exception("Failed to validate: ".$ex->getMessage(), 2);
            }
        }
        if (isset($session)) {

             // save the session
            $_SESSION['fb_token'] = $session->getToken();

            // create a session using saved token or the new one we generated at login
            $session = new FacebookSession( $session->getToken() );

            // get the details using set session
            $details = $this->getDetails( $session );

            // format details for compatibility with userHelper class
            $details['handlername'] = 'facebook';
            $details['username'] = $details['first_name'];
            $details['displayname'] = $details['name'];
            //$details['email'] = $details['email'];
            $details['fields'] = array('id');

            return $details;
        } else {
            $this->loginRedirect( $helper->getLoginUrl( array( 'email' ) ) );
        }
    }

    public static function logout() {
        unset($_SESSION['fb_token']);
    }

    // Redirect to login page
    public function loginRedirect( $url ) {
        header("Cache-control: private, no-cache");
        header("Status: 302 Moved");
        header("Location: ".$url );
        exit;
    }

    // Get users session
    public function getDetails($session) {
        try {
            $user = (new FacebookRequest(
                    $session, 'GET', '/me'
                ))->execute()->getGraphObject()->asArray();
          
            if ($user['verified'] != 1) {
                throw new \Exception("Facebook account is not verified yet".$e->getMessage(), 5); 
            }
            return $user;

        } catch (FacebookRequestException $e) {
           throw new \Exception("Facebook reports error: ".$e->getMessage(), 3);

        } catch (\Exception $e) {
           throw new \Exception("Processing error: ".$e->getMessage(), 4);
        }
    
    }

    public static function profileImage( $uid , $size = 100 ) {
        if (!isset(self::$user_by_uid[$uid])) {
            // not cached, lets get it
            if (!self::$database) { self::$database = new \databaseHelper(); }
            $facebook = self::$database->query("SELECT id from facebook where uid=?", $uid);
            if ($facebook) {
                self::$user_by_uid[$uid] = $facebook[0];
            }
        } 

        if (isset(self::$user_by_uid[$uid])) {
            return 'https://graph.facebook.com/'.self::$user_by_uid[$uid]->id.'/picture?width='.$size.'&height='.$size;
        }
    }

}


// Session returns : Array ( [id] => 286170858216523 [email] => titor@ghostbox.org [first_name] => Ronald [gender] => male [last_name] => John Titor [link] => https://www.facebook.com/app_scoped_user_id/286170858216523/ [locale] => nl_NL [middle_name] => D [name] => Ronald D John Titor [timezone] => 2 [updated_time] => 2013-12-07T19:34:45+0000 [verified] => 1 )
// facebookHandler::profileImage
?>