<?php


    
    function Redirect($newpage) {
        //echo "redirecting to: $newpage";
        header("Cache-control: private, no-cache");
        header("Status: 302 Moved Permanently");
        header("Location: $newpage", true, 302);
        exit;
    }

    function BenchmarkStats() {
        //$benchmark = new \Benchmark(__FUNCTION__);
        $benchmark = new \Benchmark();
        //$benchmark->close(__FUNCTION__);
        $benchmark->result();
        $stats = new databaseHelper(); 
                    debug(128, sprintf("%25s %-10s query:%01.0f insert:%01.0f update:%01.0f delete:%1.0f",
                        "all","sql",
                           $stats->getDbStatistics()->query,
                           $stats->getDbStatistics()->insert,
                           $stats->getDbStatistics()->update,
                           $stats->getDbStatistics()->delete
                        ));        
        exit;
    }

register_shutdown_function('BenchmarkStats');

    function addUrlParam($url, $param) {
        $query = parse_url($url, PHP_URL_QUERY);
        return $url .= ($query ? '&' : '?') . $param;
    }


?>