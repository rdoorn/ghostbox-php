<?php


require_once '../config/init.php';

$database = new databaseHelper();

//$count = $database->query_column(0, "SELECT count(itemid) as count FROM files")[0];
$items = $database->query_column(0, "SELECT itemid FROM files ORDER BY itemid limit 22000, 50000");

$parallel = 1;
$count = 0;
foreach ($items as $itemid) {
    $item = new ItemObject();
    $item->getId($itemid);

    try {
        $cache = new CacheObject($item);
        $item->set('cache', $cache);

        $album = new \AlbumObject();
        $parent = $album->initAlbumById( $item->get('parentid') )->getParent();
        $path = $album->path();
        $item->set('path', $path);

        print ++$count." / ".sizeof($items)."...";
        //
                $imageHelper = new \ImageHelper( DATA_DIR.'/'.$item->get('path').'/'.$item->get('name') );
                $imageHelper->parseImage($item);
                $item->save();

        $cachepath = $item->get('cache')->getPath(THUMB_SIZE);
        if (!$cachepath) {
            print "Generating Cache...";
            $cache->createThumbnail();
        } 
        print "OK!\n";
    } catch ( Exception $e) {
        //throw new Exception ($item->get('itemid')." returned error:".$e->getMessage());
        print ++$count." (id:".$item->get('itemid').") returned error:".$e->getMessage()."\n";
        //exit;
        //$item->delete();
    }
    gc_collect_cycles();
    

}


?>