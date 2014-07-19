<?php

namespace Favorites;

class favoritesObject extends \AlbumObject {
    protected $favoritesdate;

    public function __construct() {
        self::$database = new \databaseHelper();
    }

    function getFavorites( $uid ) {
        $result = $this->database->query("SELECT itemid FROM favorites WHERE uid=?", $uid);
        if ($result) {
            return array_filter($result, function (&$i) { return $i = $i->itemid; } );
        }

    }

    public function findFavorites( $uid ) {
        if (!isset(self::$visibleItems)) {
            $result = self::$database->query("SELECT itemid, favoritesdate FROM favorites WHERE uid=?", $uid);
            if ($result) { 
                foreach($result as $item) {
                    $favoritesdate[ $item->itemid ] = $item->favoritesdate;
                }
                $items = array_map(function ($var) { return $var->itemid; }, $result );
                self::$visibleItems = self::getItemsById($items);
                foreach (self::$visibleItems as $item) {
                    $item->set( 'favoritesdate', $favoritesdate[$item->get('itemid')] );

                }
                /*
                FIXME: allow to manually sort favorites.. which requires per user orderid for image
                */

            }
                //throw new \Exception("No Favorites exist for this user yet...", 404);       
            //print "getChildren reported items: ".print_r($result)."<br><br>";
        }
        //return self::$visibleItems;
        return $this;
    }


}



?>