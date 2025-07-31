<?php

namespace App\Libraries;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
class Jwt
{
    private $key;

    public function __construct()
    {
        $this->key = getenv('JWT_SECRET') ?: 'your_fallback_secret_key';
    }

    public function encode($data, $exp = 3600)
    {
        $issuedAt = time();
        $expirationTime = $issuedAt + $exp;

        return FirebaseJWT::encode(
            [
                'iat' => $issuedAt,
                'exp' => $expirationTime,
                'data' => $data,
            ],
            $this->key,
            'HS256'
        );
    }

    public function decode($token)
    {
        try {
            $decoded = FirebaseJWT::decode($token, new Key($this->key, 'HS256'));
            return $decoded->data;
        } catch (\Exception $e) {
            return false;
        }
    }
}
