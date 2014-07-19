<?php


class ItemObject {
    private $itemid;
    private $parentid;
    private $name;
    private $displayname;
    private $directory;
    private $date;
    private $uid;
    private $views;
    private $highlight;
    private $description;
    private $sortby;
    private $sortorder;
    private $orderid;
    private $rotation;
    private $originaldate;
    private $originalwidth;
    private $originalheight;
    private $mime;
    private $captition;
    private $color;
    private $brightness;
    private $rating;
    private $size;
    private $imageid;
    private $path;
    private $cache;
    private $cachename;
    private $keywords;

    private static $database;

    public function __construct($item = null) {
        $benchmark = new \Benchmark(__CLASS__.__FUNCTION__ );
        self::$database = new databaseHelper();
        // load our initial variables if we got some
        if (is_object($item)) { $item = get_object_vars($item); }
        if ($item) {
            foreach ($item as $key => $val) {
                $this->$key = $val;
            }
        }
        $benchmark->close(__CLASS__.__FUNCTION__);
    }

    public function getId($itemid) {
        $benchmark = new \Benchmark(__FUNCTION__);
        $result = self::$database->query("SELECT *, items.itemid as itemid, files.itemid as imageid FROM items 
                                          LEFT JOIN files on (items.itemid=files.itemid)
                                          LEFT JOIN directories on (items.itemid=directories.itemid)
                                          WHERE items.itemid=?", $itemid )[0];

        if (is_object($result)) { $result = get_object_vars($result); }
        if ($result) {
            foreach ((array)$result as $key => $val) {
                $this->$key = $val;
            }
        }

        //print_r($result);
        $benchmark->close(__FUNCTION__);
        return $this;
    }

    public function load(Item $item) {
        //$this = $item;
        //print_r($this);
        //return $this->itemData;
    }

    // add a new object to the database
    public function save() {
        if (!isset($this->itemid)) { // no item id yet, so this is a new item....
            //print_r($this);
            $itemid = self::$database->insert("INSERT INTO items SET parentid=?, directory=?, name=?, displayname=?, description=?, date=?, uid=?, orderid=?, views=?", 
                        $this->get('parentid'),
                        $this->get('directory'),
                        $this->get('name'),
                        $this->get('displayname'),
                        $this->get('description')?$this->get('description'):'',
                        $this->get('date'),
                        $this->get('uid'),
                        $this->get('orderid')?$this->get('orderid'):0,
                        0
                        );
            $this->set('itemid', $itemid);
            if ($this->get('directory')) {
                self::$database->insert("INSERT INTO directories SET itemid=?, highlight=?, sortby=?, sortorder=?", 
                        $itemid,
                        $this->get('highlight'),
                        $this->get('sortby')?$this->get('sortby'):"date",
                        $this->get('sortorder')?$this->get('sortorder'):"asc"
                        );

            } else { // file
                // we only add the entry, which means we should read and parse the file later (when we view it)
                self::$database->insert("INSERT INTO files SET itemid=?", 
                        $itemid
                        );

            }

        } else { // existing item... update!

            self::$database->update("UPDATE items SET parentid=?, directory=?, name=?, displayname=?, description=?, date=?, uid=?, orderid=?, views=? WHERE itemid=?", 
                        $this->get('parentid'),
                        $this->get('directory'),
                        $this->get('name'),
                        $this->get('displayname'),
                        $this->get('description'),
                        $this->get('date'),
                        $this->get('uid'),
                        $this->get('orderid'),
                        $this->get('views'),
                        $this->get('itemid')
                        );
            if ($this->get('directory')) {
                self::$database->update("UPDATE directories SET highlight=?, sortby=?, sortorder=? WHERE itemid=?", 
                        $this->get('highlight'),
                        $this->get('sortby'),
                        $this->get('sortorder'),
                        $this->get('itemid')
                        );/*
                print "DIR UPDATE:<br>";
                print_r($this);
                print "-------------<br>";*/
                //print_r(debug_backtrace());

            } else { // file
                // we only add the entry, which means we should read and parse the file later (when we view it)
                self::$database->update("UPDATE files SET rotation=?, originaldate=?, originalwidth=?, originalheight=?, mime=?, captition=?, color=?, brightness=?, rating=?, filedescription=? WHERE itemid=?", 
                        $this->get('rotation'),
                        $this->get('originaldate'),
                        $this->get('originalwidth'),
                        $this->get('originalheight'),
                        $this->get('mime'),
                        $this->get('captition'),
                        $this->get('color'),
                        $this->get('brightness'),
                        $this->get('rating'),
                        $this->get('filedescription'),
                        $this->get('itemid')
                        );

                // FIXME:, when updating keywords to a file we make it a seperate call to save resources
                if ($this->get('keywords')) {
                    $this->set('keywords', array_filter(array_unique($this->get('keywords'))));
                    $newkeywords = 0;
                    // remove old keywords
                    self::$database->delete("DELETE FROM items_keywords WHERE itemid=?", $this->get('itemid'));
                    foreach ($this->get('keywords') as $keyword) {
                        // check if the keyword is already known
                        $key = self::$database->query("SELECT keyid FROM keywords WHERE keyword=?", $keyword);
                        if (isset($key[0])) {
                            $keyid = $key[0]->keyid;
                        } else {
                            // add new keyword;
                            $keyid = self::$database->insert("INSERT INTO keywords SET keyword=?", $keyword);
                        }
                        // insert new keywords
                        self::$database->insert("INSERT INTO items_keywords SET keyid=?, itemid=?", $keyid, $this->get('itemid'));
                    }
                    // save new keywords to file
                    // lets only do that on request?
                }

            }

        }
        return $this;
    }

    // get all child ids recursive - used in mass deleting dirs
    public function getChildrenRecursive($itemid) {
        $items = self::$database->query("SELECT itemid,directory FROM items WHERE parentid=?", $itemid);
        $result[] = $itemid ;
        if ($items) {
            foreach ($items as $item) {
                if ($item->directory == 1) { // directory
                    $recursive = $this->getChildrenRecursive($item->itemid);
                    foreach($recursive as $recitem) {
                        $result[] = $recitem;
                    }
                } else { // file
                    $result[] = $item->itemid;
                }
            }
        }
        return $result;
    }

    public function delTree($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file") && !is_link($dir)) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    } 

    public function delete() {
        // first get all id's recursive so we can delete it all at once...
        $items = $this->getChildrenRecursive($this->itemid);
        $items[] = $this->itemid;

        // get cache so we can clean up the old files (if any)
        $qMarks = str_repeat('?,', count($items) - 1) . '?';

        // files
        $cacheresult = call_user_func_array( array(self::$database, 'query'), 
                    array_merge(array("SELECT cachename FROM cache WHERE itemid IN ($qMarks)"), $items) );
        if ($cacheresult) {
            foreach ($cacheresult as $cache) {
                @unlink(CACHE_DIR.'/'.$cache->name);
            }
        }
        // delete cache
        call_user_func_array( array(self::$database, 'delete'), 
                    array_merge(array("DELETE FROM cache where itemid IN ($qMarks)"), $items) );
        // delete directories
        call_user_func_array( array(self::$database, 'delete'), 
                    array_merge(array("DELETE FROM directories where itemid IN ($qMarks)"), $items) );
        // update directories highlights - shoild not be needed, if image is not found, we'll find a new one when calling the album;
        //call_user_func_array( array(self::$database, 'update'), 
                    //array_merge(array("UPDATE directories set highlight=? where highlight IN ($qMarks)",0), $items) );
        // delete favorites
        call_user_func_array( array(self::$database, 'delete'), 
                    array_merge(array("DELETE FROM favorites where itemid IN ($qMarks)"), $items) );

        // delete files
        call_user_func_array( array(self::$database, 'delete'), 
                    array_merge(array("DELETE FROM files where itemid IN ($qMarks)"), $items) );
        // delete keywords
        call_user_func_array( array(self::$database, 'delete'), 
                    array_merge(array("DELETE FROM items_keywords where itemid IN ($qMarks)"), $items) );
        // delete items
        call_user_func_array( array(self::$database, 'delete'), 
                    array_merge(array("DELETE FROM items where itemid IN ($qMarks)"), $items) );

        // FIXME: finally delete the core item from disk (recusively?)
        $diskName = DATA_DIR.'/'.$this->path.'/'.$this->name;
        if (($this->path == "") || ($this->name == "")) {
            debug(16, "Aborted local disk removal: $diskName");
        }
        if (is_file($diskName)) {
            debug(16, "REMOVING file:". $diskName);
            unlink($diskName);
        } elseif (is_dir($diskName)) {
            debug(16, "REMOVING directory:". $diskName);
            $this->delTree($diskName);
        }

    }


    public function create() {
        $this->itemData = new \stdClass();
        return $this->itemData;
    }

    public function set($target, $val) {
        $this->$target = $val;
        return $this;
    }

    public function get($target) {
        //if  (isset($this->target)) {
            return $this->$target;
        //}
    }

    public function getJson() {
        try {
            // FIXME: need the color codes too.. but then in rgb?
            return array(
                "itemid" => $this->get('itemid'),
                "name" => $this->get('name'),
                "displayname" => $this->get('displayname'),
                "description" => $this->get('description'),
                "directory" => $this->get('directory'),
                "height" => $this->get('height'),                
                "width" => $this->get('width'),
                "r" => $this->get('r'),
                "g" => $this->get('g'),
                "b" => $this->get('b'),
                "rating" => $this->get('rating'),
                "path" => $this->getUrl('path'),
                "cache" => $this->get('cache')->getAllUrl()
                );
        } catch( \Exception $e ) {
            throw new \Exception("Failed get json data: ".$e->getMessage(), 500);
        }
    }

    public function swap($a, $b) {
        list($this->$a, $this->$b) = array($this->$b, $this->$a);
    }

    // rename object on disk (don't forget to save in the database!)
    public function rename($name, $description) {

        //$name=preg_replace("/[^a-zA-Z0-9_ &,\.\+-]/", "",  $name);
        //$description=preg_replace("/[^a-zA-Z0-9_ &,\.\+-]/", "",  $description);

        // the names we write here have to be filesystem safe... so no..
        // empty files
        // files existing of only dots
        // files with /

        // your beeing weird here
        if (preg_match('/^\.+$/', $name.$description)) {
            throw new \Exception("File name cannot exist out of dots alone", 400);
        }
        if (preg_match('/\//', $name.$description)) {
            throw new \Exception("Invalid character: /", 400);
        }
        if ($name.$description == "") {
            throw new \Exception("Empty filename not permitted", 400);
        }


        $name=trim(ltrim($name));
        $description=trim(ltrim($description));

        $oldname = $this->name;

        $newname = $name;
        if ($description != "") {
            $newname.=' - '.$description;
        }

        if (file_exists(DATA_DIR.'/'.$this->path.'/'.$newname)) {
            throw new \Exception("file already exists", 400);
        }

        // we fix this in the display before, but we save it correclty so display doesn't need to fix this...
        $items = explode(' - ', $newname);
        $savename = array_shift($items);

        if (isset($items)) { 
            $savedescription = join(' - ', $items); 
        } else { 
            $savedescription = "";
        }

        //print "old file: ".$oldname."\n";
        //print "new file: ".$newname."\n";
        //exit;

        if (rename(DATA_DIR.'/'.$this->path.'/'.$oldname, DATA_DIR.'/'.$this->path.'/'.$newname)) {
            $this->name = $newname;
            $this->displayname = $savename;
            $this->description = $savedescription;
            return $this;
        } else {
            throw new \Exception("rename failed unexpectedly", 2);
        }
    }

    public function clearCache() {
        // skip cleaning the cache from disk, it will get overwritten on re-creation
        self::$database->delete("DELETE FROM cache WHERE itemid=?", 
              $this->itemid
              );
    }

    // we only do this on request as they are always required
    public function getKeywords() {
        $keywords = self::$database->query("SELECT keyword FROM items_keywords 
                                            LEFT JOIN keywords USING (keyid) 
                                            WHERE itemid=?", $this->get('itemid'));
        if ($keywords) {
            foreach ($keywords as $keyword) {
                $this->keywords[] = $keyword->keyword;
            }

        }
    }

    public function getUrl($item) {
        $urls = explode('/', $this->$item);
        foreach ($urls as &$url) {
            $url = urlencode($url);
        }
        return join('/', $urls);
    }

    public function rotateLeft() {
        $rotateArray = array( "0" => 8, "1"=>8, "2"=>7, "3"=>6, "4"=>5, "5"=>2,"6"=>1, "7"=>4, "8"=>3 );
        $this->rotation = $rotateArray[ $this->rotation ];
        //self::updateRotation($item);

        $metaclass = METADATA_HANDLER;
        $meta = new $metaclass ( DATA_DIR.'/'.$this->path.'/'.$this->name );
        $meta->setOrientation($this->rotation);

        return $this;
    }

    public function rotateRight() {
        //debug(16, "right:".print_r($item));
        $rotateArray = array( "0" => 6, "1"=>6, "2"=>5, "3"=>8, "4"=>7, "5"=>4,"6"=>3, "7"=>2, "8"=>1 );
        $this->rotation = $rotateArray[ $this->rotation ];

        $metaclass = METADATA_HANDLER;
        $meta = new $metaclass ( DATA_DIR.'/'.$this->path.'/'.$this->name );
        $meta->setOrientation($this->rotation);

        //self::updateRotation($item);
        return $this;
    }

    public function saveRating() {
        $metaclass = METADATA_HANDLER;
        $meta = new $metaclass ( DATA_DIR.'/'.$this->path.'/'.$this->name );
        $meta->setRating($this->rating);
    }

    public function saveKeywords() {
        $metaclass = METADATA_HANDLER;
        $meta = new $metaclass ( DATA_DIR.'/'.$this->path.'/'.$this->name );
        $meta->setKeywords($this->keywords);
    }

}

?>