<?php

class SyncHelper {
    protected static $database;

    public function __construct() {
        self::$database = new databaseHelper();

    }
/*
    public static function getRecursiveIds($itemid) {
        $items = self::$database->query("SELECT itemid,directory FROM items WHERE parentid=?", $itemid);
        $result[] = $itemid ;
        if ($items) {
            foreach ($items as $item) {
                if ($item->directory == 1) { // directory
                    $recursive = self::getRecursiveIds($item->itemid);
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
    public static function removeFromDatabase($itemid) {
        if (!isset(self::$database)) { self::$database = new databaseHelper(); }

        // first get all id's recursive so we can delete it all at once...
        $items = self::getRecursiveIds($itemid);
        $items[] = $itemid;

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

    }
    */

    // check $path, and see if there are things other then $items
    public static function checkLocalDisk($path, $items = array() ) {

        $user = new \userHelper();
        $owner = $user->getUserByUsername( explode('/', strtolower($path))[0] );
        
        $album = new AlbumObject();
        $parent = $album->getIdsByPath( explode('/', strtolower($path)) );
        /*print_r($path);
        print "<br>";
        print_r($items);
        print "<br>";*/
        // Get all items from disk
        $files = array_map(function ($var) { return $var->get('name'); }, $items );

        $directory = DATA_DIR.'/'.$path;
        if ($handle = opendir($directory)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    if (!in_array($entry, $files)) {

                        //print "new item $entry<br>";
                        $prettyname = ucwords(preg_replace('/[_]/', ' ', $entry));

                        // try to set description if we have a name who can contain one (' - ')
                        if (strpos(  $entry , " - ")) {
                            $description = explode(' - ', $entry);
                            $prettyname = ucwords(preg_replace('/[_]/', ' ', array_shift($description)));
                            $description = join (' - ', $description);
                        } else {
                            $prettyname = ucwords(preg_replace('/[_]/', ' ', $entry));
                            $description = '';     
                        }


                        $item = new ItemObject();
                        $item->set('name', $entry)
                             ->set('parentid', end($parent))
                             ->set('displayname', $prettyname)
                             ->set('description', $description)
                             ->set('date', time() )
                             ->set('uid', $owner->uid)
                             ->set('directory', is_dir($directory.'/'.$entry)?1:0);
                        $item->save();
                        //print "writen new item: ".$item->get('itemid').' named:'.$item->get('name').'<br>';
                        $newitems[] = $item->get('itemid');

                        // get sub directories too
                        if ($item->get('directory') == 1) {
                            self::checkLocalDisk($path.'/'.$entry);
                        }
                        //print_r($item);
/*
                        $test = new ItemObject();
                        $test->getId(1);
                        print_r($test);
                        print "<br>";
                        $prettyname = ucwords(preg_replace('/[_]/', ' ', $entry));

                        $item = new ItemObject();
                        $item->set('name', $entry)
                             ->set('displayname', $prettyname);
                        print_r($item);
                        $item2 = new ItemObject($item);
                        print_r($item2);
                        print "New item: $entry";
                        if (is_file($entry)) { // new File

                        } else { // new Directory

                        }
                        */
                    }
                }
            }
        }
        if (isset($newitems)) { return $newitems; }

    }

}

?>