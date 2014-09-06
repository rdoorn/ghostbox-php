<?php

include_once('php/config/init.php');
    
//$request = new rest();
$httpRequest = new httpRequest();

// Insert headers
$hook->execute('head');


try {

    if (!defined('EXAMPLE_CONFIG')) {
        $currentUser = new userHelper();
        $user = $currentUser->getUserBySession( session_id() );
    } 

    
    //$currentUser->getUserBySession( session_id() );

    // check what resoirce we are calling
    switch ( $httpRequest->getResource() ) {
        case 'login':
            if (!isset($_SESSION['pwd'])) { $_SESSION['pwd'] = $_SERVER['HTTP_REFERER']; }
            // needs rethought - this will deny google/facebook account linking if we skip login with session
            // the login button is gone when logged in, maybe don't check this?
            //if ( !$currentUser->getUserBySession( session_id() ) ) { // only if we don't have a session do a login
                // fixme - needs sokething to handle multiple login types return values
                $user = $hook->execute('login_handler', $httpRequest );
                if ( $user ) {
                    $currentUser->registerUser( $user );
                } else {
                    throw new Exception("No details were returned by login handler.", 1); // NOTICE: this happens when browser reloads at login page - redirect to / ?
                }
            //} 
            if ( isset($_SESSION['pwd'])) { 
                $url = $_SESSION['pwd'] ;
                unset($_SESSION['pwd']);
                Redirect($url);
            } else {
                Redirect('/');
            }
            break;
        case 'logout':
            if (!isset($_SESSION['pwd'])) { $_SESSION['pwd'] = $_SERVER['HTTP_REFERER']; }
            $hook->execute('logout_handler', $httpRequest );
            if ( isset($_SESSION['pwd'])) { 
                $url = $_SESSION['pwd'] ;
                unset($_SESSION['pwd']);
                Redirect($url);
            } else {
                Redirect('/');
            }
            break;
        case 'generate':
            $hook->execute('image_generator', $httpRequest );
            break;
        case 'setup':
            $hook->execute('html_layout');
            print "Starting Setup!";
            $hook->execute('setup', $httpRequest );
            break;
/*        case 'submit':
            $hook->execute('json_submit', $httpRequest );
            break;*/
/*        case '':
            print "main page";
            break;*/
        default:
            //$hook->execute('page_view_'.$httpRequest->getResource(), $httpRequest );
            if (defined('EXAMPLE_CONFIG')) { # First time setup - redirect to /setup
                Redirect($_SERVER["REQUEST_SCHEME"].'://'.$_SERVER["HTTP_HOST"].dirname($_SERVER["REQUEST_URI"]."x").'/setup');
            }

            if ($httpRequest->getRequestMethod() == 'put') {
                if (isset($user)) {
                    $hook->execute('put_article_'.$httpRequest->getResource(), $httpRequest, $user);
                } else {
                    throw new Exception("You have to be logged in to perform this action.", 403);
                }
            } elseif ($httpRequest->getHttpAccept() == 'json') {
                $hook->execute('json_article_'.$httpRequest->getResource(), $httpRequest);
            } else {
                $hook->execute('html_layout');
            }
            break;
    }


} catch( Exception $e ) {
    // display error, and pass how to display it
    displayError($e, $httpRequest->getHttpAccept());
}


?>
