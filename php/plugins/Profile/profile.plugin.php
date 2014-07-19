<?php

namespace Profile;

function nav_profile( $request ) {
    global $hook;

    $user = profileHandler::getProfileId( $request );

    $urls=$hook->execute('profile_menu_items', $request ); 

    if ($request->getResource() == 'albums') {
        $profile_image_big = $hook->execute('album_profile_image', $user->uid, 100, $request );
        $profile_image_small = $hook->execute('album_profile_image', $user->uid, 50, $request );
    } else {
        $profile_image_big = $hook->execute('user_profile_image', $user->uid, 100 );
        $profile_image_small = $hook->execute('user_profile_image', $user->uid, 50 );
    }
 
    ?>
      <div id="nav_profile_photo">
          <div id="nav_background_photo" style="background-image: url(/images/placeholder.jpg); margin-top:-35px;"></div>
          <div id="nav_profile_mask"></div>
      </div>
      <a href="/profile/<?php print $user->username; ?>">
          <div id="nav_profile_image" style="background-image: url(<?php print $profile_image_big; ?>);">
              <img height="50" width="50" src="<?php print $profile_image_small; ?>">
          </div>
      </a>
      <div id="nav_profile_name"><?php print $user->displayname; ?></div>
      <div id="nav_profile_description"><?php print $hook->execute('user_profile_description', $request); ?></div>
      <div id="nav_bar">
      <ul>
      <?php
      // set default?
      $default = 1;
      foreach ($urls as $name => $details) {
         if (isset($details['selected'])) { $default = 0; }
      }

      foreach ($urls as $name => $details) {
          print '<li><a href="'.$details['url'].'" '.((isset($details['selected'])||$default==1)?'class="selected"':'').'>'.profileHandler::trimName($name, 25).'</a></li>';
          $default=0;
      }
      ?>
       <li style="float:right"><a href="">Edit</a></li>
      </ul>
      </div>
    <?php
    
}
\hooks::add('html_nav_profile', __namespace__.'\nav_profile' );
\hooks::add('html_nav_albums', __namespace__.'\nav_profile' );
\hooks::add('html_nav_favorites', __namespace__.'\nav_profile' );



/*
\hooks::add('head_css_profile',  function () { 
    return '<link rel="stylesheet" type="text/css" href="/css/nav.css">'; 
});
*/

// CSS and JavaScript

function profile_head_css( $request ) { 
    return array('<link rel="stylesheet" type="text/css" href="/css/nav.css">'); 
}

function profile_head_js( $request ) { 
    global $user;
    $profile = \Profile\profileHandler::getProfileId( $request );

    return array('<script type="text/javascript" src="/js/profilescroll.js"></script> <!-- profile background image scroller //-->');
};
\hooks::add('head_js_profile', __namespace__.'\profile_head_js' );
\hooks::add('head_css_profile', __namespace__.'\profile_head_css' );



?>