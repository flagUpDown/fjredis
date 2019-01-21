<?php
namespace FlagUpDown\Exceptions;

class StreamException extends \Exception
{
    public static $READ_OPTERATE  = 1;
    public static $WRITE_OPTERATE = 2;

    public function __construct($message, $code = 0, $previous = null)
    {
        switch ($code) {
            case self::$READ_OPTERATE:
                $message = 'read opteration: ' . $message;
                break;
            case self::$WRITE_OPTERATE:
                $message = 'write opteration: ' . $message;
                break;
        }
        parent::__construct($message, $code, $previous);
    }
}
