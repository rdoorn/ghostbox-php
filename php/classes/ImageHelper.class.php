<?php

class ImageHelper {
    private $filename;

    function __construct( $filename ) {
        if (!is_file($filename)) {
            throw new \Exception("Error reading file: ".$filename, 404);
        }
        $this->filename = $filename;
    }

    public function calculatePalette() {
        $result = new \stdClass();
        
        $avg = PALETTE_AVERAGE;
        $colors = $this->colorPalette($this->filename, $avg);
        $rt=0;$bt=0;$gt=0;
        foreach ($colors as $color) {
            list ($r,$g,$b) = str_split($color,2);
            $rt += hexdec($r); $gt += hexdec($g); $bt += hexdec($b);
        }
        $rt = $rt/$avg;
        $gt = $gt/$avg;
        $bt = $bt/$avg;
        $result->color=dechex($rt).dechex($gt).dechex($bt);
        $result->r = $rt;
        $result->g = $gt;
        $result->b = $bt;
        $result->brightness=($rt+$rt+$bt+$gt+$gt+$gt)/6;
        return $result;
    }


    private function colorPalette($imageFile, $numColors, $granularity = 5) { 
       $granularity = max(1, abs((int)$granularity)); 
       $colors = array(); 
       $size = @getimagesize($imageFile); 
       $img = @imagecreatefromstring(file_get_contents($imageFile)); 
    
       for($x = 0; $x < $size[0]; $x += $granularity) 
       { 
          for($y = 0; $y < $size[1]; $y += $granularity) 
          { 
             $thisColor = imagecolorat($img, $x, $y); 
             $rgb = imagecolorsforindex($img, $thisColor); 
             $red = round(round(($rgb['red'] / 0x33)) * 0x33); 
             $green = round(round(($rgb['green'] / 0x33)) * 0x33); 
             $blue = round(round(($rgb['blue'] / 0x33)) * 0x33); 
             $thisRGB = sprintf('%02X%02X%02X', $red, $green, $blue); 
             if(array_key_exists($thisRGB, $colors)) 
             { 
                $colors[$thisRGB]++; 
             } 
             else 
             { 
                $colors[$thisRGB] = 1; 
             } 
          } 
       } 
       arsort($colors); 
       return array_slice(array_keys($colors), 0, $numColors); 
    } 

    public function getXmpData($filename, $chunkSize)    {
        if (!is_int($chunkSize)) {
            throw new RuntimeException('Expected integer value for argument #2 (chunkSize)');
        }
    
        if ($chunkSize < 12) {
            throw new RuntimeException('Chunk size cannot be less than 12 argument #2 (chunkSize)');
        }
    
        if (($file_pointer = fopen($filename, 'r')) === FALSE) {
            throw new RuntimeException('Could not open file for reading');
        }
    
        $startTag = '<x:xmpmeta';
        $endTag = '</x:xmpmeta>';
        $buffer = NULL;
        $hasXmp = FALSE;
    
        while (($chunk = fread($file_pointer, $chunkSize)) !== FALSE) {
            if ($chunk === "") {
                break;
            }
    
            $buffer .= $chunk;
            $startPosition = strpos($buffer, $startTag);
            $endPosition = strpos($buffer, $endTag);
    
            if ($startPosition !== FALSE && $endPosition !== FALSE) {
                $buffer = substr($buffer, $startPosition, $endPosition - $startPosition + 12);
                $hasXmp = TRUE;
                break;
            } elseif ($startPosition !== FALSE) {
                $buffer = substr($buffer, $startPosition);
                $hasXmp = TRUE;
            } elseif (strlen($buffer) > (strlen($startTag) * 2)) {
                $buffer = substr($buffer, strlen($startTag));
            }
        }
    
        fclose($file_pointer);
        return ($hasXmp) ? $buffer : NULL;
    }


    public function parseImage($item) {

        //print_r($item);
        $size = getimagesize($this->filename, $info);
        $item->set('originalwidth', $size[0]);
        $item->set('originalheight', $size[1]);
        $item->set('mime', $size['mime']);

        // if we have IPTC data, read it
        if(isset($info['APP13'])) {
            $iptc = iptcparse($info['APP13']);
        }
        $item->set('captition', isset($iptc['2#120'][0])?$iptc['2#120'][0]:"");
        $item->set('keywords', isset($iptc["2#025"])?$iptc["2#025"]:array());

        // get Exif data - fail silently
        $exif = @exif_read_data($this->filename);
        $item->set('rotation', isset($exif['Orientation'])?$exif['Orientation']:1);
        $item->set('originaldate', isset($exif['DateTimeOriginal'])?$exif['DateTimeOriginal']:0);
        $item->set('captition', isset($iptc['2#120'][0])?$iptc['2#120'][0]:"");

        // get XML data
        $xmp = $this->getXmpData($this->filename, 65534);
        preg_match('/xmp:Rating="(\d+)"/', $xmp, $rating);
        $item->set('rating', $rating?$rating[1]:0);

        if ($item->get('originaldate') == 0) {
            $item->set('originaldate', filemtime($this->filename));
        } else {
            list($ymd,$hms) = explode(' ', $item->get('originaldate'));
            list ($year, $month, $day) = explode(':', $ymd);
            list ($hour, $minute, $second) = explode(':', $hms);
            $item->set('originaldate', mktime( $hour, $minute, $second, $month, $day, $year ));
        }
        return $item;

    }

    public static function swapImageCoordinates($rotation) {
        if (($rotation>=5) && ($rotation<=8)) {
            return true;
        }
    }       

}

?>