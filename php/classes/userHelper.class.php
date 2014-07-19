<?php

class userHelper {
    protected static $database;
    protected static $user_by_id;
    protected static $user_by_session;
    protected static $user_by_username;    

    public function __construct() {
        self::$database = new databaseHelper();
    }

    // find users session ID in the database
    public function getUserBySession( $sid ) {
        if (!isset(self::$user_by_session[$sid])) {
            $result = self::$database->query("SELECT uid, sid, username, displayname, albumid from users where sid=?", $sid);
            if ($result) { $this->cacheUser($result[0]); } // don't cache misses
        }
        return self::$user_by_session[$sid];
    }


    // find users ID in the database
    public function getUserById( $uid ) {
        if (!isset(self::$user_by_id[$uid])) {
            $result = self::$database->query("SELECT uid, sid, username, displayname, albumid from users where uid=?", $uid);
            if ($result) { $this->cacheUser($result[0]); } // don't cache misses
        }
        return self::$user_by_id[$sid];
    }


    // find users username in the database
    public function getUserByUsername( $username ) {
        if (!isset(self::$user_by_username[$username])) {
            $result = self::$database->query("SELECT uid, sid, username, displayname, albumid from users where username=?", $username);
            if ($result) { $this->cacheUser($result[0]); } // don't cache misses
        }
        return self::$user_by_username[$username];
    }

    private function cacheUser( $user ) {
        self::$user_by_session[$user->sid] = $user; 
        self::$user_by_id[$user->uid] = $user; 
        self::$user_by_username[strtolower($user->username)] = $user; 
    }

    // Add or update the user information
    public function registerUser( $user ) {
        // requires:
        // session id
        // facebook/google id =  handler id name
        //                    =  handler id string
        // username
        // displayname 
        // email
        // albumid? - can be found from username  (name + parent 0)
        if (
            !isset($user['handlername']) ||
            !isset($user['id']) ||
            !isset($user['username']) ||
            !isset($user['displayname']) ||
            !isset($user['email']) 
            ) {
            throw new \Exception("Login plugins failed to provide required information", 2);
        } 

        // Check if user already exists in its handler
        $result = self::$database->query("SELECT uid from users left join ".$user['handlername']." USING (uid) where id=?", $user['id']);
        if ($result) {
            // update existing user
            self::$database->update("UPDATE users set sid=?, displayname=?, email=?, lastlogin=?, ip=? where uid=?", 
                           session_id(), $user['displayname'], $user['email'], time(), \httpRequest::sourceIP(), $result[0]->uid );

            // update handler
            foreach ($user['fields'] as $item) {
                self::$database->update("UPDATE ".$user['handlername']." set $item=? where uid=?", 
                            $user[$item], $result[0]->uid );
            }
        } else {

            // Check if user already exists by session (to link handlers)
            $result = self::$database->query("SELECT uid from users where sid=?", session_id());
            if ($result) {
                // user already has a login, but not the handler yet
                // so re-use uid
                $uid = $result[0]->uid;

            } else {

                // create new user
                $username = $this->generateUsername( $user['username'] );

                $uid = self::$database->insert("INSERT INTO users set sid=?, username=?, displayname=?, email=?", 
                           session_id(), $username, $user['displayname'], $user['email'] );


                // create initial album
                $albumid = self::$database->insert("INSERT INTO items SET parentid=?, directory=?, name=?, displayname=?, date=?, uid=?",
                        0, 1, $username, $username, time(), $uid);

                // create initial album as a directory
                self::$database->insert("INSERT INTO directories SET itemid=?, orderid=?",
                        $albumid, $albumid);

                // link album in users profile
                self::$database->update("UPDATE users SET albumid=? WHERE uid=?", $albumid, $uid);

                // create users directories
                if (!is_dir(DATA_DIR.'/'.$username)) { mkdir(DATA_DIR.'/'.$username); }
                if (!is_dir(CACHE_DIR.'/'.$username)) { mkdir(CACHE_DIR.'/'.$username); }

            }
            
            // create handler
            self::$database->insert("INSERT INTO ".$user['handlername']." SET uid=?", $uid);
            // update handler (yes very inefficient)
            foreach ($user['fields'] as $item) {
                self::$database->update("UPDATE ".$user['handlername']." set $item=? where uid=?", 
                        $user[$item], $uid );
            }

        }

    }

    // get the first avilable userid based on base name
    public function generateUsername( $base ) {
        $result = self::$database->query("SELECT username FROM `users` WHERE username like CONCAT('%', ?, '%') order by username desc limit 1", strtolower($base));
        if ($result) {
            // User already exists
            preg_match('/'.$base.'(\d+)/', $result[0]->username, $match);
            if (isset($match[1])) {
                $username=$base.((int)$match[1]+1);
            } else {
                $username=$base.'2';
            }
        } else {
            $username=$base;
        }
        $username=preg_replace('/[\.\/]/', "", stripslashes($username)); // remove dots and back/forward slashes
        return $username;
    }

    // logout the user by destroying its php session
    public static function logoutUser( ) {
        self::$database->update("UPDATE users set sid=? where sid=?", 
                           "", session_id());
        session_destroy();
    }

    public function set($target, $val) {
        $this->$target = $val;
        return $this;
    }

    public function get($target) {
        return $this->$target;
    }



}

/*

    public function register($user) {
        $result = $this->db->query("SELECT uid from users where fbid=?", $user['id']);
        if (isset($result[0])) {
            // returning user
            return $this->db->update("UPDATE users set sid=?, fbid=?, displayname=?, email=? where uid=?", 
                           session_id(), $user['id'], $user['first_name']." ".$user['last_name'], $user['email'], $result[0]->uid);
        } else {
            // new user
            // generate new username
            $result = $this->db->query("SELECT username FROM `users` WHERE username like CONCAT('%', ?, '%') order by username desc limit 1", $user['first_name']);
            if (isset($result[0])) {
                // User already exists
                preg_match('/'.$user['first_name'].'(\d+)/', $result[0]->username, $match);
                if (isset($match[1])) {
                    $username=$user['first_name'].((int)$match[1]+1);
                } else {
                    $username=$user['first_name'].'2';
                }
            } else {
                $username=$user['first_name'];
            }
            $username=preg_replace('/[\.\/]/', "");

            // create initial album
            $albumid = $this->db->insert("INSERT INTO items SET parentid=?, path=?, directory=?, name=?, displayname=?, date=?, uid=?",
                        0, '/', 1, $username, $username, time(), 999999);

            // create user FIXME: middle name too
            return $this->db->insert("INSERT INTO users set sid=?, fbid=?, displayname=?, email=?, username=?, unixid=?, albumid=?", 
                           session_id(), $user['id'], $user['first_name']." ".$user['last_name'], $user['email'], $username, 999999, $albumid);
            
            // create users directories
            if (!is_dir(DATA_DIR.'/'.$username)) { mkdir(DATA_DIR.'/'.$username); }
            if (!is_dir(CACHE_DIR.'/'.$username)) { mkdir(CACHE_DIR.'/'.$username); }

        }

    }

    public function logout($user) {
        $this->db->update("UPDATE users set sid=? where uid=?", 
                           "", $user->getId());
        session_destroy();
    }

    */
?>