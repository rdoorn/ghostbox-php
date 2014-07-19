<?php

class syncHelper {
    protected static $database;

    public function __construct() {
        self::$database = new databaseHelper();
    }

    public static function removeFromDatabase($itemid) {
        if (!isset(self::$database)) { self::$database = new databaseHelper(); }

        // get cache so we can clean up the old files (if any)
        $cacheresult = self::$database->query("SELECT name FROM cache WHERE itemid=?", $id);
        foreach ($cacheresult as $cache) {
            @unlink(CACHE_DIR.'/'.$cache->name);
        }
        self::$database->delete("DELETE FROM cache where itemid=?", $id);
        self::$database->delete("DELETE FROM directories where itemid=?", $id);
        self::$database->delete("DELETE FROM favorites where itemid=?", $id);
        self::$database->delete("DELETE FROM files where itemid=?", $id);
        self::$database->delete("DELETE FROM items_keywords where itemid=?", $id);
        self::$database->delete("DELETE FROM items where itemid=?", $id);

    }

}

?>