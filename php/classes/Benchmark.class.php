<?php


class Benchmark {
    static $data = array();

    function __construct($item = null) {
        if (!BENCHMARK) { return; }
            if (!$item) { return; }
            if (!isset(self::$data[$item])) { self::$data[$item] = array(); }
            self::$data[$item]['start'] = microtime(true);
            if (!isset(self::$data[$item]['times'])) { self::$data[$item]['times'] = array(); }
            if (!isset(self::$data[$item]['count'])) { self::$data[$item]['count'] = array(); }
    }

    function close($item, $type = 'exec') {
        if (!BENCHMARK) { return; }
            self::$data[$item]['times'][$type][] = microtime(true)-self::$data[$item]['start'];
            self::$data[$item]['start'] = microtime(true);
            if (isset(self::$data[$item]['count'][$type])) { 
                self::$data[$item]['count'][$type]++; 
            } else { 
                self::$data[$item]['count'][$type]=1; 
            }
    }
    function result() {
        if (!BENCHMARK) { return; }
        foreach (self::$data as $name => $details) {
            //debug(128, "$name.count ".$details['count']);//." total:".array_sum(self::$data[$name]['times'])." avg:".(array_sum(self::$data[$name]['times'])/$details['count']));
            foreach ($details['times'] as $timedname => $times) {
                    debug(128, sprintf("%25s %-10s count:%01.0f total:%01.7f avg:%01.7f ",
                        $name,$timedname,
                        self::$data[$name]['count'][$timedname], 
                        array_sum(self::$data[$name]['times'][$timedname]), 
                        array_sum(self::$data[$name]['times'][$timedname])/$details['count'][$timedname]
                        ));
            }
        }
    }

}


?>