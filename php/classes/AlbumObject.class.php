<?php

class AlbumObject {
    private static $pathItems; // items in path
    private static $pathString; // string of path
    private static $items; // all items
    protected static $visibleItems; // albums visible items
    private static $imageCache; // image cache
    private static $pathCache; // path cache per dir
    private static $pathParentCache; // path cache per parent
    private static $idByNameCache; // cache for id by name
    //private static $pathItemCache; // path cache per parent
    //private static $imageMap; // link between items and images
    protected static $database;

    public function __construct() {
        self::$database = new databaseHelper();
    }

/*
    public function byId($albumid) {
        if (!isset(self::$path)) {

        }
    }
*/
    public function initAlbumById($id) {
        //if (!isset(self::$pathItems)) {
            //self::$pathItems = self::getPathbyId($id);
            //self::$pathItems = array_reverse( self::getItemsById( self::getPathbyId($id) ) );
            self::$pathItems = self::getItemsById( self::getPathbyId($id) );
            self::$pathString = join('/', array_map(function ($var) { return $var->get('name'); }, self::$pathItems ));
        //}
        return $this;
        //return self::$pathItems;
    }

    // fill path of self
    public function initAlbumByPath($path) {
        //if (!isset(self::$pathItems)) {
            self::$pathItems = self::getItemsById( self::getIdsByPath($path) );
            self::$pathString = join('/', array_map(function ($var) { return $var->get('name'); }, self::$pathItems ));
        //} 
        return $this;
        //return self::$pathItems;
    }

    // get 1 item
    public function getItemById($item) {
        return $this->getItemsById(array($item))[0];
    }

