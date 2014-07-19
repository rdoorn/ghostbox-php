<?php

namespace Facebook;

function loginCheck( $request ) {
    if (isset($request->getRequestVars()[0]) && ($request->getRequestVars()[0]) == 'facebook') { // only respond to sub request /login/facebook
        $facebook = new facebookHandler();
        return $facebook->loginCheck();
    }
}
\hooks::add('login_handler', __namespace__.'\loginCheck' );


\hooks::add('user_profile_image', __namespace__.'\facebookHandler::profileImage',11 );
\hooks::add('album_profile_image', __namespace__.'\facebookHandler::profileImage',11 );

\hooks::add('logout_handler', facebookHandler::logout() );





?>