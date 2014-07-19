<?php

class CacheObject {
    private $sizes;
    private $itemid;
    private $parent;
    protected static $database;

    public function __construct($item) {
        self::$database = new databaseHelper();

        $this->parent = $item;
        $this->itemid = $item->get('itemid');
        /*$itemid) { 
        $this->getId($itemid);*/
    }

    public function loadItem($itemid) {
        //if (!self::$database) { self::$database = new databaseHelper(); }
        $this->itemid = $itemid;
        $result = self::$database->query("SELECT * FROM cache WHERE itemid=?", $itemid);
        if ($result) {
            foreach ($result as $item) {
                $this->sizes = new \stdClass();
                $this->sizes->{$item->size} = $item->cachename;
            }
        }
        return $this;
    }

    public function load($item) {
        if (is_object($item)) { $item = get_object_vars($item); }
        if (is_array($item)) {
            $this->itemid = $item['itemid'];
            if (isset($item['size'])) {
                if (!is_object($this->sizes)) { $this->sizes = new stdClass(); }
                $this->sizes->$item['size'] = $item['cachename'];
            }
        } else { $this->itemid = $item; }
        return $this;
    }


    public function set($size, $val) {
        $this->sizes->$size = $val;
        return $this;
    }

    public function getPath($size) {
        if (isset($this->sizes->$size)) {
            $filename = $this->sizes->$size;
            if (strpos( $filename, '/') === 0 ) { return $filename; } 
            else {
                    // if we got injected a full path already, use it
                //$filename = $filename[0].'/'.$filename[1].'/'.substr($filename,2,strlen($filename)-2);
            }

            $localpath = CACHE_DIR.'/'.$filename;
            if (file_exists($localpath)) { // cache is valid
                return $localpath;
            } 
            return false;
        }
    }

    public function getUrl($size) {
        if (isset($this->sizes->$size)) {
            $filename = $this->sizes->$size;
            if (strpos( $filename, '/') === 0 ) { return $filename; } 
            else {
                    // if we got injected a full path already, use it
                //$filename = $filename;
            }

            $localpath = CACHE_DIR.'/'.$filename;
            $url = CACHE_PATH.'/'.$filename;
            if (file_exists($localpath)) { // cache is valid
                return $url;
            } else { // cache has dissapeared, regenerate
                $this->clear( $size );
                return "/generate/{$this->itemid}?h={$size}&t=".time(); // timestap is added couse some browsers keep caching the redirect on a session
            }
            return $this->sizes->$size;
        } else { // not in cache
            return "/generate/{$this->itemid}?h={$size}&t=".time();
        }
    }

    public function getAllUrl() { // get all sizes we have in memory
        if (!isset($this->sizes)) {
            $this->sizes = new \stdClass();
            $this->sizes->{THUMB_SIZE} = "/generate/{$this->itemid}?h=".THUMB_SIZE."&t=".time();
        }
        foreach ($this->sizes as $size => $item) {
            $items[ $size ] = $this->getUrl($size);
        }
        return $items;
    }

    public function clear( $size = null ) {
        // skip cleaning the cache from disk, it will get overwritten on re-creation
        if ($size) {
            unset($this->sizes->$size);
            self::$database->delete("DELETE FROM cache WHERE itemid=? and size=?", 
                  $this->itemid, $size
                  );

        } else {
            unset($this->sizes);
            self::$database->delete("DELETE FROM cache WHERE itemid=?", 
                $this->itemid
                );
        }
        return $this;
    }

    public function exists($size) {
        return isset($this->sizes->$size);
    }

    public function inject( $size, $path ) {
        if (!is_object($this->sizes)) { $this->sizes = new \stdClass(); }
        $this->sizes->$size = $path;
        return $this;
    }

    public function getSourcePath() {
        return DATA_DIR.'/'.$this->parent->get('path').'/'.$this->parent->get('name');
    }

    public function getCacheName($width, $height) {

        $source = $this->getSourcePath();
        $cache = sha1($source).$this->parent->get('rotation').$width.$height.$this->parent->get('name');

        // make the directories if they don't exist yet, we will use this as path

        return $cache[0].'/'.$cache[1].'/'.substr($cache,2,strlen($cache)-2);

    }

    public function createThumbnail() {
        $imageclass = IMAGE_HANDLER;
        $image = new $imageclass ( $this->getSourcePath() );

        $cachename = $this->getCacheName(null,THUMB_SIZE);


        if (!is_dir( CACHE_DIR.'/'.$cachename[0] )) { mkdir(CACHE_DIR.'/'.$cachename[0]); }
        if (!is_dir( CACHE_DIR.'/'.$cachename[0].'/'.$cachename[2] )) { mkdir(CACHE_DIR.'/'.$cachename[0].'/'.$cachename[2]);}

        $destination = CACHE_DIR.'/'.$cachename;

        if (is_file($destination)) { // cache file already exists, did we not clean up?
            $result = self::$database->query("SELECT itemid FROM cache WHERE itemid=? and size=?",
                               $this->itemid, THUMB_SIZE);
            if (isset($result[0])) { // cache exists in DB too.. lets not call this again..
                return;
            }
        }
        //$image->setKeywords($this->keywords);

        $image->writeCache($destination, null, THUMB_SIZE, THUMB_QUALITY, $this->parent->get('rotation') );

        // if we havn't exited we were sucessfull, so write to db
        self::$database->insert("INSERT INTO cache SET itemid=?, size=?, cachename=?",
                                $this->itemid, THUMB_SIZE, $cachename);

        $imagehelper = new ImageHelper($destination);
        $palette = $imagehelper->calculatePalette();

        self::$database->update("update files set color=?, r=?, g=?, b=?, brightness=? where itemid=?", 
                $palette->color, $palette->r, $palette->g, $palette->b, $palette->brightness, $this->itemid);
        debug(16, "Written palette ".print_r($palette,true)." for ".$this->itemid);


    }


    public function createImage($width, $height, $quality = 80) {
        $imageclass = IMAGE_HANDLER;
        $image = new $imageclass ( $this->getSourcePath() );

        $cachename = $this->getCacheName($width, $height);


        if (!is_dir( CACHE_DIR.'/'.$cachename[0] )) { mkdir(CACHE_DIR.'/'.$cachename[0]); }
        if (!is_dir( CACHE_DIR.'/'.$cachename[0].'/'.$cachename[2] )) { mkdir(CACHE_DIR.'/'.$cachename[0].'/'.$cachename[2]); }

        $destination = CACHE_DIR.'/'.$cachename;

        if (is_file($destination)) { // cache file already exists, did we not clean up?
            $result = self::$database->query("SELECT itemid FROM cache WHERE itemid=? and size=?",
                               $this->itemid, $width+$height);
            if (isset($result[0])) { // cache exists in DB too.. lets not call this again..
                return;
            }
        }

        //$image->setKeywords($this->keywords);

        $image->writeCache($destination, $width, $height, $quality, $this->parent->get('rotation') );

        // if we havn't exited we were sucessfull, so write to db
        self::$database->insert("INSERT INTO cache SET itemid=?, size=?, cachename=?",
                                $this->itemid, $width+$height, $cachename);

    }

}

?>