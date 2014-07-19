<?php


namespace Photos;

function photos_article( $request ) {
    global $hook;

    $item = new \ItemObject();
    $item->getId( $request->getRequestVars()[0] );

    // get items path
    $album = new \AlbumObject();
    $parent = $album->initAlbumById( $item->get('parentid') )->getParent();

    // get the remainder of the album to fine next and previous item
              $album->findChildren()                                
                       ->sortAlbum( 
                            isset($request->getData()['sort'])?
                            array( $request->getData()['sort'] => isset($request->getData()['order'])?$request->getData()['order']:"asc"  ):
                            array( 
                               'directory' => 'desc',
                                $album->getParent()->get('sortby') => $album->getParent()->get('sortorder')
                            )
                        );
        $start = key ( $album->findId( $item->get('itemid')) );
        $start = ($start==0)?0:($start-1);

        $length = 3;
        //print "key: $start";

        $items = $album->cutAlbum($start, $length) // 265ms
                       ->getItems(); // 15ms
        //print_r($items);

    $path = $album->path();
    $item->set('path', $path);

    // set cache object
    $cache = new \CacheObject($item);
    $cache->loadItem($item->get('itemid'));
    $item->set('cache', $cache);


    if (!$item->get('cache')->exists(1600)) { // only show loading background if the image is beeing generated
        print '<style>article { background: url("/images/photos/loading.gif") no-repeat center center; }</style>';
    }

    // get the source url
    $params = $request->getRequestVars();
    foreach (array_splice($params, 1) as $path) {

        $newUrl[] = urlencode($path);
    }
    $viaUrl = join('/', $newUrl);

    if ( isset($request->getData()['order']) ) { $viaUrl = addUrlParam($viaUrl, "order=".$request->getData()['order']); }
    if ( isset($request->getData()['sort']) ) { $viaUrl = addUrlParam($viaUrl, "sort=".$request->getData()['sort']); }

    print '<div id="imageframe"><div id="imagecontainer"><img src="'.$item->get('cache')->getUrl(1600).'"></div></div>';
    
    debug(16, print_r($items, true));
    // next and previous buttons
    if ( key ( $album->findId( $item->get('itemid')) ) == 0 ) { // we are at the start
        print '<a href="/photos/'.$items[1]->get('itemid').'/'.$viaUrl.'"><div id="nextbutton" class="photoarticlebutton"></div></a>';
    } elseif ( ( $items[1]->get('itemid') == $item->get('itemid') ) && (!isset($items[2])) ) { // we are at the end
        print '<a href="/photos/'.$items[0]->get('itemid').'/'.$viaUrl.'"><div id="previousbutton" class="photoarticlebutton"></div></a>';
    } else {
        print '<a href="/photos/'.$items[0]->get('itemid').'/'.$viaUrl.'"><div id="previousbutton" class="photoarticlebutton"></div></a>';
        print '<a href="/photos/'.$items[2]->get('itemid').'/'.$viaUrl.'"><div id="nextbutton" class="photoarticlebutton"></div></a>';
    }
    
}
\hooks::add('html_article_photos', __NAMESPACE__.'\photos_article');

function photos_aside( $request ) {
    global $hook;
    $params = $request->getRequestVars();
    foreach (array_splice($params, 2) as $path) {

        $newUrl[] = urlencode($path);
    }
    $viaUrl = join('/', $newUrl);

    if ( isset($request->getData()['order']) ) { $viaUrl = addUrlParam($viaUrl, "order=".$request->getData()['order']); }
    if ( isset($request->getData()['sort']) ) { $viaUrl = addUrlParam($viaUrl, "sort=".$request->getData()['sort']); }


    ?>
        <div title="Toggle Menu" id="menubutton" class="photomenubutton"></div>
        <div title="Toggle Fullscreen" id="fullscreenbutton" class="photomenubutton"></div>
        <a href="/<?php echo $viaUrl; ?>"><div title="Back to <?php print $request->getRequestVars()[2]; ?>" id="upbutton" class="photomenubutton"></div></a>
        <div id="user">This is a users name</div>
        <div id="views"></div>
        <div id="date"></div>
        <div id="rating"></div>
        <div id="tags"></div>
        <div id="album"></div>
        <div id="exif"></div>
        <div id="comments"></div>
        <div id="close"></div>
    <?php
}
\hooks::add('html_aside_photos', __NAMESPACE__.'\photos_aside');



function photos_head_css( $request ) {
    return array('<link rel="stylesheet" type="text/css" href="/css/photos.css">');
}

\hooks::add('head_css_photos', __namespace__.'\photos_head_css' );


function photos_head_js( $request ) {
    return '<script type="text/javascript" src="/js/photos.js"></script> <!-- photo details //-->';
}

\hooks::add('head_js_photos', __namespace__.'\photos_head_js' );

?>