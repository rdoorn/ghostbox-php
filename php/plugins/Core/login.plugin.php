<?php

/*
 *   Currently there is no login handler, user the facebook/google one for that. this does handle the default logout though.
 */



namespace Core;

function logout() {
    \userHelper::logoutUser();
}

\hooks::add('logout_handler', __namespace__.'\logout' );

?>