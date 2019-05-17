<?php
namespace FlagUpDown\Utils;

if (!defined('CRLF')) {
    define('CRLF', sprintf('%s%s', chr(13), chr(10)));
}

class CommandEncode
{
    public static function array_encode($arr)
    {
        return sprintf('*%d%s%s', count($arr), CRLF, implode('', array_map(['self', '_string_encode'], $arr)));
    }

    protected static function _string_encode($str)
    {
        return sprintf('$%d%s%s%s', strlen($str), CRLF, $str, CRLF);
    }
}
