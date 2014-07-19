<?php

namespace Exiv2;

class metaData implements \MetaDataInterface {
    private $filename;

    // Check if file exists, and save it in our Object
    function __construct( $filename ) {
        if (!is_file($filename)) {
            throw new \Exception("Error reading file: ".$filename, 404);
        }
        $this->filename = $filename;
    }

    // Save rating to file
    public function setRating($rating) {
        try {
            debug(16, EXIV2_PATH."exiv2".
                    " -M ".escapeshellarg('set Xmp.xmp.Rating '.(int)$rating)." ".
                    escapeshellarg($this->filename));
            exec(EXIV2_PATH."exiv2".
                    " -M ".escapeshellarg('set Xmp.xmp.Rating '.(int)$rating)." ".
                    escapeshellarg($this->filename),
                    $output, $result);
            if ($result != 0) {
                debug(16, "error?".print_r($output,true));
                throw new \Exception("exiv2 rating command failed: $result", 400);
            }
        } catch( \Exception $e ) {
            throw new \Exception("Failed to update rating of image: ".$e->getMessage(), 400);
        }
    }

    // Clear original keywords and Save current keywords
    public function setKeywords($keywords) {
        try {
            debug(16, EXIV2_PATH."exiv2".
                    " -M 'del Iptc.Application2.Keywords String' ".
                    escapeshellarg($this->filename));
            exec(EXIV2_PATH."exiv2".
                    " -M 'del Iptc.Application2.Keywords String' ".
                    escapeshellarg($this->filename),
                    $output, $result);
            foreach ($keywords as $keyword) {
                    debug(16, EXIV2_PATH."exiv2".
                            " -M ".escapeshellarg('add Iptc.Application2.Keywords String '.$keyword)." ".
                    escapeshellarg($this->filename));
                    exec(EXIV2_PATH."exiv2".
                            " -M ".escapeshellarg('add Iptc.Application2.Keywords String '.$keyword)." ".
                            escapeshellarg($this->filename),
                            $output, $result);
                    if ($result != 0) {
                        debug(16, "error?".print_r($output,true));
                        throw new \Exception("exiv2 tags command failed: $result", 400);
                    }
            }
        } catch( \Exception $e ) {
            throw new \Exception("Failed to update tags of image: ".$e->getMessage(), 400);
        }

    }

    // Save orientation to file
    public function setOrientation($orientation) {
        try {
            debug(16, EXIV2_PATH."exiv2".
                    " -M ".escapeshellarg('set Exif.Image.Orientation '.$orientation)." ".
                    escapeshellarg($this->filename));
            exec(EXIV2_PATH."exiv2".
                    " -M ".escapeshellarg('set Exif.Image.Orientation '.$orientation)." ".
                    escapeshellarg($this->filename),
                    $output, $result);
            if ($result != 0) {
                debug(16, "error?".print_r($output,true));
                throw new \Exception("exiv2 orientation command failed: $result", 400);
            }
        } catch( \Exception $e ) {
            throw new \Exception("Failed to update rotation of image: ".$e->getMessage(), 400);
        }
    }

}

?>