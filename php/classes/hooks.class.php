<?php

/*
 * hooks class
 */
class hooks {

    private $actions = array();

    public function count ($tag) {
        $counter = 0;
        foreach ($this->actions[$tag] as $priority => $functions) {
            foreach ($functions as $function) {
                $counter++;
            }
        }
        return $counter;
    }

    public function add ($tag, $function, $prio = 10) {
        $this->actions["$tag"][$prio][] = $function;
    }

    public function execute ($tag, $args = NULL) {
        if (! isset ($this->actions[$tag])) {
            return 0;
        }

        debug(8,"executing tag $tag");
        
        $numParams = func_num_args();
        $params = func_get_args();
        if ($numParams>1) { 
            array_shift($params);
        }

        // sort by priority before using
        ksort($this->actions[$tag]);
        foreach ($this->actions[$tag] as $priority => $functions) {

            foreach ($functions as $function) {

                if (is_callable($function)) {
                    //debug(8,"executing $function attached to ".print_r($tag,true));

                    $result = call_user_func_array($function, $params);
                    //debug(4,"$function returned".print_r($result,true));
                    if (is_array($result)) {
                        if (!isset($merged_result)) { $merged_result = array();}
                        $merged_result=array_merge_recursive($merged_result,$result);
                    } elseif(isset($result)) {
                        //if ($result == 'exit_hook') { return true; } // allow you to stop processing more hooks
                        $merged_result = $result;
                    }
                } else {
                    print "error, no function defined for: '".print_r($function, true)."'\n";
                }
            } // actions in prio
        } // foreach priority
        if (isset($merged_result)) { return $merged_result; }
    }

    public function load_plugins ($dir) {
        debug(2,"Loading plugin dir: $dir");
        if ($fh = @opendir($dir)) {
            while ( false !== ($file = readdir($fh)) ) {
                if (is_file("$dir/$file") && (substr($file,-11) == ".plugin.php") ) {
                    debug(2,"Loading plugin: $dir/$file");
                    require_once "$dir/$file";
                } elseif (is_dir("$dir/$file") && ($file != "." && $file != "..")) { // recursive
                    $this->load_plugins("$dir/$file");
                }
            }
        }
    }
}
