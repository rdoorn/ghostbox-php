<?php

namespace Albums;

function albums_menu( $request ) {

    // we are hooked in /profile
    // so we have a menu item if we have both a user, and are in the first sub
    // once we decend in to albums, we don't show this button
    $user = \Profile\profileHandler::getProfileId( $request );

    if ($user) {
        $path = $request->getRequestVars();

        //print_r($path);
        // create home folder
        $mainurl = "/albums/{$user->username}";
        $urls['albums']['url'] = $mainurl;
        //if (end($path) == $user->username) {
            //$urls['albums']['selected'] = 1;
        //}

        // get album path to generate nav screen
        if ($request->getResource() == 'albums') {

            $album = new \AlbumObject();
            $items = $album->initAlbumByPath( $path )->getPathItems();
            array_shift($items);
    
            // show remaining folders
            $mainurl = "/albums/{$user->username}";
            foreach ($items as $item) {
                $mainurl .= "/". urlencode($item->get('name'));
                $urls[$item->get('displayname')]['url'] = $mainurl;
            }
        }
        if (!empty($items)) {
            $urls[end($items)->get('displayname')]['selected'] = 1;
        }
        return $urls;
    
    } 

}
\hooks::add('profile_menu_items', __NAMESPACE__.'\albums_menu');

function album_profile_image( $uid, $size, $request ) {
        if ($request->getResource() == 'albums') {
            $path = $request->getRequestVars();
            //$album = new albumsHandler();
            $album = new \AlbumObject();
            $albumids = $album->initAlbumByPath($path)->getParent(); 
            // skip home folder
            //array_shift($albumids);
            debug(1,print_r($albumids,true)) ;
        }
}
\hooks::add('album_profile_image', __namespace__.'\album_profile_image',13 );


function album_profile_description( $request ) {
        if ($request->getResource() == 'albums') {
            $album = new \AlbumObject();
            //$path = $request->getRequestVars();
            //$items = $album->initAlbumByPath( $path );
            $name = $album->getParent()->get('displayname');
            $description = $album->getParent()->get('description');
            if (isset($description) && ($description != "")) {
                $name .= ' - '.$description;
            }
            return $name;
        }

}
\hooks::add('user_profile_description', __NAMESPACE__.'\album_profile_description');


