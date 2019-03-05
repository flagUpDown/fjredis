<?php
namespace FlagUpDown\Utils;

use FlagUpDown\Exceptions\RespErrorException;
use FlagUpDown\Exceptions\StreamException;

if (!defined('CRLF')) {
    define('CRLF', sprintf('%s%s', chr(13), chr(10)));
}

class RespDecode
{
    protected static $SIMPLE_STRING = '+';
    protected static $INTEGER       = ':';
    protected static $BULK_STRING   = '$';
    protected static $ARRAY         = '*';
    protected static $ERROR         = '-';

    public static function decode($streamHandle)
    {
        $headLine = fgets($streamHandle);
        if ($headLine === false) {
            $info = stream_get_meta_data($streamHandle);
            if ($info['timed_out']) {
                throw new StreamException('timed out', StreamException::$READ_OPTERATE);
            }
            throw new StreamException('lost stream handler', StreamException::$READ_OPTERATE);
        }
        $headLine   = rtrim($headLine, CRLF);
        $strType    = $headLine[0];
        $strPayload = substr($headLine, 1);
        $result     = [];
        switch ($strType) {
            //简单字符串
            case self::$SIMPLE_STRING:
                $result = $strPayload;
                break;
            //整数
            case self::$INTEGER:
                $result = intval($strPayload);
                break;
            //大块字符串
            case self::$BULK_STRING:
                $length = intval($strPayload);
                if ($length === -1) {
                    $result = null;
                } else {
                    $result = stream_get_contents($streamHandle, $length + 2);
                    if (!$result) {
                        throw new StreamException('Error reading reply', StreamException::$READ_OPTERATE);
                    }
                    $result = substr($result, 0, $length);
                }
                break;
            //数组
            case self::$ARRAY:
                $count = intval($strPayload);
                if ($count === -1) {
                    $result = null;
                } else {
                    $result = [];
                    for ($i = 0; $i < $count; $i++) {
                        $result[] = self::decode($streamHandle);
                    }
                }
                break;
            //错误类型
            case self::$ERROR:
                throw new RespErrorException($strPayload);
                break;
            default:
                throw new \Exception('redis reply parse error');
                break;
        }
        return $result;
    }
}
