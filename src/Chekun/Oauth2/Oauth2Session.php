<?php namespace Chekun\Oauth2;


class OAuth2Session {

    public static function isLaravel()
    {
        if (class_exists("Session")) {
            $reflected = new \ReflectionClass("Session");
            if ($reflected->getNamespaceName() === "Illuminate\Support\Facades")
            {
                return true;
            }
        }
        return false;
    }

    public static function write ($key, $value)
    {
        if (static::isLaravel()) {
            Session::flash($key, $value);
        } else {
            $_SESSION[$key] = $value;
        }
    }

    public static function read ($key, $value)
    {
        if (static::isLaravel()) {
            return Session::get($key);
        } else {
            // @FIXME Not safe enough.
            return $_SESSION[$key];
        }
    }
}