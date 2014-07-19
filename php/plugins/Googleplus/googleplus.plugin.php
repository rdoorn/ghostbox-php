<?php

namespace Googleplus;

function loginCheck( $request ) {
    if (isset($request->getRequestVars()[0]) && ($request->getRequestVars()[0]) == 'googleplus') { // only respond to sub request /login/googleplus
        $googleplus = new googleplusHandler();
        return $googleplus->loginCheck();
    }
}
\hooks::add('login_handler', __namespace__.'\loginCheck' );

\hooks::add('user_profile_image', __namespace__.'\googleplusHandler::profileImage' );
\hooks::add('album_profile_image', __namespace__.'\googleplusHandler::profileImage' );

\hooks::add('logout_handler', googleplusHandler::logout() );



?>