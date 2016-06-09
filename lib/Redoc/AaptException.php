<?php
namespace Redoc;

use Exception;

class AaptException extends Exception{

    const NO_ERROR = 0;
    const OTHER_ERROR = 3;

    protected $msgBag = array(
        self::NO_ERROR => "No error",
        self::OTHER_ERROR => "#apt-get install aapt\n#add aapt to environment variable",
    );

    protected $code;

    public function __construct($code){
        $message = "";
        /** @var Exception $previous */
        $previous = null;
        parent::__construct($message, $code, $previous);
    }

    public function getErrorMsg(){
        $errorCode = self::OTHER_ERROR;
        foreach($this->msgBag as $key => $msg){
            if($this->code == $key){
                $errorCode = $key;
                break;
            }
        }
        return $this->msgBag[$errorCode];
    }
}