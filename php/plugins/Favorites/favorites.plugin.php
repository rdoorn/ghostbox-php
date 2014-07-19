<?php

namespace Favorites;

function favorites_menu( $request ) {

    // we are hooked in /profile
    // so we have a menu item if we have both a user, and are in the first sub
    // once we decend in to albums, we don't show this button
    $user = \Profile\profileHandler::getProfileId( $request );

    if ($user) {
        $path = $request->getRequestVars();
        //print join('/',$path).'<br>';
        //print "/profile/{$user->username}/favorites";
        if (sizeof($path)<2) {
        //if (($request->getResource() == 'profile') || ($request->getResource() == 'favorites')) {
            
            //$mainurl = "/profile/{$user->username}/favorites";
            $mainurl = "/favorites/{$user->username}";
            $urls['favorites']['url'] = $mainurl;

            if ($request->getResource() == 'favorites') {
                $urls['favorites']['selected'] = 1;
            }

            return $urls;
        }
    } 

}
\hooks::add('profile_menu_items', __NAMESPACE__.'\favorites_menu');



function favorites_article( $request ) {
    global $hook;
    $user = \Profile\profileHandler::getProfileId( $request );

    $start = 0;
    $length = 20;
    if (isset($request->getData()['start'])) { $start = $request->getData()['start']; }
    if (isset($request->getData()['length'])) { $length = $request->getData()['length']; }

    // only show on favorites page
    //if (preg_match("@{$user->username}/favorites@", join('/',$request->getRequestVars()) )) {

        $favorites = new favoritesObject();

        $favorites->initAlbumById( $user->albumid )
                           ->findFavorites( $user->uid )
                           ->sortAlbum( 
                            isset($request->getData()['sort'])?
                            array( $request->getData()['sort'] => isset($request->getData()['order'])?$request->getData()['order']:"asc"  ):
                            array( 
                                    'directory' => 'desc',
                                    'favoritesdate' => 'desc'
                                ) 
                            );
        if (isset($request->getData()['related'])) { 
            $start = key ( $album->findId($request->getData()['related']) ) -1;
            $length = 10;
        }

        $items = $favorites->cutAlbum($start, $length)
                           ->getCacheBySize( THUMB_SIZE)
                           ->getItems();

            //$items = $favorites->getFavorites( $user->uid );

        // Display the data
        if ($request->getHttpAccept() == 'json') { 
            //print json_encode($items);
            if (isset($items) && !empty($items)) {
                foreach ($items as $item) {
                    $json_items[] = $item->getJson();
                }
                print json_encode($json_items);
            }
        } else {
            // Show all items
            print '<div id="gallery_list">';
            if (isset($items)) {
                foreach ($items as $item) {
                    $hook->execute('gallery_item_thumbnail', $item, $request ); 
                }
            } else { print "empty folder" ;}
            print '</div>';
        }
    //}

}
\hooks::add('html_article_favorites', __NAMESPACE__.'\favorites_article',9);



/*
\hooks::add('head_css_favorites',  function () { 
    return '<link rel="stylesheet" type="text/css" href="/css/nav.css">'; 
});

/*
\hooks::add('head_js_favorites',  function () { 
      return '<script type="text/javascript" src="/js/profilescroll.js"></script> <!-- profile background image scroller //-->'.
           '<script type="text/javascript" src="/js/orderimages.js"></script> <!-- image order module //-->'.
           '<script type="text/javascript" src="/js/preloader.js"></script> <!-- image loader //-->'; 
; 
});
*/

// lift off the album view scripts


\hooks::add('head_css_favorites', '\Profile\profile_head_css' );
\hooks::add('head_js_favorites', '\Profile\profile_head_js' );
\hooks::add('head_css_favorites', '\Albums\albums_head_view_css' );
\hooks::add('head_js_favorites', '\Albums\albums_head_view_js' );

?>