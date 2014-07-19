<?php

set_error_handler(function ($err_severity, $err_msg, $err_file, $err_line, array $err_context) {
     // error was suppressed with the @-operator
     if (0 === error_reporting()) { return false;}
     switch($err_severity)
     {
         case E_ERROR:               throw new ErrorException            ($err_msg, 0, $err_severity, $err_file, $err_line);
         case E_WARNING:             throw new WarningException          ($err_msg, 0, $err_severity, $err_file, $err_line);
         case E_PARSE:               throw new ParseException            ($err_msg, 0, $err_severity, $err_file, $err_line);
         case E_NOTICE:              throw new NoticeException           ($err_msg, 0, $err_severity, $err_file, $err_line);
         case E_CORE_ERROR:          throw new CoreErrorException        ($err_msg, 0, $err_severity, $err_file, $err_line);
         case E_CORE_WARNING:        throw new CoreWarningException      ($err_msg, 0, $err_severity, $err_file, $err_line);
         case E_COMPILE_ERROR:       throw new CompileErrorException     ($err_msg, 0, $err_severity, $err_file, $err_line);
         case E_COMPILE_WARNING:     throw new CoreWarningException      ($err_msg, 0, $err_severity, $err_file, $err_line);
         case E_USER_ERROR:          throw new UserErrorException        ($err_msg, 0, $err_severity, $err_file, $err_line);
         case E_USER_WARNING:        throw new UserWarningException      ($err_msg, 0, $err_severity, $err_file, $err_line);
         case E_USER_NOTICE:         throw new UserNoticeException       ($err_msg, 0, $err_severity, $err_file, $err_line);
         case E_STRICT:              throw new StrictException           ($err_msg, 0, $err_severity, $err_file, $err_line);
         case E_RECOVERABLE_ERROR:   throw new RecoverableErrorException ($err_msg, 0, $err_severity, $err_file, $err_line);
         case E_DEPRECATED:          throw new DeprecatedException       ($err_msg, 0, $err_severity, $err_file, $err_line);
         case E_USER_DEPRECATED:     throw new UserDeprecatedException   ($err_msg, 0, $err_severity, $err_file, $err_line);
     }
});

class WarningException              extends ErrorException {}
class ParseException                extends ErrorException {}
class NoticeException               extends ErrorException {}
class CoreErrorException            extends ErrorException {}
class CoreWarningException          extends ErrorException {}
class CompileErrorException         extends ErrorException {}
class CompileWarningException       extends ErrorException {}
class UserErrorException            extends ErrorException {}
class UserWarningException          extends ErrorException {}
class UserNoticeException           extends ErrorException {}
class StrictException               extends ErrorException {}
class RecoverableErrorException     extends ErrorException {}
class DeprecatedException           extends ErrorException {}
class UserDeprecatedException       extends ErrorException {}


// TODO look at http://pear.php.net/package/XML_Serializer

    function displayError($error, $httpAccept) {
        $errorRequest = "{$_SERVER['REQUEST_METHOD']} {$_SERVER['HTTP_HOST']} {$_SERVER['REDIRECT_STATUS']} {$_SERVER['REQUEST_URI']} ({$_SERVER['REDIRECT_URI']})\n";
        $errorTrace = "Exception '{$error->getMessage()}'({$error->getCode()}) in {$error->getFile()}({$error->getLine()})\n{$error->getTraceAsString()}";
        $errorMessage=array ( "Success" => false, "Message" => $error->getMessage(), "Code" => $error->getCode() );
        error_log($errorRequest.$errorTrace);
        switch($httpAccept) {
            case "json":
                if ($error->getCode() > 300) {
                    header("{$_SERVER["SERVER_PROTOCOL"]} {$error->getCode()} {$error->getMessage()}", true, $error->getCode());
                    header("Status: {$error->getCode()} {$error->getMessage()}", true, $error->getCode());
                }
                print json_encode($errorMessage);
                exit;
            case "xml":
                print assocArrayToXML('error', $errorMessage);
                exit;
            default: // html and other
                if ($error->getCode() > 300) {
                    // Show full error page
                    header("{$_SERVER["SERVER_PROTOCOL"]} {$error->getMessage()}", true, $error->getCode());
                    header("{$_SERVER["SERVER_PROTOCOL"]} {$error->getCode()} {$error->getMessage()}", true, $error->getCode());
                    /*$data = new stdClass;
                    $data->error=json_decode(json_encode($errorMessage), FALSE);
                    $gui = new guiDisplay();
                    $gui->error($data);*/
                    ?>
                        <div class="error"><br><br>
                         <a href="javascript:history.back();">
                          Oops.... You have encountered an error. <br>

                         <?php echo $errorMessage['Message'] ?><br>
                         </a>
                        </div>
                    <?php
                    exit;
                } else {
                    echo "<pre>{$errorTrace}</pre>";
                }
        }
    }

function assocArrayToXML($root_element_name,$ar)
{
    $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><{$root_element_name}></{$root_element_name}>");
    $f = create_function('$f,$c,$a','
            foreach($a as $k=>$v) {
                if(is_array($v)) {
                    $ch=$c->addChild($k);
                    $f($f,$ch,$v);
                } else {
                    $c->addChild($k,$v);
                }
            }');
    $f($f,$xml,$ar);
    return $xml->asXML();
}

function debug($level, $detail) {
    if (DEBUG & $level) {
        error_log("[DEBUG-$level] $detail\n");
        //if (PHP_SAPI != 'cli') { print "<br>"; }
    }
}


?>
