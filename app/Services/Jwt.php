<?php namespace App\Services;

class Jwt {

    public static function encode($payload) {
        $payload += array('exp' => strtotime("+2 hours"));
        return \JWT::encode($payload, env('APP_KEY'));
    }

    public static function decode($token) {
        return \JWT::decode($token, env('APP_KEY'));
    }

}