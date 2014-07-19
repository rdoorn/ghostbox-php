<?php

//class databaseHelper extends PDO {

class databaseHelper extends PDO {
    protected static $database;
    protected static $statistics;
    protected $db;

    // mysqli_report(MYSQLI_REPORT_STRICT);

    // Create initial DB connection
    public function __construct() {

        if(self::$database === null) {
            try {
                //self::$database = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS, array(
                self::$database = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS, array(
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
                ));
                self::$database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$database->query('SET CHARACTER SET utf8');

        
                self::$statistics = new \stdClass();
                self::$statistics->query = 0;
                self::$statistics->insert = 0;
                self::$statistics->update = 0;
                self::$statistics->delete = 0;

            } catch ( PDOException $e ) {
                error_log("Error connecting to the database: {$e->getMessage()}");
                #print_r($e->getMessage());
                #throw new \Exception("Internal server error",500);
            }
        }

    }

/*
    public static function connect() {
        if(self::$database === null) {
            new databaseHelper();
        }
        return self::$database;
    }*/
    /*
     * Show SQL error
     */
    private function errorMessage($error) {
        if (DEBUG_SQL_ERROR) {
            print "Error No: ".$error->getCode(). " - ". $error->getMessage() . "<br >";
            print nl2br($this->getExceptionTraceAsString($error));
        }
        // $obs->generic->exit_clean; # TODO: redirect to maintenance page?
    }

   /*
    * Additional tracing for full-line debugging
    */

    private function getExceptionTraceAsString($exception) {
        $rtn = "";
        $count = 0;
        foreach ($exception->getTrace() as $frame) {
            $args = "";
            if (isset($frame['args'])) {
                $args = array();
                foreach ($frame['args'] as $arg) {
                    if (is_string($arg)) {
                        $args[] = "'" . $arg . "'";
                    } elseif (is_array($arg)) {
                        $args[] = "Array";
                    } elseif (is_null($arg)) {
                        $args[] = 'NULL';
                    } elseif (is_bool($arg)) {
                        $args[] = ($arg) ? "true" : "false";
                    } elseif (is_object($arg)) {
                        $args[] = get_class($arg);
                    } elseif (is_resource($arg)) {
                        $args[] = get_resource_type($arg);
                    } else {
                        $args[] = $arg;
                    }
                }
                $args = join(", ", $args);
            }
            $rtn .= sprintf( "#%s %s(%s): %s(%s)\n",
                             $count,
                             isset($frame['file']) ? $frame['file'] : 'unknown file',
                             isset($frame['line']) ? $frame['line'] : 'unknown line',
                             (isset($frame['class']))  ? $frame['class'].$frame['type'].$frame['function'] : $frame['function'],
                             $args );
            $count++;
            }
        return $rtn;
    }

    /*
     * query's that return data
     */
    public function query($query) {

        $numParams = func_num_args();
        $params = func_get_args();

        # On to the SQL stuff
        if (DEBUG_SQL_ALL) { debug(1,print_r($params,1)); }
        try {
            $tdb = self::$database->prepare($params[0]);

            for ($i = 1; $i < $numParams; $i++){
                $tdb->bindParam($i, $params[$i]);
            }

            $tdb->execute();
            //$fetch = PDO::FETCH_OBJ;
            //if ($fetch == 1) { 
                //$tdb->setFetchMode(PDO::FETCH_COLUMN,0);
            //} else {
                $tdb->setFetchMode(PDO::FETCH_OBJ);
            //}
            $ret = array();
            while ($row = $tdb->fetch()) {
                $ret[] = $row;
            }
        } catch( PDOException $e ) {
            $this->errorMessage($e);
        }

        self::$statistics->query++;
        return empty($ret)?false:$ret;
    } // query


    public function query_column($query) {
        //debug(128, "COUMN:".PDO::FETCH_COLUMN);
        //debug(128, "OBJ:".PDO::FETCH_OBJ);

        $numParams = func_num_args()-1;
        $params = func_get_args();

        $column = array_shift($params);

        # On to the SQL stuff
        if (DEBUG_SQL_ALL) { debug(1,print_r($params,1)); }
        try {
            $tdb = self::$database->prepare($params[0]);

            for ($i = 1; $i < $numParams; $i++){
                $tdb->bindParam($i, $params[$i]);
            }

            $tdb->execute();
            $tdb->setFetchMode(PDO::FETCH_COLUMN,$column);
            $ret = array();
            while ($row = $tdb->fetch()) {
                $ret[] = $row;
            }
        } catch( PDOException $e ) {
            $this->errorMessage($e);
        }

        self::$statistics->query++;
        return empty($ret)?false:$ret;
    } // query


    /*
     * Update query
     */
    public function update($query) {

        $numParams = func_num_args();
        $params = func_get_args();

        if (DEBUG_SQL_ALL) { debug(1,print_r($params,1)); }
        try {
            $tdb = self::$database->prepare($params[0]);

            for ($i = 1; $i < $numParams; $i++){
                $tdb->bindParam($i, $params[$i]);
            }
            $tdb->execute();
            $ret = $tdb->rowCount();

        } catch( PDOException $e ) {
            $this->errorMessage($e);
        }

        self::$statistics->update++;
        return $ret;
    } // insert


    /*
     * Delete query
     */
    public function delete($query) {

        $numParams = func_num_args();
        $params = func_get_args();

        if (DEBUG_SQL_ALL) { debug(1,print_r($params,1)); }
        try {
            $tdb = self::$database->prepare($params[0]);

            for ($i = 1; $i < $numParams; $i++){
                $tdb->bindParam($i, $params[$i]);
            }
            $tdb->execute();
            $ret = $tdb->rowCount();

        } catch( PDOException $e ) {
            $this->errorMessage($e);
        }

        self::$statistics->delete++;
        return $ret;
    } // insert


    /*
     * Insert query, for last insertid
     */
    public function insert($query) {

        $numParams = func_num_args();
        $params = func_get_args();

        if (DEBUG_SQL_ALL) { debug(1,print_r($params,1)); }
        try {
            $tdb = self::$database->prepare($params[0]);

            for ($i = 1; $i < $numParams; $i++){
                $tdb->bindParam($i, $params[$i]);
            }

            $tdb->execute();
            $ret = self::$database->lastInsertId();

        } catch( PDOException $e ) {
            $this->errorMessage($e);
        }

        self::$statistics->insert++;
        return $ret;
    }

    /*
     * Get statistics
     */
    public function getDbStatistics() {
        return self::$statistics;
    }

}

?>