function albums_article( $request ) {
    global $hook;
    $user = \Profile\profileHandler::getProfileId( $request );
    //$album = new albumsHandler();

    $start = 0;
    $length = 20;
    if (isset($request->getData()['start'])) { $start = $request->getData()['start']; }
    if (isset($request->getData()['length'])) { $length = $request->getData()['length']; }
    // album start is in both /albums/uid as in /profile/uid

        // sub album
        $album = new \AlbumObject();
        $album->initAlbumByPath( $request->getRequestVars() ) // 62ms
                       ->findChildren()                                // 224ms
                       ->checkForNewItems()                            // 249ms 
                       //->sortAlbum(  $album->getParent()->get('sortby') , $album->getParent()->get('sortorder') ) // 268ms
                       ->sortAlbum( 
                            isset($request->getData()['sort'])?
                            array( $request->getData()['sort'] => isset($request->getData()['order'])?$request->getData()['order']:"asc"  ):
                            array( 
                               'directory' => 'desc',
                                $album->getParent()->get('sortby') => $album->getParent()->get('sortorder')
                            )

                        );
                       //->sortAlbum( 'directory' )
        if (isset($request->getData()['related'])) { 
            $start = key ( $album->findId($request->getData()['related']) ) -1;
            $length = 10;
        }
        $items = $album->cutAlbum($start, $length) // 265ms
                       ->getCacheBySize( THUMB_SIZE) // 295ms
                       ->getItems(); // 15ms



    // Display the data
    if ($request->getHttpAccept() == 'json') { 
        //print json_encode($items);
        if (isset($items) && !empty($items)) {
            foreach ($items as $item) {
                $json_items[] = $item->getJson(); // 10ms
            }
            //print "json";
            print '{"Data":'.json_encode($json_items).'}';
        } else {
            print '{"Data":null,"Message":"EOF"}';
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
}
//\hooks::add('html_article_profile', __NAMESPACE__.'\albums_article');
\hooks::add('html_article_albums', __NAMESPACE__.'\albums_article');

\hooks::add('json_article_albums', __NAMESPACE__.'\albums_article');
//\hooks::add('json_article_profile', __NAMESPACE__.'\albums_article');


// Submit function for album changes
function album_put( $request, $user ) {
    global $hook;
    $owner = \Profile\profileHandler::getProfileId( $request );

    if (preg_match("@{$owner->username}/*$@", join('/',$request->getRequestVars()) )) {
        // profile page
        $album = new \AlbumObject();
        $parent = $album->initAlbumById( $owner->albumid )->getParent();
        /*
        ->getParent();
        $path = $album->path();
        */

    } elseif ($request->getResource() == 'albums') {
        // album page
        $album = new \AlbumObject();
        $parent = $album->initAlbumByPath( $request->getRequestVars() )->getParent();
        /*
        ->getParent();
        $path = $album->path();
        */

    }

    // get submit data
    $json = $request->getData();

    // get item
    $item = new \ItemObject();
    $item->getId($json->Id);

    // Check that the user is the owner of the object
    if ($item->get('uid') != $user->uid) {
        throw new \Exception("You are not authorized to change this item.", 403);
    }

    // check if item and parent match
    if ($item->get('parentid') != $parent->get('itemid')) {
        throw new \Exception("item does not exist in this space", 404);
    }
    //$item->set('path', $path);
    //print "path: ".$item->get('path')."\n";

    $response = new \stdClass();

    $response->data = $hook->execute('put_article_albums_action', $json, $item, $album);
    // perform requested action        
    switch($json->Item) { // <----- this should be at the favorites section
        case 'addFavorite':
            break;
        case 'removeFavorite':
            break;
        /*default:
            throw new \Exception("Unsupported action", 404);
            break;*/

    }

    $response->Success = true;
    print json_encode($response);
    //print_r($item);
    
}
\hooks::add('put_article_albums', __NAMESPACE__.'\album_put');
\hooks::add('put_article_profile', __NAMESPACE__.'\album_put');




/*
\hooks::add('head_css_albums',  function ( $request ) { 
    global $user;
    $profile = \Profile\profileHandler::getProfileId( $request );

    if (isset($user) && ($user->username == $profile->username)) {
        $editCss='<link rel="stylesheet" type="text/css" href="/css/editor.css">';
    } else { $editCss = "";}
    return '<link rel="stylesheet" type="text/css" href="/css/nav.css">'.
            $editCss; 
});
*/

// CSS and JavaScript


function albums_head_edit_css( $request ) {
    global $user;
    $profile = \Profile\profileHandler::getProfileId( $request );

    if (isset($user) && ($user->username == $profile->username)) {
        return array('<link rel="stylesheet" type="text/css" href="/css/editor.css">');
    }
}

function albums_head_view_css( $request ) {
    return array('<link rel="stylesheet" type="text/css" href="/css/albums.css">');
}

function albums_head_edit_js( $request ) {
    global $user;
    $profile = \Profile\profileHandler::getProfileId( $request );

    if (isset($user) && ($user->username == $profile->username)) {
        return array('<script type="text/javascript" src="/js/contenteditable.js"></script> <!-- image loader //-->',
                '<script type="text/javascript" src="/js/dragdrop.js"></script> <!-- drag/drop ordering //-->',
                '<script type="text/javascript" src="/js/editor.js"></script> <!-- drag/drop ordering //-->',
                '<script type="text/javascript" src="/js/multiselect.js"></script> <!-- multiedit //-->');
    }

}

function albums_head_view_js( $request ) {
    return array('<script type="text/javascript" src="/js/orderimages.js"></script> <!-- image order module //-->',
           '<script type="text/javascript" src="/js/preloader.js"></script> <!-- image loader //-->'); 

}

// album specific
\hooks::add('head_css_albums', __namespace__.'\albums_head_view_css' );
\hooks::add('head_css_albums', __namespace__.'\albums_head_edit_css' );

\hooks::add('head_js_albums', __namespace__.'\albums_head_view_js' );
\hooks::add('head_js_albums', __namespace__.'\albums_head_edit_js' );


// load profile java too
\hooks::add('head_css_albums', '\Profile\profile_head_css' );
\hooks::add('head_js_albums', '\Profile\profile_head_js' );

?>