<?php

namespace ImageMagick;

class imageHandler {
    private $filename;
    
    function __construct( $filename ) {
        if (!is_file($filename)) {
            throw new \Exception("Error reading file: ".$filename, 404);
        }
        $this->filename = $filename;
    }

    public function writeCache($destination, $width, $height, $quality, $orientation, $bg = 0) {

        if ( (($width <= 400) && ($width > 0)) 
         || (($height <= 400) && ($height > 0)) ) { $action = '-thumbnail'; $sizeparam = ''; } else { $action = '-resize'; $sizeparam = '\>'; }
        try {

            debug(16, IMAGICK_PATH."convert ".
                    $this->imageOrientationParams($orientation)." ".
                    $action." ".
                    ($width?$width:'')."x".($height?$height:'').$sizeparam." ".
                    "-strip -quality ".$quality." ".
                    escapeshellarg($this->filename).' '.escapeshellarg($destination).($bg==1?" >/dev/null &":""));

            exec(IMAGICK_PATH."convert ".
                    $this->imageOrientationParams($orientation)." ".
                    $action." ".
                    ($width?$width:'')."x".($height?$height:'').$sizeparam." ".
                    "-strip -quality ".$quality." ".
                    escapeshellarg($this->filename).' '.escapeshellarg($destination).($bg==1?" >/dev/null &":""),
                    $output, $result);

                    if ($result != 0) {
                        debug(16, "error?".print_r($output,true));
                        throw new \Exception("image magick convert command failed: $result", 400);
                    }

        } catch( \Exception $e ) {
            throw new \Exception("Failed to create image: ".$e->getMessage(), 400);
        }
    }


    private function imageOrientationParams($orientation) {
        //print "orientation: $orientation";
        $corrections[0]=array('',               '');
        $corrections[1]=array('',               '');
        $corrections[2]=array('',               '-flop');
        $corrections[3]=array('-rotate 180',    '');
        $corrections[4]=array('',               '-flip');
        $corrections[5]=array('-rotate 90',     '-flop');
        $corrections[6]=array('-rotate 90',     '');
        $corrections[7]=array('-rotate -90',    '-flop');
        $corrections[8]=array('-rotate -90',    '');

        return " ".$corrections[$orientation][0]." ".$corrections[$orientation][1]." ";
    }       

}

?>