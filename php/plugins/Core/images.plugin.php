<?php

namespace Core;

function show_thumbnail_html($item, $request) {
        global $user, $hook;
        //$image_url = imagesCache::getCache($item, THUMB_SIZE);

        // need to do something rgarding:
        //$link_url = imagesHandler::getPathById($item, 1);
        //print_r($item);

        /*if ((null !== $item->get('color') && (strlen($item->get('color')) == 6))) {
            list ($r,$g,$b) = str_split($item->get('color'),2);
            $rt = hexdec($r); $gt = hexdec($g); $bt = hexdec($b);
        } else { $rt = 0; $gt = 0; $bt = 0; }*/

        if ($item->get('directory') == 0) {
            $url = '/photos/'.$item->get('itemid').'/_via/albums/'.$item->getUrl('path');
            if ( isset($request->getData()['order']) ) { $url = addUrlParam($url, "order=".$request->getData()['order']); }
            if ( isset($request->getData()['sort']) ) { $url = addUrlParam($url, "sort=".$request->getData()['sort']); }
            print '<a id="'.$item->get('itemid').'" draggable="true" href="'.$url.'">';
        } else {
            print '<a id="'.$item->get('itemid').'" draggable="true" href="/albums/'.$item->getUrl('path').'/'.$item->getUrl('name').'">';
        }

        //print '<div id="frame'.$item->itemid.'" owidth="'.floor((THUMB_SIZE/$item->originalheight)*$item->originalwidth).'" 
        //print '<div owidth="'.floor((THUMB_SIZE/$item->originalheight)*$item->originalwidth).'" 
                  //oheight="'.THUMB_SIZE.'" class="frame" style="background-color: rgba('.$rt.', '.$gt.', '.$bt.', 0.3); ">';
        print '<div owidth="'.$item->get('width').'" 
                  oheight="'.$item->get('height').'" class="frame" style="background-color: rgba('.$item->get('r').', '.$item->get('g').', '.$item->get('b').', 0.3); ">';
        $hook->execute('thumbnail_addon_top', $item, $user);
        //print '<img src="'.$item->get('cache')[THUMB_SIZE].'">';
        print '<img src="'.$item->get('cache')->getUrl(THUMB_SIZE).'">';
        print '<div class="mini_info" style="'.($item->get('directory')?'display: block;':'').'">';
        print '<div type="displayname" class="displayname"';
        if (isset($user->uid) && ($item->get('uid') == $user->uid))  {
            print ' contenteditable="true"';
        }
        print '>'.htmlspecialchars($item->get('displayname')).'</div>';
        print '<div type="description" class="description"';
        if (isset($user->uid) && ($item->get('uid') == $user->uid))  {
            print ' contenteditable="true"';
        }
        print '>'.htmlspecialchars($item->get('description')).' </div>';
        print '</div>';
        if ( $item->get('directory') ) {
            print '<div class="foldericon"><img src="/images/foldericon.png"></div>';
        }        
        $hook->execute('thumbnail_addon_bottom', $item, $user);
        print '</div>';
        print '</a>';
}
\hooks::add('gallery_item_thumbnail', __NAMESPACE__.'\show_thumbnail_html');



function image_generator( $request ) {
    // get item
    $item = new \ItemObject();
    $item->getId( $request->getRequestVars()[0] );

    // get items path
    $album = new \AlbumObject();
    $parent = $album->initAlbumById( $item->get('parentid') )->getParent();
    $path = $album->path();
    $item->set('path', $path);

    // set cache object
    $cache = new \CacheObject($item);


    
    //$item = imagesHandler::getIdByUrl( $request );
    $height = NULL;
    $width = NULL;
    if (isset($request->getData()['h'])) { $height = $request->getData()['h']; }
    if (isset($request->getData()['w'])) { $width = $request->getData()['w']; }
    if ((!in_array($height, unserialize(IMAGE_HEIGHTS)))
     || (!in_array($width, unserialize(IMAGE_WIDTHS)))) {
        throw new \Exception("Invalid size requested", 403);
    } 

    if ($height == THUMB_SIZE) {
        $cache->createThumbnail();
    } else {
        $cache->createImage($width, $height, IMAGE_QUALITY);
    }
    //print $cache->getCacheName($width, $height);
    //$cache = imagesCache::createCache($item, $width, $height);
    \Redirect(CACHE_PATH.'/'.$cache->getCacheName($width, $height) );
}
\hooks::add('image_generator', __NAMESPACE__.'\image_generator');


?>