<?php



function html_header( $user, $request ) {
    global $hook;
    if (isset($user) ) {
        ?>
          <a href="/profile/<?php print $user->username; ?>">
           <img class="right" height="40" width="40" alt="profile image" src="<?php print $hook->execute('user_profile_image', $user->uid, 40 ); ?>">
          </a>
          <div id="header_profile_text">
           <?php print $user->displayname; ?><br />
           <a class="button" href="/logout">Logout</a>
          </div>
        <?php
    } else {
        ?>
         <div id="header_profile_login">
          Login with:
          <ul class="login-icons">
           <li class="facebook">
            <a class="connect-button" rel="nofollow" href="<?php echo FACEBOOK_LOGIN_URL; ?>"></a>
           </li>
           <!--
           <li class="twitter">
            <a class="connect-button" rel="nofollow" href="<?php echo "x" ?>"></a>
           </li> //-->
           <li class="googleplus">
            <a class="connect-button" rel="nofollow" href="<?php echo GOOGLEPLUS_LOGIN_URL; ?>"></a>
           </li>
          </ul>
         </div>
        <?php        
    } 
}
\hooks::add('html_header', __NAMESPACE__.'\html_header');


function html_footer() {
    print "Copyrighted ".date("Y")." @ Iceblade";
}
\hooks::add('html_footer', __NAMESPACE__.'\html_footer');


\hooks::add('html_layout', function () { include_once(TEMPLATE_DIR."/layout.php"); } );

?>