    // get multiple items
    public function getItemsById(array $itemlist) {
        $benchmark = new \Benchmark(__FUNCTION__);
        $qMarks = str_repeat('?,', count($itemlist) - 1) . '?';
        $benchmark->close(__FUNCTION__, 'strreap');
/*
        $result = call_user_func_array( array(self::$database, 'query'), 
                        array_merge(array("SELECT *, items.itemid AS itemid, files.itemid AS imageid FROM items 
                                        LEFT JOIN directories USING (itemid) 
                                        LEFT JOIN files on (items.itemid=files.itemid or directories.highlight=files.itemid)
                                        LEFT JOIN cache on (cache.itemid=files.itemid)
                                        WHERE items.itemid IN ($qMarks)"), $itemlist) );    
*/

        // files
/*
        $result = call_user_func_array( array(self::$database, 'query'), 
                    array_merge(array("SELECT *, items.itemid as itemid, files.itemid as imageid FROM items 
                                        LEFT JOIN files on (items.itemid=files.itemid)
                                        LEFT JOIN cache on (items.itemid=cache.itemid and size=?)
                                        WHERE directory=? AND items.itemid IN ($qMarks)", 320, 0), $itemlist) );
        // dirs
        $benchmark->close(__FUNCTION__, 'sql1');
        $result2 = call_user_func_array( array(self::$database, 'query'), 
                    array_merge(array("SELECT *, items.itemid as itemid, files.itemid as imageid FROM items 
                                        LEFT JOIN directories USING (itemid) 
                                        LEFT JOIN files on (directories.highlight=files.itemid)
                                        LEFT JOIN cache on (directories.highlight=cache.itemid and size=?)
                                        WHERE directory=? AND items.itemid IN ($qMarks)", 320, 1), $itemlist) );
        $benchmark->close(__FUNCTION__, 'sql2');
*/
        
        $result = call_user_func_array( array(self::$database, 'query'), 
                    array_merge(array("SELECT *, items.itemid as itemid, files.itemid as imageid FROM items 
                                        LEFT JOIN files on (items.itemid=files.itemid)
                                        WHERE directory=? AND items.itemid IN ($qMarks)", 0), $itemlist) );
        // dirs
        $benchmark->close(__FUNCTION__, 'sql1');
        $result2 = call_user_func_array( array(self::$database, 'query'), 
                    array_merge(array("SELECT *, items.itemid as itemid, files.itemid as imageid FROM items 
                                        LEFT JOIN directories USING (itemid) 
                                        LEFT JOIN files on (directories.highlight=files.itemid)
                                        WHERE directory=? AND items.itemid IN ($qMarks)", 1), $itemlist) );
        $benchmark->close(__FUNCTION__, 'sql2');

        //
        /*$benchmark->close(__FUNCTION__, 'sql3');
        $result2 = call_user_func_array( array(self::$database, 'query'), 
                    array_merge(array("SELECT *, items.itemid as itemid, files.itemid as imageid FROM items 
                                        LEFT JOIN directories USING (itemid) 
                                        LEFT JOIN files on (directories.highlight=files.itemid or items.itemid=files.itemid)
                                        WHERE items.itemid IN ($qMarks)"), $itemlist) );*/
        $benchmark->close(__FUNCTION__, 'sql2');

        // merge results (quicker then doing both at once in mysql...)
        if ($result2) {
            foreach ($result2 as $res) {
                $result[] = $res;
            }
        }
        if (!$result) { return false; }

        //print_r($result);
        $benchmark->close(__FUNCTION__, 'merge');


        // Update result string with items we need later, and put it in the static
        foreach($result as $key => $item) {

            self::$items[$item->itemid] = new ItemObject($item);
            $object = &self::$items[$item->itemid];

            if (isset(self::$pathParentCache[$object->get('parentid')]) ) {
                $object->set('path', self::$pathParentCache[$object->get('parentid')] );
            } else {
                $object->set('path', self::getPathNamebyId($object->get('itemid')) );
                self::$pathParentCache[$object->get('parentid')] = $object->get('path');
            }

            $benchmark->close(__FUNCTION__, 'pcache');
            // set items path
            /*if (isset(self::$pathString)) {
                //$result[$key]->path = self::$pathString;
                $object->set('path', self::$pathString);
            } else {
                // find path for this image
                //$result[$key]->path = self::getPathNamebyId($item->itemid);
                $object->set('path', self::getPathNamebyId($item->itemid) );
            }*/
            //$object->set('path', self::getPathNamebyId($item->itemid) );

            // Check if the file still exists
            //if (!is_file(IMAGE_DIR.'/'.self::))
            if (!file_exists(DATA_DIR.'/'.$object->get('path').'/'.$object->get('name'))) {
                    debug(1,"File dissapeared! ".DATA_DIR.'/'.$object->get('path').'/'.$object->get('name'));
                    //SyncHelper::removeFromDatabase($object->get('itemid'));
                    $object->delete();
                    continue;
            }

            $benchmark->close(__FUNCTION__, 'fexist');


            if (($object->get('directory') == 0) && ($object->get('mime') == ''))  {
                // We have an unparsed file, lets parse it!
                
                //$imageproc = new \Images\imagesProcessing();
                //$object = $imageproc->parseImage($object);
                //$object->save();

                $imageHelper = new \ImageHelper( DATA_DIR.'/'.$object->get('path').'/'.$object->get('name') );
                $imageHelper->parseImage($object);
                $object->save();
            }

            $benchmark->close(__FUNCTION__, 'iproc');

            //$result[$key]->cachename = \Images\imagesCache::getCache($item, THUMB_SIZE);
            //$result[$key]->cache = &self::$imageCache[$item->imageid];

            // set actual sizes based on rotation
            if (\ImageHelper::swapImageCoordinates($object->get('rotation'))) {
                //$object->swap('originalwidth', 'originalheight');
                $object->set('height', $object->get('originalwidth') );
                $object->set('width', $object->get('originalheight') );
            } else {
                $object->set('width', $object->get('originalwidth') );
                $object->set('height', $object->get('originalheight') );
            }

            $benchmark->close(__FUNCTION__, 'coords');

            // get short description by name
            /*
            if (($object->get('description') == "") && (strpos(  $object->get('displayname') , " - "))) {
                $description = explode(' - ', $object->get('displayname'));
                $object->set('displayname',array_shift($description));
                $object->set('description',join (' - ', $description));
            }
            */

            // all sucessfull objects;
            $objects[] = self::$items[$item->itemid];

            //$result[$key]->cache = &self::$imageCache[$item->itemid];


            //print "Cache:".print_r($result[$key]->cache, true)."<br>";
            //self::$items[$item->itemid] = new ItemObject();
            //self::$items[$item->itemid]->load($item);
            //print "loaded id: {$item->itemid}<br>";
        }

        // return the populated ids
        $benchmark->close(__FUNCTION__);
        if (isset($objects)) {
            return $objects;
        } else {
            return;
        }
    }


    // get the cache of the specifies id's
    public function getCacheBySize($size) {
        $benchmark = new \Benchmark(__FUNCTION__);
        if (!isset(self::$visibleItems)) { return $this; }
        // create map table
        foreach (self::$visibleItems as $items) {
            $itemids[$items->get('imageid')] = $items->get('itemid'); // images pointing to their itemid
            $imageids[$items->get('itemid')] = $items->get('imageid'); // the images per item
        }
        /*
        print "<bR>itemids: (imageid -> itemid) <br>";
        print_r($itemids);
        print "<bR>imageids: (itemid -> imageid) <br>";
        print_r($imageids);
        */
        if (empty(self::$visibleItems)) { return $this; }
        $qMarks = str_repeat('?,', count($imageids) - 1) . '?';
        $images = call_user_func_array( array(self::$database, 'query'), 
                        array_merge(array("SELECT * FROM cache WHERE size=? and itemid IN ($qMarks)",
                            $size), $imageids) );
        // save those who's images returned
        if ($images) {
            foreach($images as $image) {
                //print_r($image);
                
                // we might not have this itemobject yet, create one if its new
                //if (!isset(self::$items[$image->itemid])) { self::$items[$image->itemid] = new ItemObject(); }

                $cache = new CacheObject( self::$items[ $itemids[$image->itemid] ] );
                $cache->load( $image );
                //print_r($cache);
                // load cache in the items cache
                self::$items[ $itemids[$image->itemid] ]->set('cache', $cache);

                //self::$items[ $itemids[$image->itemid] ]->set('cache', array($size => \Images\imagesCache::checkCache($image, $size) ) );

                // make sure cache is valid
                //self::$imageCache[$image->itemid][$size] = \Images\imagesCache::checkCache($image, $size);

                $hascache[ $itemids[$image->itemid] ] = $image->itemid;
            }
        } else { $hascache = array(); }
        $nocache = array_diff($imageids, $hascache);

        //print_r($nocache);
        

        // generate the ones that didn't have any
        foreach($nocache as $item => $image ) {
            //if (!isset(self::$items[$image->itemid])) { self::$items[$image->itemid] = new ItemObject(); }

            if ($image>0) { // if item already has a image attached (highlight or file)...
                $cache = new CacheObject( self::$items[ $item ] );
                $cache->load( $image );
                self::$items[ $item ]->set('cache', $cache);
            } else { // this has to be a directory without highlight

                // first check if the directory has any new items
                $path = self::$pathString.'/'.self::$items[ $item ]->get('name');

                $album = new AlbumObject();
                $parent = $album->getIdsByPath( explode('/', strtolower($path)) );
                //print_r($parent);
                $parent = end($parent);

                //print "Found directory without highlight: ".$path." ($parent)<br>";

                // try to find an image - we already updated our local disk check, so this should be sufficient
                $image = $this->findImage($parent);
                //print "imag: $image";
                if ($image>0) {
                    $newimage = $this->getItemById($image);


                    $cache = new CacheObject( self::$items[ $parent ] );
                    $cache->load( $image );
                    self::$items[ $parent ]->set('cache', $cache);

                    self::$items[ $parent ]->set('highlight', $image)->save();
                    //self::$items[ $parent ]->set('cache', array($size => '/generate/'.$image.'?h='.$size ) );
                    self::$items[ $parent ]->set('originalheight', $newimage->get('originalheight') );
                    self::$items[ $parent ]->set('originalwidth', $newimage->get('originalwidth') );
                    self::$items[ $parent ]->set('rotation', $newimage->get('rotation') );
                    // correct coordinates
                    if (\ImageHelper::swapImageCoordinates($newimage->get('rotation'))) {
                        //$object->swap('originalwidth', 'originalheight');
                        self::$items[ $parent ]->set('height', $newimage->get('originalwidth') );
                        self::$items[ $parent ]->set('width', $newimage->get('originalheight') );
                    } else {
                        self::$items[ $parent ]->set('width', $newimage->get('originalwidth') );
                        self::$items[ $parent ]->set('height', $newimage->get('originalheight') );
                    }

                } else {
                    // no image, empty folder!
                    $cache = new CacheObject( self::$items[ $parent ] );
                    $cache->load( $parent );
                    $cache->inject( $size, '/images/empty.png' );
                    //print_r($cache);
                    self::$items[ $parent ]->set('cache', $cache);

                    //self::$items[ $itemids[$item] ]->set('cache', array($size => '/images/empty.png'));
                    self::$items[ $parent ]->set('height', 300 ); 
                    self::$items[ $parent ]->set('width', 300 );
                    // do we need original height/width too?
                    self::$items[ $parent ]->set('originalheight', 300 ); 
                    self::$items[ $parent ]->set('originalwidth', 300 );
                    self::$items[ $parent ]->set('rotation', 1 );
                }

            }

        }
/*
            if (self::$items[ $item ]->get('directory') == 0) {
                // regenerate cache if we are a file
                $cache = new CacheObject( self::$items[ $item ] );
                $cache->load( $item );
                self::$items[ $item ]->set('cache', $cache);


                // OLD self::$items[ $item ]->set('cache', array($size => '/generate/'.$item.'?h='.$size ) );
            //} elseif (self::$items[ $item ]->get('highlight') >0) {
                // regenerate cache if we have a highlight and are a directory
                //self::$items[ $item ]->set('cache', array($size => '/generate/'.self::$items[ $item ]->get('highlight').'?h='.$size ) );
            } else {
                // no image, this has to be a directory.. or the image is gone which should have been noticed earlier

                // first check if the directory has any new items
                $path = self::$pathString.'/'.self::$items[ $item ]->get('name');

                $album = new AlbumObject();
                $parent = $album->getIdsByPath( explode('/', strtolower($path)) );
                //print_r($parent);
                $parent = end($parent);

                //print "Found directory without highlight: ".$path." ($parent)<br>";

                // try to find an image - we already updated our local disk check, so this should be sufficient
                $image = $this->findImage($parent);
                //print "imag: $image";
                if ($image>0) {
                    $newimage = $this->getItemById($image);


                    $cache = new CacheObject( self::$items[ $parent ] );
                    $cache->load( $image );
                    self::$items[ $parent ]->set('cache', $cache);

                    self::$items[ $parent ]->set('highlight', $image)->save();
                    //self::$items[ $parent ]->set('cache', array($size => '/generate/'.$image.'?h='.$size ) );
                    self::$items[ $parent ]->set('originalheight', $newimage->get('originalheight') );
                    self::$items[ $parent ]->set('originalwidth', $newimage->get('originalwidth') );
                    self::$items[ $parent ]->set('rotation', $newimage->get('rotation') );
                    // correct coordinates
                    if (\ImageHelper::swapImageCoordinates($newimage->get('rotation'))) {
                        //$object->swap('originalwidth', 'originalheight');
                        self::$items[ $parent ]->set('height', $newimage->get('originalwidth') );
                        self::$items[ $parent ]->set('width', $newimage->get('originalheight') );
                    } else {
                        self::$items[ $parent ]->set('width', $newimage->get('originalwidth') );
                        self::$items[ $parent ]->set('height', $newimage->get('originalheight') );
                    }

                } else {
                    // no image, empty folder!
                    $cache = new CacheObject( self::$items[ $parent ] );
                    $cache->load( $parent );
                    $cache->inject( $size, '/images/empty.png' );
                    //print_r($cache);
                    self::$items[ $parent ]->set('cache', $cache);

                    //self::$items[ $itemids[$item] ]->set('cache', array($size => '/images/empty.png'));
                    self::$items[ $parent ]->set('height', 300 ); 
                    self::$items[ $parent ]->set('width', 300 );
                    // do we need original height/width too?
                    self::$items[ $parent ]->set('originalheight', 300 ); 
                    self::$items[ $parent ]->set('originalwidth', 300 );
                    self::$items[ $parent ]->set('rotation', 1 );
                }
            }
                //print_r(self::$items[ $item ]);
*/                

        
        $benchmark->close(__FUNCTION__);
        return $this;

    }


    // Find a image in the tree below the ID
    function findImage( $id ) {
        $image = self::$database->query("SELECT itemid FROM items WHERE parentid=? AND directory=?"
                            , $id, 0);
        if (isset($image[0])) {
            return $image[0]->itemid;
        } else {
            $dirs = self::$database->query("SELECT itemid FROM items WHERE parentid=? AND directory=?"
                            , $id, 1);
            if ($dirs) { // non empty dir?, then follow!
                foreach ($dirs as $dir) {
                    $result = $this->findImage( $dir->itemid );
                    if ($result > 0) {
                        return $result;
                    }
                }
            }
            return 0;
        }
    }


    public function checkForNewItems() {
        if (isset(self::$visibleItems) && isset(self::$pathString)) {

            $items = \SyncHelper::checkLocalDisk( self::$pathString , self::$visibleItems );
            if ( $items ) {
                foreach (self::getItemsById($items) as $item) {
                    //print "added item to backlog...".print_r($item,true);
                    self::$visibleItems[] = $item;
                }
            }
        }
        return $this;
        //\SyncHelper::checkLocalDisk($album->path(), $items);
    }


    public function findChildren() {
        $benchmark = new \Benchmark(__FUNCTION__);
        if (!isset(self::$visibleItems)) {
            //$result = self::$database->query("SELECT itemid FROM items WHERE parentid=?", end(self::$pathItems)->get('itemid'));
            $result = self::$database->query_column(0, "SELECT itemid FROM items WHERE parentid=?", end(self::$pathItems)->get('itemid'));
            if ($result) {
                $benchmark->close(__FUNCTION__, 'sql');
                //$items = array_values($result);
                //$items = array_column($result, 'itemid'); FIXME:  query2 - optimisation
                //print_r($result);
                
                //$items = array_map(function ($var) { return $var->itemid; }, $result );

                //self::$visibleItems = self::getItemsById($items);
                self::$visibleItems = self::getItemsById($result);
            }
        }
        $benchmark->close(__FUNCTION__);
        //return self::$visibleItems;
        return $this;
    }

    public function getItems() {
        if (!isset(self::$visibleItems)) { return; }
        return self::$visibleItems;
    }

    // get id by name in parent
    private function getIdByName($name, $parentid) {
        $benchmark = new \Benchmark(__FUNCTION__);
        if (!isset(self::$idByNameCache[$name.'_'.$parentid])) {
            $result = self::$database->query("SELECT name, itemid, parentid FROM items WHERE name=? AND parentid=?", $name, $parentid);
            if ($result) {
                self::$idByNameCache[$name.'_'.$parentid] = $result[0];
                self::$pathCache[$result[0]->itemid]=$result[0];
                return self::$idByNameCache[$name.'_'.$parentid];
            } else {
                throw new \Exception("Invalid album requested", 404);       
            }
        } else {
            return self::$idByNameCache[$name.'_'.$parentid];
        }
        $benchmark->close(__FUNCTION__);
    }

    // return all id's in the requested path, last id beeing what to show
    public function getIdsByPath($path) {
        $benchmark = new \Benchmark(__FUNCTION__);
        // get first id, this is our user id
        $start = $this->getIdByName(array_shift($path), 0);        
        $parentid = $start->itemid;
        $list[] = $start->itemid;
        foreach ($path as $pathitem) {
            $item = $this->getIdByName($pathitem, $parentid);
            $parentid = $item->itemid;
            $list[] = $item->itemid;
        }
        return $list;
        $benchmark->close(__FUNCTION__);
    }

    // return all id's in the requested path, last id beeing what to show
    private function getPathbyId($id) {
        // get first id, this is our user id
        $benchmark = new \Benchmark(__FUNCTION__);
        $parentid = $id;
        $tries = 0;
        while (($parentid != 0) && ($tries < 10)) {
                if (!isset(self::$pathCache[$parentid])) {
                    self::$pathCache[$parentid] = self::$database->query("SELECT name, itemid, parentid FROM items WHERE itemid=?", $parentid)[0];
                }
                $paths[] = self::$pathCache[$parentid]->itemid;
                $parentid = self::$pathCache[$parentid]->parentid;
                $tries++;
        }
        $benchmark->close(__FUNCTION__);
        return $paths;
    }

    // return path without our callers name
    private function getPathNamebyId($id) {
        // get first id, this is our user id
        $benchmark = new \Benchmark(__FUNCTION__);
        $parentid = $id;
        $tries = 0;
        while (($parentid != 0) && ($tries < 10)) {
                if (!isset(self::$pathCache[$parentid])) {
                    self::$pathCache[$parentid] = self::$database->query("SELECT name, itemid, parentid FROM items WHERE itemid=?", $parentid)[0];
                }
                $paths[] = self::$pathCache[$parentid]->name;
                $parentid= self::$pathCache[$parentid]->parentid;
                $tries++;
        }
        array_shift($paths);
        $benchmark->close(__FUNCTION__);
        return join('/',array_reverse($paths));
    }


    // Sort object by a value
    public function sortAlbum($order = array('name', 'asc') ) {
    //public function sortAlbum($sort, $order ) {
        $benchmark = new \Benchmark(__FUNCTION__);
        //debug(128, "sort order: $sort $order");
        if (!isset(self::$visibleItems)) { return $this; }
        try {
            // sort based on multiple keys, first one is most important one

            // - sort takes about 1 second, compared to instant on usort strcmp... 
            usort(self::$visibleItems, function ($a, $b) use ($order) {
                $t = array(true => -1, false => 1);
                $r = true;
                $k = 1;
                foreach ($order as $key => $value) {
                    $k = ($value === 'asc') ? 1 : -1;
                    $r = ($a->get($key) < $b->get($key));
                    if ($a->get($key) !== $b->get($key)) {
                        return $t[$r] * $k;
                    }
                }
                return $t[$r] * $k;
            });
            /*
            debug(128, "sort: $sort / order: $order");
            
                usort(self::$visibleItems, function($a, $b) use ($sort)
                {
                    debug(128, "sort: ".$a->get($sort)." / ".$b->get($sort));
                    return strcmp($a->get($sort), $b->get($sort));
                });
            */
            //print "reverse $reverse order $order<br>";
            //if ($reverse) { self::$visibleItems = array_reverse(self::$visibleItems); }
        } catch ( \Exception $e ) {
            throw new \Exception("Invalid sort field: ".print_r($order,true).$e->getMessage(), 1);    
        }
        $benchmark->close(__FUNCTION__);
        return $this;
    }

    public function findId($itemid) {
        if (!isset(self::$visibleItems)) { return; }
        
        $item = array_filter(
            self::$visibleItems,
            function ($e) use ($itemid) {
                return $e->get('itemid') == $itemid;
            }
        );

        return $item;
    }
    // only show part of the album
    public function cutAlbum($start, $length) {
        if (!isset(self::$visibleItems)) { return $this; }

        self::$visibleItems = array_slice(self::$visibleItems,$start, $length);
        return $this;
    }


    // return path variable
    public function path() {
        return self::$pathString;
    }

    // return path items
    public function getPathItems() {
        return self::$pathItems;
    }

    // return current path item
    public function getParent() {
        return end(self::$pathItems);
    }

    public function findObjectIndex($needle, $value) {
        foreach(self::$visibleItems as $key => $struct) {
            if ($struct->get($needle) == $value) { return $key; }
        }
    }


}

?>